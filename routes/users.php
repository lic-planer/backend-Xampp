<?php

include '../src/headers.php';

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \Firebase\JWT\JWT;

define('SECRET','ziomkizparszywejpiatki');
define('ALGORITHM','HS256');

require '../db/userOperations.php';
require '../src/token.php';


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
 *Param: username, password, email
*/
$app->post('/api/user/registration', function(Request $request, Response $response) {

    $username = trim($request->getParam('username'));
    $password = $request->getParam('password');
    $email = trim($request->getParam('email'));

    $db = new userOperations();
    $db->createUser($username,$password,$email);
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
 *Param: email || (oldPass && newPass)
*/
$app->put('/api/user', function(Request $request, Response $response) {

    $token = new token();
    $jwt = $token->getToken($request);

    $id = $jwt->user[0]->id;
    $email = trim($request->getParam('email'));
    $oldPass = trim($request->getParam('oldPass'));
    $newPass = trim($request->getParam('newPass'));

    $db = new userOperations();

    if (($newPass === '') && ($oldPass === '')) {
        if (!$db->isEmailInUse($email) && $db->isEmailCorrect($email)) {
            $db->updateEmail($id, $email);
            $db->createActivationToken($email);
            $db->changeEmailActivateF($id);
            $db->sendEmail($email);
            echo '{"notice": {"text": "E-mail weryfikacyjny został wysłany."}}';
        }
    } else {
        if (!$db->isPasswordCorrect($newPass) || !$db->isPasswordCorrect($oldPass) || !$db->passwordExists($id, $oldPass)) {
        } else {
            $passwordHash = password_hash($newPass, PASSWORD_DEFAULT);
            $db->updatePassword($id, $passwordHash);
        }
    }
});

/*Deactivate user
 *Method: PUT
 *Route: /api/loggedUser/deactivate
 *Param: -
*/
$app->put('/api/loggedUser/deactivate', function(Request $request, Response $response) {

    $token = new token();
    $jwt = $token->getToken($request);

    $id = $jwt->user[0]->id;

    $db = new userOperations();
    $db->deactivateUser($id);

});

/*Email verification - registration/update
 *Method: GET
 *Route: /verify
 *Param: activationToken
*/
$app->get('/verify', function(Request $request, Response $response) {

    $activationToken = $request->getParam('activationToken');

    $db = new userOperations();
    if ($db->activationTokenCorrect($activationToken)) {
        $db->changeEmailActivateT($activationToken);
    }
});

/*Remind password
 *Method: PUT
 *Route: /api/user/remindPassword
 *Param: email
*/
$app->put('/api/user/remindPassword', function(Request $request, Response $response) {

    $email = trim($request->getParam('email'));

    $db = new userOperations();
    if (!$db->emailExists($email)) {
        $db->sendEmailWithPassword($email);
    }
});