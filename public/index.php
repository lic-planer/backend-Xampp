<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;

require '../vendor/autoload.php';

$app = new \Slim\App;

$logger = new Logger("slim");
$rotating = new RotatingFileHandler(__DIR__ . "/logs/slim.log", 0, Logger::DEBUG);
$logger->pushHandler($rotating);

$container = $app->getContainer();

$container["jwt"] = function ($container) {
    return new StdClass;
};

$app->jwt = [
    "id" => 1,
    "username" => "olik",
    "emial" => "olik@op.pl"
];

$app->add(new \Slim\Middleware\JwtAuthentication([
    "algorithm" => ["HS256", "HS384"],
    "attribute" => "jwt",
    "environment" => "HTTP_X_TOKEN",
    "header" => "X-Token",
    "path" => "/api",
    "logger" => $logger,
    "secret" => "supersecretkeyyoushouldnotcommittogithub",
    "callback" => function ($request, $response, $arguments) use ($container) {
        $container["jwt"] = $arguments["decoded"];
    },
    "error" => function ($request, $response, $arguments) {
        $data["status"] = "error";
        $data["message"] = $arguments["message"];
        return $response
            ->withHeader("Content-Type", "application/json")
            ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }
]));

require  '../src/db.php';
require '../src/routes.php';

$app->run();