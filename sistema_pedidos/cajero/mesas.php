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
    <title>Gestión de Mesas - Sistema de Pedidos</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/cajero.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .mesas-container {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .mesas-header {
            margin-bottom: 2rem;
        }

        .mesas-title {
            font-size: 2rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0.5rem;
        }

        .mesas-subtitle {
            color: #64748b;
            font-size: 1.1rem;
        }

        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #64748b;
            font-size: 0.9rem;
        }
/* Estilos para el modal */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    animation: fadeIn 0.3s ease;
}

.modal-content {
    background-color: white;

    padding: 2rem;
    border-radius: 1rem;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
    width: 90%;
    max-width: 500px;
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    animation: slideIn 0.3s ease;
}

.close-button {
    color: #aaa;
    float: right;
    font-size: 1.5rem;
    font-weight: bold;
    cursor: pointer;
    line-height: 1;
    margin-top: -0.5rem;
    margin-right: -0.5rem;
}

.close-button:hover,
.close-button:focus {
    color: #000;
}

/* Animaciones */
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translate(-50%, -60%);
    }
    to {
        opacity: 1;
        transform: translate(-50%, -50%);
    }
}

/* Para hacer el modal responsive */
@media (max-width: 768px) {
    .modal-content {
        width: 95%;
        padding: 1.5rem;
        margin: 1rem;
    }
}
        .stat-disponible { border-left: 4px solid #10b981; }
        .stat-ocupada { border-left: 4px solid #ef4444; }
        .stat-reservada { border-left: 4px solid #f59e0b; }
        .stat-mantenimiento { border-left: 4px solid #6b7280; }

        .filters {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .filter-btn {
            padding: 0.75rem 1.5rem;
            border: 2px solid #e2e8f0;
            background: white;
            border-radius: 0.75rem;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
        }

        .filter-btn.active {
            background: #3b82f6;
            color: white;
            border-color: #3b82f6;
        }

        .filter-btn:hover:not(.active) {
            border-color: #3b82f6;
            color: #3b82f6;
        }

        .refresh-btn {
            padding: 0.75rem 1.5rem;
            background: #10b981;
            color: white;
            border: none;
            border-radius: 0.75rem;
            cursor: pointer;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .refresh-btn:hover {
            background: #059669;
            transform: translateY(-2px);
        }

        .add-btn {
            padding: 0.75rem 1.5rem;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 0.75rem;
            cursor: pointer;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            margin-left: auto;
        }

        .add-btn:hover {
            background: #2563eb;
            transform: translateY(-2px);
        }

        .mesas-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .mesa-card {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            cursor: pointer;
            border: 3px solid transparent;
        }

        .mesa-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px -3px rgba(0, 0, 0, 0.1);
        }

        .mesa-card.disponible {
            border-color: #10b981;
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
        }

        .mesa-card.ocupada {
            border-color: #ef4444;
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
            cursor: not-allowed;
        }

        .mesa-card.reservada {
            border-color: #f59e0b;
            background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
        }

        .mesa-card.mantenimiento {
            border-color: #6b7280;
            background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
            cursor: not-allowed;
        }

        .mesa-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .mesa-numero {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e293b;
        }

        .mesa-estado {
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .estado-disponible { background: #d1fae5; color: #065f46; }
        .estado-ocupada { background: #fee2e2; color: #991b1b; }
        .estado-reservada { background: #fef3c7; color: #92400e; }
        .estado-mantenimiento { background: #e5e7eb; color: #374151; }

        .mesa-info {
            text-align: center;
        }

        .mesa-capacidad {
            font-size: 1rem;
            color: #64748b;
            margin-bottom: 0.5rem;
        }

        .mesa-ubicacion {
            font-size: 0.9rem;
            color: #94a3b8;
            font-style: italic;
        }

        .mesa-pedido-info {
            margin-top: 1rem;
            padding: 0.75rem;
            background: rgba(255, 255, 255, 0.7);
            border-radius: 0.5rem;
            font-size: 0.8rem;
        }

        .pedido-cliente {
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 0.25rem;
        }

        .pedido-tiempo {
            color: #64748b;
            font-size: 0.75rem;
        }

        .mesa-actions {
            margin-top: 1rem;
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .btn-sm {
            padding: 0.5rem 0.75rem;
            font-size: 0.8rem;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .btn-success { background: #10b981; color: white; }
        .btn-success:hover { background: #059669; }

        .btn-warning { background: #f59e0b; color: white; }
        .btn-warning:hover { background: #d97706; }

        .btn-danger { background: #ef4444; color: white; }
        .btn-danger:hover { background: #dc2626; }

        .btn-secondary { background: #64748b; color: white; }
        .btn-secondary:hover { background: #475569; }

        .btn-info { background: #3b82f6; color: white; }
        .btn-info:hover { background: #2563eb; }

        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: #64748b;
            grid-column: 1 / -1;
        }

        .empty-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        /* Estilos para formularios */
        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #374151;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e5e7eb;
            border-radius: 0.5rem;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 1.5rem;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Sugerencias de ubicación */
        .sugerencias-ubicacion {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            align-items: center;
            margin-top: 0.5rem;
        }

        .sugerencia-chip {
            background: #e0f2fe;
            color: #0369a1;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 1px solid #bae6fd;
        }

        .sugerencia-chip:hover {
            background: #bae6fd;
            transform: translateY(-1px);
        }

        @media (max-width: 768px) {
            .mesas-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            }
            
            .filters {
                flex-direction: column;
            }
            
            .add-btn, .refresh-btn {
                margin-left: 0;
                justify-content: center;
            }
            
            .mesa-actions {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="mesas-container">
        <!-- Header -->
        <div class="mesas-header">
            <h1 class="mesas-title">Gestión de Mesas</h1>
            <p class="mesas-subtitle">Visualiza y gestiona el estado de todas las mesas del restaurante</p>
        </div>

        <!-- Estadísticas -->
        <div class="stats-cards">
            <div class="stat-card stat-disponible">
                <div class="stat-number" id="total-disponibles">0</div>
                <div class="stat-label">Mesas Disponibles</div>
            </div>
            <div class="stat-card stat-ocupada">
                <div class="stat-number" id="total-ocupadas">0</div>
                <div class="stat-label">Mesas Ocupadas</div>
            </div>
            <div class="stat-card stat-reservada">
                <div class="stat-number" id="total-reservadas">0</div>
                <div class="stat-label">Mesas Reservadas</div>
            </div>
            <div class="stat-card stat-mantenimiento">
                <div class="stat-number" id="total-mantenimiento">0</div>
                <div class="stat-label">En Mantenimiento</div>
            </div>
        </div>

        <!-- Filtros y Acciones -->
        <div class="filters">
            <button class="filter-btn active" data-filter="todas">Todas las Mesas</button>
            <button class="filter-btn" data-filter="disponible">Disponibles</button>
            <button class="filter-btn" data-filter="ocupada">Ocupadas</button>
            <button class="filter-btn" data-filter="reservada">Reservadas</button>
            <button class="filter-btn" data-filter="mantenimiento">Mantenimiento</button>
            
            <button class="refresh-btn" id="refreshMesasBtn">
                <i class="fas fa-sync-alt"></i>
                Actualizar
            </button>
            
            <button class="add-btn" onclick="abrirModalNuevaMesa()">
                <i class="fas fa-plus"></i>
                Nueva Mesa
            </button>
        </div>

        <!-- Grid de Mesas -->
        <div class="mesas-grid" id="mesasGrid">
            <div class="empty-state">
                <i class="fas fa-spinner fa-spin"></i>
                Cargando mesas...
            </div>
        </div>
    </div>

    <!-- Modal para cambiar estado de mesa -->
    <div id="estadoModal" class="modal">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <h3>Cambiar Estado de Mesa</h3>
            <div id="modalMesaInfo"></div>
            <div class="estado-options" style="display: flex; gap: 1rem; justify-content: center; margin-top: 1.5rem;">
                <button class="btn btn-success" data-estado="disponible">
                    <i class="fas fa-check"></i> Disponible
                </button>
                <button class="btn btn-warning" data-estado="reservada">
                    <i class="fas fa-clock"></i> Reservada
                </button>
                <button class="btn btn-danger" data-estado="mantenimiento">
                    <i class="fas fa-tools"></i> Mantenimiento
                </button>
            </div>
        </div>
    </div>

    <script src="../assets/js/mesas.js"></script>
</body>
</html>