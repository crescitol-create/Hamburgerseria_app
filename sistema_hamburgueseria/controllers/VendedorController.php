<?php
require_once 'config/database.php';
require_once 'models/Pedido.php';
require_once 'models/Stock.php';

class VendedorController {
    public function index() {
        $db = (new Database())->getConnection();
        $stockModel = new Stock($db);
        $combos = $stockModel->obtenerCombosConStockDisponible();
        $zonas = $stockModel->obtenerZonas();

        $mensaje = "";
        $error = "";

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $pedidoModel = new Pedido($db);
            $telefono = $_POST['telefono'] ?? '';
            $cliente = $_POST['cliente'] ?? '';
            $tipo = $_POST['tipo_pedido'] ?? 'local';
            $zona = $_POST['id_zona'] ?? null;
            $direccion = $_POST['direccion'] ?? '';
            $metodo_pago = $_POST['metodo_pago'] ?? 'efectivo';
            
            $detalles_sel = $_POST['detalles'] ?? [];

            $items = [];
            foreach ($detalles_sel as $id_comida => $data) {
                $cant = intval($data['cantidad'] ?? 0);
                $observacion = !empty($data['observacion']) ? trim($data['observacion']) : null;

                if ($cant > 0) {
                    $items[] = [
                        'id_comida'   => $id_comida, 
                        'cantidad'    => $cant,
                        'observacion' => $observacion
                    ];
                }
            }

            if (!empty($items)) {
                $resultado = $pedidoModel->crearPedido($telefono, $cliente, $tipo, $zona, $direccion, $items, $metodo_pago);
                if ($resultado['success']) {
                    $mensaje = "¡Pedido #" . $resultado['id_pedido'] . " cobrado con éxito! Total: $" . number_format($resultado['total'], 2) . " (" . strtoupper($metodo_pago) . ")";
                    // Recargar combos para actualizar el stock en pantalla tras la venta
                    $combos = $stockModel->obtenerCombosConStockDisponible();
                } else {
                    $error = "❌ NO SE PUDO REALIZAR EL PEDIDO: " . $resultado['error'];
                }
            } else {
                $error = "Debe seleccionar al menos un producto.";
            }
        }

        require_once 'views/vendedor/index.php';
    }
}
?>