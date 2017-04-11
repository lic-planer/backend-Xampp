<?php

include '../src/headers.php';

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require '../db/boardOperations.php';

/*Get Boards
 *Method: GET
 *Route: /api/boards
 *Param: -
*/
$app->get('/api/boards', function(Request $request, Response $response) {

    $db = new boardOperations();
    $db->getBoards();

});

/*Get Boards Owner
 *Method: GET
 *Route: /api/loggedUser/boards/owner
 *Param: -
*/
$app->get('/api/loggedUser/boards/owner', function(Request $request, Response $response) {

    $token = new token();
    $jwt = $token->getToken($request);
    $id_owner = $jwt->user[0]->id;

    $db = new boardOperations();
    $db->getOwnersBoards($id_owner);

});

/*Create Board
 *Method: POST
 *Route: /api/board
 *Param: name
*/
$app->post('/api/board', function(Request $request, Response $response) {

    $token = new token();
    $jwt = $token->getToken($request);

    $id_owner = $jwt->user[0]->id;
    $name = trim($request->getParam('name'));

    $db = new boardOperations();
    $db->createBoard($name, $id_owner);

});

/*Update Board
 *Method: PUT
 *Route: /api/board/{id}
 *Param: name
*/
$app->put('/api/board/{id}', function(Request $request, Response $response) {

    $id = $request->getAttribute('id');
    $name = trim($request->getParam('name'));

    $db = new boardOperations();

    if (!$db->isNameCorrect($name)) {

    } else {
        $db->updateName($id, $name);
    }
});

/*Delete Board
 *Method: DELETE
 *Route: /api/board/{id}
 *Param: -
*/
$app->delete('/api/board/{id}', function(Request $request, Response $response) {

    $id = $request->getAttribute('id');

    $db = new boardOperations();
    $db->deleteBoard($id);

});

/*Add Member
 *Method: POST
 *Route: /api/board/{id}/member
 *Param: username
*/
$app->post('/api/board/{id}/member', function(Request $request, Response $response) {

    $id_board = $request->getAttribute('id');
    $username = $request->getParam('username');

    $db = new userOperations();
    $user = $db->getUserByUsername($username);
    if ($user !== false) {
        $db = new boardOperations();
        $id_user = array_column($user, 'id');
        if ($db->isAccountActivate($id_user) && $db->ownerBoard($id_user, $id_board) && $db->memberExists($id_user, $id_board)) {
            $db->addMemeber($id_board, $id_user[0]);
        }
    } else {
        echo '{"error": {"text": "Podany użytkownik nie istnieje!"}}';
        header("Status: 400 Bad request");
    }
});

/*Get Board's Members
 *Method: GET
 *Route: /api/board/{id}/members
 *Param: -
*/
$app->get('/api/board/{id}/members', function(Request $request, Response $response) {

    $id_board = $request->getAttribute('id');

    $db = new boardOperations();
    $db->getBoardsMembers($id_board);

});

/*Get Member’s Boards
 *Method: GET
 *Route: /api/loggedUser/boards/member
 *Param: -
*/
$app->get('/api/loggedUser/boards/member', function(Request $request, Response $response) {

    $token = new token();
    $jwt = $token->getToken($request);
    $id_user = $jwt->user[0]->id;

    $db = new boardOperations();
    $db->getMembersBoards($id_user);

});

/*Delete Member
 *Method: DELETE
 *Route: /api/board/{id}/member/{id_member}
 *Param: -
*/
$app->delete('/api/board/{id}/member/{id_member}', function(Request $request, Response $response) {

    $id_board = $request->getAttribute('id');
    $id_member = $request->getAttribute('id_member');

    $db = new boardOperations();
    $db->deleteMember($id_board, $id_member);

});
