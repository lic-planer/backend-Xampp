<?php

require '../vendor/autoload.php';

$app = new \Slim\App([
    'settings' => [
        'displayErrorDetails' => true
    ]
]);

$app->add(new \Slim\Middleware\JwtAuthentication([
    "secret" => "ziomkizparszywejpiatki",
    "secure" => false,
    "path" => "/api",
    "passthrough" =>["/api/user/login", "/api/user/registration", "/api/user/remindPassword"],
    //"callback" => function ($request, $response, $arguments) use ($container) {
    //    $container["jwt"] = $arguments["decoded"];
    //}
]));

require '../db/db.php';
require '../routes/users.php';
require '../routes/boards.php';
require '../routes/lists.php';
require '../routes/tasks.php';
require '../routes/comments.php';

$app->run();