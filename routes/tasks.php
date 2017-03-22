<?php

include '../src/headers.php';

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require '../db/taskOperations.php';

/*Get task
 *Method: GET
 *Route: /api/task/{id}
 *Param: -
*/
$app->get("/api/task/{id}", function ($request, $response, $arguments) {

    $id = $request->getAttribute('id');

    $db = new taskOperations();
    $db->getTask($id);

});

/*Get list’s tasks
 *Method: GET
 *Route: /api/list/{id}/tasks
 *Param: -
*/
$app->get("/api/list/{id}/tasks", function ($request, $response, $arguments) {

    $id_list = $request->getAttribute('id');

    $db = new taskOperations();
    $db->getListsTasks($id_list);

});

/*Create task
 *Method: POST
 *Route: /api/list/{id}/task
 *Param: name
*/
$app->post("/api/list/{id}/task", function ($request, $response, $arguments) {

    $id_list = $request->getAttribute('id');
    $name = trim($request->getParam('name'));

    $db = new taskOperations();
    $db->createTask($name, $id_list);

});

/*Update task
 *Method: PUT
 *Route: /api/task/{id}
 *Param: name || term || desc || attachment
*/
$app->put("/api/task/{id}", function ($request, $response, $arguments) {

    $id = $request->getAttribute('id');
    $name = trim($request->getParam('name'));
    $term = trim($request->getParam('term'));
    $description = trim($request->getParam('description'));
    $attachment = trim($request->getParam('attachment'));

    $db = new taskOperations();

    if ($name !== '') {
        $db->updateName($id, $name);
    } elseif ($term !== '') {
        if ($db->isTermCorrect($term) && $db->isTermGtCurrent($term)) {
            $db->updateTerm($id, $term);
        }
    } elseif ($description !== '') {
        $db->updateDescription($id, $description);
    } elseif ($attachment !== '') {
        $db->updateAttachment($id, $attachment);
    }

});

/*Delete task
 *Method: DELETE
 *Route: /api/task/{id}
 *Param: -
*/
$app->delete("/api/task/{id}", function ($request, $response, $arguments) {

    $id = $request->getAttribute('id');

    $db = new taskOperations();
    if ($db->correctOrder($id)) {
        $db->deleteTask($id);
    }
});

/*Change order of tasks
 *Method: POST
 *Route: /api/list/{id}/tasks
 *Param: task_order
*/ //  TRZEBA PODAC WSZYSTKIE ZADANIA Z DANEJ LISTY
$app->post("/api/list/{id}/tasks", function ($request, $response, $arguments) {

    $id_list = $request->getAttribute('id');
    $task_order = $request->getParam('task_order');
    $task = explode(',' , $task_order);

    $db = new taskOperations();
    if ($db->existTasksInList($id_list, $task)) {
        $db->updateOrder($id_list, $task);
    }
});