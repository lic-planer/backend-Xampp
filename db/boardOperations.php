<?php

class boardOperations
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

    public function createBoard($name, $id_owner)
    {
        if (!$this->isNameCorrect($name)) {

        } else {
            try {
                $stmt = $this->con->prepare("INSERT INTO board (name, id_user) VALUES (?, ?)");
                $stmt->execute(array($name, $id_owner));

                echo '{"notice": {"text": "Stworzono tablicę."}}';
            } catch (PDOException $e) {
                echo '{"error": {"text": ' . $e->getMessage() . '}}';
            }
        }
    }

    public function getBoards()
    {
        $stmt = $this->con->prepare("SELECT * FROM board");

        try {
            $stmt->execute();
            $boards = $stmt->fetchAll(PDO::FETCH_OBJ);
            echo json_encode($boards, JSON_UNESCAPED_UNICODE);

        } catch(PDOException $e){
            echo '{"error": {"text": '.$e->getMessage().'}}';
        }
    }

    public function getOwnersBoards($id_owner)
    {
        $stmt = $this->con->prepare("SELECT * FROM board WHERE id_user = ?");

        try {
            $stmt->execute(array($id_owner));
            $user = $stmt->fetchAll(PDO::FETCH_OBJ);
            echo json_encode($user, JSON_UNESCAPED_UNICODE);

        } catch(PDOException $e){
            echo '{"error": {"text": '.$e->getMessage().'}}';
        }
    }

    public function getBoardsMembers($id_board)
    {
        $stmt = $this->con->prepare("SELECT u.id, u.username, u.password, u.email, u.avatar, u.activate FROM `user` u LEFT JOIN member m 
            ON u.id = m.id_user WHERE m.id_board = ?");

        try {
            $stmt->execute(array($id_board));;
            $user = $stmt->fetchAll(PDO::FETCH_OBJ);
            echo json_encode($user, JSON_UNESCAPED_UNICODE);

        } catch(PDOException $e){
            echo '{"error": {"text": '.$e->getMessage().'}}';
        }
    }

    public function getMembersBoards($id_user)
    {
        $stmt = $this->con->prepare("SELECT b.id, b.name, b.id_user FROM `board` b LEFT JOIN member m 
            ON b.id = m.id_board WHERE m.id_user = ?");

        try {
            $stmt->execute(array($id_user));;
            $user = $stmt->fetchAll(PDO::FETCH_OBJ);

            echo json_encode($user, JSON_UNESCAPED_UNICODE);

        } catch(PDOException $e){
            echo '{"error": {"text": '.$e->getMessage().'}}';
        }
    }

    public function isNameCorrect($name)
    {
        if ($name === null) {
            echo '{"error": {"text": "Nazwa tablicy nie może być pusta!"}}';
            return false;
        } else {
            return true;
        }
    }

    public function updateName($id, $name)
    {
        $sql = "UPDATE board SET
            name    = :name
            WHERE id = :id";

        try {
            $db = new db();
            $db = $db->connect();

            $stmt = $db->prepare($sql);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            echo '{"notice": {"text": "Nazwa tablicy została zmieniona."}}';
        } catch(PDOException $e){
            echo '{"error": {"text": '.$e->getMessage().'}}';
        }
    }

    public function deleteBoard($id)
    {
        $sql = "DELETE FROM board 
            WHERE id = :id";

        try {
            $db = new db();
            $db = $db->connect();

            $stmt = $db->prepare($sql);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            echo '{"notice": {"text": "Usunięto tablicę."}}';
        } catch(PDOException $e){
            echo '{"error": {"text": '.$e->getMessage().'}}';
        }
    }

    public function addMemeber($id_board, $id_user)
    {
        $stmt = $this->con->prepare("INSERT INTO member (id_user, id_board) VALUES (?, ?)");

        try {
            $stmt->execute(array($id_user, $id_board));

            echo '{"notice": {"text": "Dodano użytkownika do tablicy."}}';
        } catch (PDOException $e) {
            echo '{"error": {"text": ' . $e->getMessage() . '}}';
        }
    }

    public function deleteMember($id_board, $id_member)
    {
        $sql = "DELETE FROM member 
            WHERE id_board = :id_board AND id_user = :id_user";

        try {
            $db = new db();
            $db = $db->connect();

            $stmt = $db->prepare($sql);
            $stmt->bindParam(':id_board', $id_board);
            $stmt->bindParam(':id_user', $id_member);
            $stmt->execute();

            echo '{"notice": {"text": "Usunięto użytkownika z tablicy."}}';
        } catch(PDOException $e){
            echo '{"error": {"text": '.$e->getMessage().'}}';
        }
    }

}