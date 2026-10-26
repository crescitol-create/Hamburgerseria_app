<?php
class Database {
    private $host = "localhost";
    private $db_name = "el_pasillito";
    private $username = "root";
    private $password = "";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
        } catch(PDOException $exception) {
            error_log("Error de conexión a la BD: " . $exception->getMessage());
            die("Error de conexión con la base de datos. Por favor, intente más tarde.");
        }
        return $this->conn;
    }
}
?>