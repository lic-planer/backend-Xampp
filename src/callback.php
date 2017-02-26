<?php
$app = new \Slim\App();

$container = $app->getContainer();
$container["jwt"] = function ($container) {
    return new StdClass;
};

$app->add(new \Slim\Middleware\JwtAuthentication([
    "secret" => "ziomkizparszywejpiatki",
    "callback" => function ($request, $response, $arguments) use ($container) {
        $container["jwt"] = $arguments["decoded"];
    }
]));