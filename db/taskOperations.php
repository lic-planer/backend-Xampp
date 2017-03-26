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

    public function createTask($name, $id_list)
    {
        if (!$this->isNameCorrect($name)) {

        } else {
            try {
                $stmt = $this->con->prepare("INSERT INTO task (name, id_list) VALUES (?, ?)");
                $stmt->execute(array($name, $id_list));

                if ($this->incItemOrder($name, $id_list)) {
                    echo '{"notice": {"text": "Stworzono zadanie."}}';
                }
            } catch (PDOException $e) {
                echo '{"error": {"text": ' . $e->getMessage() . '}}';
            }
        }
    }

    public function isNameCorrect($name)
    {
        if ($name === null || $name === '') {
            echo '{"error": {"text": "Nazwa zadania nie może być pusta!"}}';
            return false;
        } else {
            return true;
        }
    }

    public function incItemOrder ($name, $id_list)
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
                    WHERE name = :name";

            $db = new db();
            $db = $db->connect();
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':item_order', $intMaxListOrder);
            $stmt->bindParam(':name', $name);
            $stmt->execute();

            return true;
        }
        return true;
    }

    public function getTask($id)
    {
        $stmt = $this->con->prepare("SELECT * FROM task WHERE id = ?");

        try {
            $stmt->execute(array($id));
            $user = $stmt->fetchAll(PDO::FETCH_OBJ);
            echo json_encode($user, JSON_UNESCAPED_UNICODE);

        } catch(PDOException $e){
            echo '{"error": {"text": '.$e->getMessage().'}}';
        }
    }

    public function getListsTasks($id_list)
    {
        $stmt = $this->con->prepare("SELECT t.id, t.name, t.description, t.term, t.id_list, t.item_order FROM `task` t RIGHT JOIN list l 
            ON t.id_list = l.id WHERE t.id_list = ?");

        try {
            $stmt->execute(array($id_list));
            $user = $stmt->fetchAll(PDO::FETCH_OBJ);
            echo json_encode($user, JSON_UNESCAPED_UNICODE);

        } catch(PDOException $e){
            echo '{"error": {"text": '.$e->getMessage().'}}';
        }
    }

    public function updateName($id, $name)
    {
        $sql = "UPDATE task SET
            name    = :name
            WHERE id = :id";

        try {
            $db = new db();
            $db = $db->connect();

            $stmt = $db->prepare($sql);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            echo '{"notice": {"text": "Nazwa zadania została zmieniona."}}';
        } catch(PDOException $e){
            echo '{"error": {"text": '.$e->getMessage().'}}';
        }
    }

    public function updateDescription($id, $description)
    {
        $sql = "UPDATE task SET
            description    = :description
            WHERE id = :id";

        try {
            $db = new db();
            $db = $db->connect();

            $stmt = $db->prepare($sql);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            echo '{"notice": {"text": "Opis zadania został zmieniony."}}';
        } catch(PDOException $e){
            echo '{"error": {"text": '.$e->getMessage().'}}';
        }
    }

    public function updateTerm($id, $term)
    {
        $sql = "UPDATE task SET
            term    = :term
            WHERE id = :id";

        try {
            $db = new db();
            $db = $db->connect();

            $stmt = $db->prepare($sql);
            $stmt->bindParam(':term', $term);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            echo '{"notice": {"text": "Termin zadania został zmieniony."}}';
        } catch(PDOException $e){
            echo '{"error": {"text": '.$e->getMessage().'}}';
        }
    }

    public function deleteTask($id)
    {
        $sql = "DELETE FROM task 
            WHERE id = :id";

        try {
            $db = new db();
            $db = $db->connect();

            $stmt = $db->prepare($sql);
            $stmt->bindParam(':id', $id);
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

    public function isTermGtCurrent($term)
    {
        $now = new DateTime();
        $formatNow = $now->format('Y-m-d');

        if ($term >= $formatNow) {
            return true;
        } else {
            echo '{"notice": {"text": "Podana data nie może być wcześniejsza od dzisiejszej."}}';
            return false;
        }
    }

    public function updateOrder($id_list, $task)
    {
        $i = 1 ;
        foreach($task as $id) {

            $sql  = "UPDATE task 
                        SET item_order = :item_order 
                        WHERE id = :id AND id_list = :id_list";

            try {
                $db = new db();
                $db = $db->connect();
                $query = $db->prepare($sql);

                $query->bindParam(':item_order', $i);
                $query->bindParam(':id', $id);
                $query->bindParam(':id_list', $id_list);
                $query->execute();

            } catch (PDOException $e) {
                echo 'PDOException : '.  $e->getMessage();
            }
            $i++ ;
        }
        echo '{"notice": {"text": "Zaktualizowano kolejność zadań."}}';
    }

    public function existTasksInList($id_list, $task)
    {
        $arrTaskFromDb = array();
        $stmt = $this->con->prepare("SELECT id FROM task WHERE id_list = ?");

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
                echo '{"error": {"text": "Podane zadania nie są z tej samej listy!"}}';
                return false;
            }

        } catch(PDOException $e) {
            echo '{"error": {"text": '.$e->getMessage().'}}';
        }
        return true;
    }


    public function correctOrder($id)
    {
        $arrTaskFromDb = array();
        $stmt = $this->con->prepare("SELECT id_list, item_order FROM task WHERE id = ?");

        try {
            $stmt->execute(array($id));
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
            echo '{"notice": {"text": "Zmniejszono kolejność zadań o 1."}}';
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
        $uploaded = move_uploaded_file($file, $attachDir.$name);

        if ($uploaded === true) {
            echo '{"notice": {"text": "Zapisano w folderze."}}';
        } else {
            echo '{"error": {"text": "Wystąpił błąd podczas zapisu pliku."}}';
        }
    }


    public function addFileToDatabase($id, $attachName)
    {
        $attachName2 = $id.'-'.$attachName;
        $file = base64_encode($attachName2);

        try {
            $stmt = $this->con->prepare("INSERT INTO attachment (id, name, file) VALUES (?, ?, ?)");
            $stmt->execute(array($id, $attachName, $file));

            echo '{"notice": {"text": "Plik został dodany do bazy."}}';
        } catch(PDOException $e){
            echo '{"error": {"text": '.$e->getMessage().'}}';
        }
    }


    public function fileExists($id)
    {
        $stmt = $this->con->prepare("SELECT * FROM attachment WHERE id=?");
        $stmt->execute(array($id));
        $stmt->fetch(PDO::FETCH_ASSOC);
        $num_rows = $stmt->rowCount();

        if ($num_rows > 0) {
            return true;
        } else {
            return false;
        }
    }


    public function deleteFileFromDatabase($id)
    {
        $sql = "DELETE FROM attachment
            WHERE id = :id";

        try {
            $db = new db();
            $db = $db->connect();

            $stmt = $db->prepare($sql);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            echo '{"notice": {"text": "Usunięto plik z bazy."}}';
        } catch(PDOException $e){
            echo '{"error": {"text": '.$e->getMessage().'}}';
        }
    }


    public function deleteFileFromFolder($id)
    {
        $stmt = $this->con->prepare("SELECT * FROM attachment WHERE id=?");
        $stmt->execute(array($id));
        $attachment = $stmt->fetch(PDO::FETCH_ASSOC);
        $file = $attachment['file'];
        $file = base64_decode($file);

        $attachDir = '../attachments/';
        $delete = unlink($attachDir . $file);
        if ($delete === true) {
            echo '{"notice": {"text": "Usunięto z folderu."}}';
        } else {
            echo '{"error": {"text": "Wystąpił błąd podczas usuwania pliku."}}';
        }
    }


}