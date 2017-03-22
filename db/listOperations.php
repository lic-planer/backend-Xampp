<?php

class listOperations
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

    public function createList($name, $id_board)
    {
        if (!$this->isNameCorrect($name)) {

        } else {
            try {
                $stmt = $this->con->prepare("INSERT INTO list (name, id_board) VALUES (?, ?)");
                $stmt->execute(array($name, $id_board));

                if ($this->incItemOrder($name, $id_board)) {
                    echo '{"notice": {"text": "Stworzono listę."}}';
                }
            } catch (PDOException $e) {
                echo '{"error": {"text": ' . $e->getMessage() . '}}';
            }
        }
    }

    public function isNameCorrect($name)
    {
        if ($name === '') {
            echo '{"error": {"text": "Nazwa listy nie może być pusta!"}}';
            return false;
        } else {
            return true;
        }
    }

    public function incItemOrder ($name, $id_board)
    {
        $stmt = $this->con->prepare("SELECT item_order FROM list WHERE id_board = ?");
        $stmt->execute(array($id_board));
        $itemOrder = $stmt->fetchAll(PDO::FETCH_OBJ);
        $sizeOfItemOrder = sizeof($itemOrder);
        $maxItemOrder = max($itemOrder);
        $arrMaxItemOrder[] = (array)$maxItemOrder;
        $intMaxListOrder = (int)$arrMaxItemOrder[0]['item_order'];

        if ($sizeOfItemOrder > 1) {
            $sql = "UPDATE list SET
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

    public function getList($id)
    {
        $stmt = $this->con->prepare("SELECT * FROM list WHERE id = ?");

        try {
            $stmt->execute(array($id));
            $user = $stmt->fetchAll(PDO::FETCH_OBJ);
            echo json_encode($user, JSON_UNESCAPED_UNICODE);

        } catch(PDOException $e){
            echo '{"error": {"text": '.$e->getMessage().'}}';
        }
    }

    public function getBoardsList($id_board)
    {
        $stmt = $this->con->prepare("SELECT l.id, l.name, l.id_board, l.item_order FROM `list` l RIGHT JOIN board b 
            ON l.id_board = b.id WHERE l.id_board = ? ORDER BY l.item_order ASC");

        try {
            $stmt->execute(array($id_board));
            $list = $stmt->fetchAll(PDO::FETCH_OBJ);
            echo json_encode($list, JSON_UNESCAPED_UNICODE);

        } catch(PDOException $e){
            echo '{"error": {"text": '.$e->getMessage().'}}';
        }
    }

    public function updateName($id, $name)
    {
        $sql = "UPDATE list SET
            name    = :name
            WHERE id = :id";

        try {
            $db = new db();
            $db = $db->connect();

            $stmt = $db->prepare($sql);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            echo '{"notice": {"text": "Nazwa listy została zmieniona."}}';
        } catch(PDOException $e){
            echo '{"error": {"text": '.$e->getMessage().'}}';
        }
    }

    public function deleteList($id)
    {
        $sql = "DELETE FROM list 
            WHERE id = :id";

        try {
            $db = new db();
            $db = $db->connect();

            $stmt = $db->prepare($sql);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            echo '{"notice": {"text": "Usunięto listę."}}';
        } catch(PDOException $e){
            echo '{"error": {"text": '.$e->getMessage().'}}';
        }
    }

    public function updateOrder($id_board, $list)
    {
        $i = 1 ;
        foreach($list as $id) {

            $sql  = "UPDATE list 
                        SET item_order = :item_order 
                        WHERE id = :id AND id_board = :id_board";

            try {
                $db = new db();
                $db = $db->connect();
                $query = $db->prepare($sql);

                $query->bindParam(':item_order', $i);
                $query->bindParam(':id', $id);
                $query->bindParam(':id_board', $id_board);
                $query->execute();

            } catch (PDOException $e) {
                echo 'PDOException : '.  $e->getMessage();
            }
            $i++ ;
        }
        echo '{"notice": {"text": "Zaktualizowano kolejność list."}}';
    }

    public function existListsInBoard($id_board, $list)
    {
        $arrListFromDb = array();
        $stmt = $this->con->prepare("SELECT id FROM list WHERE id_board = ?");

        try {
            $stmt->execute(array($id_board));
            $listFromDb = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $it = new RecursiveIteratorIterator(new RecursiveArrayIterator($listFromDb));
            foreach($it as $v) {
                $arrListFromDb[] = $v;
            }

            $result = array_diff($list, $arrListFromDb);

            if ($result === array()) {
                return true;
            } else {
                echo '{"error": {"text": "Podane listy nie są z tej samej tablicy!"}}';
                return false;
            }

        } catch(PDOException $e) {
            echo '{"error": {"text": '.$e->getMessage().'}}';
        }
        return true;
    }


    public function correctOrder($id)
    {
        $arrListFromDb = array();
        $stmt = $this->con->prepare("SELECT id_board, item_order FROM list WHERE id = ?");

        try {
            $stmt->execute(array($id));
            $list = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $id_board = $list[0]['id_board'];
            $item_order = $list[0]['item_order'];

            $allLists = $this->allLists($id_board);
            $it = new RecursiveIteratorIterator(new RecursiveArrayIterator($allLists));
            foreach($it as $v) {
                $arrListFromDb[] = (int)$v;
            }

            $key = array_search($item_order,$arrListFromDb);
            $allGtKey = array_slice($arrListFromDb, $key+1);

            if ($allGtKey === array()) {
                return true;
            } else {
                $this->decItemOrder($id_board, $allGtKey);
                return true;
            }
        } catch(PDOException $e) {
            echo '{"error": {"text": '.$e->getMessage().'}}';
        }
        return true;
    }

    public function allLists($id_board)
    {
        $stmt = $this->con->prepare("SELECT l.item_order FROM `list` l RIGHT JOIN board b 
            ON l.id_board = b.id WHERE l.id_board = ? ORDER BY l.item_order ASC");

        try {
            $stmt->execute(array($id_board));
            $list = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $list;

        } catch(PDOException $e){
            echo '{"error": {"text": '.$e->getMessage().'}}';
        }
        return true;
    }

    public function decItemOrder($id_board, $allGtKey)
    {
        $sql = "UPDATE list SET
            item_order = item_order-1
            WHERE id_board = :id_board AND item_order IN (".implode(',',$allGtKey).")";

        try {
            $db = new db();
            $db = $db->connect();
            $query = $db->prepare($sql);
            $query->bindParam(':id_board', $id_board);
            $query->execute();
            echo '{"notice": {"text": "Zmniejszono kolejność list o 1."}}';
        } catch (PDOException $e) {
            echo 'PDOException : '.  $e->getMessage();
        }
    }

}