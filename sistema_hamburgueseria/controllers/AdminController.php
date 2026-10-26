<?php
require_once 'config/database.php';
require_once 'models/Pedido.php';
require_once 'models/Stock.php';
require_once 'models/Usuario.php';

class AdminController {
    public function index() {
        $db = (new Database())->getConnection();
        $pedidoModel = new Pedido($db);
        $stockModel = new Stock($db);
        $usuarioModel = new Usuario($db);

        // Procesar edición de usuario (nombre y/o contraseña)
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_usuario'])) {
            $id_usuario = $_POST['id_usuario'];
            $nombre_usuario = $_POST['usuario'];
            $password = $_POST['password'] ?? null;
            
            $usuarioModel->actualizarUsuario($id_usuario, $nombre_usuario, $password);
            header("Location: index.php?action=admin");
            exit();
        }

        // Datos para las estadísticas
        $balance = $pedidoModel->obtenerVentasTotales();
        $ventas_metodo = $pedidoModel->obtenerVentasPorMetodo();
        $mas_vendido = $pedidoModel->obtenerProductoMasVendido();
        $cierres_diarios = $pedidoModel->obtenerCierresDiarios();
        
        // Datos para usuarios e insumos críticos
        $usuarios = $usuarioModel->obtenerUsuarios();
        $materias = $stockModel->obtenerMateriasPrimas();
        $insumos_criticos = array_filter($materias, function($m) { return $m['stock'] <= 5; });

        require_once 'views/admin/index.php';
    }

    public function stock() {
        $db = (new Database())->getConnection();
        $stockModel = new Stock($db);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['crear_materia'])) {
                $stockModel->agregarMateriaPrima($_POST['nombre'], $_POST['unidad'], $_POST['stock'], $_POST['fecha_vencimiento']);
            } elseif (isset($_POST['editar_materia'])) {
                $stockModel->actualizarMateriaPrima($_POST['id_mat'], $_POST['nombre'], $_POST['unidad'], $_POST['stock'], $_POST['fecha_vencimiento']);
            } elseif (isset($_POST['eliminar_materia'])) {
                $stockModel->eliminarMateriaPrima($_POST['id_mat']);
            } elseif (isset($_POST['crear_combo'])) {
                $stockModel->crearCombo($_POST['nombre'], $_POST['precio'], $_POST['descripcion'], $_POST['ingredientes'] ?? []);
            } elseif (isset($_POST['editar_combo'])) {
                $stockModel->actualizarCombo($_POST['id_comida'], $_POST['nombre'], $_POST['precio'], $_POST['descripcion'], $_POST['ingredientes'] ?? []);
            } elseif (isset($_POST['eliminar_combo'])) {
                $stockModel->eliminarCombo($_POST['id_comida']);
            }

            header("Location: index.php?action=admin_stock");
            exit();
        }

        $materias = $stockModel->obtenerMateriasPrimas();
        $combos = $stockModel->obtenerCombos();
        require_once 'views/admin/stock.php';
    }
}
?>