<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \Firebase\JWT\JWT;

define('SECRET','ziomkizparszywejpiatki');
define('ALGORITHM','HS256');

$app = new \Slim\App;

require  '../src/dbOperation.php';

//Get All Users
$app->get('/api/users', function(Request $request, Response $response) {
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

//Get Single User
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

//User Registration
$app->post('/api/user/registration', function(Request $request, Response $response) {

    $username = $request->getParam('username');
    $password = $request->getParam('password');
    $email = $request->getParam('email');
    $avatar = $request->getParam('avatar');

    $db = new dbOperation();
    $db->createUser($username,$password,$email,$avatar);

});

//Update User
$app->put('/api/user/update/{id}', function(Request $request, Response $response) {

    $id = $request->getAttribute('id');
    $username = $request->getParam('username');
    $password = $request->getParam('password');
    $email = $request->getParam('email');
    $avatar = $request->getParam('avatar');
    $hash = md5($password);

    $sql = "UPDATE user SET
        username = :username,
        password = :hash,
        email    = :email,
        avatar   = :avatar
        WHERE id = $id";

    try {
        $db = new db();
        $db = $db->connect();

        $stmt = $db->prepare($sql);

        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':hash', $hash);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':avatar', $avatar);

        $stmt->execute();

        echo '{"notice": {"text": "User Updated"}}';

    } catch(PDOException $e){
        echo '{"error": {"text": '.$e->getMessage().'}}';
    }
});

//Delete User
$app->delete('/api/user/delete/{id}', function(Request $request, Response $response) {

        $id = $request->getAttribute('id');
        $sql = "DELETE FROM user WHERE id = $id";

        try {
            $db = new db();
            $db = $db->connect();

            $stmt = $db->prepare($sql);
            $stmt->execute();
            $db = null;

            echo '{"notice": {"text": "User Deleted"})';

        } catch (PDOException $e) {
            echo '{"error": {"text": ' . $e->getMessage() . '}}';
        }

});

//User Login
$app->post('/api/user/login', function(Request $request, Response $response) {

    $username = $request->getParam('username');
    $password = $request->getParam('password');
    $email = $request->getParam('email');

    $now = new DateTime();
    $future = new DateTime("now +2 hours");
    $tokenId = base64_encode(random_bytes(32));

    $db = new dbOperation();

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
});
