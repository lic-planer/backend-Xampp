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

        if ($username === null || $email === null || $pass === null) {
            echo '{"error": {"text": "Pole nazwa użytkownika, hasło i email nie mogą być puste!"}}';
        } elseif ($this->isUsernameInUse($username) || $this->isEmailInUse($email) || !$this->isUsernameCorrect($username)
            || !$this->isEmailCorrect($email) || !$this->isPasswordCorrect($pass)) {

        }  else {
            $passwordHash = password_hash($pass, PASSWORD_DEFAULT);

            try {
                $stmt = $this->con->prepare("INSERT INTO user (username, password, email, avatar) VALUES (?, ?, ?, ?)");
                $stmt->execute(array($username, $passwordHash, $email, $avatar));

                $this->createActivationToken($email);
                $this->sendEmail($email);

                echo '{"notice": {"text": "Użytkownik został dodany."}}';
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

        if (preg_match("/^[a-zA-Z0-9]+$/",$username) && ($usernameLen >= 3 && $usernameLen <= 30)) {
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

            echo '{"notice": {"text": "Token aktywacyjny został wygenerowany."}}';
        } catch(PDOException $e){
            echo '{"error": {"text": '.$e->getMessage().'}}';
        }
    }

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

            echo '{"notice": {"text": "Email jest nieaktywny."}}';
        } catch(PDOException $e){
            echo '{"error": {"text": '.$e->getMessage().'}}';
        }
    }

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

            echo '{"notice": {"text": "Email jest aktywny."}}';
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
            echo '{"notice": {"text": "Weryfikacja e-maila przebiegła pomyślnie."}}';
            return true;
        } else {
            echo '{"notice": {"text": "Błąd weryfikacji e-maila."}}';
            return false;
        }
    }


    public function sendEmail($email)
    {
        $stmt = $this->con->prepare("SELECT * FROM user WHERE email = ?");
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
    }


    public function getProtectedValue($obj, $name) {
        $array = (array)$obj;
        $prefix = chr(0).'*'.chr(0);
        return $array[$prefix.$name];
    }


    public function checkTheImageType($avatarType)
    {
        if ($avatarType === "image/jpeg" || $avatarType === "image/gif" || $avatarType === "image/png") {
            echo '{"notice": {"text": "Poprawny format obrazu."}}';
            return true;
        }
        echo '{"error": {"text": "Niepoprawny format obrazu!"}}';
        return false;
    }


    public function saveAvatarToFolder($id, $file, $avatarName)
    {
        $avatarsDir = '../avatars/';
        $name = $id.'-'.$avatarName;
        $uploaded = move_uploaded_file($file, $avatarsDir.$name);

        if ($uploaded === true) {
            echo '{"notice": {"text": "Zapisano w folderze."}}';
        } else {
            echo '{"error": {"text": "Wystąpił błąd podczas zapisu pliku."}}';
        }
    }


    public function addAvatarToDatabase($id, $avatarName)
    {
        $avatarName = $id.'-'.$avatarName;
        $avatar = base64_encode($avatarName);

        $sql = "UPDATE user SET
            avatar = :avatar
            WHERE id = :id";

        try {
            $db = new db();
            $db = $db->connect();

            $stmt = $db->prepare($sql);
            $stmt->bindParam(':avatar', $avatar);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            echo '{"notice": {"text": "Obrazek został dodany do bazy."}}';
        } catch(PDOException $e){
            echo '{"error": {"text": '.$e->getMessage().'}}';
        }
    }


    public function avatarExists($id)
    {
        $stmt = $this->con->prepare("SELECT * FROM user WHERE id=?");
        $stmt->execute(array($id));
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $avatar = $user['avatar'];

        if ($avatar !== null) {
            return true;
        } else {
            return false;
        }
    }


    public function deleteAvatar($id)
    {
        $sql = "UPDATE user SET
            avatar = null
            WHERE id = :id";

        try {
            $db = new db();
            $db = $db->connect();

            $stmt = $db->prepare($sql);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            echo '{"notice": {"text": "Usunięto avatar z bazy."}}';
        } catch(PDOException $e){
            echo '{"error": {"text": '.$e->getMessage().'}}';
        }
    }


    public function deleteAvatarFromFolder($id)
    {
        $stmt = $this->con->prepare("SELECT * FROM user WHERE id=?");
        $stmt->execute(array($id));
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $avatar = $user['avatar'];
        $avatar = base64_decode($avatar);

        $avatarsDir = '../avatars/';
        $delete = unlink($avatarsDir.$avatar);
        if ($delete === true) {
            echo '{"notice": {"text": "Usunięto z folderu."}}';
        } else {
            echo '{"error": {"text": "Wystąpił błąd podczas usuwania pliku."}}';
        }
    }


}