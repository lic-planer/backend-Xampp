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

    //Funkcja zwiększająca wartości kolumny ‘item_order’, nowo utworzonej listy w bazie danych o 1.
    public function incItemOrder($name, $id_board)
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

    public function getBoardsListNested($id_board)
    {
        $stmt = $this->con->prepare("SELECT l.id, l.name, l.id_board, l.item_order, t.id_task, t.task_name, t.item_order, t.term
          FROM task t RIGHT JOIN list l ON t.id_list = l.id RIGHT JOIN  board b ON l.id_board = b.id 
          WHERE l.id_board = ? ORDER BY l.item_order, t.item_order ASC");

        try {
            $stmt->execute(array($id_board));

            $stmt2 = $this->con->prepare("SELECT name FROM board WHERE id = ?");
            $stmt2->execute(array($id_board));
            $boardName = $stmt2->fetch(PDO::FETCH_ASSOC);
            $boardName = $boardName['name'];

            $arr = array();
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

                $arr[$row['id']]['id'] = $row['id'];
                $arr[$row['id']]['name'] = $row['name'];
                $arr[$row['id']]['b_name'] = $boardName;
                $temp = array('id_task' => $row['id_task'], 'task_name' => $row['task_name'], 'term' => $row['term']);

                if ($temp['id_task'] === null){
                    $arr[$row['id']]['tasks'] = null;
                } else {
                    $arr[$row['id']]['tasks'][] = $temp;
                }
            }

            $base_out = array();

            foreach ($arr as $key => $record) {
                $base_out[] = $record;
            }

            echo json_encode($base_out, JSON_UNESCAPED_UNICODE);
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

    //Funkcja wykorzystywana do 'drag&drop'. Aktualizuje kolejność wyświetlania list.
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

    //Funkcja sprawdzająca czy podane listy znajdują się w tej samej tablicy.
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

    //Funkcja wykorzystywana do 'drag&drop'. Sprawdza pozycję usuwanej listy w bazie - jeżeli zajmuje ona
    //ostatnie miejsce w bazie to kolejność pozostałych list się nie zmienia. W przypadku kiedy usuwana lista
    //znajduje się przed innymi listami, to wywoływana jest funkcja zmniejszająca wartość kolumny 'item_order'.
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

    //Funkcja zwracająca wszystkie listy, porządkując je rosnąco, na podstawie ‘item_order’.
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

    //Funkcja zmniejszająca wartość kolumny ‘item_order’ o jeden w listach, których indeks jest o jeden większy od listy usuwanej.
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
            //echo '{"notice": {"text": "Zmniejszono kolejność list o 1."}}';
        } catch (PDOException $e) {
            echo 'PDOException : '.  $e->getMessage();
        }
    }

    public function userHasAccess($id_user, $id_board)
    {
        $arrBoardsOwnerFromDb = array();
        $arrBoardsMemberFromDb = array();
        $id_board = array($id_board);

        try {
            $stmt = $this->con->prepare("SELECT id FROM board WHERE id_user = ?");
            $stmt->execute(array($id_user));
            $listOfIdBoardsOwnerFromDatabase = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $it = new RecursiveIteratorIterator(new RecursiveArrayIterator($listOfIdBoardsOwnerFromDatabase));
            foreach($it as $v) {
                $arrBoardsOwnerFromDb[] = $v;
            }

            $stmt = $this->con->prepare("SELECT id_board FROM member WHERE id_user = ?");
            $stmt->execute(array($id_user));
            $listOfIdBoardsMemberFromDatabase = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $it = new RecursiveIteratorIterator(new RecursiveArrayIterator($listOfIdBoardsMemberFromDatabase));
            foreach($it as $v) {
                $arrBoardsMemberFromDb[] = $v;
            }

            $resultMember = array_diff($id_board, $arrBoardsMemberFromDb);
            $resultOwner = array_diff($id_board, $arrBoardsOwnerFromDb);

            if ($resultMember === array() || $resultOwner === array()) {
                return true;
            } else {
                echo '{"error": {"text": "Nie masz dostępu do tej tablicy!"}}';
                header("Status: 401 Unauthorized");
                return false;
            }

        } catch (PDOException $e) {
            echo 'PDOException : '.  $e->getMessage();
        }
    }

}