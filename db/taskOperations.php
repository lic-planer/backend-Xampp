<?php

class taskOperations
{
    private $con = null;

    function __construct() {
        require_once '../db/db.php';

        try {
            $db = new db();
            $this->con = $db->connect();
        } catch (PDOException $e) {
            echo 'Połączenie nie mogło zostać utworzone:<br> ' . $e->getMessage();
        }
    } //__construct

    public function createTask($task_name, $id_list)
    {
        if (!$this->isNameCorrect($task_name)) {

        } else {
            try {
                $stmt = $this->con->prepare("INSERT INTO task (task_name, id_list) VALUES (?, ?)");
                $stmt->execute(array($task_name, $id_list));

                if ($this->incItemOrder($task_name, $id_list)) {
                    echo '{"notice": {"text": "Stworzono zadanie."}}';
                }
            } catch (PDOException $e) {
                echo '{"error": {"text": ' . $e->getMessage() . '}}';
            }
        }
    }

    public function isNameCorrect($task_name)
    {
        if ($task_name === null || $task_name === '') {
            echo '{"error": {"text": "Nazwa zadania nie może być pusta!"}}';
            return false;
        } else {
            return true;
        }
    }

    public function incItemOrder($task_name, $id_list)
    {
        $stmt = $this->con->prepare("SELECT item_order FROM task WHERE id_list = ?");
        $stmt->execute(array($id_list));
        $itemOrder = $stmt->fetchAll(PDO::FETCH_OBJ);
        $sizeOfItemOrder = sizeof($itemOrder);
        $maxItemOrder = max($itemOrder);
        $arrMaxItemOrder[] = (array)$maxItemOrder;
        $intMaxListOrder = (int)$arrMaxItemOrder[0]['item_order'];

        if ($sizeOfItemOrder > 1) {
            $sql = "UPDATE task SET
                    item_order    = :item_order+1
                    WHERE task_name = :task_name";

            $db = new db();
            $db = $db->connect();
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':item_order', $intMaxListOrder);
            $stmt->bindParam(':task_name', $task_name);
            $stmt->execute();

            return true;
        }
        return true;
    }

    public function getTask($id_task)
    {
        $stmt = $this->con->prepare("SELECT * FROM task WHERE id_task = ?");

        try {
            $stmt->execute(array($id_task));
            $task = $stmt->fetchAll(PDO::FETCH_OBJ);
            echo json_encode($task, JSON_UNESCAPED_UNICODE);

        } catch(PDOException $e){
            echo '{"error": {"text": '.$e->getMessage().'}}';
        }
    }


    public function updateName($id_task, $task_name)
    {
        $sql = "UPDATE task SET
            task_name    = :task_name
            WHERE id_task = :id_task";

        try {
            $db = new db();
            $db = $db->connect();

            $stmt = $db->prepare($sql);
            $stmt->bindParam(':task_name', $task_name);
            $stmt->bindParam(':id_task', $id_task);
            $stmt->execute();

            echo '{"notice": {"text": "Nazwa zadania została zmieniona."}}';
        } catch(PDOException $e){
            echo '{"error": {"text": '.$e->getMessage().'}}';
        }
    }

    public function updateDescription($id_task, $description)
    {
        $sql = "UPDATE task SET
            description    = :description
            WHERE id_task = :id_task";

        try {
            $db = new db();
            $db = $db->connect();

            $stmt = $db->prepare($sql);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':id_task', $id_task);
            $stmt->execute();

            echo '{"notice": {"text": "Opis zadania został zmieniony."}}';
        } catch(PDOException $e){
            echo '{"error": {"text": '.$e->getMessage().'}}';
        }
    }

    public function updateDescriptionToNull($id_task)
    {
        $sql = "UPDATE task SET
            description = null
            WHERE id_task = :id_task";

        try {
            $db = new db();
            $db = $db->connect();

            $stmt = $db->prepare($sql);
            $stmt->bindParam(':id_task', $id_task);
            $stmt->execute();

            echo '{"notice": {"text": "Opis zadania został usunięty."}}';
        } catch(PDOException $e){
            echo '{"error": {"text": '.$e->getMessage().'}}';
        }
    }

    public function updateTerm($id_task, $term)
    {
        $sql = "UPDATE task SET
            term    = :term
            WHERE id_task = :id_task";

        try {
            $db = new db();
            $db = $db->connect();

            $stmt = $db->prepare($sql);
            $stmt->bindParam(':term', $term);
            $stmt->bindParam(':id_task', $id_task);
            $stmt->execute();

            echo '{"notice": {"text": "Termin zadania został zmieniony."}}';
        } catch(PDOException $e){
            echo '{"error": {"text": '.$e->getMessage().'}}';
        }
    }

    public function updateTermToNull($id_task)
    {
        $sql = "UPDATE task SET
            term    = null
            WHERE id_task = :id_task";

        try {
            $db = new db();
            $db = $db->connect();

            $stmt = $db->prepare($sql);
            $stmt->bindParam(':id_task', $id_task);
            $stmt->execute();

            echo '{"notice": {"text": "Termin zadania został usunięty."}}';
        } catch(PDOException $e){
            echo '{"error": {"text": '.$e->getMessage().'}}';
        }
    }

    public function deleteTask($id_task)
    {
        $sql = "DELETE FROM task 
            WHERE id_task = :id_task";

        try {
            $db = new db();
            $db = $db->connect();

            $stmt = $db->prepare($sql);
            $stmt->bindParam(':id_task', $id_task);
            $stmt->execute();

            echo '{"notice": {"text": "Zadanie zostało usunięte."}}';
        } catch(PDOException $e){
            echo '{"error": {"text": '.$e->getMessage().'}}';
        }
    }

    public function isTermCorrect($term)
    {
        if (preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/",$term))
        {
            return true;
        } else {
            echo '{"notice": {"text": "Podano zły format daty [yyyy-mm-dd]."}}';
            return false;
        }
    }

    //Funkcja wykorzystywana do 'drag&drop'. Aktualizuje kolejność wyświetlania zadań.
    public function updateOrder($id_list, $task)
    {
        $i = 1 ;
        foreach($task as $id_task) {

            $sql  = "UPDATE task 
                        SET item_order = :item_order 
                        WHERE id_task = :id_task AND id_list = :id_list";

            try {
                $db = new db();
                $db = $db->connect();
                $query = $db->prepare($sql);

                $query->bindParam(':item_order', $i);
                $query->bindParam(':id_task', $id_task);
                $query->bindParam(':id_list', $id_list);
                $query->execute();

            } catch (PDOException $e) {
                echo 'PDOException : '.  $e->getMessage();
            }
            $i++ ;
        }
        echo '{"notice": {"text": "Kolejność zadań została zmieniona."}}';
    }

    //Funkcja sprawdzająca czy podane zadania znajdują się w tej samej liście.
    public function existTasksInList($id_list, $task)
    {
        $arrTaskFromDb = array();
        $stmt = $this->con->prepare("SELECT id_task FROM task WHERE id_list = ?");

        try {
            $stmt->execute(array($id_list));
            $taskFromDb = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $it = new RecursiveIteratorIterator(new RecursiveArrayIterator($taskFromDb));
            foreach($it as $v) {
                $arrTaskFromDb[] = $v;
            }

            $result = array_diff($task, $arrTaskFromDb);

            if ($result === array()) {
                return true;
            } else {
                //echo '{"error": {"text": "Podane zadania nie są z tej samej listy!"}}';
                return false;
            }

        } catch(PDOException $e) {
            echo '{"error": {"text": '.$e->getMessage().'}}';
        }
        return true;
    }

    //Funkcja wykorzystywana do 'drag&drop'. Sprawdza pozycję usuwanego zadania w bazie - jeżeli zajmuje ono
    //ostatnie miejsce w bazie to kolejność pozostałych zadań się nie zmienia. W przypadku kiedy usuwane zadanie
    //znajduje się przed innymi zadaniami, to wywoływana jest funkcja zmniejszająca wartość kolumny 'item_order'.
    public function correctOrder($id_task)
    {
        $arrTaskFromDb = array();
        $stmt = $this->con->prepare("SELECT id_list, item_order FROM task WHERE id_task = ?");

        try {
            $stmt->execute(array($id_task));
            $task = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $id_list = $task[0]['id_list'];
            $item_order = $task[0]['item_order'];

            $allTasks = $this->allTasks($id_list);
            $it = new RecursiveIteratorIterator(new RecursiveArrayIterator($allTasks));
            foreach($it as $v) {
                $arrTaskFromDb[] = (int)$v;
            }

            $key = array_search($item_order,$arrTaskFromDb);
            $allGtKey = array_slice($arrTaskFromDb, $key+1);

            if ($allGtKey === array()) {
                return true;
            } else {
                $this->decItemOrder($id_list, $allGtKey);
                return true;
            }
        } catch(PDOException $e) {
            echo '{"error": {"text": '.$e->getMessage().'}}';
        }
        return true;
    }

    public function allTasks($id_list)
    {
        $stmt = $this->con->prepare("SELECT t.item_order FROM `task` t RIGHT JOIN list l 
            ON t.id_list = l.id WHERE t.id_list = ? ORDER BY t.item_order ASC");

        try {
            $stmt->execute(array($id_list));
            $task = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $task;

        } catch(PDOException $e){
            echo '{"error": {"text": '.$e->getMessage().'}}';
        }
        return true;
    }

    //Funkcja zmniejszająca wartość kolumny ‘item_order’ o jeden w zadaniach, których indeks jest o jeden większy od usuwanego zadania.
    public function decItemOrder($id_list, $allGtKey)
    {
        $sql = "UPDATE task SET
            item_order = item_order-1
            WHERE id_list = :id_list AND item_order IN (".implode(',',$allGtKey).")";

        try {
            $db = new db();
            $db = $db->connect();
            $query = $db->prepare($sql);
            $query->bindParam(':id_list', $id_list);
            $query->execute();
            //echo '{"notice": {"text": "Zmniejszono kolejność zadań o 1."}}';
        } catch (PDOException $e) {
            echo 'PDOException : '.  $e->getMessage();
        }
    }

    public function getProtectedValue($obj, $name) {
        $array = (array)$obj;
        $prefix = chr(0).'*'.chr(0);
        return $array[$prefix.$name];
    }

    public function saveFileToFolder($id, $file, $attachName)
    {
        $attachDir = '../attachments/';
        $name = $id.'-'.$attachName;
        move_uploaded_file($file, $attachDir.$name);
    }


    public function addFileToDatabase($id, $attachName)
    {
        $date = date('Y-m-d G:i:s', time());

        $sql = "UPDATE task SET
            attachment_name = :attachment_name,
            attachment_date = :attachment_date
            WHERE id_task = :id_task";

        try {
            $db = new db();
            $db = $db->connect();

            $stmt = $db->prepare($sql);
            $stmt->bindParam(':attachment_name', $attachName);
            $stmt->bindParam(':attachment_date', $date);
            $stmt->bindParam(':id_task', $id);
            $stmt->execute();

            echo '{"notice": {"text": "Plik został dodany."}}';
        } catch(PDOException $e){
            echo '{"error": {"text": '.$e->getMessage().'}}';
        }
    }


    public function fileExists($id)
    {
        $stmt = $this->con->prepare("SELECT attachment_name FROM task WHERE id_task=?");
        $stmt->execute(array($id));
        $name = $stmt->fetch(PDO::FETCH_ASSOC);
        $name = $name['attachment_name'];
        if ($name !== null) {
            return true;
        } else {
            return false;
        }
    }


    public function deleteFileFromDatabase($id)
    {
        $sql = "UPDATE task SET
            attachment_name = null,
            attachment_date = null
            WHERE id_task = :id_task";

        try {
            $db = new db();
            $db = $db->connect();

            $stmt = $db->prepare($sql);
            $stmt->bindParam(':id_task', $id);
            $stmt->execute();

        } catch(PDOException $e){
            echo '{"error": {"text": '.$e->getMessage().'}}';
        }
    }


    public function deleteFileFromFolder($id)
    {
        $stmt = $this->con->prepare("SELECT attachment_name FROM task WHERE id_task=?");
        $stmt->execute(array($id));
        $attachment = $stmt->fetch(PDO::FETCH_ASSOC);
        $file = $attachment['attachment_name'];
        $file = $id.'-'.$file;

        $attachDir = '../attachments/';
        unlink($attachDir . $file);
    }

    public function getAttachmentNameById($id)
    {
        $stmt = $this->con->prepare("SELECT attachment_name FROM task WHERE id_task=?");
        $stmt->execute(array($id));
        $name = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $name = $name[0]['attachment_name'];
        $fullName = $id.'-'.$name;
        return $fullName;
    }

    public function transferTaskToNewList($id_task, $id_list)
    {
        $sql = "UPDATE task SET
            id_list = :id_list
            WHERE id_task = :id_task";

        try {
            $db = new db();
            $db = $db->connect();

            $stmt = $db->prepare($sql);
            $stmt->bindParam(':id_task', $id_task);
            $stmt->bindParam(':id_list', $id_list);
            $stmt->execute();

            echo '{"notice": {"text": "Zadanie przeniesiono do innej listy."}}';
        } catch(PDOException $e){
            echo '{"error": {"text": '.$e->getMessage().'}}';
        }
    }

    public function getTaskName($id_task)
    {
        $stmt = $this->con->prepare("SELECT task_name FROM task WHERE id_task = ?");

        try {
            $stmt->execute(array($id_task));
            $task = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $task = $task[0]['task_name'];
            return $task;
        } catch(PDOException $e){
            echo '{"error": {"text": '.$e->getMessage().'}}';
        }
    }

    public function getMaxItemOrder($id_list)
    {
        $stmt = $this->con->prepare("SELECT item_order FROM task WHERE id_list = ?");

        try {
            $stmt->execute(array($id_list));
            $itemOrder = $stmt->fetchAll(PDO::FETCH_OBJ);
            $maxItemOrder = max($itemOrder);
            $arrMaxItemOrder[] = (array)$maxItemOrder;
            $intMaxListOrder = (int)$arrMaxItemOrder[0]['item_order'];
            return $intMaxListOrder;
        } catch(PDOException $e){
            echo '{"error": {"text": '.$e->getMessage().'}}';
        }
    }

    public function incItemOrder1($id_task, $maxItemOrder)
    {
        $sql = "UPDATE task SET
            item_order = :maxItemOrder + 1
            WHERE id_task = :id_task";

        try {
            $db = new db();
            $db = $db->connect();

            $stmt = $db->prepare($sql);
            $stmt->bindParam(':maxItemOrder', $maxItemOrder);
            $stmt->bindParam(':id_task', $id_task);
            $stmt->execute();

        } catch(PDOException $e){
            echo '{"error": {"text": '.$e->getMessage().'}}';
        }
    }

    public function existsTaskInList($task_name, $id_list)
    {
        $stmt = $this->con->prepare("SELECT id_task FROM task WHERE task_name = ? AND id_list = ?");
        $stmt->execute(array($task_name, $id_list));
        $stmt->fetch(PDO::FETCH_ASSOC);
        $num_rows = $stmt->rowCount();

        if ($num_rows > 0) {
            echo '{"error": {"text": "Zadanie w tej liście już istnieje!"}}';
            return true;
        } else {
            return false;
        }
    }

    public function fileSize($fileSize)
    {
        if ($fileSize <= 8388608) {
            return true;
        } else {
            echo '{"error": {"text": "Rozmiar pliku jest za duży! (max. 8 MB)"}}';
            return false;
        }
    }

}