<?php
session_start();
require_once '../includes/db_connection.php';

// Inicializar variables de sesión si no existen
if (!isset($_SESSION['recovery_data'])) {
    $_SESSION['recovery_data'] = [
        'step' => 1,
        'usuario_id' => null,
        'pregunta' => '',
        'nombre_usuario' => ''
    ];
}

$error = '';
$success = '';

// Usar datos de sesión
$step = $_SESSION['recovery_data']['step'];
$usuario_id = $_SESSION['recovery_data']['usuario_id'];
$pregunta = $_SESSION['recovery_data']['pregunta'];
$nombre_usuario_session = $_SESSION['recovery_data']['nombre_usuario'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step == 1) {
        $nombre_usuario = $_POST['nombre_usuario'] ?? '';
        
        if (empty($nombre_usuario)) {
            $error = "Por favor, ingrese su nombre de usuario.";
        } else {
            $stmt = $pdo->prepare("SELECT idUsuario, pregunta_seguridad, respuesta_seguridad FROM usuarios WHERE nombre_usuario = ?");
            $stmt->execute([$nombre_usuario]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && !empty($user['pregunta_seguridad'])) {
                // Guardar en sesión
                $_SESSION['recovery_data'] = [
                    'step' => 2,
                    'usuario_id' => $user['idUsuario'],
                    'pregunta' => $user['pregunta_seguridad'],
                    'nombre_usuario' => $nombre_usuario
                ];
                
                // Redirigir para evitar reenvío del formulario
                header("Location: forgot_password.php");
                exit();
            } else {
                $error = "Usuario no encontrado o no tiene pregunta de seguridad configurada.";
            }
        }
    }
    elseif ($step == 2) {
        $respuesta = $_POST['respuesta'] ?? '';
        
        if (empty($respuesta)) {
            $error = "Por favor, ingrese su respuesta.";
        } else {
            $stmt = $pdo->prepare("SELECT idUsuario FROM usuarios WHERE idUsuario = ? AND LOWER(TRIM(respuesta_seguridad)) = LOWER(TRIM(?))");
            $stmt->execute([$usuario_id, $respuesta]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                $_SESSION['recovery_data']['step'] = 3;
                header("Location: forgot_password.php");
                exit();
            } else {
                $error = "Respuesta incorrecta. Intenta nuevamente.";
                // Mantener en el paso 2 pero mostrar error
            }
        }
    }
    elseif ($step == 3) {
        $nueva_password = $_POST['nueva_password'] ?? '';
        $confirmar_password = $_POST['confirmar_password'] ?? '';
        
        if ($nueva_password !== $confirmar_password) {
            $error = "Las contraseñas no coinciden.";
        } elseif (strlen($nueva_password) < 4) {
            $error = "La contraseña debe tener al menos 4 caracteres.";
        } else {
            $hashed_password = password_hash($nueva_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE usuarios SET contrasena = ? WHERE idUsuario = ?");
            
            if ($stmt->execute([$hashed_password, $usuario_id])) {
                $success = "✅ Contraseña actualizada correctamente. Ahora puedes iniciar sesión.";
                $_SESSION['recovery_data']['step'] = 4;
                
                // Limpiar datos de recuperación después de éxito
                unset($_SESSION['recovery_data']);
            } else {
                $error = "Error al actualizar la contraseña. Por favor, intenta nuevamente.";
            }
        }
    }
}

// Actualizar variables locales después de posibles cambios
$step = $_SESSION['recovery_data']['step'] ?? 1;
$usuario_id = $_SESSION['recovery_data']['usuario_id'] ?? null;
$pregunta = $_SESSION['recovery_data']['pregunta'] ?? '';
$nombre_usuario_session = $_SESSION['recovery_data']['nombre_usuario'] ?? '';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recuperar Contraseña</title>
    <style>
        body { 
            background: #1a1a2e; 
            color: white; 
            font-family: Arial; 
            padding: 20px;
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container { 
            max-width: 500px; 
            width: 100%;
            background: #2d2d44; 
            padding: 30px; 
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
        input, button { 
            width: 100%; 
            padding: 12px; 
            margin: 10px 0; 
            border-radius: 5px; 
            border: 1px solid #444; 
            font-size: 16px;
        }
        input {
            background: #1a1a2e;
            color: white;
        }
        button { 
            background: #ffd700; 
            color: #000; 
            border: none; 
            cursor: pointer; 
            font-weight: bold;
        }
        button:hover {
            background: #ffed4e;
        }
        .error { 
            background: #ff4444; 
            padding: 15px; 
            border-radius: 5px; 
            margin: 15px 0; 
        }
        .success { 
            background: #44ff44; 
            padding: 15px; 
            border-radius: 5px; 
            margin: 15px 0; 
            color: #000;
        }
        .pregunta {
            background: #4444ff;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            font-weight: bold;
        }
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        .back-link a {
            color: #ffd700;
            text-decoration: none;
        }
        h2 {
            text-align: center;
            margin-bottom: 25px;
            color: #ffd700;
        }
        .reset-btn {
            background: #ff6b6b;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>🔐 Recuperar Contraseña</h2>
        
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?= $success ?></div>
            <div class="back-link">
                <a href="login.php">🎯 Iniciar Sesión</a>
            </div>
        <?php endif; ?>

        <?php if ($step == 1): ?>
            <form method="POST" id="step1Form">
                <p>Ingresa tu nombre de usuario:</p>
                <input type="text" name="nombre_usuario" placeholder="Nombre de usuario" required 
                       value="<?= htmlspecialchars($_POST['nombre_usuario'] ?? '') ?>">
                <button type="submit">🔍 Buscar Usuario</button>
            </form>
            <div class="back-link">
                <a href="login.php">← Volver al Login</a>
            </div>
        
        <?php elseif ($step == 2): ?>
            <form method="POST" id="step2Form">
                <input type="hidden" name="usuario_id" value="<?= $usuario_id ?>">
                <p><strong>Usuario:</strong> <?= htmlspecialchars($nombre_usuario_session) ?></p>
                <div class="pregunta"><?= htmlspecialchars($pregunta) ?></div>
                <input type="text" name="respuesta" placeholder="Tu respuesta" required 
                       value="<?= htmlspecialchars($_POST['respuesta'] ?? '') ?>">
                <button type="submit">✅ Verificar Respuesta</button>
            </form>
            <div class="back-link">
                <button class="reset-btn" onclick="resetProcess()">🔄 Empezar de nuevo</button>
                <a href="login.php">← Volver al Login</a>
            </div>
        
        <?php elseif ($step == 3): ?>
            <form method="POST" id="step3Form">
                <input type="hidden" name="usuario_id" value="<?= $usuario_id ?>">
                <p><strong>Usuario:</strong> <?= htmlspecialchars($nombre_usuario_session) ?></p>
                <p>Ingresa tu nueva contraseña:</p>
                <input type="password" name="nueva_password" placeholder="Nueva contraseña" minlength="4" required>
                <input type="password" name="confirmar_password" placeholder="Confirmar contraseña" minlength="4" required>
                <button type="submit">🔄 Cambiar Contraseña</button>
            </form>
            <div class="back-link">
                <button class="reset-btn" onclick="resetProcess()">🔄 Empezar de nuevo</button>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function resetProcess() {
            if (confirm('¿Estás seguro de que quieres empezar de nuevo?')) {
                window.location.href = 'forgot_password.php?reset=1';
            }
        }

        // Validación de contraseñas en tiempo real (para paso 3)
        const nuevaPassword = document.querySelector('input[name="nueva_password"]');
        const confirmarPassword = document.querySelector('input[name="confirmar_password"]');
        
        if (nuevaPassword && confirmarPassword) {
            function validarPasswords() {
                if (nuevaPassword.value !== confirmarPassword.value) {
                    confirmarPassword.style.borderColor = '#ff4444';
                } else {
                    confirmarPassword.style.borderColor = '#44ff44';
                }
            }
            
            nuevaPassword.addEventListener('input', validarPasswords);
            confirmarPassword.addEventListener('input', validarPasswords);
        }
    </script>
</body>
</html>