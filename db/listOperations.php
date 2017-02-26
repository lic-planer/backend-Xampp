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

                echo '{"notice": {"text": "Stworzono listę."}}';
            } catch (PDOException $e) {
                echo '{"error": {"text": ' . $e->getMessage() . '}}';
            }
        }
    }

    public function isNameCorrect($name)
    {
        if ($name === null) {
            echo '{"error": {"text": "Nazwa listy nie może być pusta!"}}';
            return false;
        } else {
            return true;
        }
    }

    public function getList($id)
    {
        $sql = "SELECT * FROM list WHERE id = $id";

        try {
            $db = new db();
            $db = $db->connect();

            $stmt = $db->query($sql);
            $user = $stmt->fetchAll(PDO::FETCH_OBJ);
            $db = null;
            echo json_encode($user);

        } catch(PDOException $e){
            echo '{"error": {"text": '.$e->getMessage().'}}';
        }
    }

    public function getBoardsList($id_board)
    {
        $sql = "SELECT l.id, l.name, l.id_board FROM `list` l RIGHT JOIN board b 
            ON l.id_board = b.id WHERE l.id_board = $id_board";

        try {
            $db = new db();
            $db = $db->connect();

            $stmt = $db->query($sql);
            $user = $stmt->fetchAll(PDO::FETCH_OBJ);
            $db = null;
            echo json_encode($user);

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

}