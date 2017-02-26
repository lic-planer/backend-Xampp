<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \Firebase\JWT\JWT;

define('SECRET','ziomkizparszywejpiatki');
define('ALGORITHM','HS256');

require '../db/userOperations.php';
require '../src/token.php';

/*Get All Users
 *Method: GET
 *Route: /api/users
 *Param: -
*/
$app->get("/api/users", function ($request, $response, $arguments) {

    $db = new userOperations();
    $db->getUsers();

});

/*Get Single User
 *Method: GET
 *Route: /api/user
 *Param: -
*/
$app->get('/api/user', function(Request $request, Response $response) {

    $token = new token();
    $jwt = $token->getToken($request);

    $id = $jwt->user[0]->id;

    $db = new userOperations();
    $db->getUser($id);

});

/*User Registration
 *Method: POST
 *Route: /api/user/registration
 *Param: username, password, email, avatar
*/
$app->post('/api/user/registration', function(Request $request, Response $response) {

    $username = $request->getParam('username');
    $password = $request->getParam('password');
    $email = $request->getParam('email');
    $avatar = $request->getParam('avatar');

    $db = new userOperations();
    $db->createUser($username,$password,$email,$avatar);

});

/*User Login
 *Method: POST
 *Route: /api/user/login
 *Param: username, password
*/
$app->post("/api/user/login", function ($request, $response, $arguments) {

    $username = $request->getParam('username');
    $password = $request->getParam('password');

    $now = new DateTime();
    $future = new DateTime("now +2 hours");
    $tokenId = base64_encode(random_bytes(32));

    $db = new userOperations();

    if($db->checkLogin($username,$password)) {

        $user = $db->getUserByUsername($username);
        $act = array_column($user, 'activate');

        if ($act[0] === '0') {
            $db->activateUser($username);
        }

        $payload = [
            "jti" => $tokenId,
            "iat" => $now->getTimeStamp(),
            "exp" => $future->getTimeStamp(),
            "user" => $user,
        ];

        $token = JWT::encode($payload, SECRET);
        $data["status"] = "ok";
        $data["token"] = $token;

        return $response->withStatus(201)
            ->withHeader("Content-Type", "application/json")
            ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    } else {
        echo '{"error": {"text": "Nieprawidłowy login lub hasło!"}}';
    }

});

/*Update email || password
 *Method: PUT
 *Route: /api/user
 *Param: email || password
*/
$app->put('/api/user', function(Request $request, Response $response) {

    $token = new token();
    $jwt = $token->getToken($request);

    $id = $jwt->user[0]->id;
    $email = $request->getParam('email');
    $password = $request->getParam('password');

    $db = new userOperations();

    if ($password === null) {
        if ($db->isEmailInUse($email) || !$db->isEmailCorrect($email)) {

        } else {
            $db->updateEmail($id, $email);
        }
    } else {

        if (!$db->isPasswordCorrect($password)) {
        } else {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $db->updatePassword($id, $passwordHash);
        }
    }
});

/*Deactivate user
 *Method: PUT
 *Route: /api/user/deactivate
 *Param: -
*/

$app->put('/api/user/deactivate', function(Request $request, Response $response) {

    $token = new token();
    $jwt = $token->getToken($request);

    $id = $jwt->user[0]->id;

    $db = new userOperations();
    $db->deactivateUser($id);

});
