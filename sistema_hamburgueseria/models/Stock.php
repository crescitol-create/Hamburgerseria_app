<?php
class Stock {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function obtenerMateriasPrimas() {
        return $this->conn->query("SELECT * FROM mat_prima ORDER BY nombre ASC")->fetchAll();
    }

    public function agregarMateriaPrima($nombre, $unidad, $stock_inicial, $fecha_vencimiento) {
        $stock_seguro = max(0, floatval($stock_inicial)); // Garantiza que no sea negativo
        $sql = "INSERT INTO mat_prima (nombre, unidad, stock, fecha_vencimiento) VALUES (:nom, :uni, :stk, :fec)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':nom' => $nombre,
            ':uni' => $unidad,
            ':stk' => $stock_seguro,
            ':fec' => $fecha_vencimiento ?: null
        ]);
    }

    public function actualizarMateriaPrima($id_mat, $nombre, $unidad, $stock, $fecha_vencimiento) {
        $stock_seguro = max(0, floatval($stock)); // Garantiza que no sea negativo
        $sql = "UPDATE mat_prima SET nombre = :nom, unidad = :uni, stock = :stk, fecha_vencimiento = :fec WHERE id_mat = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':nom' => $nombre,
            ':uni' => $unidad,
            ':stk' => $stock_seguro,
            ':fec' => $fecha_vencimiento ?: null,
            ':id'  => $id_mat
        ]);
    }

    public function eliminarMateriaPrima($id_mat) {
        $sql = "DELETE FROM mat_prima WHERE id_mat = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([':id' => $id_mat]);
    }

    public function crearCombo($nombre, $precio, $descripcion, $ingredientes) {
        try {
            $this->conn->beginTransaction();

            $stmtC = $this->conn->prepare("INSERT INTO comida (nombre, precio, descripcion) VALUES (:nom, :pre, :des)");
            $stmtC->execute([':nom' => $nombre, ':pre' => $precio, ':des' => $descripcion]);
            $id_comida = $this->conn->lastInsertId();

            $stmtI = $this->conn->prepare("INSERT INTO comida_mat_prima (id_comida, id_mat, cantidad) VALUES (:com, :mat, :cant)");
            foreach ($ingredientes as $mat_id => $cant) {
                if ($cant > 0) {
                    $stmtI->execute([':com' => $id_comida, ':mat' => $mat_id, ':cant' => max(0, $cant)]);
                }
            }

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    public function obtenerCombos() {
        $combos = $this->conn->query("SELECT * FROM comida ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);

        foreach ($combos as &$combo) {
            // 1. Mantiene el array de ingredientes para edición/administración
            $stmt = $this->conn->prepare("SELECT id_mat, cantidad FROM comida_mat_prima WHERE id_comida = :id");
            $stmt->execute([':id' => $combo['id_comida']]);
            $combo['ingredientes'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

            // 2. Cálculo dinámico de unidades posibles según materia prima en stock
            $sqlMat = "SELECT cmp.cantidad AS cant_requerida, mp.stock AS stock_actual 
                       FROM comida_mat_prima cmp 
                       JOIN mat_prima mp ON cmp.id_mat = mp.id_mat 
                       WHERE cmp.id_comida = :id_comida";
            
            $stmtMat = $this->conn->prepare($sqlMat);
            $stmtMat->execute([':id_comida' => $combo['id_comida']]);
            $ingredientes_stock = $stmtMat->fetchAll(PDO::FETCH_ASSOC);

            if (empty($ingredientes_stock)) {
                $combo['stock_disponible'] = 999;
            } else {
                $maximos_posibles = [];
                foreach ($ingredientes_stock as $ing) {
                    if ($ing['cant_requerida'] > 0) {
                        $unidades = floor($ing['stock_actual'] / $ing['cant_requerida']);
                        $maximos_posibles[] = max(0, intval($unidades));
                    }
                }
                $combo['stock_disponible'] = !empty($maximos_posibles) ? min($maximos_posibles) : 0;
            }
        }

        return $combos;
    }

    public function obtenerCombosConStockDisponible() {
        return $this->obtenerCombos();
    }

    public function actualizarCombo($id_comida, $nombre, $precio, $descripcion, $ingredientes) {
        try {
            $this->conn->beginTransaction();

            $stmt = $this->conn->prepare("UPDATE comida SET nombre = :nom, precio = :pre, descripcion = :des WHERE id_comida = :id");
            $stmt->execute([':nom' => $nombre, ':pre' => $precio, ':des' => $descripcion, ':id' => $id_comida]);

            $stmtDel = $this->conn->prepare("DELETE FROM comida_mat_prima WHERE id_comida = :id");
            $stmtDel->execute([':id' => $id_comida]);

            $stmtI = $this->conn->prepare("INSERT INTO comida_mat_prima (id_comida, id_mat, cantidad) VALUES (:com, :mat, :cant)");
            foreach ($ingredientes as $mat_id => $cant) {
                if ($cant > 0) {
                    $stmtI->execute([':com' => $id_comida, ':mat' => $mat_id, ':cant' => max(0, $cant)]);
                }
            }

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    public function eliminarCombo($id_comida) {
        $sql = "DELETE FROM comida WHERE id_comida = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([':id' => $id_comida]);
    }

    public function obtenerZonas() {
        return $this->conn->query("SELECT * FROM zona_envio ORDER BY costo_envio ASC")->fetchAll();
    }
}
?>