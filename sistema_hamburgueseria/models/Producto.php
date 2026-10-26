<?php
class Producto {
    private $conn;
    private $table = "comida";

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Obtiene todos los productos/combos disponibles en el sistema.
     */
    public function obtenerTodos() {
        $query = "SELECT * FROM " . $this->table . " ORDER BY nombre ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Obtiene un producto/combo específico por su ID junto con su receta/ingredientes.
     */
    public function obtenerPorId($id_comida) {
        $query = "SELECT * FROM " . $this->table . " WHERE id_comida = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id_comida);
        $stmt->execute();
        $producto = $stmt->fetch();

        if ($producto) {
            // Traer los ingredientes asociados al combo desde la relación comida_mat_prima
            $queryIng = "SELECT cmp.id_mat, mp.nombre, mp.unidad, cmp.cantidad 
                         FROM comida_mat_prima cmp
                         JOIN mat_prima mp ON cmp.id_mat = mp.id_mat
                         WHERE cmp.id_comida = :id";
            $stmtIng = $this->conn->prepare($queryIng);
            $stmtIng->bindParam(':id', $id_comida);
            $stmtIng->execute();
            $producto['ingredientes'] = $stmtIng->fetchAll();
        }

        return $producto;
    }

    /**
     * Crea un nuevo producto/combo y registra su receta vinculada a las materias primas.
     */
    public function crear($nombre, $precio, $descripcion, $ingredientes = []) {
        try {
            $this->conn->beginTransaction();

            $query = "INSERT INTO " . $this->table . " (nombre, precio, descripcion) VALUES (:nombre, :precio, :descripcion)";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ':nombre' => $nombre,
                ':precio' => $precio,
                ':descripcion' => $descripcion
            ]);

            $id_comida = $this->conn->lastInsertId();

            // Insertar ingredientes de la receta
            if (!empty($ingredientes)) {
                $queryIng = "INSERT INTO comida_mat_prima (id_comida, id_mat, cantidad) VALUES (:id_comida, :id_mat, :cantidad)";
                $stmtIng = $this->conn->prepare($queryIng);

                foreach ($ingredientes as $id_mat => $cantidad) {
                    if ($cantidad > 0) {
                        $stmtIng->execute([
                            ':id_comida' => $id_comida,
                            ':id_mat' => $id_mat,
                            ':cantidad' => $cantidad
                        ]);
                    }
                }
            }

            $this->conn->commit();
            return $id_comida;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    /**
     * Actualiza la información básica y precio de un combo/producto.
     */
    public function actualizar($id_comida, $nombre, $precio, $descripcion) {
        $query = "UPDATE " . $this->table . " SET nombre = :nombre, precio = :precio, descripcion = :descripcion WHERE id_comida = :id";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            ':nombre' => $nombre,
            ':precio' => $precio,
            ':descripcion' => $descripcion,
            ':id' => $id_comida
        ]);
    }

    /**
     * Elimina un combo/producto. Por las restricciones clave foránea en SQL,
     * se eliminarán sus relaciones en comida_mat_prima automáticamente.
     */
    public function eliminar($id_comida) {
        $query = "DELETE FROM " . $this->table . " WHERE id_comida = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id_comida);
        return $stmt->execute();
    }
}
?>