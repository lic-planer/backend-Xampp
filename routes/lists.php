<?php

include '../src/headers.php';

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require '../db/listOperations.php';

/*Get list
 *Method: GET
 *Route: /api/list/{id}
 *Param: -
*/
$app->get("/api/list/{id}", function ($request, $response, $arguments) {

    $id = $request->getAttribute('id');

    $db = new listOperations();
    $db->getList($id);

});

/*Get lists
 *Method: GET
 *Route: /api/board/{id}/lists
 *Param: -
*/
$app->get("/api/board/{id}/lists", function ($request, $response, $arguments) {

    $id_board = $request->getAttribute('id');

    $db = new listOperations();
    $db->getBoardsList($id_board);

});

/*Create list
 *Method: POST
 *Route: /api/board/{id}/list
 *Param: name
*/
$app->post("/api/board/{id}/list", function ($request, $response, $arguments) {

    $id_board = $request->getAttribute('id');
    $name = trim($request->getParam('name'));

    $db = new listOperations();
    $db->createList($name, $id_board);

});

/*Update list
 *Method: PUT
 *Route: /api/list/{id}
 *Param: name
*/
$app->put("/api/list/{id}", function ($request, $response, $arguments) {

    $id = $request->getAttribute('id');
    $name = trim($request->getParam('name'));

    $db = new listOperations();

    if (!$db->isNameCorrect($name)) {

    } else {
        $db->updateName($id, $name);
    }

});

/*Delete list
 *Method: DELETE
 *Route: /api/list/{id}
 *Param: -
*/
$app->delete("/api/list/{id}", function ($request, $response, $arguments) {

    $id = $request->getAttribute('id');

    $db = new listOperations();
    if ($db->correctOrder($id)) {
        $db->deleteList($id);
    }
});


/*Change order of lists
 *Method: POST
 *Route: /api/board/{id}/lists
 *Param: list_order
*/ //  TRZEBA PODAC WSZYSTKIE LISTY Z DANEJ TABLICY
$app->post("/api/board/{id}/lists", function ($request, $response, $arguments) {

    $id_board = $request->getAttribute('id');
    $list_order = $request->getParam('list_order');
    $list = explode(',' , $list_order);

    $db = new listOperations();
    if ($db->existListsInBoard($id_board, $list)) {
        $db->updateOrder($id_board, $list);
    }
});
