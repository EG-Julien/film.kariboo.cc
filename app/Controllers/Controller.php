<?php

namespace App\Controllers;

use Psr\Http\Message\RequestInterfarce;
use Psr\Http\Message\ResponseInterface;

class Controller {
    private $container;
    public static $allocine;
    public static $DB;

    function __construct($container) {
        $this->container = $container;
    }

    public static function setAllocine($allocine) {
        self::$allocine = $allocine;
    }

    public function getAllocine() {
        return self::$allocine;
    }

    public function render($response, $name, $params = []) {
        $this->container->view->render($response, $name, $params);
    }

    public function str_random($length){
        $alphabet = "0123456789azertyuiopqsdfghjklmwxcvbnAZERTYUIOPQSDFGHJKLMWXCVBN";
        return substr(str_shuffle(str_repeat($alphabet, $length)), 0, $length);
    }

    public function accessAllowed($response) {
        if (!isset($_SESSION['user']->password)) {
            $this->render($response, "404.twig");
            return false;
        }
        return true;
    }

    public function set_user($user) {
        $_SESSION['user'] = $user;
    }

    public function get_user() {
        if (!empty($_SESSION['user']->password)) {
            return $_SESSION['user'];
        }
        return false;
    }

    public  function logout() {
        unset($_SESSION['user']);
        return;
    }

    static function setDB($DB) {
        self::$DB = $DB;
    }

    static function getDB() {
        return self::$DB;
    }
}