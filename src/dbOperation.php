<?php

class dbOperation
{
    private $con = null;

    function __construct() {
        require_once  '../src/db.php';

        try {
            $db = new db();
            $this->con = $db->connect();
        } catch (PDOException $e) {
            echo 'Połączenie nie mogło zostać utworzone:<br> ' . $e->getMessage();
        }
    } //__construct

    public function createUser($username, $pass, $email, $avatar)
    {
        if ($this->isUsernameInUse($username)) {
            echo '{"error": {"text": "Użytkownik o podanym nicku już istnieje!"}}';
        } elseif ($this->isEmailInUse($email)) {
            echo '{"error": {"text": "Użytkownik o podanym mailu już istnieje!"}}';
        } elseif (!$this->isEmailCorrect($email)) {
            echo '{"error": {"text": "Niepoprawny format maila!"}}';
        } elseif (!$this->isUsernameCorrect($username)) {
            echo '{"error": {"text": "Nieprawidłowy login! Login musi się składać z liter i cyfr oraz nie może zawierać spacji!"}}';
        } else {
            $password = md5($pass);

            try {
                $stmt = $this->con->prepare("INSERT INTO user (username, password, email, avatar) VALUES (?, ?, ?, ?)");
                $stmt->execute(array($username, $password, $email, $avatar));

                echo '{"notice": {"text": "User Added"}}';

            } catch (PDOException $e) {
                //echo '{"error": {"text": '.$this->isUsernameInUse($username).'}}';
                echo '{"error": {"text": ' . $e->getMessage() . '}}';
            }
        }
    }



    private function isUsernameInUse($username)
    {
        $stmt = $this->con->prepare("SELECT id FROM user WHERE username=?");
        $stmt->execute(array($username));
        $stmt->fetch(PDO::FETCH_ASSOC);
        $num_rows = $stmt->rowCount();

        if ($num_rows > 0) {
            return true;
        } else {
            return false;
        }
    }

    private function isEmailInUse($email)
    {
        $stmt = $this->con->prepare("SELECT id FROM user WHERE email=?");
        $stmt->execute(array($email));
        $stmt->fetch(PDO::FETCH_ASSOC);
        $num_rows = $stmt->rowCount();

        if ($num_rows > 0) {
            return true;
        } else {
            return false;
        }
    }

    private function isEmailCorrect($email)
    {
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return true;
        } else {
            return false;
        }
    }

    private function isUsernameCorrect($username)
    {
        if (preg_match("/^[a-zA-Z0-9]+$/",$username)) {
            return true;
        } else {
            return false;
        }
    }



    public function checkLogin($username, $password, $email)
    {
        $passwordHash = md5($password);
        $stmt = $this->con->prepare("SELECT password FROM user WHERE (username=? OR email=?)");
        $stmt->execute(array($username,$email));
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $num_rows = $stmt->rowCount();

        if ($num_rows > 0) {
            if ($passwordHash === $user['password']) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
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
}