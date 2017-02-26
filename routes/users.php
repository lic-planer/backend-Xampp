<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \Firebase\JWT\JWT;

require '../db/userOperations.php';

/*Get All Users
 *Method: GET
 *Route: /api/users
 *Param: -
*/
$app->get("/api/users", function ($request, $response, $arguments) {

    $sql = "SELECT * FROM user";

    try {
        $db = new db();
        $db = $db->connect();

        $stmt = $db->query($sql);
        $users = $stmt->fetchAll(PDO::FETCH_OBJ);
        $db = null;
        echo json_encode($users);

    } catch(PDOException $e){
        echo '{"error": {"text": '.$e->getMessage().'}}';
    }
});

/*Get Single User
 *Method: GET
 *Route: /api/user/{id}
 *Param: -
*/
$app->get('/api/user/{id}', function(Request $request, Response $response) {

    $id = $request->getAttribute('id');
    $sql = "SELECT * FROM user WHERE id = $id";

    try {
        $db = new db();
        $db = $db->connect();

        $stmt = $db->query($sql);
        $user = $stmt->fetchAll(PDO::FETCH_OBJ);
        $db = null;
        echo json_encode($user);

    } catch(PDOException $e){
        echo '{"error": {"text": '.$e->getMessage().'}}';
    }
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
    $activate = 1;

    $db = new userOperations();

    if($db->checkLogin($username,$password)) {

        $user = $db->getUserByUsername($username);
        $act = array_column($user, 'activate');

        if ($act[0] === '0') {
            $sql = "UPDATE user SET
            activate = :activate
            WHERE username = :username";
            try {
                $db = new db();
                $db = $db->connect();
                $stmt = $db->prepare($sql);
                $stmt->bindParam(':activate', $activate);
                $stmt->bindParam(':username', $username);
                $stmt->execute();
            } catch(PDOException $e){
                echo '{"error": {"text": '.$e->getMessage().'}}';
            }
        }

        $payload = [
            "jti" => $tokenId,
            "iat" => $now->getTimeStamp(),
            "exp" => $future->getTimeStamp(),
            "user" => $user,
        ];

        $token = JWT::encode($payload, 'ziomkizparszywejpiatki');
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
 *Route: /api/user/{id}
 *Param: email || password
*/
$app->put('/api/user/{id}', function(Request $request, Response $response) {

    $id = $request->getAttribute('id');
    $email = $request->getParam('email');
    $password = $request->getParam('password');

    $db = new userOperations();

    if ($password === null) {
        if ($db->isEmailInUse($email)) {
            echo '{"error": {"text": "Taki email już istnieje! Proszę podać inny."}}';
        } else {

            $sql = "UPDATE user SET
            email    = :email
            WHERE id = $id";

            try {
                $db = new db();
                $db = $db->connect();

                $stmt = $db->prepare($sql);
                $stmt->bindParam(':email', $email);
                $stmt->execute();

                echo '{"notice": {"text": "Email updated"}}';
            } catch(PDOException $e){
                echo '{"error": {"text": '.$e->getMessage().'}}';
            }
        }
    } else {

        $passwordLen = strlen($password);
        $hash = md5($password);

        if ($passwordLen < 8) {
            echo '{"error": {"text": "Hasło musi zawierać minimum 8 znaków!"}}';
        } else {

            $sql = "UPDATE user SET
            password = :hash
            WHERE id = $id";

            try {
                $db = new db();
                $db = $db->connect();
                $stmt = $db->prepare($sql);
                $stmt->bindParam(':hash', $hash);
                $stmt->execute();

                echo '{"notice": {"text": "Password updated"}}';
            } catch(PDOException $e){
                echo '{"error": {"text": '.$e->getMessage().'}}';
            }
        }
    }
});

/*Deactivate user
 *Method: PUT
 *Route: /api/user/{id}/deactivate
 *Param: -
*/

$app->put('/api/user/{id}/deactivate', function(Request $request, Response $response) {

    $id = $request->getAttribute('id');
    $activate = 0;
    $sql = "UPDATE user SET
        activate = :activate
        WHERE id = $id";

    try {
        $db = new db();
        $db = $db->connect();
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':activate', $activate);
        $stmt->execute();
        echo '{"notice": {"text": "User is deactivated "}}';
    } catch(PDOException $e){
        echo '{"error": {"text": '.$e->getMessage().'}}';
    }
});

//Logowanie przy użyciu username lub email

/*$app->post('/api/user/login', function(Request $request, Response $response) {

    $username = $request->getParam('username');
    $password = $request->getParam('password');
    $email = $request->getParam('email');

    $now = new DateTime();
    $future = new DateTime("now +2 hours");
    $tokenId = base64_encode(random_bytes(32));

    $db = new usersOperation();

    if($db->checkLogin($username,$password, $email)) {
        if ($email === null) {
            $user = $db->getUserByUsername($username);
        } else {
            $user = $db->getUserByEmail($email);
        }

        $payload = [
            "jti" => $tokenId,
            "iat" => $now->getTimeStamp(),
            "exp" => $future->getTimeStamp(),
            "user" => $user,
        ];

        $token = JWT::encode($payload, SECRET, ALGORITHM);
        $data["status"] = "ok";
        $data["token"] = $token;

        return $response->withStatus(201)
            ->withHeader("Content-Type", "application/json")
            ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    } else {
        echo '{"error": {"text": "Nieprawidłowy login/email lub hasło!"}}';
    }

});*/
