<?php

require '../vendor/autoload.php';

$app = new \Slim\App();

$app->add(new \Slim\Middleware\JwtAuthentication([
    "secret" => "ziomkizparszywejpiatki",
    "secure" => false,
    "path" => "/api",
    "passthrough" =>["/api/user/login", "/api/user/registration"],
    //"callback" => function ($request, $response, $arguments) use ($container) {
    //    $container["jwt"] = $arguments["decoded"];
    //}
]));

require '../db/db.php';
require '../routes/users.php';
require '../routes/boards.php';

$app->run();