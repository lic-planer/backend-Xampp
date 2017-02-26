<?php

class userOperations
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

    public function createUser($username, $pass, $email, $avatar)
    {

        if ((trim($username) === '' || $username === null) ||  (trim($email) === '' || $email === null) || $pass === null) {
            echo '{"error": {"text": "Pole nazwa użytkownika, hasło i email nie mogą być puste!"}}';
        } elseif ($this->isUsernameInUse($username) || $this->isEmailInUse($email) || !$this->isUsernameCorrect($username)
            || !$this->isEmailCorrect($email) || !$this->isPasswordCorrect($pass)) {

        }  else {
            $passwordHash = password_hash($pass, PASSWORD_DEFAULT);

            try {
                $stmt = $this->con->prepare("INSERT INTO user (username, password, email, avatar) VALUES (?, ?, ?, ?)");
                $stmt->execute(array($username, $passwordHash, $email, $avatar));

                echo '{"notice": {"text": "Użytkownik został dodany."}}';
            } catch (PDOException $e) {
                echo '{"error": {"text": ' . $e->getMessage() . '}}';
            }
        }

    }

    public function  getUser($id)
    {
        $sql = "SELECT * FROM user WHERE id = $id";

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

    public function getUsers()
    {
        $sql = "SELECT * FROM user";

        try {
            $db = new db();
            $db = $db->connect();

            $stmt = $db->query($sql);
            $users = $stmt->fetchAll(PDO::FETCH_OBJ);
            $db = null;
            echo json_encode($users);

        } catch(PDOException $e){
            echo '{"error": {"text": '.$e->getMessage().'}}';
        }
    }

    private function isUsernameInUse($username)
    {
        $stmt = $this->con->prepare("SELECT id FROM user WHERE username=?");
        $stmt->execute(array($username));
        $stmt->fetch(PDO::FETCH_ASSOC);
        $num_rows = $stmt->rowCount();

        if ($num_rows > 0) {
            echo '{"error": {"text": "Użytkownik o podanym nicku już istnieje!"}}';
            return true;
        } else {
            return false;
        }
    }

    public function isEmailInUse($email)
    {
        $stmt = $this->con->prepare("SELECT id FROM user WHERE email=?");
        $stmt->execute(array($email));
        $stmt->fetch(PDO::FETCH_ASSOC);
        $num_rows = $stmt->rowCount();

        if ($num_rows > 0) {
            echo '{"error": {"text": "Użytkownik o podanym mailu już istnieje!"}}';
            return true;
        } else {
            return false;
        }
    }

    public function isEmailCorrect($email)
    {
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return true;
        } else {
            echo '{"error": {"text": "Niepoprawny format maila!"}}';
            return false;
        }
    }

    public function isUsernameCorrect($username)
    {
        $usernameLen = strlen($username);

        if (preg_match("/^[a-zA-Z0-9]+$/",$username) && ($usernameLen > 3 && $usernameLen < 30)) {
            return true;
        } else {
            echo '{"error": {"text": "Nieprawidłowa nazwa użytkownika! 
            Nazwa użytkownika musi zawierać od 3 do 30 znaków, składać się z liter i cyfr oraz nie może zawierać spacji!"}}';
            return false;
        }
    }

    public function isPasswordCorrect($password)
    {
        if (strlen($password) < 8) {
            echo '{"error": {"text": "Hasło musi zawierać minimum 8 znaków!"}}';
            return false;
        } else {
            return true;
        }
    }

    public function getUserByUsername($username)
    {
        $stmt = $this->con->prepare("SELECT * FROM user WHERE username=?");
        $stmt->execute(array($username));
        $user = $stmt->fetchAll(PDO::FETCH_OBJ);
        return $user;
    }

    public function getUserByEmail($email)
    {
        $stmt = $this->con->prepare("SELECT * FROM user WHERE email=?");
        $stmt->execute(array($email));
        $user = $stmt->fetchAll(PDO::FETCH_OBJ);
        return $user;
    }

    public function checkLogin($username, $password)
    {
        $stmt = $this->con->prepare("SELECT password FROM user WHERE username=?");
        $stmt->execute(array($username));
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $num_rows = $stmt->rowCount();
        $verify = password_verify($password, $user['password']);

        if ($num_rows > 0) {
            if ($verify) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function updateEmail($id, $email)
    {
        $sql = "UPDATE user SET
            email    = :email
            WHERE id = :id";

        try {
            $db = new db();
            $db = $db->connect();

            $stmt = $db->prepare($sql);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            echo '{"notice": {"text": "Email został zaktualizowany."}}';
        } catch(PDOException $e){
            echo '{"error": {"text": '.$e->getMessage().'}}';
        }
    }

    public function updatePassword($id, $password)
    {
        $sql = "UPDATE user SET
            password    = :password
            WHERE id = :id";

        try {
            $db = new db();
            $db = $db->connect();

            $stmt = $db->prepare($sql);
            $stmt->bindParam(':password', $password);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            echo '{"notice": {"text": "Hasło zostało zmienione."}}';
        } catch(PDOException $e){
            echo '{"error": {"text": '.$e->getMessage().'}}';
        }
    }

    public function activateUser($username)
    {
        $sql = "UPDATE user SET
            activate = 1
            WHERE username = :username";
        try {
            $db = new db();
            $db = $db->connect();
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':username', $username);
            $stmt->execute();
        } catch(PDOException $e){
            echo '{"error": {"text": '.$e->getMessage().'}}';
        }
    }

    public function deactivateUser($id)
    {
        $sql = "UPDATE user SET
        activate = 0
        WHERE id = :id";

        try {
            $db = new db();
            $db = $db->connect();
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            echo '{"notice": {"text": "Konto użytkownika zostało dezaktywowane."}}';
        } catch(PDOException $e){
            echo '{"error": {"text": '.$e->getMessage().'}}';
        }
    }

}