<?php

class userOperations
{
    private $con = null;

    //Funkcja służąca do łączenia się z bazą danych
    function __construct() {
        require_once '../db/db.php';

        try {
            $db = new db();
            $this->con = $db->connect();
        } catch (PDOException $e) {
            echo 'Połączenie nie mogło zostać utworzone:<br> ' . $e->getMessage();
        }
    } //__construct

    public function createUser($username, $pass, $email)
    {
        if ($username === null || $email === null || $pass === null) {
            echo '{"error": {"text": "Pole nazwa użytkownika, hasło i email nie mogą być puste!"}}';
            header("Status: 400 Bad request");
        } elseif ($this->isUsernameInUse($username) || $this->isEmailInUse($email) || !$this->isUsernameCorrect($username)
            || !$this->isEmailCorrect($email) || !$this->isPasswordCorrect($pass)) {

        }  else {
            $passwordHash = password_hash($pass, PASSWORD_DEFAULT);

            try {
                $stmt = $this->con->prepare("INSERT INTO user (username, password, email) VALUES (?, ?, ?)");
                $stmt->execute(array($username, $passwordHash, $email));

                $this->createActivationToken($email);
                $this->sendEmail($email);

                echo '{"notice": {"text": "E-mail weryfikacyjny został wysłany."}}';
            } catch (PDOException $e) {
                echo '{"error": {"text": ' . $e->getMessage() . '}}';
            }
        }

    }

    public function  getUser($id)
    {
        $stmt = $this->con->prepare("SELECT * FROM user WHERE id = ?");

        try {

            $stmt->execute(array($id));
            $user = $stmt->fetchAll(PDO::FETCH_OBJ);
            echo json_encode($user, JSON_UNESCAPED_UNICODE);

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
            header("Status: 400 Bad request");
            return true;
        } else {
            return false;
        }
    }

    public function isEmailInUse($email)
    {
        $stmt = $this->con->prepare("SELECT id FROM user WHERE email=?");

        try {
            $stmt->execute(array($email));
            $stmt->fetch(PDO::FETCH_ASSOC);
            $num_rows = $stmt->rowCount();

            if ($num_rows > 0) {
                echo '{"error": {"text": "Użytkownik o podanym mailu już istnieje!"}}';
                header("Status: 400 Bad request");
                return true;
            } else {
                return false;
            }
        } catch(PDOException $e){
            echo '{"error": {"text": '.$e->getMessage().'}}';
        }
        return true;
    }

    public function isEmailCorrect($email)
    {
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return true;
        } else {
            echo '{"error": {"text": "Niepoprawny format maila!"}}';
            header("Status: 400 Bad request");
            return false;
        }
    }

    public function isUsernameCorrect($username)
    {
        $usernameLen = strlen($username);

        if (preg_match("/^[a-zA-Z0-9]+$/",$username) && ($usernameLen >= 3 && $usernameLen <= 30)) {
            return true;
        } else {
            echo '{"error": {"text": "Nieprawidłowa nazwa użytkownika! Nazwa użytkownika musi zawierać od 3 do 30 znaków, składać się z liter i cyfr oraz nie może zawierać spacji!"}}';
            header("Status: 400 Bad request");
            return false;
        }
    }

    public function isPasswordCorrect($password)
    {
        if (strlen($password) < 8) {
            echo '{"error": {"text": "Hasło musi zawierać minimum 8 znaków!"}}';
            header("Status: 400 Bad request");
            return false;
        } else {
            return true;
        }
    }

    public function passwordExists($id, $password)
    {
        $stmt = $this->con->prepare("SELECT * FROM user WHERE id=?");
        $stmt->execute(array($id));
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $num_rows = $stmt->rowCount();
        $verify = password_verify($password, $user['password']);

        if ($num_rows > 0) {
            if ($verify) {
                return true;
            } else {
                echo '{"notice": {"text": "Podane stare hasło jest błędne."}}';
                return false;
            }
        } else {
            echo '{"notice": {"text": "Podane stare hasło jest błędne."}}';
            return false;
        }
    }

    public function getUserByUsername($username)
    {
        $stmt = $this->con->prepare("SELECT * FROM user WHERE username=?");
        $stmt->execute(array($username));
        $user = $stmt->fetchAll(PDO::FETCH_OBJ);
        if ($user === array()){
            return false;
        } else {
            return $user;
        }
    }

    public function getUserByEmail($email)
    {
        $stmt = $this->con->prepare("SELECT * FROM user WHERE email=?");
        $stmt->execute(array($email));
        $user = $stmt->fetchAll(PDO::FETCH_OBJ);
        return $user;
    }

    //Funkcja sprawdzająca czy konto użytkownika zostało zweryfikowane poprzez link aktywacyjny.
    public function checkLogin($username, $password)
    {
        $stmt = $this->con->prepare("SELECT * FROM user WHERE username=?");
        $stmt->execute(array($username));
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $num_rows = $stmt->rowCount();
        $verify = password_verify($password, $user['password']);

        if ($user['emailActivate'] == 1) {
            if ($num_rows > 0) {
                if ($verify) {
                    return true;
                } else {
                    return false;
                }
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

    public function createActivationToken($email)
    {
        $activationToken = hash("sha256", $email);

        $sql = "UPDATE user SET
            activationToken = :activationToken
            WHERE email = :email";

        try {
            $db = new db();
            $db = $db->connect();

            $stmt = $db->prepare($sql);
            $stmt->bindParam(':activationToken', $activationToken);
            $stmt->bindParam(':email', $email);
            $stmt->execute();

        } catch(PDOException $e){
            echo '{"error": {"text": '.$e->getMessage().'}}';
        }
    }

    //Po wysłaniu linku aktywacyjnego, funkcja ta automatycznie zmienia wartość kolumny 'emailActivate' w bazie danych na 0,
    //co jest równoważne z brakiem dostępu użytkownika do aplikacji.
    public function changeEmailActivateF($id)
    {
        $sql = "UPDATE user SET
            emailActivate = 0
            WHERE id = :id";

        try {
            $db = new db();
            $db = $db->connect();

            $stmt = $db->prepare($sql);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

        } catch(PDOException $e){
            echo '{"error": {"text": '.$e->getMessage().'}}';
        }
    }

    //Po wejściu w link aktywacyjny, funkcja ta automatycznie zmienia wartość kolumny w bazie danych na 1, co umożliwia użytkownikowi zalogowanie się.
    public function changeEmailActivateT($activationToken)
    {
        $sql = "UPDATE user SET
            emailActivate = 1
            WHERE activationToken = :activationToken";

        try {
            $db = new db();
            $db = $db->connect();

            $stmt = $db->prepare($sql);
            $stmt->bindParam(':activationToken', $activationToken);
            $stmt->execute();

        } catch(PDOException $e){
            echo '{"error": {"text": '.$e->getMessage().'}}';
        }
    }


    public function activationTokenCorrect($activationToken)
    {
        $stmt = $this->con->prepare("SELECT username FROM user WHERE activationToken=?");
        $stmt->execute(array($activationToken));
        $num_rows = $stmt->rowCount();

        if ($num_rows > 0) {
            echo 'Weryfikacja e-maila przebiegła pomyślnie! Możesz się zalogować.';
            return true;
        } else {
            echo '{"notice": {"text": "Błąd weryfikacji e-maila."}}';
            return false;
        }
    }


    public function sendEmail($email)
    {
        $stmt = $this->con->prepare("SELECT * FROM user WHERE email = ?");

        try {
            $stmt->execute(array($email));
            $user = $stmt->fetchAll(PDO::FETCH_OBJ);
            $username = array_column($user, 'username');
            $activationToken = array_column($user, 'activationToken');

            $to = $email;
            $subject = 'AgRest - account';
            $message = 'Account verification!
        
        Hello '.$username[0].'. Please click this link to verify your account:
         
        http://arrez.vot.pl/public/index.php/verify?activationToken='.$activationToken[0].'  

        If you have received this email by mistake ignore it.';
            $headers = 'From: http://arrez.vot.pl';
            mail($to,$subject,$message,$headers);

        } catch(PDOException $e){
            echo '{"error": {"text": '.$e->getMessage().'}}';
        }

    }

    public function sendEmailWithPassword($email)
    {
        $stmt = $this->con->prepare("SELECT * FROM user WHERE email = ?");

        try {
            $stmt->execute(array($email));
            $user = $stmt->fetchAll(PDO::FETCH_OBJ);
            $username = array_column($user, 'username');
            $id_user = array_column($user, 'id');
            $newPassword = $this->generatePassword();
            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $this->updatePassword($id_user[0], $passwordHash);

            $to = $email;
            $subject = 'AgRest - new password.';
            $message = '        
        Hello '.$username[0].'. Here is your new password: '.$newPassword.'  

        If you have received this email by mistake ignore it.';
            $headers = 'From: http://arrez.vot.pl';
            mail($to,$subject,$message,$headers);
        } catch(PDOException $e){
            echo '{"error": {"text": '.$e->getMessage().'}}';
        }
    }

    public function generatePassword()
    {
        $rand = substr(md5(microtime()),rand(0,26),8);
        return $rand;
    }

    public function emailExists($email)
    {
        $stmt = $this->con->prepare("SELECT username FROM user WHERE email =?");
        try {
            $stmt->execute(array($email));
            $num_rows = $stmt->rowCount();

            if ($num_rows > 0) {
                return false;
            } else {
                echo '{"error": {"text": "Użytkownik o podanym mailu nie istnieje!"}}';
                header("Status: 400 Bad request");
                return true;
            }
        } catch(PDOException $e){
            echo '{"error": {"text": '.$e->getMessage().'}}';
        }
    }

    //Funkcja umożliwiająca pobieranie wartości ‘protected’ z obiektu.
    public function getProtectedValue($obj, $name)
    {
        $array = (array)$obj;
        $prefix = chr(0).'*'.chr(0);
        return $array[$prefix.$name];
    }

}