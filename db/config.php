<?php

class db
{
    private $dbhost = 'localhost';
    private $dbuser = 'arrez_backend';
    private $dbpass = 'agrest_';
    private $dbname = 'arrez_agrest';

    public function  connect()
    {
        $mysql_connect_str = "mysql:host=$this->dbhost;dbname=$this->dbname";
        $dbConnection = new PDO($mysql_connect_str, $this->dbuser, $this->dbpass);
        $dbConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $dbConnection;
    }

}