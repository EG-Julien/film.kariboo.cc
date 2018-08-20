<?php
@session_start();

date_default_timezone_set('Europe/Paris');

require '../vendor/autoload.php';
require '../config.php';

$app = new \Slim\App(
    [
        'settings' => [
            'displayErrorDetails' => true
        ]
    ]
);

require '../app/container.php';
require '../app/Class/Allocine.php';

define('ALLOCINE_PARTNER_KEY', '100043982026');
define('ALLOCINE_SECRET_KEY', '29d185d98c984a359e6e6f26a0474269');

$allocine = new Allocine(ALLOCINE_PARTNER_KEY, ALLOCINE_SECRET_KEY);

try {
    $DB = new PDO('mysql:dbname=' . $dbname . ';host=' . $dbhost . ';charset=utf8', "$dbuser", "$dbpassword");
    $DB->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $DB->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
} catch (Exception $e) {
    die($e);
}

\App\Controllers\Controller::setDB($DB);
\App\Controllers\Controller::setAllocine($allocine);

$app->get('/', \App\Controllers\HomeCtrl::class . ':Home')->setName("home");
$app->post('/', \App\Controllers\UsersCtrl::class . ':login')->setName("login_post");
$app->get('/signup', \App\Controllers\HomeCtrl::class . ':SignUp')->setName("signup");
$app->post('/signup', \App\Controllers\UsersCtrl::class . ':SignUp')->setName("signup");
$app->get('/dashboard', \App\Controllers\HomeCtrl::class . ':Dashboard')->setName("dashboard");
$app->get('/logout', \App\Controllers\HomeCtrl::class . ':Logout')->setName("logout");
$app->get('/user/settings', \App\Controllers\UsersCtrl::class . 'Settings')->setName("user_settings");
$app->get('/vote/{id}/{user}', \App\Controllers\HomeCtrl::class . ":Vote")->setName("vote");

$app->run();