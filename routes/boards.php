<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \Firebase\JWT\JWT;

//$app = new \Slim\App;

require '../db/boardOperations.php';

/*Get Boards
 *Method: GET
 *Route: /api/boards
 *Param: -
*/
$app->get('/api/boards', function(Request $request, Response $response) {

    $sql = "SELECT * FROM board";

    try {
        $db = new db();
        $db = $db->connect();

        $stmt = $db->query($sql);
        $boards = $stmt->fetchAll(PDO::FETCH_OBJ);
        $db = null;
        echo json_encode($boards);

    } catch(PDOException $e){
        echo '{"error": {"text": '.$e->getMessage().'}}';
    }
});

/*Create Board
 *Method: POST
 *Route: /api/board/create
 *Param: name, id (administratora)
*/
$app->post('/api/board/create', function(Request $request, Response $response) {

    $name = $request->getParam('name');
    $id_owner = $request->getParam('id');

    $db = new boardOperations();
    $db->createBoard($name, $id_owner);

});

