<?php
require_once 'config/database.php';
require_once 'models/Usuario.php';

class AuthController {
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $db = (new Database())->getConnection();
            $userModel = new Usuario($db);

            $nombre = trim($_POST['nombre'] ?? '');
            $password = trim($_POST['password'] ?? '');

            $usuario = $userModel->login($nombre, $password);

            if ($usuario) {
                // Previene fijación de sesión regenerando el ID
                session_regenerate_id(true);
                $_SESSION['user'] = $usuario;
                header("Location: index.php?action=" . $usuario['rol']);
                exit();
            } else {
                $error = "Usuario o contraseña incorrectos.";
                require_once 'views/auth/login.php';
            }
        } else {
            require_once 'views/auth/login.php';
        }
    }
}
?>