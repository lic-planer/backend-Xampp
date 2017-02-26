<?php

class boardOperations
{
    private $con = null;

    function __construct() {
        require_once '../db/config.php';

        try {
            $db = new db();
            $this->con = $db->connect();
        } catch (PDOException $e) {
            echo 'Połączenie nie mogło zostać utworzone:<br> ' . $e->getMessage();
        }
    } //__construct

    public function createBoard ($name, $id_owner)
    {
        try {
            $stmt = $this->con->prepare("INSERT INTO board (name, id_user) VALUES (?, ?)");
            $stmt->execute(array($name, $id_owner));

            echo '{"notice": {"text": "Board Created"}}';
        } catch (PDOException $e) {
            echo '{"error": {"text": ' . $e->getMessage() . '}}';
        }
    }
}