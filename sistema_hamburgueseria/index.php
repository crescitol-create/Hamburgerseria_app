<?php
session_start();
require_once 'config/database.php';

$action = $_GET['action'] ?? 'login';

// 1. Procesar autenticación si se envió el formulario de Login
if ($action === 'autenticar') {
    require_once 'controllers/AuthController.php';
    (new AuthController())->login();
    exit();
}

// 2. Si no hay sesión iniciada, mostrar el formulario de Login
if (!isset($_SESSION['user'])) {
    require_once 'views/auth/login.php';
    exit();
}

// 3. Redirección si entra a la raíz con sesión activa
if ($action === 'login') {
    header("Location: index.php?action=" . $_SESSION['user']['rol']);
    exit();
}

// 4. Control de acceso según el rol cargado en $_SESSION['user']
switch ($action) {
    case 'vendedor':
        if ($_SESSION['user']['rol'] !== 'vendedor' && $_SESSION['user']['rol'] !== 'admin') {
            header("Location: index.php");
            exit();
        }
        require_once 'controllers/VendedorController.php';
        (new VendedorController())->index();
        break;

    case 'cocinero':
        if ($_SESSION['user']['rol'] !== 'cocinero' && $_SESSION['user']['rol'] !== 'admin') {
            header("Location: index.php");
            exit();
        }
        require_once 'controllers/CocineroController.php';
        (new CocineroController())->index();
        break;

    case 'admin':
        if ($_SESSION['user']['rol'] !== 'admin') {
            header("Location: index.php");
            exit();
        }
        require_once 'controllers/AdminController.php';
        (new AdminController())->index();
        break;

    case 'admin_stock':
        if ($_SESSION['user']['rol'] !== 'admin') {
            header("Location: index.php");
            exit();
        }
        require_once 'controllers/AdminController.php';
        (new AdminController())->stock();
        break;

    case 'logout':
        session_destroy();
        header("Location: index.php");
        exit();

    default:
        header("Location: index.php?action=" . $_SESSION['user']['rol']);
        exit();
}
?>