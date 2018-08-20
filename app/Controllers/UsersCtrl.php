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

        // TODO : Add unique login & email verification

        $DB = $this::getDB();
        $q = $DB->prepare("INSERT INTO users (login, password, email, firstname, lastname) VALUES (?, ?, ?, ?, ?)");
        $q->execute([
            $data["login"],
            sha1($data["password"]),
            $data["email"],
            $data["fname"],
            $data["lname"]
        ]);

        return $response->withRedirect("/");
    }
}