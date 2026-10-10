<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'Cajero' && $_SESSION['role'] !== 'Admin')) {
    header("Location: ../acceso_denegado.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cajero - Sistema de Pedidos</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/cajero.css">
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

        .cajero-container {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
            margin-left: 100px;
        }

        .cajero-header {
            margin-bottom: 2rem;
        }

        .cajero-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--bg-dark);
            margin-bottom: 0.5rem;
        }

        .cajero-subtitle {
            color: var(--secondary-color);
            font-size: 1.1rem;
        }

        /* Panel Principal */
        .main-panel {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .order-section {
            background: var(--bg-card);
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: var(--shadow);
        }

        .summary-section {
            background: var(--bg-card);
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: var(--shadow);
            position: sticky;
            top: 2rem;
            height: fit-content;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--bg-dark);
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid #e2e8f0;
        }

        /* Tarjeta de Cliente */
        .client-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 1rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
        }

        .client-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .client-title {
            font-size: 1.2rem;
            font-weight: 600;
        }

        .change-client-btn {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: var(--transition);
            font-weight: 500;
        }

        .change-client-btn:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .client-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .info-item {
            display: flex;
            flex-direction: column;
        }

        .info-label {
            font-size: 0.8rem;
            opacity: 0.8;
            margin-bottom: 0.25rem;
        }

        .info-value {
            font-size: 1rem;
            font-weight: 600;
        }

        /* Configuración del Pedido */
        .order-config {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-label {
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }

        .form-control {
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

        /* Lista de Productos */
        .products-list {
            max-height: 400px;
            overflow-y: auto;
            margin-bottom: 2rem;
            padding: 10px;
        }

        .product-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 0.75rem;
            margin-bottom: 0.75rem;
            transition: var(--transition);
        }

        .product-item:hover {
            border-color: var(--primary-color);
            transform: translateX(5px);
        }

        .product-info {
            flex: 1;
        }

        .product-name {
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.25rem;
        }

        .product-details {
            display: flex;
            gap: 1rem;
            font-size: 0.9rem;
            color: var(--secondary-color);
        }

        .product-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .quantity-btn {
            width: 2rem;
            height: 2rem;
            border: 2px solid #e2e8f0;
            background: white;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .quantity-btn:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }

        .quantity-value {
            font-weight: 600;
            min-width: 2rem;
            text-align: center;
        }

        .remove-btn {
            color: var(--danger-color);
            background: none;
            border: none;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 0.5rem;
            transition: var(--transition);
        }

        .remove-btn:hover {
            background: #fef2f2;
        }

        /* Resumen del Pedido */
        .summary-total {
            background: #f8fafc;
            padding: 1.5rem;
            border-radius: 0.75rem;
            margin-bottom: 1.5rem;
        }

        .total-line {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
        }

        .total-label {
            color: var(--secondary-color);
        }

        .total-value {
            font-weight: 600;
            color: var(--text-dark);
        }

        .grand-total {
            border-top: 2px solid #e2e8f0;
            padding-top: 0.75rem;
            margin-top: 0.75rem;
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .btn {
            padding: 1rem 1.5rem;
            border: none;
            border-radius: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-size: 1rem;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }

        .btn-success {
            background: var(--success-color);
            color: white;
        }

        .btn-success:hover {
            background: #059669;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .btn-secondary {
            background: var(--secondary-color);
            color: white;
        }

        .btn-secondary:hover {
            background: #475569;
            transform: translateY(-2px);
        }

        /* Estados vacíos */
        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: var(--secondary-color);
        }

        .empty-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .main-panel {
                grid-template-columns: 1fr;
            }

            .summary-section {
                position: static;
            }
        }

        @media (max-width: 768px) {
            .cajero-container {
                margin-left: 0;
                padding: 1rem;
            }

            .order-config {
                grid-template-columns: 1fr;
            }

            .client-info {
                grid-template-columns: 1fr;
            }
        }

        /* Estilos para la sección de pedidos en tiempo real */
        .pedidos-realtime-section {
            background: var(--bg-card);
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: var(--shadow);
            margin-top: 2rem;
        }

        .pedidos-container {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            max-height: 600px;
            overflow-y: auto;
        }

        .pedido-card {
            border: 2px solid #e2e8f0;
            border-radius: 0.75rem;
            padding: 1.5rem;
            transition: var(--transition);
            background: white;
        }

        .pedido-card.preparando {
            border-left: 4px solid var(--primary-color);
        }

        .pedido-card.completado {
            border-left: 4px solid var(--success-color);
        }

        .pedido-card.pagado {
            border-left: 4px solid #10b981;
        }

        .pedido-card.cancelado {
            border-left: 4px solid var(--danger-color);
            opacity: 0.7;
        }

        .pedido-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .pedido-info {
            flex: 1;
        }

        .pedido-numero {
            font-weight: 700;
            font-size: 1.2rem;
            color: var(--text-dark);
            margin-bottom: 0.25rem;
        }

        .pedido-cliente {
            color: var(--secondary-color);
            font-size: 0.9rem;
        }

        .pedido-estado {
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .estado-preparando {
            background: #dbeafe;
            color: var(--primary-color);
        }

        .estado-completado {
            background: #d1fae5;
            color: #059669;
        }

        .estado-pagado {
            background: #dcfce7;
            color: #16a34a;
            border: 1px solid #bbf7d0;
        }

        .estado-cancelado {
            background: #fee2e2;
            color: #dc2626;
        }

        .pedido-detalles {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .pedido-productos {
            font-size: 0.9rem;
        }

        .producto-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.40rem;
        }

        .producto-nombre {
            color: var(--text-dark);
        }

        .producto-cantidad {
            color: var(--secondary-color);
            font-weight: 600;
        }

        .pedido-servicio, .pedido-pago {
            font-size: 0.9rem;
        }

        .detalle-label {
            font-weight: 600;
            color: var(--secondary-color);
            font-size: 0.8rem;
            margin-bottom: 0.25rem;
        }

        .detalle-value {
            color: var(--text-dark);
            font-weight: 500;
        }

        .pedido-total {
            text-align: right;
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--primary-color);
        }

        .pedido-actions {
            display: flex;
            justify-content: flex-end;
            gap: 0.5rem;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #e2e8f0;
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.8rem;
        }

        .btn-danger {
            background: var(--danger-color);
            color: white;
        }

        .btn-danger:hover {
            background: #dc2626;
            transform: translateY(-1px);
        }

        .loading-state, .empty-state-pedidos {
            text-align: center;
            padding: 3rem 2rem;
            color: var(--secondary-color);
        }

        .empty-state-pedidos .empty-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .tiempo-transcurrido {
            font-size: 0.8rem;
            color: var(--secondary-color);
            margin-top: 0.25rem;
        }

        .urgente {
            background: #fef2f2 !important;
            border: 1px solid #fecaca !important;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.8; }
            100% { opacity: 1; }
        }

        /* Estilos para mesas */
        .mesas-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 1rem;
            margin: 1rem 0;
        }

        .mesa-item {
            padding: 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 0.75rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .mesa-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .mesa-item.disponible {
            border-color: #10b981;
            background: #f0fdf4;
        }

        .mesa-item.ocupada {
            border-color: #ef4444;
            background: #fef2f2;
            cursor: not-allowed;
            opacity: 0.6;
        }

        .mesa-item.reservada {
            border-color: #f59e0b;
            background: #fffbeb;
        }

        .mesa-numero {
            font-weight: bold;
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
        }

        .mesa-info {
            font-size: 0.8rem;
            color: #64748b;
        }

        .mesa-estado {
            display: block;
            margin-top: 0.25rem;
            font-weight: 600;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .pedido-detalles {
                grid-template-columns: 1fr;
                gap: 0.5rem;
            }
            
            .pedido-header {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .pedido-actions {
                justify-content: stretch;
            }
            
            .pedido-actions .btn {
                flex: 1;
            }
        }

        /* Nuevos estilos para el flujo mejorado */
        .flujo-info {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 1rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
        }

        .flujo-steps {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1rem;
        }

        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            flex: 1;
        }

        .step-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 0.5rem;
            font-size: 1.2rem;
        }

        .step.preparando .step-icon {
            background: var(--primary-color);
        }

        .step.completado .step-icon {
            background: var(--success-color);
        }

        .step.pagado .step-icon {
            background: #10b981;
        }

        .step-line {
            flex: 1;
            height: 2px;
            background: rgba(255,255,255,0.3);
            margin: 0 1rem;
            margin-top: 20px;
        }

        .step-text {
            font-size: 0.8rem;
            opacity: 0.9;
        }

        .step.active .step-text {
            font-weight: 600;
            opacity: 1;
        }

        /* Estilos para modales */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: white;
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 10px 25px -3px rgba(0,0,0,0.1);
            position: relative;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }

        .close-button {
            position: absolute;
            right: 1rem;
            top: 1rem;
            font-size: 1.5rem;
            cursor: pointer;
            color: #64748b;
        }

        .close-button:hover {
            color: #374151;
        }

        /* Estilos para búsqueda en modal de clientes */
        .search-client-section, .register-client-section {
            margin-bottom: 2rem;
        }

        .search-client-section h4, .register-client-section h4 {
            margin-bottom: 1rem;
            color: var(--text-dark);
        }

        .search-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }

        .client-result {
            margin-top: 1rem;
            padding: 1rem;
            border-radius: 0.5rem;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
        }

        .client-result.success {
            background: #f0fdf4;
            border-color: #bbf7d0;
        }

        .client-result.error {
            background: #fef2f2;
            border-color: #fecaca;
        }

        /* Estilos para productos en modal */
        .search-filter-container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .categoria {
            margin-bottom: 2rem;
        }

        .categoria h4 {
            color: var(--primary-color);
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 0.5rem;
            margin-bottom: 1rem;
        }
        /* Estilos para el modal de mesas */
.mesas-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 1rem;
    margin: 1rem 0;
    max-height: 400px;
    overflow-y: auto;
    padding: 10px;
}

.mesa-item {
    padding: 1rem;
    border: 2px solid #e2e8f0;
    border-radius: 0.75rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
    background: white;
}

.mesa-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.mesa-item.disponible {
    border-color: #10b981;
    background: #f0fdf4;
}

.mesa-item.ocupada {
    border-color: #ef4444;
    background: #fef2f2;
    cursor: not-allowed;
}

.mesa-item.reservada {
    border-color: #f59e0b;
    background: #fffbeb;
    cursor: not-allowed;
}
/* Agrega estos estilos al final de tu CSS existente */
#campos_pago_movil {
    transition: all 0.3s ease;
    animation: slideDown 0.3s ease;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Estilo para información de pago móvil en pedidos */
.pedido-pago .detalle-value small {
    display: block;
    margin-top: 0.25rem;
    font-size: 0.8rem;
}
.mesa-item.mantenimiento {
    border-color: #6b7280;
    background: #f3f4f6;
    cursor: not-allowed;
}

.mesa-numero {
    font-weight: bold;
    font-size: 1.2rem;
    margin-bottom: 0.5rem;
    color: var(--text-dark);
}

.mesa-info {
    font-size: 0.8rem;
    color: #64748b;
}

.mesa-estado {
    display: block;
    margin-top: 0.25rem;
    font-weight: 600;
    padding: 0.25rem 0.5rem;
    border-radius: 0.375rem;
    font-size: 0.7rem;
}

.estado-disponible {
    background: #d1fae5;
    color: #065f46;
}

.estado-ocupada {
    background: #fee2e2;
    color: #991b1b;
}

.estado-reservada {
    background: #fef3c7;
    color: #92400e;
}

.estado-mantenimiento {
    background: #e5e7eb;
    color: #374151;
}
    </style>
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="cajero-container">
        <!-- Header -->
        <div class="cajero-header">
            <h1 class="cajero-title">Módulo de Cajero</h1>
            <p class="cajero-subtitle">Gestiona y procesa pedidos de forma eficiente</p>
        </div>

        <!-- Información del Flujo -->
        <div class="flujo-info">
            <h3 style="margin: 0; font-size: 1.2rem;">
                <i class="fas fa-sync-alt"></i>
                Nuevo Flujo de Pedidos
            </h3>
            <div class="flujo-steps">
                <div class="step preparando active">
                    <div class="step-icon">
                        <i class="fas fa-utensils"></i>
                    </div>
                    <div class="step-text">En Preparación</div>
                </div>
                <div class="step-line"></div>
                <div class="step completado">
                    <div class="step-icon">
                        <i class="fas fa-check"></i>
                    </div>
                    <div class="step-text">Listo para Cobrar</div>
                </div>
                <div class="step-line"></div>
                <div class="step pagado">
                    <div class="step-icon">
                        <i class="fas fa-cash-register"></i>
                    </div>
                    <div class="step-text">Pagado</div>
                </div>
            </div>
        </div>

        <!-- Panel Principal -->
        <div class="main-panel">
            <!-- Sección de Configuración del Pedido -->
            <div class="order-section">
                <!-- Tarjeta de Cliente -->
                <div class="client-card">
                    <div class="client-header">
                        <div class="client-title">Cliente Actual</div>
                        <button class="change-client-btn" id="openClientModalBtn">
                            <i class="fas fa-exchange-alt"></i>
                            Cambiar Cliente
                        </button>
                    </div>
                    <div class="client-info">
                        <div class="info-item">
                            <span class="info-label">Nombre</span>
                            <span class="info-value" id="nombreClienteActual">No seleccionado</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Cédula</span>
                            <span class="info-value" id="cedulaClienteActual">N/A</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">ID Cliente</span>
                            <span class="info-value" id="idClienteActual">N/A</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Estado</span>
                            <span class="info-value" style="color: #86efac;">Pendiente</span>
                        </div>
                    </div>
                </div>

                <!-- Tarjeta de Mesa -->
                <div class="mesa-card" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; padding: 1.5rem; border-radius: 1rem; margin-bottom: 2rem; box-shadow: var(--shadow);">
                    <div class="mesa-header">
                        <div class="mesa-title">Mesa Actual</div>
                        <button class="change-mesa-btn" onclick="cargarMesas()" style="background: rgba(255, 255, 255, 0.2); color: white; border: none; padding: 0.5rem 1rem; border-radius: 0.5rem; cursor: pointer; transition: var(--transition);">
                            <i class="fas fa-chair"></i>
                            Cambiar Mesa
                        </button>
                    </div>
                    <div class="mesa-info">
                        <div class="info-item">
                            <span class="info-label">Número</span>
                            <span class="info-value" id="numero_mesa_actual">No seleccionada</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Estado</span>
                            <span class="info-value" style="color: #86efac;">Por asignar</span>
                        </div>
                    </div>
                </div>
                <input type="hidden" id="id_mesa_seleccionada" name="id_mesa">

                <h3 class="section-title">Configuración del Pedido</h3>
                
                <form id="pedidoForm">
                    <input type="hidden" id="id_cliente_seleccionado" name="id_cliente">
                    
                    <div class="order-config">
                        <div class="form-group">
                            <label class="form-label" for="tipo_servicio">
                                <i class="fas fa-concierge-bell"></i>
                                Tipo de Servicio
                            </label>
                            <select id="tipo_servicio" name="tipo_servicio" class="form-control" required>
                                <option value="Servicio en Mesa">🍽️ Servicio en Mesa</option>
                                <option value="Para Llevar">🥡 Para Llevar</option>
                                <option value="A Domicilio">🚚 A Domicilio</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="tipo_pago">
                                <i class="fas fa-credit-card"></i>
                                Método de Pago
                            </label>
                            <select id="tipo_pago" name="tipo_pago" class="form-control" required onchange="mostrarCamposPagoMovil()">
                                <option value="Efectivo">💵 Efectivo</option>
                                <option value="Tarjeta de Crédito">💳 Tarjeta de Crédito</option>
                                <option value="Transferencia">🏦 Transferencia</option>
                                <option value="Pago Móvil">📱 Pago Móvil</option>
                            </select>
                        </div>
                    </div>

                    <!-- Campos de Pago Móvil -->
                    <div id="campos_pago_movil" style="display: none; margin-top: 1rem; padding: 1rem; background: #f8fafc; border-radius: 0.5rem; border: 1px solid #e2e8f0;">
                        <div style="margin-bottom: 1rem; color: #374151; font-weight: 600;">
                            <i class="fas fa-mobile-alt"></i> Información de Pago Móvil
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="banco_pago_movil">
                                <i class="fas fa-university"></i>
                                Banco
                            </label>
                            <select id="banco_pago_movil" name="banco_pago_movil" class="form-control">
                                <option value="">Seleccione el banco</option>
                                <option value="Banesco">Banesco</option>
                                <option value="Mercantil">Banco Mercantil</option>
                                <option value="Venezuela">Banco de Venezuela</option>
                                <option value="Provincial">Banco Provincial</option>
                                <option value="Bancaribe">Bancaribe</option>
                                <option value="BOD">BOD</option>
                                <option value="BNC">BNC</option>
                                <option value="Banco Plaza">Banco Plaza</option>
                                <option value="Banco Exterior">Banco Exterior</option>
                                <option value="100% Banco">100% Banco</option>
                                <option value="Banco del Tesoro">Banco del Tesoro</option>
                                <option value="Banco Bicentenario">Banco Bicentenario</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="referencia_pago_movil">
                                <i class="fas fa-hashtag"></i>
                                Número de Referencia
                            </label>
                            <input type="text" id="referencia_pago_movil" name="referencia_pago_movil" 
                                   class="form-control" placeholder="Ej: 123456789"
                                   maxlength="20">
                            <small style="color: #64748b; font-size: 0.8rem;">
                                Ingrese el número de referencia proporcionado por el pago móvil
                            </small>
                        </div>
                    </div>

                    <h3 class="section-title">Productos del Pedido</h3>
                    
                    <div class="products-list" id="selected-products-list">
                        <div class="empty-state">
                            <div class="empty-icon">
                                <i class="fas fa-shopping-basket"></i>
                            </div>
                            <h4>No hay productos agregados</h4>
                            <p>Agrega productos al pedido para continuar</p>
                        </div>
                    </div>

                    <button type="button" class="btn btn-primary" id="openProductsModalBtn">
                        <i class="fas fa-plus"></i>
                        Agregar Productos
                    </button>
                </form>
            </div>

            <!-- Sección de Resumen -->
            <div class="summary-section">
                <h3 class="section-title">Resumen del Pedido</h3>
                
                <div class="summary-total">
                    <div class="total-line">
                        <span class="total-label">Subtotal:</span>
                        <span class="total-value" id="subtotal-pedido">$0.00</span>
                    </div>
                    <div class="total-line">
                        <span class="total-label">IVA (12%):</span>
                        <span class="total-value" id="iva-pedido">$0.00</span>
                    </div>
                    <div class="total-line grand-total">
                        <span class="total-label">Total USD:</span>
                        <span class="total-value" id="total-pedido">$0.00</span>
                    </div>
                    <!-- Nueva línea para mostrar total en bolívares -->
                    <div class="total-line grand-total-bs" style="border-top: 2px solid #e2e8f0; padding-top: 0.75rem; margin-top: 0.75rem; font-size: 1.1rem; font-weight: 700; color: #059669;">
                        <span class="total-label">Total Bs:</span>
                        <span class="total-value" id="total-pedido-bs">Bs 0.00</span>
                    </div>
                    <!-- Información de tasa de cambio -->
                    <div style="text-align: center; margin-top: 0.5rem; font-size: 0.8rem; color: #64748b;">
                        <i class="fas fa-sync-alt"></i>
                        Tasa: <span id="tasa-cambio-actual">$1.00 = Bs 0.00</span>
                        <button type="button" onclick="actualizarTasaCambio()" style="background: none; border: none; color: #3b82f6; cursor: pointer; margin-left: 0.5rem;">
                            <i class="fas fa-redo-alt"></i>
                        </button>
                    </div>
                </div>

                <div class="action-buttons">
                    <button type="submit" class="btn btn-success" id="finalizarPedidoBtn">
                        <i class="fas fa-paper-plane"></i>
                        Enviar a Cocina
                    </button>
                    
                    <button type="button" class="btn btn-secondary" id="limpiarPedidoBtn">
                        <i class="fas fa-broom"></i>
                        Limpiar Pedido
                    </button>
                </div>

                <div style="margin-top: 2rem; padding: 1rem; background: #f0f9ff; border-radius: 0.75rem; border-left: 4px solid var(--primary-color);">
                    <h4 style="margin-bottom: 0.5rem; color: var(--primary-color);">
                        <i class="fas fa-info-circle"></i>
                        Información del Nuevo Flujo
                    </h4>
                    <p style="font-size: 0.9rem; color: var(--secondary-color); margin: 0;">
                        • <strong>Paso 1:</strong> Crear pedido → Enviar a cocina<br>
                        • <strong>Paso 2:</strong> Cocina prepara → Marca como completado<br>
                        • <strong>Paso 3:</strong> Cajero cobra → Solo cuando está completado<br>
                        • El cobro se realiza después de la preparación
                    </p>
                </div>
            </div>
        </div>

        <!-- Sección de Pedidos en Tiempo Real -->
        <div class="pedidos-realtime-section">
            <h3 class="section-title">
                <i class="fas fa-clock"></i>
                Pedidos en Tiempo Real
                <button class="btn btn-secondary btn-sm" id="refreshPedidosBtn" style="margin-left: auto;">
                    <i class="fas fa-sync-alt"></i>
                    Actualizar
                </button>
            </h3>
            
            <div class="pedidos-container" id="pedidosRealtimeContainer">
                <div class="loading-state">
                    <i class="fas fa-spinner fa-spin"></i>
                    Cargando pedidos...
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Cliente -->
    <div id="clientModal" class="modal">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <h3>Gestión de Clientes</h3>
            
            <div class="search-client-section">
                <h4>Buscar Cliente Existente</h4>
                <div class="form-group">
                    <label for="cedula_buscar">Cédula:</label>
                    <div class="cedula-container">
                        <select class="tipo-cedula-select" id="tipo_cedula_buscar">
                            <option value="V">V</option>
                            <option value="E">E</option>
                            <option value="J">J</option>
                            <option value="G">G</option>
                            <option value="P">P</option>
                        </select>
                        <input type="text" id="cedula_buscar" name="cedula_buscar" placeholder="Número de cédula" class="cedula-input" maxlength="8">
                    </div>
                    <div class="search-actions">
                        <button type="button" class="btn btn-secondary" id="buscarClienteBtn">
                            <i class="fas fa-search"></i>
                            Buscar Cliente
                        </button>
                    </div>
                </div>
                <div id="clienteResult" class="client-result" style="display: none;">
                    <!-- Resultado de búsqueda aparecerá aquí -->
                </div>
            </div>
            
            <hr>
            
            <div class="register-client-section">
                <h4>Registrar Nuevo Cliente</h4>
                <form id="clientForm">
                    <div class="form-group">
                        <label for="cedula_cliente_modal">Cédula:</label>
                        <div class="cedula-container">
                            <select class="tipo-cedula-select" id="tipo_cedula_nuevo" name="tipo_cedula" required>
                                <option value="V">V</option>
                                <option value="E">E</option>
                                <option value="J">J</option>
                                <option value="G">G</option>
                                <option value="P">P</option>
                            </select>
                            <input type="text" id="cedula_cliente_modal" name="cedula_cliente" class="cedula-input" placeholder="Número de cédula" maxlength="8" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="nombre_cliente_modal">Nombre:</label>
                        <input type="text" id="nombre_cliente_modal" name="nombre_cliente" class="form-control" placeholder="Nombre del cliente" required>
                    </div>
                    <div class="form-group">
                        <label for="apellido_cliente_modal">Apellido:</label>
                        <input type="text" id="apellido_cliente_modal" name="apellido_cliente" class="form-control" placeholder="Apellido del cliente" required>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i>
                        Registrar Cliente
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal de Productos -->
    <div id="productosModal" class="modal">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <h3>Seleccionar Productos</h3>
            
            <div class="search-filter-container">
                <input type="text" id="searchInput" placeholder="Buscar por nombre..." class="form-control">
                <select id="categoryFilter" class="form-control">
                    <option value="">Todas las categorías</option>
                </select>
            </div>
            
            <div id="productos-list-modal">
                <p>Cargando productos...</p>
            </div>
            
            <button type="button" class="btn btn-primary" id="addSelectedProductsBtn">Añadir a Pedido</button>
        </div>	
    </div>

    <!-- Incluir el JavaScript corregido -->
    <script src="../assets/js/cajero.js"></script>
</body>
</html>