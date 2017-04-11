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
 *Param: name || term || desc
*/
$app->put("/api/task/{id}", function ($request, $response, $arguments) {

    $id = $request->getAttribute('id');
    $name = trim($request->getParam('name'));
    $term = trim($request->getParam('term'));
    $description = trim($request->getParam('description'));

    $db = new taskOperations();

    if ($name !== '') {
        $db->updateName($id, $name);
    } elseif ($term !== '' && $term !== 'null') {
        if ($db->isTermCorrect($term)) {
            $db->updateTerm($id, $term);
        }
    } elseif ($term === 'null') {
            $db->updateTermToNull($id);
    } elseif ($description !== '' && $description !== 'null') {
        $db->updateDescription($id, $description);
    } elseif ($description === 'null') {
        $db->updateDescriptionToNull($id);
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
<<<<<<< HEAD
        if ($db->fileExists($id)) {
            $db->deleteFileFromFolder($id);
            $db->deleteFileFromDatabase($id);
        }
=======
>>>>>>> origin/master
        $db->deleteTask($id);
    }
});

/*Change order of tasks
 *Method: POST
 *Route: /api/list/{id}/tasks
 *Param: task_order
*/
$app->post("/api/list/{id}/tasks", function ($request, $response, $arguments) {

    $id_list = $request->getAttribute('id');
    $task_order = $request->getParam('task_order');
    $task = explode(',' , $task_order);

    $db = new taskOperations();
    if ($db->existTasksInList($id_list, $task)) {
        $db->updateOrder($id_list, $task);
    }
});

/*Add attachment
 *Method: POST
 *Route: /api/task/{id}/attachment
 *Param: attachment
 */
$app->post('/api/task/{id}/attachment', function(Request $request, Response $response) {

    $id = $request->getAttribute('id');
    $files = $request->getUploadedFiles();
    $attachment = ($files["attachment"]);

    if ($files != null) {

        $attachName = 'name';
<<<<<<< HEAD
        $attachSize = 'size';
=======
>>>>>>> origin/master
        $file = $attachment->file;

        $db = new taskOperations();
        $attachName = $db->getProtectedValue($attachment, $attachName);
<<<<<<< HEAD
        $attachSize = $db->getProtectedValue($attachment, $attachSize);

        if ($db->fileSize($attachSize))
        {
            if ($db->fileExists($id)) {
                $db->deleteFileFromFolder($id);
                $db->deleteFileFromDatabase($id);
            }
            $db->saveFileToFolder($id, $file, $attachName);
            $db->addFileToDatabase($id, $attachName);
        }
=======

        if ($db->fileExists($id)) {
            $db->deleteFileFromFolder($id);
            $db->deleteFileFromDatabase($id);
        }
        $db->saveFileToFolder($id, $file, $attachName);
        $db->addFileToDatabase($id, $attachName);

>>>>>>> origin/master
    } else {
        echo '{"error": {"text": "Nie wybrano pliku!"}}';
    }
});

/*Delete attachment
 *Method: DELETE
 *Route: /api/task/{id}/attachment
 *Param: -
 */
$app->delete('/api/task/{id}/attachment', function(Request $request, Response $response) {

    $id = $request->getAttribute('id');

    $db = new taskOperations();
<<<<<<< HEAD
    if ($db->fileExists($id)) {
        $db->deleteFileFromFolder($id);
        $db->deleteFileFromDatabase($id);
        echo '{"notice": {"text": "Usunięto plik."}}';
    } else {
        echo '{"notice": {"text": "Plik już nie istnieje."}}';
=======
    $db->deleteFileFromFolder($id);
    $db->deleteFileFromDatabase($id);
    echo '{"notice": {"text": "Usunięto plik."}}';
});

/*Download attachment
 *Method: GET
 *Route: /api/task/{id}/attachment
 *Param: -
 */
$app->get('/api/task/{id}/attachment', function(Request $request, Response $response) {

    $id = $request->getAttribute('id');
    $db = new taskOperations();
    if ($db->fileExists($id)) {
        $db->downloadFile($id);
>>>>>>> origin/master
    }
});

/*Transfer task to other list
 *Method: GET
 *Route: /api/task/{id}/toList/{id_list}
 *Param: -
 */
$app->get('/api/task/{id}/toList/{id_list}', function(Request $request, Response $response) {

    $id_task = $request->getAttribute('id');
    $id_list = $request->getAttribute('id_list');

    $db = new taskOperations();

<<<<<<< HEAD
    $maxItemOrder = $db->getMaxItemOrder($id_list);
    if ($db->correctOrder($id_task)) {
        $db->incItemOrder1($id_task, $maxItemOrder);
        $db->transferTaskToNewList($id_task, $id_list);
    }

=======
    $task_name = $db->getTaskName($id_task);
    $maxItemOrder = $db->getMaxItemOrder($id_list);
    //if (!$db->existsTaskInList($task_name, $id_list)) {
        if ($db->correctOrder($id_task)) {
            $db->incItemOrder1($id_task, $maxItemOrder);
            $db->transferTaskToNewList($id_task, $id_list);
        }
    //}
>>>>>>> origin/master
});
