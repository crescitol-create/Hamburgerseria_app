<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cocina - EL PASILLITO</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <meta http-equiv="refresh" content="8">
    <style>
        .freezer-widget {
            background-color: var(--bg-card, #1e293b);
            border: 1px solid var(--border-color, #334155);
            border-radius: 8px;
            padding: 12px 18px;
            margin-bottom: 20px;
            max-width: 380px;
            color: #ffffff;
        }
        .freezer-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border-color, #334155);
            padding-bottom: 6px;
            margin-bottom: 10px;
        }
        .freezer-body {
            display: flex;
            justify-content: space-around;
            text-align: center;
        }
    </style>
</head>
<body>
    <header>
        <div class="logo-container">
            <img src="assets/img/logo.png" alt="Logo">
            <h1>Monitor de Cocina</h1>
        </div>
        <div>
            <span>Cocinero: <strong>Agus</strong></span> |
            <a href="index.php?action=logout" style="color:var(--accent-gold);">Salir</a>
        </div>
    </header>

    <main class="main-container">

        <!-- COMPONENTE TEMPERATURA Y HUMEDAD FREEZER -->
        <?php
            $ip_esp32_sensor = "192.168.1.50";
            $ip_esp32_alarma = "192.168.1.118";
        ?>

        <div class="freezer-widget">
            <div class="freezer-header">
                <h4 style="margin: 0; font-size: 1em;">❄ Monitoreo Freezer</h4>
                <span id="esp-status-indicator" style="font-size: 0.75em; padding: 2px 6px; border-radius: 10px; background-color: #f59e0b; color: #000; font-weight: bold;">Conectando...</span>
            </div>
            <div class="freezer-body">
                <div>
                    <span style="font-size: 0.8em; color: var(--text-muted, #94a3b8); display: block;">Temperatura</span>
                    <strong id="freezer-temp" style="font-size: 1.5em; color: #38bdf8;">-- °C</strong>
                </div>
                <div style="border-left: 1px solid var(--border-color, #334155); padding-left: 15px;">
                    <span style="font-size: 0.8em; color: var(--text-muted, #94a3b8); display: block;">Humedad</span>
                    <strong id="freezer-hum" style="font-size: 1.5em; color: #34d399;">-- %</strong>
                </div>
            </div>
        </div>

        <h2>Pedidos Pendientes para Preparar</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px;">
            <?php if (empty($pedidos)): ?>
                <div class="card" style="grid-column: 1 / -1; text-align: center; padding: 40px;">
                    <h3>No hay pedidos pendientes en este momento.</h3>
                    <p style="color: var(--text-muted);">Los nuevos pedidos ingresados por el vendedor aparecerán automáticamente aquí.</p>
                </div>
            <?php else: ?>
                <?php foreach($pedidos as $p): ?>
                    <div class="card" style="border-top: 5px solid var(--accent-gold);">
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <h3 style="margin:0;">Pedido #<?= $p['id_pedido'] ?></h3>
                            <span class="badge badge-pendiente"><?= strtoupper($p['tipo_pedido']) ?></span>
                        </div>
                        <p style="margin: 8px 0;"><small>Hora Entrada: <?= date('H:i:s', strtotime($p['hora_entrada'])) ?></small></p>
                        <p style="margin: 5px 0;"><strong>Cliente:</strong> <?= htmlspecialchars($p['cliente_nombre']) ?></p>
                        <hr style="border-color: var(--border-color); margin: 10px 0;">
                        <h4 style="margin: 5px 0; color: var(--accent-gold);">Detalle del Pedido:</h4>
                        <ul style="padding-left: 20px; margin: 10px 0;">
                            <?php foreach($p['detalles'] as $d): ?>
                                <li style="font-size: 1.1em; margin-bottom: 8px;">
                                    <strong><?= $d['cantidad'] ?>x</strong> <?= htmlspecialchars($d['comida_nombre']) ?>
                                    
                                    <!-- DETECCIÓN AUTOMÁTICA DEL CAMPO OBSERVACIONES -->
                                    <?php 
                                        $obs = $d['observacion'] ?? $d['observaciones'] ?? $d['detalle'] ?? $d['nota'] ?? $d['comentario'] ?? null;
                                    ?>
                                    <?php if (!empty($obs)): ?>
                                        <br><small style="color: #f59e0b; font-weight: bold; background: rgba(245, 158, 11, 0.15); padding: 2px 6px; border-radius: 4px; display: inline-block; margin-top: 2px;">
                                            📌 Nota: <?= htmlspecialchars($obs) ?>
                                        </small>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php if(!empty($p['direccion'])): ?>
                            <p style="background: #2a2a2a; padding: 8px; border-radius: 4px; font-size: 0.9em;">
                                <strong>Dirección de entrega:</strong> <?= htmlspecialchars($p['direccion']) ?>
                            </p>
                        <?php endif; ?>
                       
                        <form id="form-pedido-<?= $p['id_pedido'] ?>" action="index.php?action=cocinero" method="POST" style="margin-top:15px;">
                            <input type="hidden" name="id_pedido" value="<?= $p['id_pedido'] ?>">
                            <input type="hidden" name="marcar_listo" value="1">
                            <button type="button" onclick="finalizarConAlarma(<?= $p['id_pedido'] ?>)" class="btn btn-success" style="width:100%; padding: 12px; font-weight: bold; cursor: pointer;">
                                ✔ Marcar como Listo / Terminado
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <!-- SCRIPTS DE ARDUINO / ESP32 -->
    <script>
        const ESP32_SENSOR_URL = "http://<?php echo $ip_esp32_sensor; ?>/sensor";
        const ESP32_ALARMA_URL = "http://<?php echo $ip_esp32_alarma; ?>/notificar";

        // Obtener lectura de DHT11
        function obtenerLecturasFreezer() {
            fetch(ESP32_SENSOR_URL)
                .then(response => response.json())
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

        // Enviar señal a la ESP32 de la alarma y luego enviar el formulario PHP
        function finalizarConAlarma(idPedido) {
            fetch(ESP32_ALARMA_URL, { mode: 'no-cors' }).catch(err => console.log("ESP32 Alarma no disponible"));
            document.getElementById('form-pedido-' + idPedido).submit();
        }

        document.addEventListener("DOMContentLoaded", function () {
            obtenerLecturasFreezer();
            setInterval(obtenerLecturasFreezer, 5000);
        });
    </script>
</body>
</html>