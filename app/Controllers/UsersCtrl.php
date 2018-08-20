<?php

namespace App\Controllers;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class UsersCtrl extends Controller {
    public function login(RequestInterface $request, ResponseInterface $response) {
        $data = $request->getParams();
        $login = $data["email"];
        $password = sha1($data["password"]);
        $DB = $this::getDB();
        $q = $DB->prepare("SELECT * FROM users WHERE email = ? AND password = ?");
        $q->execute([$login, $password]);
        $user = $q->fetch();
        if (empty($user)) {
            return $this->render($response, "Home.twig", ["wpass" => 1]);
        } else {
            $this->set_user($user);
            return $response->withRedirect("/dashboard");
        }
    }

    public function SignUp(RequestInterface $request, ResponseInterface $response) {
        $data = $request->getParams();

        $DB = $this::getDB();

        $q = $DB->prepare("SELECT * FROM users WHERE login = ? OR email = ?");
        $q->execute([
            $data["login"],
            $data["email"]
        ]);

        $r = $q->fetchAll();

        if (empty($r)) {
            $q = $DB->prepare("INSERT INTO users (login, password, email, firstname, lastname) VALUES (?, ?, ?, ?, ?)");
            $q->execute([
                $data["login"],
                sha1($data["password"]),
                $data["email"],
                $data["fname"],
                $data["lname"]
            ]);
        } else {
            $this->flash("error", "Ce nom d'utilisateur ou cet email est déjà utilisé !");
        }


        return $response->withRedirect("/");
    }

    public function Settings($request, $response, $args) {
        if (!$this->accessAllowed($response)) {
            return;
        }

        $user = $args["user"];

        $q = self::getDB()->prepare("SELECT * FROM users WHERE login = ?");
        $q->execute([
            $user
        ]);

        $user = $q->fetch();

        $user->vote_for = count(json_decode($user->vote));

        $this->render($response, "Profil.twig", [
            "user" => $user
        ]);

    }
}