<?php
class Usuario {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Valida y obtiene la columna correspondiente a través de una lista blanca segura
    private function obtenerNombreColumnaUsuario() {
        try {
            $this->conn->query("SELECT nombre_usuario FROM usuarios LIMIT 1");
            return 'nombre_usuario';
        } catch (PDOException $e) {
            try {
                $this->conn->query("SELECT usuario FROM usuarios LIMIT 1");
                return 'usuario';
            } catch (PDOException $e2) {
                return 'nombre';
            }
        }
    }

    // Método de Login adaptativo y blindado
    public function login($usuario, $password) {
        $col = $this->obtenerNombreColumnaUsuario();
        
        $sql = "SELECT * FROM usuarios WHERE {$col} = :user";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':user' => $usuario]);
        $user = $stmt->fetch();

        if ($user && !empty($user['password'])) {
            // Verificación estricta mediante password_verify
            if (password_verify($password, $user['password'])) {
                $user['usuario'] = $user[$col];
                return $user;
            }
        }
        return false;
    }

    // Obtener lista de usuarios para el Admin
    public function obtenerUsuarios() {
        $col = $this->obtenerNombreColumnaUsuario();
        $sql = "SELECT *, {$col} AS usuario FROM usuarios ORDER BY rol ASC";
        return $this->conn->query($sql)->fetchAll();
    }

    // Actualizar nombre de usuario y/o contraseña
    public function actualizarUsuario($id_usuario, $nombre_usuario, $password = null) {
        $col = $this->obtenerNombreColumnaUsuario();

        if (!empty($password)) {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $sql = "UPDATE usuarios SET {$col} = :user, password = :pass WHERE id_usuario = :id";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([
                ':user' => $nombre_usuario,
                ':pass' => $password_hash,
                ':id'   => $id_usuario
            ]);
        } else {
            $sql = "UPDATE usuarios SET {$col} = :user WHERE id_usuario = :id";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([
                ':user' => $nombre_usuario,
                ':id'   => $id_usuario
            ]);
        }
    }
}
?>