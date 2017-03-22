<?php

class commentOperations
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

    public function createComment($content, $id_user, $id_task)
    {
        if (!$this->isContentCorrect($content)) {

        } else {
            try {
                $stmt = $this->con->prepare("INSERT INTO comment (content, id_user, id_task) VALUES (?, ?, ?)");
                $stmt->execute(array($content, $id_user, $id_task));

                echo '{"notice": {"text": "Komentarz został dodany."}}';
            } catch (PDOException $e) {
                echo '{"error": {"text": ' . $e->getMessage() . '}}';
            }
        }
    }

    public function isContentCorrect($content)
    {
        if ($content === null || $content === '') {
            echo '{"error": {"text": "Treść komentarza nie może być pusta!"}}';
            return false;
        } else {
            return true;
        }
    }

    public function getComment($id)
    {
        $stmt = $this->con->prepare("SELECT * FROM comment WHERE id = ?");

        try {
            $stmt->execute(array($id));
            $user = $stmt->fetchAll(PDO::FETCH_OBJ);
            echo json_encode($user, JSON_UNESCAPED_UNICODE);

        } catch(PDOException $e){
            echo '{"error": {"text": '.$e->getMessage().'}}';
        }
    }

    public function getTasksComments($id_task)
    {
        $stmt = $this->con->prepare("SELECT c.id, c.content, c.edited, c.id_user, c.id_task FROM `comment` c RIGHT JOIN task t 
            ON c.id_task = t.id WHERE c.id_task = ?");

        try {
            $stmt->execute(array($id_task));
            $user = $stmt->fetchAll(PDO::FETCH_OBJ);
            echo json_encode($user, JSON_UNESCAPED_UNICODE);

        } catch(PDOException $e){
            echo '{"error": {"text": '.$e->getMessage().'}}';
        }
    }

    public function updateComment($id, $content)
    {
        $sql = "UPDATE comment SET
            content = :content,
            edited = 1
            WHERE id = :id";

        try {
            $db = new db();
            $db = $db->connect();

            $stmt = $db->prepare($sql);
            $stmt->bindParam(':content', $content);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            echo '{"notice": {"text": "Komentarz został zmieniony."}}';
        } catch(PDOException $e){
            echo '{"error": {"text": '.$e->getMessage().'}}';
        }
    }

    public function deleteComment($id)
    {
        $sql = "DELETE FROM comment 
            WHERE id = :id";

        try {
            $db = new db();
            $db = $db->connect();

            $stmt = $db->prepare($sql);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            echo '{"notice": {"text": "Komentarz został usunięty."}}';
        } catch(PDOException $e){
            echo '{"error": {"text": '.$e->getMessage().'}}';
        }
    }

}