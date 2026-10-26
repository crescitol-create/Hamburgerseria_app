<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Stock y Combos - EL PASILLITO</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .modal {
            display: none; position: fixed; z-index: 100; left: 0; top: 0; width: 100%; height: 100%;
            background-color: rgba(0,0,0,0.8); justify-content: center; align-items: center;
        }
        .modal-content {
            background-color: var(--bg-card); padding: 20px; border-radius: 8px; border: 1px solid var(--accent-gold);
            width: 90%; max-width: 500px; max-height: 90vh; overflow-y: auto;
        }
        .btn-danger { background-color: #dc3545; color: white; }
        .btn-warning { background-color: #ffc107; color: black; }
        .btn-sm { padding: 4px 8px; font-size: 0.85em; }
        .alert-stock {
            background-color: rgba(220, 53, 69, 0.2);
            border-left: 5px solid #dc3545;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .low-stock { background-color: rgba(220, 53, 69, 0.3) !important; color: #ff8888; font-weight: bold; }
    </style>
</head>
<body>
    <header>
        <div class="logo-container">
            <img src="assets/img/logo.png" alt="Logo">
            <h1>Administración de Stock y Recetas</h1>
        </div>
        <div>
            <a href="index.php?action=admin" class="btn">Volver al Dashboard</a>
            <a href="index.php?action=logout" style="color:var(--accent-gold); margin-left:10px;">Salir</a>
        </div>
    </header>

    <main class="main-container">

        <!-- ALERTA DE STOCK BAJO -->
        <?php 
            $insumos_criticos = array_filter($materias, function($m) { return$m['stock'] <= 5; });
        ?>
        <?php if (!empty($insumos_criticos)): ?>
            <div class="alert-stock">
                <h3 style="color:#dc3545; margin-top:0;">⚠️ ¡ATENCIÓN! Insumos con bajo stock:</h3>
                <ul style="margin-bottom:0;">
                    <?php foreach ($insumos_criticos as$crit): ?>
                        <li>
                            <strong><?= htmlspecialchars($crit['nombre']) ?></strong>: 
                            Quedan <?= $crit['stock'] ?> <?=$crit['unidad'] ?> 
                            <?= ($crit['stock'] <= 0) ? '<span style="color:#ff3333;">(¡AGOTADO!)</span>' : '' ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <!-- SECCIÓN MATERIA PRIMA -->
            <div class="card">
                <h2>Ingresar Insumo / Materia Prima</h2>
                <form action="index.php?action=admin_stock" method="POST">
                    <div class="form-group">
                        <label>Nombre del Insumo</label>
                        <input type="text" name="nombre" placeholder="Ej: Carne Smash, Medallón, Cheddar" required>
                    </div>
                    <div class="form-group">
                        <label>Unidad de Medida</label>
                        <select name="unidad">
                            <option value="Unidades">Unidades</option>
                            <option value="Kg">Kg</option>
                            <option value="Gramos">Gramos</option>
                            <option value="Litros">Litros</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Stock Inicial</label>
                        <input type="number" step="0.01" min="0" name="stock" value="0.00" required>
                    </div>
                    <div class="form-group">
                        <label>Fecha de Vencimiento</label>
                        <input type="date" name="fecha_vencimiento">
                    </div>
                    <button type="submit" name="crear_materia" class="btn">Cargar al Inventario</button>
                </form>

                <h3>Inventario Actual</h3>
                <table>
                    <thead>
                        <tr><th>Insumo</th><th>Stock</th><th>Unidad</th><th>Acciones</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach($materias as$m): ?>
                            <tr class="<?= ($m['stock'] <= 5) ? 'low-stock' : '' ?>">
                                <td><?= htmlspecialchars($m['nombre']) ?></td>
                                <td><strong><?= $m['stock'] ?></strong></td>
                                <td><?= $m['unidad'] ?></td>
                                <td style="display:flex; gap:5px;">
                                    <button type="button" class="btn btn-warning btn-sm" onclick='abrirModalMateria(<?= json_encode($m) ?>)'>Edit</button>
                                    <form action="index.php?action=admin_stock" method="POST" onsubmit="return confirm('¿Eliminar este insumo?');">
                                        <input type="hidden" name="id_mat" value="<?= $m['id_mat'] ?>">
                                        <button type="submit" name="eliminar_materia" class="btn btn-danger btn-sm">X</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- SECCIÓN COMBOS -->
            <div class="card">
                <h2>Crear Combo / Hamburguesa</h2>
                <form action="index.php?action=admin_stock" method="POST">
                    <div class="form-group">
                        <label>Nombre del Combo</label>
                        <input type="text" name="nombre" placeholder="Ej: Double Smash Cheese" required>
                    </div>
                    <div class="form-group">
                        <label>Precio ($)</label>
                        <input type="number" step="0.01" min="0" name="precio" placeholder="350.00" required>
                    </div>
                    <div class="form-group">
                        <label>Descripción</label>
                        <textarea name="descripcion" placeholder="Ingredientes y acompañamientos..."></textarea>
                    </div>

                    <h3>Receta e Insumos requeridos:</h3>
                    <?php foreach($materias as$mat): ?>
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 8px;">
                            <label><?= htmlspecialchars($mat['nombre']) ?> (<?=$mat['unidad'] ?>):</label>
                            <input type="number" step="0.01" min="0" name="ingredientes[<?= $mat['id_mat'] ?>]" value="0" style="width: 100px;">
                        </div>
                    <?php endforeach; ?>

                    <br>
                    <button type="submit" name="crear_combo" class="btn" style="width:100%;">Guardar Combo</button>
                </form>

                <h3>Combos Existentes</h3>
                <table>
                    <thead>
                        <tr><th>Combo</th><th>Precio</th><th>Acciones</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach($combos as$c): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($c['nombre']) ?></strong></td>
                                <td>$<?=$c['precio'] ?></td>
                                <td style="display:flex; gap:5px;">
                                    <button type="button" class="btn btn-warning btn-sm" onclick='abrirModalCombo(<?= json_encode($c) ?>)'>Edit</button>
                                    <form action="index.php?action=admin_stock" method="POST" onsubmit="return confirm('¿Eliminar este combo?');">
                                        <input type="hidden" name="id_comida" value="<?= $c['id_comida'] ?>">
                                        <button type="submit" name="eliminar_combo" class="btn btn-danger btn-sm">X</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- MODAL EDITAR MATERIA PRIMA -->
    <div id="modalMateria" class="modal">
        <div class="modal-content">
            <h2>Editar Insumo</h2>
            <form action="index.php?action=admin_stock" method="POST">
                <input type="hidden" name="id_mat" id="edit_mat_id">
                <div class="form-group">
                    <label>Nombre</label>
                    <input type="text" name="nombre" id="edit_mat_nombre" required>
                </div>
                <div class="form-group">
                    <label>Unidad</label>
                    <select name="unidad" id="edit_mat_unidad">
                        <option value="Unidades">Unidades</option>
                        <option value="Kg">Kg</option>
                        <option value="Gramos">Gramos</option>
                        <option value="Litros">Litros</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Stock</label>
                    <input type="number" step="0.01" min="0" name="stock" id="edit_mat_stock" required>
                </div>
                <div class="form-group">
                    <label>Vencimiento</label>
                    <input type="date" name="fecha_vencimiento" id="edit_mat_fec">
                </div>
                <div style="display:flex; justify-content:space-between;">
                    <button type="submit" name="editar_materia" class="btn">Guardar Cambios</button>
                    <button type="button" class="btn btn-danger" onclick="cerrarModales()">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL EDITAR COMBO -->
    <div id="modalCombo" class="modal">
        <div class="modal-content">
            <h2>Editar Combo / Receta</h2>
            <form action="index.php?action=admin_stock" method="POST">
                <input type="hidden" name="id_comida" id="edit_com_id">
                <div class="form-group">
                    <label>Nombre</label>
                    <input type="text" name="nombre" id="edit_com_nombre" required>
                </div>
                <div class="form-group">
                    <label>Precio ($)</label>
                    <input type="number" step="0.01" min="0" name="precio" id="edit_com_precio" required>
                </div>
                <div class="form-group">
                    <label>Descripción</label>
                    <textarea name="descripcion" id="edit_com_desc"></textarea>
                </div>

                <h3>Editar Receta:</h3>
                <div id="contenedorRecetaEdit"></div>

                <br>
                <div style="display:flex; justify-content:space-between;">
                    <button type="submit" name="editar_combo" class="btn">Actualizar Combo</button>
                    <button type="button" class="btn btn-danger" onclick="cerrarModales()">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const materiasDisponibles = <?= json_encode($materias) ?>;

        function abrirModalMateria(materia) {
            document.getElementById('edit_mat_id').value = materia.id_mat;
            document.getElementById('edit_mat_nombre').value = materia.nombre;
            document.getElementById('edit_mat_unidad').value = materia.unidad;
            document.getElementById('edit_mat_stock').value = materia.stock;
            document.getElementById('edit_mat_fec').value = materia.fecha_vencimiento || '';
            document.getElementById('modalMateria').style.display = 'flex';
        }

        function abrirModalCombo(combo) {
            document.getElementById('edit_com_id').value = combo.id_comida;
            document.getElementById('edit_com_nombre').value = combo.nombre;
            document.getElementById('edit_com_precio').value = combo.precio;
            document.getElementById('edit_com_desc').value = combo.descripcion;

            const divReceta = document.getElementById('contenedorRecetaEdit');
            divReceta.innerHTML = '';

            materiasDisponibles.forEach(mat => {
                const cantActual = combo.ingredientes && combo.ingredientes[mat.id_mat] ? combo.ingredientes[mat.id_mat] : 0;
                divReceta.innerHTML += `
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 8px;">
                        <label>${mat.nombre} (${mat.unidad}):</label>
                        <input type="number" step="0.01" min="0" name="ingredientes[${mat.id_mat}]" value="${cantActual}" style="width: 100px;">
                    </div>`;
            });

            document.getElementById('modalCombo').style.display = 'flex';
        }

        function cerrarModales() {
            document.getElementById('modalMateria').style.display = 'none';
            document.getElementById('modalCombo').style.display = 'none';
        }
    </script>
</body>
</html>