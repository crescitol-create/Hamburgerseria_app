<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login - EL PASILLITO</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body style="display:flex; justify-content:center; align-items:center; min-height:100vh;">
    <div class="card" style="width: 360px; text-align: center;">
        <img src="assets/img/logo.png" alt="EL PASILLITO" style="max-width: 180px; margin-bottom: 15px;">
        <h2>Ingreso al Sistema</h2>
        
        <?php if (!empty($error)): ?>
            <p style="color: #ff4d4d; font-weight: bold; background: rgba(255,0,0,0.1); padding: 8px; border-radius: 4px;"><?= $error ?></p>
        <?php endif; ?>

        <form action="index.php?action=autenticar" method="POST">
            <div class="form-group">
                <label style="text-align: left;">Usuario</label>
                <input type="text" name="nombre" placeholder="Nombre de usuario" required style="width: 100%;">
            </div>
            <div class="form-group">
                <label style="text-align: left;">Contraseña</label>
                <input type="password" name="password" placeholder="Contraseña" required style="width: 100%;">
            </div>
            <button type="submit" class="btn" style="width: 100%; margin-top: 10px;">Iniciar Sesión</button>
        </form>
    </div>
</body>
</html>