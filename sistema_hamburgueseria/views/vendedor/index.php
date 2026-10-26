<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Vendedor - EL PASILLITO</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header>
        <div class="logo-container">
            <img src="assets/img/logo.png" alt="Logo">
            <h1>Caja / Nuevo Pedido</h1>
        </div>
        <div>
            <span>Vendedor: <strong>Guille</strong></span> | 
            <a href="index.php?action=logout" style="color:var(--accent-gold);">Salir</a>
        </div>
    </header>

    <main class="main-container">
        <?php if (!empty($mensaje)): ?>
            <div class="card" style="border-left: 5px solid #28a745;"><p style="font-size:1.2em; font-weight:bold; margin:0;"><?= $mensaje ?></p></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="card" style="border-left: 5px solid #dc3545;"><p style="margin:0;"><?= $error ?></p></div>
        <?php endif; ?>

        <form action="index.php?action=vendedor" method="POST">
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
                
                <!-- COLUMNA IZQUIERDA: CLIENTE Y PRODUCTOS -->
                <div>
                    <div class="card">
                        <h2>Datos del Cliente y Entrega</h2>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div class="form-group">
                                <label>Teléfono Cliente</label>
                                <input type="text" name="telefono" placeholder="Ej: 092773900">
                            </div>
                            <div class="form-group">
                                <label>Nombre Cliente</label>
                                <input type="text" name="cliente" placeholder="Nombre completo">
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px;">
                            <div class="form-group">
                                <label>Tipo de Pedido</label>
                                <select name="tipo_pedido" id="tipo_pedido" onchange="toggleEnvio(this.value)">
                                    <option value="local">Retiro en Local</option>
                                    <option value="envio">Envío a Domicilio</option>
                                </select>
                            </div>
                            <div class="form-group" id="group_zona" style="display:none;">
                                <label>Zona de Envío</label>
                                <select name="id_zona" id="id_zona" onchange="calcularTotal()">
                                    <?php foreach($zonas as $z): ?>
                                        <option value="<?= $z['id_zona'] ?>" data-costo="<?= $z['costo_envio'] ?>">
                                            <?= $z['nombre_zona'] ?> (+$<?= $z['costo_envio'] ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group" id="group_dir" style="display:none;">
                                <label>Dirección</label>
                                <input type="text" name="direccion" placeholder="Calle y Número">
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <h2>Selección de Hamburguesas y Combos</h2>
                        <table>
                            <thead>
                                <tr>
                                    <th>Producto / Combo</th>
                                    <th>Precio</th>
                                    <th>Disp. Insumos</th>
                                    <th>Cantidad</th>
                                    <th>Observación / Detalle</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($combos as $c): ?>
                                    <?php 
                                        $disp = $c['stock_disponible'] ?? 0;
                                        $badgeColor = ($disp > 5) ? '#28a745' : (($disp > 0) ? '#f59e0b' : '#dc3545');
                                    ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($c['nombre']) ?></strong><br>
                                            <small style="color:var(--text-muted);"><?= htmlspecialchars($c['descripcion']) ?></small>
                                        </td>
                                        <td>$<?= $c['precio'] ?></td>
                                        <td>
                                            <!-- MUESTRA DE STOCK DISPONIBLE EN BASE A MATERIA PRIMA -->
                                            <span style="background: <?= $badgeColor ?>; color: #fff; padding: 3px 8px; border-radius: 12px; font-weight: bold; font-size: 0.85em;">
                                                <?= $disp ?> dispon.
                                            </span>
                                        </td>
                                        <td>
                                            <input type="number" 
                                                   name="detalles[<?= $c['id_comida'] ?>][cantidad]" 
                                                   class="cant-combo" 
                                                   data-precio="<?= $c['precio'] ?>" 
                                                   value="0" min="0" max="<?= $disp ?>"
                                                   style="width: 60px;" 
                                                   <?= ($disp <= 0) ? 'disabled' : '' ?>
                                                   onchange="calcularTotal()">
                                        </td>
                                        <td>
                                            <input type="text" 
                                                   name="detalles[<?= $c['id_comida'] ?>][observacion]" 
                                                   placeholder="Ej: Sin cebolla, extra mayo" 
                                                   style="width: 100%; padding: 6px; border-radius: 4px; border: 1px solid var(--border-color, #334155); background: #1e293b; color: #fff; box-sizing: border-box;"
                                                   <?= ($disp <= 0) ? 'disabled' : '' ?>>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- COLUMNA DERECHA: SECCIÓN DE CAJA Y COBRO -->
                <div>
                    <div class="card" style="position: sticky; top: 20px; border: 2px solid var(--accent-gold);">
                        <h2 style="text-align: center; margin-bottom: 10px;">Resumen de Caja</h2>
                        <hr style="border-color: var(--border-color);">
                        
                        <div style="margin: 15px 0;">
                            <p style="display:flex; justify-content:space-between; margin: 5px 0;">
                                <span>Subtotal Productos:</span>
                                <strong>$<span id="txtSubtotal">0.00</span></strong>
                            </p>
                            <p style="display:flex; justify-content:space-between; margin: 5px 0;">
                                <span>Costo Envio:</span>
                                <strong>$<span id="txtEnvio">0.00</span></strong>
                            </p>
                            <hr style="border-color: var(--border-color);">
                            <p style="display:flex; justify-content:space-between; align-items:center; font-size: 1.4em; color: var(--accent-gold); margin: 10px 0;">
                                <span>TOTAL:</span>
                                <strong>$<span id="txtTotal">0.00</span></strong>
                            </p>
                        </div>

                        <div class="form-group">
                            <label>Medio de Pago</label>
                            <select name="metodo_pago" id="metodo_pago" onchange="toggleEfectivo(this.value)">
                                <option value="efectivo">💵 Efectivo</option>
                                <option value="transferencia">🏦 Transferencia / MercadoPago</option>
                                <option value="tarjeta">💳 Tarjeta Débito / Crédito</option>
                            </select>
                        </div>

                        <div class="form-group" id="group_efectivo">
                            <label>Paga con ($)</label>
                            <input type="number" step="0.01" id="monto_abonado" placeholder="Ej: 1000" oninput="calcularVuelto()">
                            <p style="margin-top: 8px; font-size: 1.1em; color: #28a745;">
                                Vuelto: <strong>$<span id="txtVuelto">0.00</span></strong>
                            </p>
                        </div>

                        <button type="submit" class="btn" style="width:100%; font-size:1.2em; padding: 15px; margin-top: 10px;">
                            🛒 Cobrar y Confirmar
                        </button>
                    </div>
                </div>

            </div>
        </form>
    </main>

    <script>
        function toggleEnvio(val) {
            const esEnvio = (val === 'envio');
            document.getElementById('group_zona').style.display = esEnvio ? 'block' : 'none';
            document.getElementById('group_dir').style.display = esEnvio ? 'block' : 'none';
            calcularTotal();
        }

        function toggleEfectivo(val) {
            document.getElementById('group_efectivo').style.display = (val === 'efectivo') ? 'block' : 'none';
        }

        function calcularTotal() {
            let subtotal = 0;
            const combos = document.querySelectorAll('.cant-combo');
            combos.forEach(input => {
                const cant = parseFloat(input.value) || 0;
                const precio = parseFloat(input.getAttribute('data-precio')) || 0;
                subtotal += (cant * precio);
            });

            let costoEnvio = 0;
            const tipoPedido = document.getElementById('tipo_pedido').value;
            if (tipoPedido === 'envio') {
                const selectZona = document.getElementById('id_zona');
                const optSelected = selectZona.options[selectZona.selectedIndex];
                if (optSelected) {
                    costoEnvio = parseFloat(optSelected.getAttribute('data-costo')) || 0;
                }
            }

            const total = subtotal + costoEnvio;

            document.getElementById('txtSubtotal').innerText = subtotal.toFixed(2);
            document.getElementById('txtEnvio').innerText = costoEnvio.toFixed(2);
            document.getElementById('txtTotal').innerText = total.toFixed(2);

            calcularVuelto();
        }

        function calcularVuelto() {
            const total = parseFloat(document.getElementById('txtTotal').innerText) || 0;
            const abonado = parseFloat(document.getElementById('monto_abonado').value) || 0;
            const vuelto = abonado - total;
            
            const txtV = document.getElementById('txtVuelto');
            if (vuelto >= 0 && abonado > 0) {
                txtV.innerText = vuelto.toFixed(2);
                txtV.style.color = '#28a745';
            } else {
                txtV.innerText = '0.00';
            }
        }
    </script>
</body>
</html>