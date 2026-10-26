<?php
class Pedido {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function crearPedido($telefono, $nombre_cliente, $tipo, $id_zona, $direccion, $items, $metodo_pago = 'efectivo') {
        try {
            $this->conn->beginTransaction();

            // 1. Verificación de stock disponible con bloqueo pesimista (FOR UPDATE)
            foreach ($items as $item) {
                $stmtRec = $this->conn->prepare("SELECT cmp.cantidad, cmp.id_mat, mp.nombre as nombre_insumo, mp.stock 
                                                 FROM comida_mat_prima cmp 
                                                 JOIN mat_prima mp ON cmp.id_mat = mp.id_mat 
                                                 WHERE cmp.id_comida = :com FOR UPDATE");
                $stmtRec->execute([':com' => $item['id_comida']]);
                $ingredientes = $stmtRec->fetchAll();

                foreach ($ingredientes as $ing) {
                    $cant_necesaria = $ing['cantidad'] * $item['cantidad'];
                    if ($ing['stock'] < $cant_necesaria) {
                        $this->conn->rollBack();
                        return [
                            'success' => false, 
                            'error' => "Stock insuficiente de '{$ing['nombre_insumo']}'. Requerido: {$cant_necesaria}, Disponible: {$ing['stock']}"
                        ];
                    }
                }
            }

            // 2. Registro o actualización del cliente
            $telefono_guardar = null;
            if (!empty(trim($telefono))) {
                $telefono_guardar = trim($telefono);
                $nombre_guardar = !empty(trim($nombre_cliente)) ? trim($nombre_cliente) : 'Cliente General';
                
                $stmtCl = $this->conn->prepare("INSERT INTO cliente (telefono, nombre) VALUES (:tel, :nom) ON DUPLICATE KEY UPDATE nombre = :nom");
                $stmtCl->execute([':tel' => $telefono_guardar, ':nom' => $nombre_guardar]);
            }

            // 3. Cálculo de costos
            $costo_envio = 0;
            if ($tipo === 'envio' && !empty($id_zona)) {
                $stmtZ = $this->conn->prepare("SELECT costo_envio FROM zona_envio WHERE id_zona = :id");
                $stmtZ->execute([':id' => $id_zona]);
                $resZ = $stmtZ->fetch();
                $costo_envio = $resZ['costo_envio'] ?? 0;
            }

            $costo_comida = 0;
            foreach ($items as $item) {
                $stmtC = $this->conn->prepare("SELECT precio FROM comida WHERE id_comida = :id");
                $stmtC->execute([':id' => $item['id_comida']]);
                $comida = $stmtC->fetch();
                if ($comida) {
                    $costo_comida += ($comida['precio'] * $item['cantidad']);
                } else {
                    $this->conn->rollBack();
                    return ['success' => false, 'error' => "El producto seleccionado con ID {$item['id_comida']} no existe."];
                }
            }

            $costo_total = $costo_comida + $costo_envio;

            // 4. Inserción de la cabecera del pedido
            $queryPed = "INSERT INTO pedido (telefono_cliente, tipo_pedido, direccion, id_zona, costo_total, metodo_pago, estado, hora_entrada) 
                         VALUES (:tel, :tipo, :dir, :zona, :total, :metodo, 'pendiente', NOW())";
            $stmtPed = $this->conn->prepare($queryPed);
            $stmtPed->execute([
                ':tel' => $telefono_guardar,
                ':tipo' => $tipo,
                ':dir' => !empty($direccion) ? $direccion : null,
                ':zona' => !empty($id_zona) ? $id_zona : null,
                ':total' => $costo_total,
                ':metodo' => $metodo_pago
            ]);
            $id_pedido = $this->conn->lastInsertId();

            // 5. Inserción de detalles y descuento final del stock
            foreach ($items as $item) {
                $stmtC = $this->conn->prepare("SELECT precio FROM comida WHERE id_comida = :id");
                $stmtC->execute([':id' => $item['id_comida']]);
                $comida = $stmtC->fetch();
                
                if ($comida) {
                    $subtotal = $comida['precio'] * $item['cantidad'];
                    $observacion = !empty($item['observacion']) ? trim($item['observacion']) : null;

                    $stmtDet = $this->conn->prepare("INSERT INTO detalle_pedido (id_pedido, id_comida, cantidad, subtotal, observacion) VALUES (:ped, :com, :cant, :sub, :obs)");
                    $stmtDet->execute([
                        ':ped' => $id_pedido,
                        ':com' => $item['id_comida'],
                        ':cant' => $item['cantidad'],
                        ':sub' => $subtotal,
                        ':obs' => $observacion
                    ]);

                    $stmtRec = $this->conn->prepare("SELECT id_mat, cantidad FROM comida_mat_prima WHERE id_comida = :com");
                    $stmtRec->execute([':com' => $item['id_comida']]);
                    $ingredientes = $stmtRec->fetchAll();

                    foreach ($ingredientes as $ing) {
                        $cant_usada = $ing['cantidad'] * $item['cantidad'];
                        $stmtDesc = $this->conn->prepare("UPDATE mat_prima SET stock = stock - :cant WHERE id_mat = :mat");
                        $stmtDesc->execute([':cant' => $cant_usada, ':mat' => $ing['id_mat']]);
                    }
                }
            }

            $this->conn->commit();
            return ['success' => true, 'id_pedido' => $id_pedido, 'total' => $costo_total];
        } catch (Exception $e) {
            $this->conn->rollBack();
            return ['success' => false, 'error' => "Error interno al procesar la transacción: " . $e->getMessage()];
        }
    }

    public function obtenerPedidosPendientes() {
        $sql = "SELECT p.*, COALESCE(c.nombre, 'Cliente Mostrador') as cliente_nombre 
                FROM pedido p 
                LEFT JOIN cliente c ON p.telefono_cliente = c.telefono 
                WHERE p.estado IN ('pendiente', 'en_preparacion') 
                ORDER BY p.hora_entrada ASC";
        $stmt = $this->conn->query($sql);
        $pedidos = $stmt->fetchAll();

        foreach ($pedidos as &$ped) {
            $sqlDet = "SELECT dp.*, com.nombre as comida_nombre 
                       FROM detalle_pedido dp 
                       JOIN comida com ON dp.id_comida = com.id_comida 
                       WHERE dp.id_pedido = :id";
            $stmtDet = $this->conn->prepare($sqlDet);
            $stmtDet->execute([':id' => $ped['id_pedido']]);
            $ped['detalles'] = $stmtDet->fetchAll();
        }
        return $pedidos;
    }

    public function cambiarEstado($id_pedido, $nuevo_estado) {
        $sql = "UPDATE pedido SET estado = :est, hora_salida = NOW() WHERE id_pedido = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([':est' => $nuevo_estado, ':id' => $id_pedido]);
    }

    public function obtenerVentasTotales() {
        $sql = "SELECT COALESCE(SUM(costo_total), 0) as total, COUNT(*) as cantidad_pedidos FROM pedido WHERE estado = 'listo'";
        return $this->conn->query($sql)->fetch();
    }

    public function obtenerVentasPorMetodo() {
        $sql = "SELECT metodo_pago, COALESCE(SUM(costo_total), 0) as total, COUNT(*) as cantidad 
                FROM pedido WHERE estado = 'listo' 
                GROUP BY metodo_pago";
        return $this->conn->query($sql)->fetchAll();
    }

    public function obtenerProductoMasVendido() {
        $sql = "SELECT com.nombre, SUM(dp.cantidad) as total_vendido 
                FROM detalle_pedido dp 
                JOIN comida com ON dp.id_comida = com.id_comida 
                JOIN pedido p ON dp.id_pedido = p.id_pedido 
                WHERE p.estado = 'listo' 
                GROUP BY dp.id_comida 
                ORDER BY total_vendido DESC LIMIT 1";
        return $this->conn->query($sql)->fetch();
    }

    public function obtenerCierresDiarios() {
        $sql = "SELECT DATE(hora_entrada) as fecha, 
                       COUNT(*) as total_pedidos, 
                       SUM(CASE WHEN metodo_pago = 'efectivo' THEN costo_total ELSE 0 END) as efectivo,
                       SUM(CASE WHEN metodo_pago = 'transferencia' THEN costo_total ELSE 0 END) as transferencia,
                       SUM(CASE WHEN metodo_pago = 'tarjeta' THEN costo_total ELSE 0 END) as tarjeta,
                       SUM(costo_total) as total_dia 
                FROM pedido 
                WHERE estado = 'listo' 
                GROUP BY DATE(hora_entrada) 
                ORDER BY fecha DESC";
        return $this->conn->query($sql)->fetchAll();
    }
}
?>