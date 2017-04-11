<?php

include '../src/headers.php';

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require '../db/commentOperations.php';

/*Get comment
 *Method: GET
 *Route: /api/comment/{id}
 *Param: -
*/
$app->get("/api/comment/{id}", function ($request, $response, $arguments) {

    $id = $request->getAttribute('id');

    $db = new commentOperations();
    $db->getComment($id);

});

/*Get task's comments
 *Method: GET
 *Route: /api/task/{id}/comments
 *Param: -
*/
$app->get("/api/task/{id}/comments", function ($request, $response, $arguments) {

    $id_task = $request->getAttribute('id');

    $db = new commentOperations();
    $db->getTasksComments($id_task);

});

/*Create comment
 *Method: POST
 *Route: /api/task/{id}/comment
 *Param: content
*/
$app->post("/api/task/{id}/comment", function ($request, $response, $arguments) {

    $id_task = $request->getAttribute('id');
    $content = trim($request->getParam('content'));

    $token = new token();
    $jwt = $token->getToken($request);
    $id_user = $jwt->user[0]->id;

    $db = new commentOperations();
    $db->createComment($content, $id_user, $id_task);

});

/*Update comment
 *Method: PUT
 *Route: /api/comment/{id}
 *Param: content
*/
$app->put("/api/comment/{id}", function ($request, $response, $arguments) {

    $id = $request->getAttribute('id');
    $content = trim($request->getParam('content'));

    $db = new commentOperations();
    if ($db->isContentCorrect($content)) {
        $db->updateComment($id, $content);
    }

});

/*Delete comment
 *Method: DELETE
 *Route: /api/comment/{id}
 *Param: -
*/
$app->delete("/api/comment/{id}", function ($request, $response, $arguments) {

    $id = $request->getAttribute('id');

    $db = new commentOperations();
    $db->deleteComment($id);

});