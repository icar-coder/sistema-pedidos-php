<?php
// sistema_pedidos/auth/profile.php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once '../includes/db_connection.php';

// Obtener datos del usuario actual
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT idUsuario, nombre_usuario, rol, fechaRegistro FROM usuarios WHERE idUsuario = ?");
$stmt->execute([$user_id]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    header("Location: login.php");
    exit();
}

// Procesar actualización de perfil
$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre_usuario = trim($_POST['nombre_usuario'] ?? '');
    $password_actual = $_POST['password_actual'] ?? '';
    $nuevo_password = $_POST['nuevo_password'] ?? '';
    $confirmar_password = $_POST['confirmar_password'] ?? '';
    
    // Validaciones
    if (empty($nombre_usuario)) {
        $mensaje = 'El nombre de usuario es requerido.';
        $tipo_mensaje = 'error';
    } else {
        try {
            // Verificar si el nombre de usuario ya existe (excluyendo el usuario actual)
            $stmt = $pdo->prepare("SELECT idUsuario FROM usuarios WHERE nombre_usuario = ? AND idUsuario != ?");
            $stmt->execute([$nombre_usuario, $user_id]);
            
            if ($stmt->fetch()) {
                $mensaje = 'El nombre de usuario ya está en uso.';
                $tipo_mensaje = 'error';
            } else {
                // Verificar contraseña actual si se quiere cambiar la contraseña
                if (!empty($nuevo_password)) {
                    $stmt = $pdo->prepare("SELECT contrasena FROM usuarios WHERE idUsuario = ?");
                    $stmt->execute([$user_id]);
                    $usuario_db = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!password_verify($password_actual, $usuario_db['contrasena'])) {
                        $mensaje = 'La contraseña actual es incorrecta.';
                        $tipo_mensaje = 'error';
                    } elseif ($nuevo_password !== $confirmar_password) {
                        $mensaje = 'Las nuevas contraseñas no coinciden.';
                        $tipo_mensaje = 'error';
                    } elseif (strlen($nuevo_password) < 6) {
                        $mensaje = 'La nueva contraseña debe tener al menos 6 caracteres.';
                        $tipo_mensaje = 'error';
                    } else {
                        // Actualizar con nueva contraseña
                        $hashed_password = password_hash($nuevo_password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE usuarios SET nombre_usuario = ?, contrasena = ? WHERE idUsuario = ?");
                        $stmt->execute([$nombre_usuario, $hashed_password, $user_id]);
                        
                        $_SESSION['user_name'] = $nombre_usuario;
                        $mensaje = 'Perfil y contraseña actualizados correctamente.';
                        $tipo_mensaje = 'success';
                    }
                } else {
                    // Actualizar solo el nombre de usuario
                    $stmt = $pdo->prepare("UPDATE usuarios SET nombre_usuario = ? WHERE idUsuario = ?");
                    $stmt->execute([$nombre_usuario, $user_id]);
                    
                    $_SESSION['user_name'] = $nombre_usuario;
                    $mensaje = 'Perfil actualizado correctamente.';
                    $tipo_mensaje = 'success';
                }
                
                // Actualizar datos locales
                $usuario['nombre_usuario'] = $nombre_usuario;
            }
        } catch (Exception $e) {
            $mensaje = 'Error al actualizar el perfil: ' . $e->getMessage();
            $tipo_mensaje = 'error';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - Sistema de Pedidos</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2563eb;
            --primary-dark: #1d4ed8;
            --secondary-color: #64748b;
            --accent-color: #f59e0b;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --text-light: #f8fafc;
            --text-dark: #1e293b;
            --bg-dark: #0f172a;
            --bg-light: #f8fafc;
            --bg-card: #ffffff;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --transition: all 0.3s ease;
        }

        .profile-container {
            margin-left: 280px;
            padding: 2rem;
            min-height: 100vh;
            background: var(--bg-light);
        }

        .profile-header {
            margin-bottom: 2rem;
        }

        .profile-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--bg-dark);
            margin-bottom: 0.5rem;
        }

        .profile-subtitle {
            color: var(--secondary-color);
            font-size: 1.1rem;
        }

        .profile-content {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 2rem;
            max-width: 1200px;
        }

        .profile-sidebar {
            background: var(--bg-card);
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: var(--shadow);
            height: fit-content;
            position: sticky;
            top: 2rem;
        }

        .profile-main {
            background: var(--bg-card);
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: var(--shadow);
        }

        .user-avatar {
            text-align: center;
            margin-bottom: 2rem;
        }

        .avatar-circle {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            color: white;
            font-size: 2.5rem;
            font-weight: bold;
        }

        .user-name {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }

        .user-role {
            background: var(--primary-color);
            color: white;
            padding: 0.25rem 1rem;
            border-radius: 1rem;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            display: inline-block;
        }

        .user-stats {
            margin-top: 2rem;
        }

        .stat-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .stat-item:last-child {
            border-bottom: none;
        }

        .stat-label {
            color: var(--secondary-color);
            font-size: 0.9rem;
        }

        .stat-value {
            font-weight: 600;
            color: var(--text-dark);
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--bg-dark);
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid #e2e8f0;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e2e8f0;
            border-radius: 0.75rem;
            font-size: 1rem;
            transition: var(--transition);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .form-help {
            font-size: 0.8rem;
            color: var(--secondary-color);
            margin-top: 0.25rem;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            font-size: 1rem;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: var(--secondary-color);
            color: white;
        }

        .btn-secondary:hover {
            background: #475569;
        }

        .alert {
            padding: 1rem;
            border-radius: 0.75rem;
            margin-bottom: 1.5rem;
            border-left: 4px solid;
        }

        .alert-success {
            background: #f0fdf4;
            border-color: var(--success-color);
            color: #166534;
        }

        .alert-error {
            background: #fef2f2;
            border-color: var(--danger-color);
            color: #dc2626;
        }

        .password-section {
            background: #f8fafc;
            padding: 1.5rem;
            border-radius: 0.75rem;
            margin-top: 2rem;
        }

        .password-toggle {
            position: relative;
        }

        .password-toggle .form-control {
            padding-right: 3rem;
        }

        .toggle-password {
            position: absolute;
            right: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--secondary-color);
            cursor: pointer;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .profile-container {
                margin-left: 0;
                padding: 1rem;
            }

            .profile-content {
                grid-template-columns: 1fr;
            }

            .profile-sidebar {
                position: static;
            }

            .form-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="profile-container">
        <!-- Header -->
        <div class="profile-header">
            <h1 class="profile-title">
                <i class="fas fa-user-cog"></i>
                Mi Perfil
            </h1>
            <p class="profile-subtitle">Gestiona tu información personal y configuración de cuenta</p>
        </div>

        <!-- Mensajes -->
        <?php if ($mensaje): ?>
            <div class="alert alert-<?php echo $tipo_mensaje === 'success' ? 'success' : 'error'; ?>">
                <i class="fas fa-<?php echo $tipo_mensaje === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>

        <div class="profile-content">
            <!-- Sidebar con información del usuario -->
            <div class="profile-sidebar">
                <div class="user-avatar">
                    <div class="avatar-circle">
                        <?php echo strtoupper(substr($usuario['nombre_usuario'], 0, 1)); ?>
                    </div>
                    <div class="user-name"><?php echo htmlspecialchars($usuario['nombre_usuario']); ?></div>
                    <div class="user-role"><?php echo htmlspecialchars($usuario['rol']); ?></div>
                </div>

                <div class="user-stats">
                    <div class="stat-item">
                        <span class="stat-label">Miembro desde</span>
                        <span class="stat-value"><?php echo date('d/m/Y', strtotime($usuario['fechaRegistro'])); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">ID de Usuario</span>
                        <span class="stat-value">#<?php echo $usuario['idUsuario']; ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Estado</span>
                        <span class="stat-value" style="color: var(--success-color);">Activo</span>
                    </div>
                </div>

                <div style="margin-top: 2rem; padding: 1rem; background: #f0f9ff; border-radius: 0.75rem; border-left: 4px solid var(--primary-color);">
                    <h4 style="margin-bottom: 0.5rem; color: var(--primary-color); font-size: 0.9rem;">
                        <i class="fas fa-info-circle"></i>
                        Información de Seguridad
                    </h4>
                    <p style="font-size: 0.8rem; color: var(--secondary-color); margin: 0;">
                        • Usa una contraseña segura<br>
                        • No compartas tus credenciales<br>
                        • Cierra sesión en dispositivos públicos
                    </p>
                </div>
            </div>

            <!-- Formulario principal -->
            <div class="profile-main">
                <h2 class="section-title">Información Personal</h2>
                
                <form method="POST" id="profileForm">
                    <div class="form-group">
                        <label class="form-label" for="nombre_usuario">
                            <i class="fas fa-user"></i>
                            Nombre de Usuario
                        </label>
                        <input type="text" 
                               id="nombre_usuario" 
                               name="nombre_usuario" 
                               class="form-control" 
                               value="<?php echo htmlspecialchars($usuario['nombre_usuario']); ?>" 
                               required>
                        <div class="form-help">Este es el nombre que verán otros usuarios en el sistema.</div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="rol">
                            <i class="fas fa-shield-alt"></i>
                            Rol del Sistema
                        </label>
                        <input type="text" 
                               id="rol" 
                               class="form-control" 
                               value="<?php echo htmlspecialchars($usuario['rol']); ?>" 
                               disabled 
                               style="background: #f8fafc;">
                        <div class="form-help">El rol solo puede ser modificado por un administrador.</div>
                    </div>

                    <!-- Sección de Cambio de Contraseña -->
                    <div class="password-section">
                        <h3 style="margin-bottom: 1rem; color: var(--text-dark);">
                            <i class="fas fa-key"></i>
                            Cambiar Contraseña
                        </h3>
                        <p style="color: var(--secondary-color); margin-bottom: 1.5rem; font-size: 0.9rem;">
                            Completa solo si deseas cambiar tu contraseña actual.
                        </p>

                        <div class="form-group">
                            <label class="form-label" for="password_actual">Contraseña Actual</label>
                            <div class="password-toggle">
                                <input type="password" 
                                       id="password_actual" 
                                       name="password_actual" 
                                       class="form-control">
                                <button type="button" class="toggle-password" data-target="password_actual">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="nuevo_password">Nueva Contraseña</label>
                            <div class="password-toggle">
                                <input type="password" 
                                       id="nuevo_password" 
                                       name="nuevo_password" 
                                       class="form-control">
                                <button type="button" class="toggle-password" data-target="nuevo_password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="form-help">Mínimo 6 caracteres.</div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="confirmar_password">Confirmar Nueva Contraseña</label>
                            <div class="password-toggle">
                                <input type="password" 
                                       id="confirmar_password" 
                                       name="confirmar_password" 
                                       class="form-control">
                                <button type="button" class="toggle-password" data-target="confirmar_password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            Guardar Cambios
                        </button>
                        <a href="<?php echo $_SESSION['role'] === 'Admin' ? '../admin/index.php' : '../cajero/index.php'; ?>" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i>
                            Volver al Dashboard
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Toggle para mostrar/ocultar contraseñas
        document.querySelectorAll('.toggle-password').forEach(button => {
            button.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                const passwordInput = document.getElementById(targetId);
                const icon = this.querySelector('i');
                
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    icon.className = 'fas fa-eye-slash';
                } else {
                    passwordInput.type = 'password';
                    icon.className = 'fas fa-eye';
                }
            });
        });

        // Validación del formulario
        document.getElementById('profileForm').addEventListener('submit', function(e) {
            const nuevoPassword = document.getElementById('nuevo_password').value;
            const confirmarPassword = document.getElementById('confirmar_password').value;
            const passwordActual = document.getElementById('password_actual').value;
            
            // Si se llena algún campo de nueva contraseña, validar todos
            if (nuevoPassword || confirmarPassword || passwordActual) {
                if (!passwordActual) {
                    alert('Debes ingresar tu contraseña actual para cambiar la contraseña.');
                    e.preventDefault();
                    return;
                }
                
                if (nuevoPassword !== confirmarPassword) {
                    alert('Las nuevas contraseñas no coinciden.');
                    e.preventDefault();
                    return;
                }
                
                if (nuevoPassword.length > 0 && nuevoPassword.length < 6) {
                    alert('La nueva contraseña debe tener al menos 6 caracteres.');
                    e.preventDefault();
                    return;
                }
            }
        });

        // Efecto de carga al enviar el formulario
        document.getElementById('profileForm').addEventListener('submit', function() {
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
            submitBtn.disabled = true;
        });
    </script>
</body>
</html>