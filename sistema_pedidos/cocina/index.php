<?php
// sistema_pedidos/cocina/index.php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'Cocina' && $_SESSION['role'] !== 'Admin')) {
    header("Location: ../acceso_denegado.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cocina - Sistema de Pedidos</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/cocina.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #dc2626;
            --primary-dark: #b91c1c;
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

        .cocina-container {
            padding: 20px;
            max-width: 1400px;
            margin: 0 auto;
            margin-left: 100px;
        }

        .cocina-header {
            margin-bottom: 2rem;
            text-align: center;
        }

        .cocina-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
        }

        .cocina-subtitle {
            color: var(--secondary-color);
            font-size: 1.2rem;
            font-weight: 500;
        }

        .status-indicator {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: var(--primary-color);
            color: white;
            border-radius: 2rem;
            font-size: 0.9rem;
            font-weight: 600;
            margin-left: 1rem;
        }

        .status-indicator .pulse {
            width: 8px;
            height: 8px;
            background: #86efac;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(0.95); opacity: 1; }
            50% { transform: scale(1.1); opacity: 0.7; }
            100% { transform: scale(0.95); opacity: 1; }
        }

        /* Layout de dos columnas */
        .pedidos-layout {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        @media (max-width: 1024px) {
            .pedidos-layout {
                grid-template-columns: 1fr;
            }
        }

        .seccion-pedidos {
            background: var(--bg-card);
            border-radius: 1rem;
            box-shadow: var(--shadow);
            padding: 1.5rem;
        }

        .seccion-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f1f5f9;
        }

        .seccion-titulo {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .contador-pedidos {
            background: var(--primary-color);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .seccion-pendiente .seccion-titulo { color: #d97706; }
        .seccion-preparando .seccion-titulo { color: #1d4ed8; }

        .seccion-pendiente .contador-pedidos { background: #d97706; }
        .seccion-preparando .contador-pedidos { background: #1d4ed8; }

        /* Grid de Pedidos */
        .pedidos-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.5rem;
            max-height: 600px;
            overflow-y: auto;
            padding-right: 0.5rem;
        }

        .pedidos-grid::-webkit-scrollbar {
            width: 6px;
        }

        .pedidos-grid::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 3px;
        }

        .pedidos-grid::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }

        .pedidos-grid::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        .pedido-card {
            background: var(--bg-card);
            border-radius: 1rem;
            box-shadow: var(--shadow);
            border-left: 6px solid var(--primary-color);
            transition: var(--transition);
            overflow: hidden;
        }

        .pedido-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .pedido-card.urgent {
            border-left-color: var(--danger-color);
            animation: urgentGlow 2s infinite;
        }

        @keyframes urgentGlow {
            0%, 100% { box-shadow: 0 4px 6px -1px rgba(220, 38, 38, 0.1); }
            50% { box-shadow: 0 4px 20px rgba(220, 38, 38, 0.3); }
        }

        .pedido-header {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            padding: 1.25rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .pedido-info {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .pedido-id {
            font-size: 1.25rem;
            font-weight: 700;
        }

        .pedido-meta {
            display: flex;
            gap: 1rem;
            font-size: 0.85rem;
            opacity: 0.9;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .pedido-timer {
            background: rgba(255, 255, 255, 0.2);
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            font-weight: 600;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .urgent-badge {
            background: #ef4444;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: bold;
            animation: pulse 1.5s infinite;
        }

        .pedido-body {
            padding: 1.25rem;
        }

        .cliente-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.25rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f1f5f9;
        }

        .cliente-avatar {
            width: 2.5rem;
            height: 2.5rem;
            background: var(--accent-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: var(--text-dark);
            font-size: 1.1rem;
        }

        .cliente-details {
            flex: 1;
        }

        .cliente-nombre {
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.25rem;
        }

        .cliente-servicio {
            color: var(--secondary-color);
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .productos-list {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .producto-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem;
            background: #f8fafc;
            border-radius: 0.5rem;
            transition: var(--transition);
        }

        .producto-item:hover {
            background: #f1f5f9;
        }

        .producto-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .producto-cantidad {
            background: var(--primary-color);
            color: white;
            width: 1.75rem;
            height: 1.75rem;
            border-radius: 0.375rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.8rem;
        }

        .producto-nombre {
            font-weight: 600;
            color: var(--text-dark);
            font-size: 0.9rem;
        }

        .producto-estado {
            padding: 0.375rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .estado-pendiente {
            background: #fef3c7;
            color: #d97706;
        }

        .estado-preparando {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .pedido-actions {
            padding: 1.25rem;
            background: #f8fafc;
            display: flex;
            gap: 0.75rem;
            border-top: 2px solid #e2e8f0;
        }

        .btn {
            padding: 0.625rem 1.25rem;
            border: none;
            border-radius: 0.5rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-size: 0.85rem;
            flex: 1;
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
            transform: translateY(-2px);
        }

        .btn-warning {
            background: var(--warning-color);
            color: white;
        }

        .btn-warning:hover {
            background: #d97706;
            transform: translateY(-2px);
        }

        /* Estados vacíos */
        .empty-state {
            text-align: center;
            padding: 3rem 1.5rem;
            background: #f8fafc;
            border-radius: 0.75rem;
            border: 2px dashed #e2e8f0;
        }

        .empty-icon {
            font-size: 3rem;
            color: var(--secondary-color);
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .empty-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }

        .empty-description {
            color: var(--secondary-color);
            font-size: 0.9rem;
        }

        /* Barra de herramientas */
        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: var(--bg-card);
            border-radius: 1rem;
            box-shadow: var(--shadow);
        }

        .stats {
            display: flex;
            gap: 2rem;
        }

        .stat-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 1rem;
            background: #f8fafc;
            border-radius: 0.75rem;
            min-width: 120px;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0.25rem;
        }

        .stat-label {
            font-size: 0.8rem;
            color: var(--secondary-color);
            font-weight: 600;
            text-transform: uppercase;
        }

        .controls {
            display: flex;
            gap: 1rem;
        }

        .refresh-btn {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 0.75rem;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
        }

        .refresh-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .refresh-btn.spinning .fa-sync {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .cocina-container {
                margin-left: 0;
                padding: 1rem;
            }

            .pedidos-layout {
                grid-template-columns: 1fr;
            }

            .toolbar {
                flex-direction: column;
                gap: 1rem;
            }

            .stats {
                width: 100%;
                justify-content: space-around;
            }

            .controls {
                width: 100%;
                justify-content: center;
            }

            .pedido-header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .pedido-meta {
                justify-content: center;
                flex-wrap: wrap;
            }

            .pedido-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="cocina-container">
        <!-- Header -->
        <div class="cocina-header">
            <h1 class="cocina-title">
                <i class="fas fa-utensils"></i>
                Módulo de Cocina
                <span class="status-indicator">
                    <span class="pulse"></span>
                    EN VIVO
                </span>
            </h1>
            <p class="cocina-subtitle">Gestión en tiempo real de pedidos - Separados por estado</p>
        </div>

        <!-- Barra de Herramientas -->
        <div class="toolbar">
            <div class="stats">
                <div class="stat-item">
                    <div class="stat-value" id="totalPedidos">0</div>
                    <div class="stat-label">Total Pedidos</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value" id="pedidosPendientes">0</div>
                    <div class="stat-label">Pendientes</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value" id="pedidosPreparando">0</div>
                    <div class="stat-label">En Preparación</div>
                </div>
            </div>
            <div class="controls">
                <button class="refresh-btn" id="refreshBtn">
                    <i class="fas fa-sync"></i>
                    Actualizar
                </button>
                <button class="refresh-btn" id="autoRefreshToggle">
                    <i class="fas fa-play"></i>
                    Auto-Refresh
                </button>
            </div>
        </div>

        <!-- Layout de dos columnas -->
        <div class="pedidos-layout">
            <!-- Sección de Pedidos Pendientes -->
            <div class="seccion-pedidos seccion-pendiente">
                <div class="seccion-header">
                    <h2 class="seccion-titulo">
                        <i class="fas fa-clock"></i>
                        Pedidos Pendientes
                        <span class="contador-pedidos" id="contadorPendientes">0</span>
                    </h2>
                </div>
                <div id="pedidos-pendientes-list" class="pedidos-grid">
                    <!-- Los pedidos pendientes se cargarán aquí dinámicamente -->
                </div>
                <div id="no-pedidos-pendientes" class="empty-state" style="display: none;">
                    <div class="empty-icon">
                        <i class="fas fa-inbox"></i>
                    </div>
                    <h3 class="empty-title">No hay pedidos pendientes</h3>
                    <p class="empty-description">Los nuevos pedidos aparecerán aquí</p>
                </div>
            </div>

            <!-- Sección de Pedidos en Preparación -->
            <div class="seccion-pedidos seccion-preparando">
                <div class="seccion-header">
                    <h2 class="seccion-titulo">
                        <i class="fas fa-utensils"></i>
                        En Preparación
                        <span class="contador-pedidos" id="contadorPreparando">0</span>
                    </h2>
                </div>
                <div id="pedidos-preparando-list" class="pedidos-grid">
                    <!-- Los pedidos en preparación se cargarán aquí dinámicamente -->
                </div>
                <div id="no-pedidos-preparando" class="empty-state" style="display: none;">
                    <div class="empty-icon">
                        <i class="fas fa-concierge-bell"></i>
                    </div>
                    <h3 class="empty-title">No hay pedidos en preparación</h3>
                    <p class="empty-description">Los pedidos en preparación aparecerán aquí</p>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/cocina.js"></script>
</body>
</html>