
<?php
// sistema_pedidos/admin/ajustes.php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
header("Location: ../acceso_denegado.php");
exit();
}

require_once '../includes/db_connection.php';

$mensaje = '';
$tipo_mensaje = '';

// Cargar configuración actual
$configuraciones = [];
try {
    $stmt = $pdo->query("SELECT clave, valor, tipo FROM configuraciones");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $configuraciones[$row['clave']] = $row['tipo'] === 'boolean' ? (bool)$row['valor'] : $row['valor'];
    }
} catch (PDOException $e) {
    error_log("Error cargando configuraciones: " . $e->getMessage());
}

// Procesar configuración general
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_general'])) {
    try {
        $pdo->beginTransaction();
        
        $configuraciones_a_guardar = [
            'nombre_sistema' => trim($_POST['nombre_sistema'] ?? ''),
            'moneda' => trim($_POST['moneda'] ?? '$'),
            'iva' => floatval($_POST['iva'] ?? 0),
            'numero_mesas' => intval($_POST['numero_mesa'] ?? 10),
            'tiempo_espera' => intval($_POST['tiempo_espera'] ?? 25)
        ];
        
        foreach ($configuraciones_a_guardar as $clave => $valor) {
            $stmt = $pdo->prepare("INSERT INTO configuraciones (clave, valor, tipo) 
                                  VALUES (?, ?, 'string') 
                                  ON DUPLICATE KEY UPDATE valor = ?, fecha_actualizacion = CURRENT_TIMESTAMP");
            $stmt->execute([$clave, $valor, $valor]);
        }
        
        $pdo->commit();
        $mensaje = 'Configuración general guardada correctamente.';
        $tipo_mensaje = 'success';
        
        // Recargar configuraciones
        $configuraciones = array_merge($configuraciones, $configuraciones_a_guardar);
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        $mensaje = 'Error al guardar la configuración: ' . $e->getMessage();
        $tipo_mensaje = 'error';
    }
}

// Procesar notificaciones
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_notificaciones'])) {
    try {
        $pdo->beginTransaction();
        
        $configuraciones_a_guardar = [
            'notificaciones_pedidos' => isset($_POST['notificaciones_pedidos']) ? 1 : 0,
            'notificaciones_inventario' => isset($_POST['notificaciones_inventario']) ? 1 : 0,
            'reportes_automaticos' => isset($_POST['reportes_automaticos']) ? 1 : 0
        ];
        
        foreach ($configuraciones_a_guardar as $clave => $valor) {
            $stmt = $pdo->prepare("INSERT INTO configuraciones (clave, valor, tipo) 
                                  VALUES (?, ?, 'boolean') 
                                  ON DUPLICATE KEY UPDATE valor = ?, fecha_actualizacion = CURRENT_TIMESTAMP");
            $stmt->execute([$clave, $valor, $valor]);
        }
        
        $pdo->commit();
        $mensaje = 'Configuración de notificaciones guardada correctamente.';
        $tipo_mensaje = 'success';
        
        // Recargar configuraciones
        $configuraciones = array_merge($configuraciones, $configuraciones_a_guardar);
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        $mensaje = 'Error al guardar las notificaciones: ' . $e->getMessage();
        $tipo_mensaje = 'error';
    }
}

// Procesar seguridad
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_seguridad'])) {
    try {
        $pdo->beginTransaction();
        
        $configuraciones_a_guardar = [
            'tiempo_sesion' => intval($_POST['tiempo_sesion'] ?? 60),
            'password_fuerte' => isset($_POST['password_fuerte']) ? 1 : 0,
            'habilitar_logs' => isset($_POST['habilitar_logs']) ? 1 : 0,
            'dias_logs' => intval($_POST['dias_logs'] ?? 30)
        ];
        
        foreach ($configuraciones_a_guardar as $clave => $valor) {
            $tipo = in_array($clave, ['tiempo_sesion', 'dias_logs']) ? 'number' : 'boolean';
            $stmt = $pdo->prepare("INSERT INTO configuraciones (clave, valor, tipo) 
                                  VALUES (?, ?, ?) 
                                  ON DUPLICATE KEY UPDATE valor = ?, fecha_actualizacion = CURRENT_TIMESTAMP");
            $stmt->execute([$clave, $valor, $tipo, $valor]);
        }
        
        $pdo->commit();
        $mensaje = 'Configuración de seguridad guardada correctamente.';
        $tipo_mensaje = 'success';
        
        // Recargar configuraciones
        $configuraciones = array_merge($configuraciones, $configuraciones_a_guardar);
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        $mensaje = 'Error al guardar la configuración de seguridad: ' . $e->getMessage();
        $tipo_mensaje = 'error';
    }
}

// Procesar backup de base de datos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generar_backup'])) {
    try {
        $backup_file = generarBackup($pdo);
        if ($backup_file) {
            $mensaje = 'Backup generado correctamente: ' . $backup_file;
            $tipo_mensaje = 'success';
        } else {
            $mensaje = 'Error al generar el backup.';
            $tipo_mensaje = 'error';
        }
    } catch (Exception $e) {
        $mensaje = 'Error al generar backup: ' . $e->getMessage();
        $tipo_mensaje = 'error';
    }
}

// Procesar limpieza del sistema
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['limpiar_sistema'])) {
    try {
        $resultado = limpiarSistema($pdo);
        $mensaje = $resultado ? 'Limpieza del sistema completada correctamente.' : 'No se encontraron datos para limpiar.';
        $tipo_mensaje = $resultado ? 'success' : 'info';
    } catch (Exception $e) {
        $mensaje = 'Error en la limpieza: ' . $e->getMessage();
        $tipo_mensaje = 'error';
    }
}

// Función para generar backup
function generarBackup($pdo) {
    $backup_dir = '../backups/';
    if (!is_dir($backup_dir)) {
        mkdir($backup_dir, 0755, true);
    }
    
    $backup_file = $backup_dir . 'backup_' . date('Y-m-d_H-i-s') . '.sql';
    $output = "";
    
    // Obtener todas las tablas
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($tables as $table) {
        // Obtener estructura de la tabla
        $output .= "--\n-- Estructura para tabla: $table\n--\n";
        $create_table = $pdo->query("SHOW CREATE TABLE $table")->fetch(PDO::FETCH_ASSOC);
        $output .= $create_table['Create Table'] . ";\n\n";
        
        // Obtener datos de la tabla
        $rows = $pdo->query("SELECT * FROM $table")->fetchAll(PDO::FETCH_ASSOC);
        if (count($rows) > 0) {
            $output .= "--\n-- Volcado de datos para tabla: $table\n--\n";
            foreach ($rows as $row) {
                $columns = array_map(function($value) use ($pdo) {
                    return $value === null ? 'NULL' : $pdo->quote($value);
                }, array_values($row));
                
                $output .= "INSERT INTO $table VALUES (" . implode(', ', $columns) . ");\n";
            }
            $output .= "\n";
        }
    }
    
    return file_put_contents($backup_file, $output) ? basename($backup_file) : false;
}

// Función para limpiar sistema
function limpiarSistema($pdo) {
    $limpiado = false;
    
    try {
        $pdo->beginTransaction();
        
        // Limpiar logs antiguos (más de 30 días)
        $stmt = $pdo->prepare("DELETE FROM logs WHERE fecha < DATE_SUB(NOW(), INTERVAL 30 DAY)");
        $stmt->execute();
        if ($stmt->rowCount() > 0) $limpiado = true;
        
        // Limpiar pedidos completados antiguos (más de 90 días)
        $stmt = $pdo->prepare("DELETE FROM pedidos WHERE estado = 'completado' AND fecha < DATE_SUB(NOW(), INTERVAL 90 DAY)");
        $stmt->execute();
        if ($stmt->rowCount() > 0) $limpiado = true;
        
        // Optimizar tablas
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($tables as $table) {
            $pdo->exec("OPTIMIZE TABLE $table");
        }
        
        $pdo->commit();
        return $limpiado;
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        throw $e;
    }
}

// Obtener estadísticas para mostrar
$estadisticas = [];
try {
    // Total pedidos
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM pedidos");
    $estadisticas['total_pedidos'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total productos
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM productos WHERE disponible = 1");
    $estadisticas['total_productos'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total usuarios
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios");
    $estadisticas['total_usuarios'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Tamaño base de datos
    $stmt = $pdo->query("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) as size 
                         FROM information_schema.tables 
                         WHERE table_schema = DATABASE()");
    $estadisticas['tamano_bd'] = $stmt->fetch(PDO::FETCH_ASSOC)['size'] ?? 0;
    
} catch (PDOException $e) {
    error_log("Error obteniendo estadísticas: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajustes del Sistema - Sistema de Pedidos</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Mantener todos los estilos CSS anteriores... */
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

        .ajustes-container {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
			margin-left:100px;
        }
        .ajustes-header {
            margin-bottom: 2rem;
        }

        .ajustes-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--bg-dark);
            margin-bottom: 0.5rem;
        }

        .ajustes-subtitle {
            color: var(--secondary-color);
            font-size: 1.1rem;
        }

        .ajustes-grid {
            display: grid;
            grid-template-columns: 250px 1fr;
            gap: 2rem;
            max-width: 1200px;
        }

        .ajustes-sidebar {
            background: var(--bg-card);
            padding: 1.5rem;
            border-radius: 1rem;
            box-shadow: var(--shadow);
            height: fit-content;
            position: sticky;
            top: 2rem;
        }

        .ajustes-main {
            background: var(--bg-card);
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: var(--shadow);
        }

        .nav-ajustes {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .nav-item-ajustes {
            margin-bottom: 0.5rem;
        }

        .nav-link-ajustes {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            text-decoration: none;
            color: var(--secondary-color);
            border-radius: 0.75rem;
            transition: var(--transition);
            font-weight: 500;
        }

        .nav-link-ajustes:hover,
        .nav-link-ajustes.active {
            background: rgba(37, 99, 235, 0.1);
            color: var(--primary-color);
        }

        .nav-link-ajustes.active {
            background: var(--primary-color);
            color: white;
        }

        .section-ajustes {
            display: none;
            animation: fadeIn 0.3s ease;
        }

        .section-ajustes.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
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

        .btn-success {
            background: var(--success-color);
            color: white;
        }

        .btn-success:hover {
            background: #059669;
        }

        .btn-warning {
            background: var(--warning-color);
            color: white;
        }

        .btn-warning:hover {
            background: #d97706;
        }

        .btn-danger {
            background: var(--danger-color);
            color: white;
        }

        .btn-danger:hover {
            background: #dc2626;
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

        .config-card {
            background: #f8fafc;
            padding: 1.5rem;
            border-radius: 0.75rem;
            margin-bottom: 1.5rem;
            border-left: 4px solid var(--primary-color);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--bg-card);
            padding: 1.5rem;
            border-radius: 0.75rem;
            box-shadow: var(--shadow);
            text-align: center;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0.25rem;
        }

        .stat-label {
            color: var(--secondary-color);
            font-size: 0.9rem;
        }

        .danger-zone {
            background: #fef2f2;
            border: 2px solid var(--danger-color);
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin-top: 2rem;
        }

        .danger-title {
            color: var(--danger-color);
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }

        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .toggle-slider {
            background-color: var(--success-color);
        }

        input:checked + .toggle-slider:before {
            transform: translateX(26px);
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .ajustes-container {
                margin-left: 0;
                padding: 1rem;
            }

            .ajustes-grid {
                grid-template-columns: 1fr;
            }

            .ajustes-sidebar {
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

    <div class="ajustes-container">
        <!-- Header -->
        <div class="ajustes-header">
            <h1 class="ajustes-title">
                <i class="fas fa-cogs"></i>
                Ajustes del Sistema
            </h1>
            <p class="ajustes-subtitle">Configuración global y administración del sistema</p>
        </div>

        <!-- Mensajes -->
        <?php if ($mensaje): ?>
            <div class="alert alert-<?php echo $tipo_mensaje === 'success' ? 'success' : 'error'; ?>">
                <i class="fas fa-<?php echo $tipo_mensaje === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>

        <div class="ajustes-grid">
            <!-- Sidebar de Navegación -->
            <div class="ajustes-sidebar">
                <ul class="nav-ajustes">
                    <li class="nav-item-ajustes">
                        <a href="#general" class="nav-link-ajustes active" data-section="general">
                            <i class="fas fa-sliders-h"></i>
                            General
                        </a>
                    </li>
                    <li class="nav-item-ajustes">
                        <a href="#notificaciones" class="nav-link-ajustes" data-section="notificaciones">
                            <i class="fas fa-bell"></i>
                            Notificaciones
                        </a>
                    </li>
                    <li class="nav-item-ajustes">
                        <a href="#backup" class="nav-link-ajustes" data-section="backup">
                            <i class="fas fa-database"></i>
                            Backup & Restore
                        </a>
                    </li>
                    <li class="nav-item-ajustes">
                        <a href="#seguridad" class="nav-link-ajustes" data-section="seguridad">
                            <i class="fas fa-shield-alt"></i>
                            Seguridad
                        </a>
                    </li>
                    <li class="nav-item-ajustes">
                        <a href="#mantenimiento" class="nav-link-ajustes" data-section="mantenimiento">
                            <i class="fas fa-tools"></i>
                            Mantenimiento
                        </a>
                    </li>
                    <li class="nav-item-ajustes">
                        <a href="#avanzado" class="nav-link-ajustes" data-section="avanzado">
                            <i class="fas fa-code"></i>
                            Avanzado
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Contenido Principal -->
            <div class="ajustes-main">
                <!-- Sección General -->
                <div id="general" class="section-ajustes active">
                    <h2 class="section-title">Configuración General</h2>
                    
                    <form method="POST">
                        <div class="config-card">
                            <h3 style="margin-bottom: 1rem; color: var(--text-dark);">
                                <i class="fas fa-store"></i>
                                Información del Negocio
                            </h3>
                            
                            <div class="form-group">
                                <label class="form-label" for="nombre_sistema">Nombre del Sistema</label>
                                <input type="text" 
                                       id="nombre_sistema" 
                                       name="nombre_sistema" 
                                       class="form-control" 
                                       value="<?php echo htmlspecialchars($configuraciones['nombre_sistema'] ?? 'Sistema de Pedidos'); ?>"
                                       required>
                                <div class="form-help">Este nombre aparecerá en el header y reportes.</div>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="moneda">Símbolo de Moneda</label>
                                <select id="moneda" name="moneda" class="form-control">
                                    <option value="$" <?php echo ($configuraciones['moneda'] ?? '$') === '$' ? 'selected' : ''; ?>>Dólar ($)</option>
                                    <option value="€" <?php echo ($configuraciones['moneda'] ?? '$') === '€' ? 'selected' : ''; ?>>Euro (€)</option>
                                    <option value="£" <?php echo ($configuraciones['moneda'] ?? '$') === '£' ? 'selected' : ''; ?>>Libra (£)</option>
                                    <option value="¥" <?php echo ($configuraciones['moneda'] ?? '$') === '¥' ? 'selected' : ''; ?>>Yen (¥)</option>
                                    <option value="₡" <?php echo ($configuraciones['moneda'] ?? '$') === '₡' ? 'selected' : ''; ?>>Colón (₡)</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="iva">Porcentaje de IVA (%)</label>
                                <input type="number" 
                                       id="iva" 
                                       name="iva" 
                                       class="form-control" 
                                       value="<?php echo htmlspecialchars($configuraciones['iva'] ?? '12'); ?>" 
                                       min="0" 
                                       max="100" 
                                       step="0.1">
                                <div class="form-help">Impuesto al Valor Agregado aplicado a las ventas.</div>
                            </div>
                        </div>

                        <div class="config-card">
                            <h3 style="margin-bottom: 1rem; color: var(--text-dark);">
                                <i class="fas fa-receipt"></i>
                                Configuración de Pedidos
                            </h3>
                            
                            <div class="form-group">
                                <label class="form-label" for="numero_mesa">Numeración de Mesas</label>
                                <input type="number" 
                                       id="numero_mesa" 
                                       name="numero_mesa" 
                                       class="form-control" 
                                       value="<?php echo htmlspecialchars($configuraciones['numero_mesas'] ?? '10'); ?>" 
                                       min="1" 
                                       max="50">
                                <div class="form-help">Número total de mesas disponibles.</div>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="tiempo_espera">Tiempo de Espera Estimado (min)</label>
                                <input type="number" 
                                       id="tiempo_espera" 
                                       name="tiempo_espera" 
                                       class="form-control" 
                                       value="<?php echo htmlspecialchars($configuraciones['tiempo_espera'] ?? '25'); ?>" 
                                       min="5" 
                                       max="120">
                                <div class="form-help">Tiempo promedio de preparación para informar a clientes.</div>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" name="guardar_general" class="btn btn-primary">
                                <i class="fas fa-save"></i>
                                Guardar Configuración
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Sección Notificaciones -->
                <div id="notificaciones" class="section-ajustes">
                    <h2 class="section-title">Configuración de Notificaciones</h2>
                    
                    <form method="POST">
                        <div class="config-card">
                            <h3 style="margin-bottom: 1rem; color: var(--text-dark);">
                                <i class="fas fa-bell"></i>
                                Preferencias de Notificaciones
                            </h3>
                            
                            <div class="form-group">
                                <label style="display: flex; align-items: center; gap: 1rem; cursor: pointer;">
                                    <div class="toggle-switch">
                                        <input type="checkbox" name="notificaciones_pedidos" value="1" 
                                               <?php echo ($configuraciones['notificaciones_pedidos'] ?? true) ? 'checked' : ''; ?>>
                                        <span class="toggle-slider"></span>
                                    </div>
                                    <span>Notificaciones de nuevos pedidos</span>
                                </label>
                                <div class="form-help">Recibir alertas cuando lleguen nuevos pedidos a cocina.</div>
                            </div>

                            <div class="form-group">
                                <label style="display: flex; align-items: center; gap: 1rem; cursor: pointer;">
                                    <div class="toggle-switch">
                                        <input type="checkbox" name="notificaciones_inventario" value="1"
                                               <?php echo ($configuraciones['notificaciones_inventario'] ?? true) ? 'checked' : ''; ?>>
                                        <span class="toggle-slider"></span>
                                    </div>
                                    <span>Recordatorios de inventario bajo</span>
                                </label>
                                <div class="form-help">Alertas cuando los productos estén por agotarse.</div>
                            </div>

                            <div class="form-group">
                                <label style="display: flex; align-items: center; gap: 1rem; cursor: pointer;">
                                    <div class="toggle-switch">
                                        <input type="checkbox" name="reportes_automaticos" value="1"
                                               <?php echo ($configuraciones['reportes_automaticos'] ?? false) ? 'checked' : ''; ?>>
                                        <span class="toggle-slider"></span>
                                    </div>
                                    <span>Reportes diarios automáticos</span>
                                </label>
                                <div class="form-help">Envío automático de reportes de ventas por email.</div>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" name="guardar_notificaciones" class="btn btn-primary">
                                <i class="fas fa-save"></i>
                                Guardar Preferencias
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Sección Backup & Restore -->
                <div id="backup" class="section-ajustes">
                    <h2 class="section-title">Backup & Restore</h2>
                    
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $estadisticas['tamano_bd'] ?? '0'; ?> MB</div>
                            <div class="stat-label">Tamaño Base de Datos</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $estadisticas['total_pedidos'] ?? '0'; ?></div>
                            <div class="stat-label">Pedidos Totales</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $estadisticas['total_productos'] ?? '0'; ?></div>
                            <div class="stat-label">Productos Activos</div>
                        </div>
                    </div>

                    <div class="config-card">
                        <h3 style="margin-bottom: 1rem; color: var(--text-dark);">
                            <i class="fas fa-download"></i>
                            Generar Backup
                        </h3>
                        <p style="color: var(--secondary-color); margin-bottom: 1.5rem;">
                            Crea una copia de seguridad de toda la base de datos del sistema.
                        </p>
                        
                        <form method="POST">
                            <button type="submit" name="generar_backup" class="btn btn-success">
                                <i class="fas fa-database"></i>
                                Generar Backup Ahora
                            </button>
                        </form>
                    </div>

                    <div class="config-card">
                        <h3 style="margin-bottom: 1rem; color: var(--text-dark);">
                            <i class="fas fa-upload"></i>
                            Restaurar Backup
                        </h3>
                        <p style="color: var(--secondary-color); margin-bottom: 1.5rem;">
                            Restaura el sistema desde un archivo de backup anterior.
                        </p>
                        
                        <div class="form-group">
                            <label class="form-label" for="archivo_backup">Seleccionar Archivo de Backup</label>
                            <input type="file" id="archivo_backup" class="form-control" accept=".sql,.backup">
                        </div>
                        
                        <button type="button" class="btn btn-warning" onclick="alert('Función de restauración en desarrollo')">
                            <i class="fas fa-history"></i>
                            Restaurar Backup
                        </button>
                    </div>
                </div>

                <!-- Sección Seguridad -->
                <div id="seguridad" class="section-ajustes">
                    <h2 class="section-title">Configuración de Seguridad</h2>
                    
                    <form method="POST">
                        <div class="config-card">
                            <h3 style="margin-bottom: 1rem; color: var(--text-dark);">
                                <i class="fas fa-user-lock"></i>
                                Controles de Acceso
                            </h3>
                            
                            <div class="form-group">
                                <label class="form-label" for="tiempo_sesion">Tiempo de Sesión (minutos)</label>
                                <input type="number" 
                                       id="tiempo_sesion" 
                                       name="tiempo_sesion" 
                                       class="form-control" 
                                       value="<?php echo htmlspecialchars($configuraciones['tiempo_sesion'] ?? '60'); ?>" 
                                       min="15" 
                                       max="480">
                                <div class="form-help">Tiempo máximo de inactividad antes de cerrar sesión automáticamente.</div>
                            </div>

                            <div class="form-group">
                                <label style="display: flex; align-items: center; gap: 1rem; cursor: pointer;">
                                    <div class="toggle-switch">
                                        <input type="checkbox" name="password_fuerte" value="1"
                                               <?php echo ($configuraciones['password_fuerte'] ?? true) ? 'checked' : ''; ?>>
                                        <span class="toggle-slider"></span>
                                    </div>
                                    <span>Requerir contraseña fuerte</span>
                                </label>
                                <div class="form-help">Las contraseñas deben incluir mayúsculas, números y símbolos.</div>
                            </div>
                        </div>

                        <div class="config-card">
                            <h3 style="margin-bottom: 1rem; color: var(--text-dark);">
                                <i class="fas fa-history"></i>
                                Registro de Actividad
                            </h3>
                            
                            <div class="form-group">
                                <label style="display: flex; align-items: center; gap: 1rem; cursor: pointer;">
                                    <div class="toggle-switch">
                                        <input type="checkbox" name="habilitar_logs" value="1"
                                               <?php echo ($configuraciones['habilitar_logs'] ?? true) ? 'checked' : ''; ?>>
                                        <span class="toggle-slider"></span>
                                    </div>
                                    <span>Habilitar logs del sistema</span>
                                </label>
                                <div class="form-help">Registrar todas las actividades importantes del sistema.</div>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="dias_logs">Retención de Logs (días)</label>
                                <input type="number" 
                                       id="dias_logs" 
                                       name="dias_logs" 
                                       class="form-control" 
                                       value="<?php echo htmlspecialchars($configuraciones['dias_logs'] ?? '30'); ?>" 
                                       min="7" 
                                       max="365">
                                <div class="form-help">Número de días que se conservarán los registros de actividad.</div>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" name="guardar_seguridad" class="btn btn-primary">
                                <i class="fas fa-save"></i>
                                Guardar Configuración de Seguridad
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Sección Mantenimiento -->
                <div id="mantenimiento" class="section-ajustes">
                    <h2 class="section-title">Mantenimiento del Sistema</h2>
                    
                    <div class="config-card">
                        <h3 style="margin-bottom: 1rem; color: var(--text-dark);">
                            <i class="fas fa-broom"></i>
                            Limpieza del Sistema
                        </h3>
                        <p style="color: var(--secondary-color); margin-bottom: 1.5rem;">
                            Elimina datos temporales y optimiza el rendimiento del sistema.
                        </p>
                        
                        <form method="POST">
                            <button type="submit" name="limpiar_sistema" class="btn btn-warning">
                                <i class="fas fa-magic"></i>
                                Ejecutar Limpieza
                            </button>
                        </form>
                    </div>

                    <div class="config-card">
                        <h3 style="margin-bottom: 1rem; color: var(--text-dark);">
                            <i class="fas fa-chart-line"></i>
                            Estadísticas del Sistema
                        </h3>
                        
                        <div class="stats-grid">
                            <div class="stat-card">
                                <div class="stat-value"><?php echo $estadisticas['total_pedidos'] ?? '0'; ?></div>
                                <div class="stat-label">Pedidos Totales</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-value"><?php echo $estadisticas['total_productos'] ?? '0'; ?></div>
                                <div class="stat-label">Productos Activos</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-value"><?php echo $estadisticas['total_usuarios'] ?? '0'; ?></div>
                                <div class="stat-label">Usuarios Registrados</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-value"><?php echo $estadisticas['tamano_bd'] ?? '0'; ?> MB</div>
                                <div class="stat-label">Espacio Usado</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sección Avanzado -->
                <div id="avanzado" class="section-ajustes">
                    <h2 class="section-title">Configuración Avanzada</h2>
                    
                    <div class="config-card">
                        <h3 style="margin-bottom: 1rem; color: var(--text-dark);">
                            <i class="fas fa-code"></i>
                            Configuración de API
                        </h3>
                        
                        <div class="form-group">
                            <label class="form-label" for="api_key">API Key</label>
                            <div style="display: flex; gap: 0.5rem;">
                                <input type="text" 
                                       id="api_key" 
                                       class="form-control" 
                                       value="<?php echo htmlspecialchars($configuraciones['api_key'] ?? 'sk_live_**********'); ?>" 
                                       readonly
                                       style="flex: 1;">
                                <button type="button" class="btn btn-secondary" onclick="generarNuevaAPIKey()">
                                    <i class="fas fa-sync"></i>
                                </button>
                            </div>
                            <div class="form-help">Clave para integraciones con otros sistemas.</div>
                        </div>
                    </div>

                    <div class="danger-zone">
                        <h3 class="danger-title">
                            <i class="fas fa-exclamation-triangle"></i>
                            Zona de Peligro
                        </h3>
                        <p style="color: var(--secondary-color); margin-bottom: 1.5rem;">
                            Estas acciones son irreversibles y pueden afectar el funcionamiento del sistema.
                        </p>
                        
                        <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                            <button type="button" class="btn btn-danger" onclick="confirmarAccion('limpiar_todos_datos')">
                                <i class="fas fa-trash"></i>
                                Limpiar Todos los Datos
                            </button>
                            <button type="button" class="btn btn-danger" onclick="confirmarAccion('resetear_contrasenas')">
                                <i class="fas fa-users"></i>
                                Resetear Contraseñas
                            </button>
                            <button type="button" class="btn btn-danger" onclick="confirmarAccion('desactivar_sistema')">
                                <i class="fas fa-ban"></i>
                                Desactivar Sistema
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Navegación entre secciones
        document.querySelectorAll('.nav-link-ajustes').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Remover active de todos los links
                document.querySelectorAll('.nav-link-ajustes').forEach(l => {
                    l.classList.remove('active');
                });
                
                // Ocultar todas las secciones
                document.querySelectorAll('.section-ajustes').forEach(section => {
                    section.classList.remove('active');
                });
                
                // Activar link y sección seleccionada
                this.classList.add('active');
                const targetSection = this.getAttribute('data-section');
                document.getElementById(targetSection).classList.add('active');
            });
        });

        // Confirmación para acciones peligrosas
        function confirmarAccion(accion) {
            const mensajes = {
                'limpiar_todos_datos': '¿Estás seguro de que quieres eliminar TODOS los datos del sistema? Esta acción es irreversible y eliminará todos los pedidos, productos y usuarios.',
                'resetear_contrasenas': '¿Estás seguro de que quieres resetear todas las contraseñas? Todos los usuarios tendrán que establecer nuevas contraseñas.',
                'desactivar_sistema': '¿Estás seguro de que quieres desactivar el sistema? El sistema dejará de estar disponible hasta que sea reactivado.'
            };
            
            if (confirm(mensajes[accion])) {
                // Aquí iría la llamada AJAX para ejecutar la acción
                alert('Acción ejecutada (simulación) - En una implementación real, esto haría una llamada AJAX al servidor');
            }
        }

        // Generar nueva API Key
        function generarNuevaAPIKey() {
            if (confirm('¿Generar una nueva API Key? Esto invalidará la clave actual.')) {
                // Simulación de generación de nueva clave
                const nuevaClave = 'sk_live_' + Math.random().toString(36).substr(2, 10);
                document.getElementById('api_key').value = nuevaClave;
                alert('Nueva API Key generada: ' + nuevaClave);
            }
        }

        // Efectos de loading en botones
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function() {
                const submitBtn = this.querySelector('button[type="submit"]');
                if (submitBtn) {
                    const originalText = submitBtn.innerHTML;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
                    submitBtn.disabled = true;
                    
                    // Restaurar después de 2 segundos (simulación)
                    setTimeout(() => {
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    }, 2000);
                }
            });
        });

        // Validación de campos numéricos
        document.querySelectorAll('input[type="number"]').forEach(input => {
            input.addEventListener('change', function() {
                const min = parseInt(this.getAttribute('min')) || 0;
                const max = parseInt(this.getAttribute('max')) || Infinity;
                const value = parseInt(this.value) || 0;
                
                if (value < min) {
                    this.value = min;
                } else if (value > max) {
                    this.value = max;
                }
            });
        });
    </script>
</body>
</html>
[file content end]