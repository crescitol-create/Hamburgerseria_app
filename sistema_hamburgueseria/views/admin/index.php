<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - EL PASILLITO</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .grid-dashboard {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .card-stat {
            background-color: var(--bg-card, #1e293b);
            border: 1px solid var(--border-color, #334155);
            border-left: 4px solid var(--accent-gold, #f59e0b);
            padding: 15px;
            border-radius: 8px;
            color: #ffffff;
        }
        .card-stat h3 { margin: 0 0 5px 0; font-size: 0.95em; color: #94a3b8; }
        .card-stat p { margin: 0; font-size: 1.5em; font-weight: bold; color: var(--accent-gold, #f59e0b); }
        
        .modal {
            display: none; position: fixed; z-index: 100; left: 0; top: 0; width: 100%; height: 100%;
            background-color: rgba(0,0,0,0.8); justify-content: center; align-items: center;
        }
        .modal-content {
            background-color: var(--bg-card, #1e293b); padding: 20px; border-radius: 8px; border: 1px solid var(--accent-gold, #f59e0b);
            width: 90%; max-width: 450px; color: #ffffff;
        }
        .btn-warning { background-color: #ffc107; color: black; border: none; cursor: pointer; }
        .btn-danger { background-color: #dc3545; color: white; border: none; cursor: pointer; }
        .btn-sm { padding: 4px 8px; font-size: 0.85em; border-radius: 4px; }
        
        .container-two-cols {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
        }
        @media (max-width: 900px) {
            .container-two-cols { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <header>
        <div class="logo-container">
            <img src="assets/img/logo.png" alt="Logo">
            <h1>Panel de Administración</h1>
        </div>
        <div>
            <a href="index.php?action=admin_stock" class="btn">Gestionar Stock y Combos</a>
            <a href="index.php?action=logout" style="color:var(--accent-gold, #f59e0b); margin-left:10px;">Salir</a>
        </div>
    </header>

    <main class="main-container" style="padding: 20px;">
        
        <!-- COMPONENTE TEMPERATURA Y HUMEDAD FREEZER (ESP32 + DHT11) -->
        <?php $ip_esp32 = "192.168.1.128"; ?>
        <div style="background-color: #1e293b; color: #ffffff; padding: 18px; border-radius: 10px; font-family: Arial, sans-serif; max-width: 380px; margin-bottom: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); border: 1px solid #334155;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; border-bottom: 1px solid #334155; padding-bottom: 8px;">
                <h4 style="margin: 0; font-size: 1.1em; font-weight: bold;">❄️ Sensores Freezer en Vivo</h4>
                <span id="esp-status-indicator" style="font-size: 0.8em; padding: 3px 8px; border-radius: 12px; background-color: #f59e0b; color: #000; font-weight: bold;">Conectando...</span>
            </div>
            
            <div style="display: flex; justify-content: space-around; text-align: center;">
                <div>
                    <span style="font-size: 0.85em; color: #94a3b8; display: block; margin-bottom: 4px;">Temperatura</span>
                    <strong id="freezer-temp" style="font-size: 2em; color: #38bdf8;">-- °C</strong>
                </div>
                <div style="border-left: 1px solid #334155; padding-left: 20px;">
                    <span style="font-size: 0.85em; color: #94a3b8; display: block; margin-bottom: 4px;">Humedad</span>
                    <strong id="freezer-hum" style="font-size: 2em; color: #34d399;">-- %</strong>
                </div>
            </div>
        </div>

        <!-- ESTADÍSTICAS GENERALES -->
        <h2>Resumen de Métricas</h2>
        <div class="grid-dashboard">
            <div class="card-stat">
                <h3>Ventas Totales</h3>
                <p>$<?= number_format($balance['total'] ?? 0, 2) ?></p>
            </div>
            <div class="card-stat">
                <h3>Pedidos Entregados</h3>
                <p><?= $balance['cantidad_pedidos'] ?? 0 ?></p>
            </div>
            <div class="card-stat">
                <h3>Producto Estrella</h3>
                <p style="font-size: 1.1em; color: #fff;"><?= $mas_vendido['nombre'] ?? 'N/A' ?> (<?= $mas_vendido['total_vendido'] ?? 0 ?> u.)</p>
            </div>
            <div class="card-stat">
                <h3>Insumos Críticos</h3>
                <p style="color: <?= count($insumos_criticos ?? []) > 0 ? '#dc3545' : '#28a745' ?>;">
                    <?= count($insumos_criticos ?? []) ?>
                </p>
            </div>
        </div>

        <div class="container-two-cols">
            
            <!-- CIERRES DE CAJA Y VENTAS DIARIAS -->
            <div>
                <div class="card" style="background-color: var(--bg-card, #1e293b); padding: 15px; border-radius: 8px;">
                    <h2>Cierres de Caja Diarios</h2>
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="text-align: left; border-bottom: 1px solid #334155;">
                                <th>Fecha</th>
                                <th>Pedidos</th>
                                <th>Efectivo</th>
                                <th>Transf.</th>
                                <th>Tarjeta</th>
                                <th>Total Día</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($cierres_diarios)): ?>
                                <?php foreach($cierres_diarios as $cierre): ?>
                                    <tr style="border-bottom: 1px solid #2d3748;">
                                        <td><strong><?= $cierre['fecha'] ?></strong></td>
                                        <td><?= $cierre['total_pedidos'] ?></td>
                                        <td>$<?= number_format($cierre['efectivo'], 2) ?></td>
                                        <td>$<?= number_format($cierre['transferencia'], 2) ?></td>
                                        <td>$<?= number_format($cierre['tarjeta'], 2) ?></td>
                                        <td><strong style="color:var(--accent-gold, #f59e0b);">$<?= number_format($cierre['total_dia'], 2) ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align:center; padding: 10px;">No hay cierres registrados aún.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- GESTIÓN DE USUARIOS Y ROLES -->
            <div>
                <div class="card" style="background-color: var(--bg-card, #1e293b); padding: 15px; border-radius: 8px;">
                    <h2>Gestión de Usuarios</h2>
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="text-align: left; border-bottom: 1px solid #334155;">
                                <th>Rol</th>
                                <th>Usuario</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($usuarios)): ?>
                                <?php foreach($usuarios as $u): ?>
                                    <tr style="border-bottom: 1px solid #2d3748;">
                                        <td><span style="text-transform: capitalize; font-weight:bold;"><?= htmlspecialchars($u['rol']) ?></span></td>
                                        <td><?= htmlspecialchars($u['usuario']) ?></td>
                                        <td>
                                            <button type="button" class="btn btn-warning btn-sm" onclick='abrirModalUsuario(<?= json_encode($u) ?>)'>Editar</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>

    <!-- MODAL EDITAR USUARIO -->
    <div id="modalUsuario" class="modal">
        <div class="modal-content">
            <h2>Editar Credenciales</h2>
            <form action="index.php?action=admin" method="POST">
                <input type="hidden" name="id_usuario" id="edit_user_id">
                
                <div class="form-group" style="margin-bottom: 15px;">
                    <label style="display:block; margin-bottom: 5px;">Nombre de Usuario</label>
                    <input type="text" name="usuario" id="edit_user_nombre" required style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #334155;">
                </div>
                
                <div class="form-group" style="margin-bottom: 15px;">
                    <label style="display:block; margin-bottom: 5px;">Nueva Contraseña <small style="color:#94a3b8;">(dejar en blanco para mantener la actual)</small></label>
                    <input type="password" name="password" placeholder="********" style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #334155;">
                </div>

                <div style="display:flex; justify-content:space-between; margin-top:20px;">
                    <button type="submit" name="editar_usuario" class="btn btn-warning" style="padding: 8px 15px;">Guardar Cambios</button>
                    <button type="button" class="btn btn-danger" onclick="cerrarModalUsuario()" style="padding: 8px 15px;">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- SCRIPTS -->
    <script>
        // Modal de gestión de usuarios
        function abrirModalUsuario(user) {
            document.getElementById('edit_user_id').value = user.id_usuario;
            document.getElementById('edit_user_nombre').value = user.usuario;
            document.getElementById('modalUsuario').style.display = 'flex';
        }

        function cerrarModalUsuario() {
            document.getElementById('modalUsuario').style.display = 'none';
        }

        // Conexión AJAX con el sensor ESP32
        document.addEventListener("DOMContentLoaded", function () {
            const esp32Url = "http://<?php echo $ip_esp32; ?>/sensor";

            function actualizarLecturaFreezer() {
                fetch(esp32Url)
                    .then(response => {
                        if (!response.ok) throw new Error("Error en la conexión");
                        return response.json();
                    })
                    .then(data => {
                        document.getElementById('freezer-temp').innerText = data.temperatura + " °C";
                        document.getElementById('freezer-hum').innerText = data.humedad + " %";
                        
                        const indicator = document.getElementById('esp-status-indicator');
                        indicator.innerText = "● EN VIVO";
                        indicator.style.backgroundColor = "#10b981";
                        indicator.style.color = "#ffffff";
                    })
                    .catch(() => {
                        const indicator = document.getElementById('esp-status-indicator');
                        indicator.innerText = "⚠️ SIN CONEXIÓN";
                        indicator.style.backgroundColor = "#ef4444";
                        indicator.style.color = "#ffffff";
                    });
            }

            actualizarLecturaFreezer();
            setInterval(actualizarLecturaFreezer, 5000);
        });
    </script>
</body>
</html>