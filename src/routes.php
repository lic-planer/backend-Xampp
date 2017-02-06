<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

$app = new \Slim\App;

//Get All Users
$app->get('/api/users', function(Request $request, Response $response){
    $sql = "SELECT * FROM user";

    try{
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
$app->get('/api/user/{id}', function(Request $request, Response $response){

    $id = $request->getAttribute('id');
    $sql = "SELECT * FROM user WHERE id = $id";

    try{
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

//Add User
$app->post('/api/user/add', function(Request $request, Response $response){

    $username = $request->getParam('username');
    $password = $request->getParam('password');
    $email = $request->getParam('email');
    $avatar = $request->getParam('avatar');
    $hash = password_hash($password, PASSWORD_BCRYPT);

    $sql = "INSERT INTO user (username,password,email,avatar) VALUES (:username,:hash,:email,:avatar)";

    try{
        $db = new db();
        $db = $db->connect();

        $stmt = $db->prepare($sql);

        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':hash', $hash);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':avatar', $avatar);

        $stmt->execute();

        echo '{"notice": {"text": "User Added"}}';

    } catch(PDOException $e){
        echo '{"error": {"text": '.$e->getMessage().'}}';
    }
});

//Update User
$app->put('/api/user/update/{id}', function(Request $request, Response $response){

    $id = $request->getAttribute('id');
    $username = $request->getParam('username');
    $password = $request->getParam('password');
    $email = $request->getParam('email');
    $avatar = $request->getParam('avatar');
    $hash = password_hash($password, PASSWORD_BCRYPT);

    $sql = "UPDATE user SET
        username = :username,
        password = :hash,
        email    = :email,
        avatar   = :avatar
        WHERE id = $id";

    try{
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
$app->delete('/api/user/delete/{id}', function(Request $request, Response $response){

    $id = $request->getAttribute('id');
    $sql = "DELETE FROM user WHERE id = $id";

    try{
        $db = new db();
        $db = $db->connect();

        $stmt = $db->prepare($sql);
        $stmt->execute();
        $db = null;

        echo '{"notice": {"text": "User Deleted"})';

    } catch(PDOException $e){
        echo '{"error": {"text": '.$e->getMessage().'}}';
    }
});