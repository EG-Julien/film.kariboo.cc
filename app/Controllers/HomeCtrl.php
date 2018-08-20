<?php

namespace App\Controllers;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Flash\Messages as FlashMessage;

class HomeCtrl extends Controller
{
    public function Home($request, $response)
    {
        if ($this->get_user() != false) {
            return $response->withRedirect("/dashboard");
        }
        return $this->render($response, "Home.twig");
    }

    public function SignUp($request, $response)
    {
        $this->render($response, "Register.twig");
    }

    public function Dashboard($request, $response)
    {
        if (!$this->accessAllowed($response)) {
            return;
        }

        $q = $this::getDB()->prepare("SELECT * FROM films WHERE film_of_the_week = 1");
        $q->execute();
        $movie_of_the_week = $q->fetch();
        $peculiar_data = json_decode($this->getAllocine()->get($movie_of_the_week->allocine_id));

        $q = $this::getDB()->prepare("SELECT * FROM films ORDER BY vote_for DESC LIMIT 1");
        $q->execute();
        $movie_of_the_next_week = $q->fetch();
        $data_nw = json_decode($this->getAllocine()->get($movie_of_the_next_week->allocine_id));

        $q = $this::getDB()->prepare("SELECT * FROM films WHERE film_of_the_week = 0 ORDER BY vote_for DESC ");
        $q->execute();
        $films = $q->fetchAll();

        $this->render($response, "Dashboard.twig", [
            "user" => $this->get_user(),
            "movie_of_the_week" => $movie_of_the_week,
            "peculiar_data" => $peculiar_data,
            "data_nw" => $data_nw,
            "movie_of_the_next_week" => $movie_of_the_next_week,
            "films" => $films,
        ]);
    }

    public function addFilm($request, $response)
    {
        if (!$this->accessAllowed($response)) {
            return;
        }

        $data = $request->getParams();
        $raw_name = $data["title"];
        $raw_year = $data["year"];
        $raw_id = $data["id"];

        $id = -1;

        if (empty($raw_name) && empty($raw_year) && !empty($raw_id)) {
            $data_sure = json_decode($this->getAllocine()->get($raw_id));

            $raw_name = $data_sure->movie->originalTitle;
            $raw_year = $data_sure->movie->productionYear;

            if (empty($data_sure)) {
                $id = -1;
            } else {
                $id = $raw_id;
            }
        } else {
            $data_nw = json_decode($this->getAllocine()->search($raw_name));


            foreach ($data_nw->feed->movie as $movie) {
                if ($movie->productionYear == $raw_year && $raw_name == $movie->originalTitle) {
                    $id = $movie->code;
                }
            }
        }


        if ($id > -1) {
            $q = $this::getDB()->prepare("SELECT id FROM films WHERE allocine_id = ?");
            $q->execute([$id]);
            $double = $q->fetchAll();
            if (empty($double)) {
                $q = $this::getDB()->prepare("INSERT INTO films (raw_title, raw_year, allocine_id, added_by) VALUES (?, ?, ?, ?)");
                $q->execute([
                    $raw_name,
                    $raw_year,
                    $id,
                    $_SESSION["user"]->login
                ]);

                $q = $this::getDB()->prepare("UPDATE users SET purpose = ? WHERE login = ?");
                $q->execute([
                    $_SESSION['user']->purpose + 1,
                    $_SESSION['user']->login
                ]);

                $_SESSION['user']->purpose++;

                $this->flash("info", "Votre film à bien été ajouté !");
            }
        }

        if ($id == -1) {
            $this->flash('error', "Une erreur est survenue pendant l'ajout du film ou le film n'existe pas !");
        }

        return $this->Dashboard($request, $response);
    }

    public function Logout($request, $response)
    {
        unset($_SESSION['user']);
        return $response->withRedirect("/");
    }

    public function Vote($request, $response, $data)
    {
        if (!$this->accessAllowed($response)) {
            return;
        }

        $id = $data["id"];
        $user = $data["user"];
        $error = 0;

        $q = self::getDB()->prepare("SELECT * FROM users WHERE login = ?");
        $q->execute([$user]);
        $r = $q->fetch();

        $q = $this::getDB()->prepare("SELECT * FROM films WHERE id = ?");
        $q->execute([$id]);
        $film = $q->fetch();

        if (!isset($r->email) || !isset($film->id)) {
            $error++;
        } else {
            $voted_for = json_decode($r->vote);

            foreach ($voted_for as $vote) {
                if ($vote == $id) {
                    $error++;
                }
            }
        }

        if ($error != 0) {
            $this->flash("error", "Vous avez déjà voté pour ce film !");
        } else {
            $voted_for[] = $id;
            $film->vote_for++;

            $q = $this::getDB()->prepare("UPDATE films SET vote_for = ? WHERE id = ?");
            $q->execute([
                $film->vote_for,
                $id
            ]);

            $q = $this::getDB()->prepare("UPDATE users SET vote = ? WHERE login = ?");
            $q->execute([
                json_encode($voted_for),
                $user
            ]);

            $this->flash("info", "Ton vote à bien été pris en compte !");
        }
        return $response->withRedirect($this->router->pathFor("dashboard"));
    }
}