<?php
require_once 'config/database.php';
require_once 'models/Pedido.php';

class CocineroController {
    public function index() {
        $db = (new Database())->getConnection();
        $pedidoModel = new Pedido($db);

        // Procesar acción de marcar pedido como listo
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['marcar_listo'])) {
            $id = intval($_POST['id_pedido']);
            if ($id > 0) {
                $pedidoModel->cambiarEstado($id, 'listo');
            }
            header("Location: index.php?action=cocinero");
            exit();
        }

        $pedidos = $pedidoModel->obtenerPedidosPendientes();
        require_once 'views/cocinero/index.php';
    }
}
?>