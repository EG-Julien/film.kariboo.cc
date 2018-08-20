<?php

namespace App\Controllers;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class HomeCtrl extends Controller {
    public function Home($request, $response) {
        if ($this->get_user() != false) {
            return $response->withRedirect("/dashboard");
        }
        return $this->render($response, "Home.twig");
    }

    public function SignUp($request, $response) {
        $this->render($response, "Register.twig");
    }

    public function Dashboard($request, $response) {
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
            "peculiar_data" => $peculiar_data,
            "data_nw" => $data_nw,
            "movie_of_the_next_week" => $movie_of_the_next_week,
            "films" => $films,
        ]);
    }

    public function Logout($request, $response) {
        unset($_SESSION['user']);
        return $response->withRedirect("/");
    }
}