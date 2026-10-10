<?php
// sistema_pedidos/auth/login.php

session_start();

require_once '../includes/db_connection.php';

$error = '';
$show_tasa_dolar = false;
$tasa_dolar = '';
$tasa_actual = '';

// Obtener la tasa actual más reciente de la base de datos
function getTasaActual($pdo) {
    $stmt = $pdo->prepare("SELECT valor_dolar FROM tasa_dolar WHERE activo = 1 ORDER BY fecha_actualizacion DESC LIMIT 1");
    $stmt->execute();
    $tasa = $stmt->fetch(PDO::FETCH_ASSOC);
    return $tasa ? $tasa['valor_dolar'] : null;
}

// Guardar nueva tasa en la base de datos
function guardarTasaDolar($pdo, $valor_dolar, $usuario_id) {
    try {
        $pdo->beginTransaction();
        
        error_log("Iniciando actualización de tasa: $valor_dolar por usuario: $usuario_id");
        
        // 1. Desactivar todas las tasas anteriores
        $stmt = $pdo->prepare("UPDATE tasa_dolar SET activo = 0 WHERE activo = 1");
        $update_result = $stmt->execute();
        error_log("Tasas anteriores desactivadas: " . ($update_result ? "éxito" : "fallo"));
        
        // 2. Insertar nueva tasa activa
        $stmt = $pdo->prepare("INSERT INTO tasa_dolar (valor_dolar, usuario_actualizacion) VALUES (?, ?)");
        $insert_result = $stmt->execute([$valor_dolar, $usuario_id]);
        error_log("Nueva tasa insertada: " . ($insert_result ? "éxito" : "fallo"));
        
        if (!$insert_result) {
            throw new Exception("Error al insertar nueva tasa");
        }
        
        // 3. Verificar si las tablas tienen las columnas necesarias antes de actualizar
        $check_ingredientes = $pdo->query("SHOW COLUMNS FROM ingredientes LIKE 'costo_bolivares'")->fetch();
        $check_productos = $pdo->query("SHOW COLUMNS FROM productos LIKE 'precio_bolivares'")->fetch();
        
        error_log("Columna costo_bolivares existe: " . ($check_ingredientes ? "sí" : "no"));
        error_log("Columna precio_bolivares existe: " . ($check_productos ? "sí" : "no"));
        
        // 4. Actualizar costos en bolívares de ingredientes (si la columna existe)
        if ($check_ingredientes) {
            $stmt = $pdo->prepare("UPDATE ingredientes SET costo_bolivares = costo_dolares * ?, fecha_actualizacion = NOW()");
            $ingredientes_result = $stmt->execute([$valor_dolar]);
            error_log("Ingredientes actualizados: " . ($ingredientes_result ? "éxito" : "fallo"));
        } else {
            error_log("Advertencia: Columna costo_bolivares no existe en ingredientes");
        }
        
        // 5. Actualizar precios y costos en bolívares de productos (si las columnas existen)
        if ($check_productos) {
            // Verificar si existe la columna costo_bolivares en productos
            $check_costo_productos = $pdo->query("SHOW COLUMNS FROM productos LIKE 'costo_bolivares'")->fetch();
            
            if ($check_costo_productos) {
                $stmt = $pdo->prepare("UPDATE productos SET precio_bolivares = precio * ?, costo_bolivares = costo_dolares * ?, fecha_actualizacion_precio = NOW()");
                $productos_result = $stmt->execute([$valor_dolar, $valor_dolar]);
            } else {
                $stmt = $pdo->prepare("UPDATE productos SET precio_bolivares = precio * ?, fecha_actualizacion_precio = NOW()");
                $productos_result = $stmt->execute([$valor_dolar]);
            }
            error_log("Productos actualizados: " . ($productos_result ? "éxito" : "fallo"));
        } else {
            error_log("Advertencia: Columna precio_bolivares no existe en productos");
        }
        
        $pdo->commit();
        error_log("Transacción completada exitosamente");
        return true;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_msg = "Error en guardarTasaDolar: " . $e->getMessage();
        error_log($error_msg);
        return false;
    }
}

// Función para redirigir según el rol
function redirectByRole($role) {
    switch($role) {
        case 'Admin':
            header("Location: ../admin/index.php");
            break;
        case 'Cajero':
            header("Location: ../cajero/index.php");
            break;
        case 'Cocina':
            header("Location: ../cocina/index.php");
            break;
        default:
            header("Location: ../admin/index.php");
            
    }
    exit();
}

// VERIFICACIÓN SIMPLIFICADA: Solo redirigir si ya está logueado y NO es formulario de tasa
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    // Si es una petición POST para actualizar tasa, no redirigir
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tasa_action'])) {
        // Permitir que continúe el procesamiento del formulario de tasa
    } else {
        // Redirigir inmediatamente a su dashboard correspondiente
        redirectByRole($_SESSION['role']);
    }
}

// PROCESAR EL FORMULARIO
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Si es el formulario de tasa del dólar (después del login exitoso)
    if (isset($_POST['tasa_action'])) {
        // VERIFICAR que el usuario está logueado
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
            $error = "Sesión no válida. Por favor, inicie sesión nuevamente.";
            session_destroy();
        } else {
            $tasa_action = $_POST['tasa_action'];
            
            if ($tasa_action === 'update' && !empty($_POST['tasa_dolar'])) {
                $tasa_dolar = floatval($_POST['tasa_dolar']);
                
                // Validar que sea un número positivo
                if ($tasa_dolar > 0) {
                    // Guardar en la base de datos
                    if (guardarTasaDolar($pdo, $tasa_dolar, $_SESSION['user_id'])) {
                        // También guardar en sesión para uso inmediato
                        $_SESSION['tasa_dolar'] = $tasa_dolar;
                        $_SESSION['tasa_dolar_timestamp'] = time();
                        
                        // REDIRIGIR SEGÚN ROL después de actualizar tasa
                        redirectByRole($_SESSION['role']);
                    } else {
                        $error = "Error al guardar la tasa en la base de datos.";
                        $show_tasa_dolar = true;
                    }
                } else {
                    $error = "Por favor, ingrese una tasa de cambio válida (mayor a 0).";
                    $show_tasa_dolar = true;
                }
            }
            // Si eligió mantener la tasa actual
            elseif ($tasa_action === 'keep') {
                // Solo actualizar el timestamp de sesión
                $_SESSION['tasa_dolar_timestamp'] = time();
                // REDIRIGIR SEGÚN ROL
                redirectByRole($_SESSION['role']);
            } else {
                $error = "Acción no reconocida o tasa vacía.";
                $show_tasa_dolar = true;
            }
        }
    }
    // Proceso de login normal
    else {
        $nombre_usuario = $_POST['nombre_usuario'] ?? '';
        $password = $_POST['password'] ?? '';

        if (empty($nombre_usuario) || empty($password)) {
            $error = "Por favor, ingrese su nombre de usuario y contraseña.";
        } else {
            try {
                $stmt = $pdo->prepare("SELECT idUsuario, nombre_usuario, contrasena, rol FROM usuarios WHERE nombre_usuario = ?");
                $stmt->execute([$nombre_usuario]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user && password_verify($password, $user['contrasena'])) {
                    // Autenticación exitosa, iniciar sesión
                    $_SESSION['user_id'] = $user['idUsuario'];
                    $_SESSION['user_name'] = $user['nombre_usuario'];
                    $_SESSION['role'] = $user['rol'];
                    
                    // Obtener tasa actual de la base de datos
                    $tasa_actual = getTasaActual($pdo);
                    if ($tasa_actual) {
                        $_SESSION['tasa_dolar'] = $tasa_actual;
                    }
                    
                    // SOLO Admin puede cambiar la tasa
                    if ($user['rol'] === 'Admin') {
                        $show_tasa_dolar = true;
                        $tasa_actual = getTasaActual($pdo); // Obtener tasa para mostrar
                    } else {
                        // Para usuarios no-admin, REDIRIGIR INMEDIATAMENTE a su dashboard
                        redirectByRole($user['rol']);
                    }
                    
                } else {
                    $error = "Nombre de usuario o contraseña incorrectos.";
                }
            } catch (PDOException $e) {
                $error = "Error en la base de datos: " . $e->getMessage();
            }
        }
    }
}

// Obtener tasa actual para mostrar (si es necesario)
if ($show_tasa_dolar) {
    $tasa_actual = getTasaActual($pdo);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Pedidos</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
            position: relative;
        }
        
        #particles-js {
            position: absolute;
            width: 100%;
            height: 100%;
            z-index: 1;
        }
        
        .login-container {
            width: 100%;
            max-width: 450px;
            padding: 40px 30px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
            position: relative;
            z-index: 2;
            color: white;
            transition: transform 0.3s ease;
        }
        
        .login-container:hover {
            transform: translateY(-5px);
        }
        
        .login-container::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: linear-gradient(45deg, #ffd700, #ffed4e, #ffd700);
            z-index: -1;
            border-radius: 22px;
            opacity: 0.7;
            filter: blur(5px);
        }
        
        h2 {
            text-align: center;
            margin-bottom: 30px;
            font-size: 28px;
            font-weight: 600;
            background: linear-gradient(to right, #ffd700, #ffed4e);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            position: relative;
            padding-bottom: 10px;
        }
        
        h2::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 3px;
            background: linear-gradient(to right, #ffd700, #ffed4e);
            border-radius: 2px;
        }
        
        .form-group {
            margin-bottom: 25px;
            position: relative;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #e0e0e0;
        }
        
        input {
            width: 100%;
            padding: 14px 15px;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            color: white;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        input:focus {
            outline: none;
            border-color: #ffd700;
            box-shadow: 0 0 0 2px rgba(255, 215, 0, 0.2);
            background: rgba(255, 255, 255, 0.12);
        }
        
        input::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }
        
        .btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
            border: none;
            border-radius: 10px;
            color: #1a1a2e;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            margin-bottom: 10px;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 7px 15px rgba(255, 215, 0, 0.4);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #6c757d 0%, #868e96 100%);
            color: white;
        }
        
        .btn-secondary:hover {
            box-shadow: 0 7px 15px rgba(108, 117, 125, 0.4);
        }
        
        .alert {
            padding: 12px 15px;
            margin-bottom: 20px;
            border-radius: 10px;
            font-weight: 500;
            text-align: center;
        }
        
        .alert-danger {
            background: rgba(220, 53, 69, 0.2);
            border: 1px solid rgba(220, 53, 69, 0.5);
            color: #f8d7da;
        }
        
        .alert-info {
            background: rgba(13, 110, 253, 0.2);
            border: 1px solid rgba(13, 110, 253, 0.5);
            color: #cfe2ff;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .logo-icon {
            font-size: 40px;
            color: #ffd700;
            margin-bottom: 10px;
        }
        
        .footer-text {
            text-align: center;
            margin-top: 25px;
            font-size: 14px;
            color: rgba(255, 255, 255, 0.6);
        }
        
        .current-rate {
            background: rgba(255, 193, 7, 0.2);
            border: 1px solid rgba(255, 193, 7, 0.5);
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 15px;
            text-align: center;
            font-size: 16px;
            font-weight: bold;
        }
        
        .admin-notice {
            background: rgba(220, 53, 69, 0.2);
            border: 1px solid rgba(220, 53, 69, 0.5);
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 15px;
            text-align: center;
            font-size: 14px;
        }
        
        .forgot-password {
            text-align: center;
            margin-top: 15px;
        }
        
        .forgot-password a {
            color: #ffd700;
            text-decoration: none;
        }
        
        .forgot-password a:hover {
            text-decoration: underline;
        }

        .btn-group {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }

        .btn-group .btn {
            margin-bottom: 0;
            flex: 1;
        }

        @media (max-width: 480px) {
            .login-container {
                margin: 20px;
                padding: 30px 20px;
            }
            
            .btn-group {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div id="particles-js"></div>
    
    <div class="login-container">
        <div class="logo">
            <div class="logo-icon">
                <?php echo $show_tasa_dolar ? '💵' : '🍽️'; ?>
            </div>
        </div>
        
        <h2>
            <?php echo $show_tasa_dolar ? 'Tasa del Dólar' : 'Iniciar Sesión'; ?>
        </h2>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($show_tasa_dolar): ?>
            <!-- Formulario para tasa del dólar (SOLO para Admin) -->
            <div class="admin-notice">
                👑 <strong>Acceso de Administrador</strong><br>
                Solo tú puedes actualizar la tasa de cambio.
            </div>
            
            <?php if ($tasa_actual): ?>
            <div class="current-rate">
                📊 Tasa actual: <strong><?php echo number_format($tasa_actual, 2); ?> Bs/$</strong>
            </div>
            <?php else: ?>
            <div class="alert alert-info">
                ℹ️ No hay tasa configurada. Por favor, ingresa una nueva tasa.
            </div>
            <?php endif; ?>
            
            <form method="POST" action="login.php">
                <div class="form-group">
                    <label for="tasa_dolar">💵 Nueva Tasa del Dólar (Bs/$):</label>
                    <input type="number" id="tasa_dolar" name="tasa_dolar" 
                           step="0.01" min="0.01" 
                           placeholder="Ej: 36.50"
                           value=""
                           required
                           autofocus>
                    <small style="color: #ccc; font-size: 12px; display: block; margin-top: 5px;">
                        Ingresa la nueva tasa de cambio. Esto actualizará automáticamente todos los precios.
                    </small>
                </div>
                
                <div class="btn-group">
                    <button type="submit" class="btn" name="tasa_action" value="update">
                        ✅ Actualizar Tasa
                    </button>
                    <button type="submit" class="btn btn-secondary" name="tasa_action" value="keep">
                        🔄 Mantener Actual
                    </button>
                </div>
            </form>
            
        <?php else: ?>
            <!-- Formulario de login normal -->
            <form method="POST" action="login.php">
                <div class="form-group">
                    <label for="nombre_usuario">👤 Nombre de Usuario:</label>
                    <input type="text" id="nombre_usuario" name="nombre_usuario" required 
                           placeholder="Ingresa tu usuario" 
                           value="<?php echo htmlspecialchars($_POST['nombre_usuario'] ?? ''); ?>"
                           autofocus>
                </div>
                
                <div class="form-group">
                    <label for="password">🔒 Contraseña:</label>
                    <input type="password" id="password" name="password" required 
                           placeholder="Ingresa tu contraseña">
                </div>
                
                <button type="submit" class="btn">🚀 Iniciar Sesión</button>
            </form>
            
            <div class="forgot-password">
                <a href="forgot_password.php">¿Olvidaste tu contraseña?</a>
            </div>
        <?php endif; ?>
        
        <div class="footer-text">
            Sistema de Gestión de Pedidos © <?php echo date('Y'); ?>
        </div>
    </div>

    <!-- Script de partículas -->
    <script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
    <script>
        // Inicializar partículas
        particlesJS('particles-js', {
            particles: {
                number: {
                    value: 80,
                    density: {
                        enable: true,
                        value_area: 800
                    }
                },
                color: {
                    value: "#ffd700"
                },
                shape: {
                    type: "circle",
                    stroke: {
                        width: 0,
                        color: "#000000"
                    }
                },
                opacity: {
                    value: 0.5,
                    random: true,
                    anim: {
                        enable: true,
                        speed: 1,
                        opacity_min: 0.1,
                        sync: false
                    }
                },
                size: {
                    value: 3,
                    random: true,
                    anim: {
                        enable: true,
                        speed: 2,
                        size_min: 0.1,
                        sync: false
                    }
                },
                line_linked: {
                    enable: true,
                    distance: 150,
                    color: "#ffed4e",
                    opacity: 0.4,
                    width: 1
                },
                move: {
                    enable: true,
                    speed: 2,
                    direction: "none",
                    random: true,
                    straight: false,
                    out_mode: "out",
                    bounce: false,
                    attract: {
                        enable: false,
                        rotateX: 600,
                        rotateY: 1200
                    }
                }
            },
            interactivity: {
                detect_on: "canvas",
                events: {
                    onhover: {
                        enable: true,
                        mode: "grab"
                    },
                    onclick: {
                        enable: true,
                        mode: "push"
                    },
                    resize: true
                },
                modes: {
                    grab: {
                        distance: 140,
                        line_linked: {
                            opacity: 1
                        }
                    },
                    push: {
                        particles_nb: 4
                    }
                }
            },
            retina_detect: true
        });

        // Enfocar automáticamente el campo correspondiente
        document.addEventListener('DOMContentLoaded', function() {
            const tasaInput = document.getElementById('tasa_dolar');
            const usuarioInput = document.getElementById('nombre_usuario');
            
            if (tasaInput) {
                tasaInput.focus();
            } else if (usuarioInput) {
                usuarioInput.focus();
            }
        });
    </script>
</body>
</html>