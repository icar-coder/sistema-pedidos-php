<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
header("Location: ../acceso_denegado.php");
exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Sistema de Pedidos</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
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

        .admin-container {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
			margin-left:100px;
        }

        /* Header del Dashboard */
        .dashboard-header {
            margin-bottom: 2rem;
        }

        .dashboard-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--bg-dark);
            margin-bottom: 0.5rem;
        }

        .dashboard-subtitle {
            color: var(--secondary-color);
            font-size: 1.1rem;
        }

        /* Cards de Estadísticas */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .stat-card {
            background: var(--bg-card);
            padding: 1.5rem;
            border-radius: 1rem;
            box-shadow: var(--shadow);
            border-left: 4px solid var(--primary-color);
            transition: var(--transition);
            cursor: pointer;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .stat-card.pedidos { border-left-color: var(--primary-color); }
        .stat-card.usuarios { border-left-color: var(--success-color); }
        .stat-card.productos { border-left-color: var(--warning-color); }
        .stat-card.ingresos { border-left-color: var(--danger-color); }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .stat-icon {
            width: 3rem;
            height: 3rem;
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }

        .stat-card.pedidos .stat-icon { background: var(--primary-color); }
        .stat-card.usuarios .stat-icon { background: var(--success-color); }
        .stat-card.productos .stat-icon { background: var(--warning-color); }
        .stat-card.ingresos .stat-icon { background: var(--danger-color); }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--bg-dark);
            margin-bottom: 0.25rem;
        }

        .stat-label {
            color: var(--secondary-color);
            font-size: 0.9rem;
            font-weight: 500;
        }

        .stat-trend {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            font-size: 0.8rem;
            margin-top: 0.5rem;
        }

        .trend-up { color: var(--success-color); }
        .trend-down { color: var(--danger-color); }

        /* Acciones Rápidas */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .action-card {
            background: var(--bg-card);
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: var(--shadow);
            text-align: center;
            transition: var(--transition);
            cursor: pointer;
            border: 2px solid transparent;
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .action-card:hover {
            transform: translateY(-3px);
            border-color: var(--primary-color);
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.15);
            text-decoration: none;
            color: inherit;
        }

        .action-icon {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        .action-title {
            font-weight: 600;
            color: var(--bg-dark);
            margin-bottom: 0.5rem;
            font-size: 1.2rem;
        }

        .action-desc {
            color: var(--secondary-color);
            font-size: 0.9rem;
            line-height: 1.4;
        }

        /* Sección de Actividad Reciente */
        .recent-activity {
            background: var(--bg-card);
            border-radius: 1rem;
            box-shadow: var(--shadow);
            padding: 2rem;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--bg-dark);
        }

        .view-all {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
        }

        .view-all:hover {
            text-decoration: underline;
        }

        .activity-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .activity-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: #f8fafc;
            border-radius: 0.75rem;
            transition: var(--transition);
        }

        .activity-item:hover {
            background: #f1f5f9;
        }

        .activity-icon {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1rem;
        }

        .activity-icon.pedido { background: var(--primary-color); }
        .activity-icon.usuario { background: var(--success-color); }
        .activity-icon.producto { background: var(--warning-color); }

        .activity-content {
            flex: 1;
        }

        .activity-text {
            font-weight: 500;
            color: var(--bg-dark);
            margin-bottom: 0.25rem;
        }

        .activity-time {
            color: var(--secondary-color);
            font-size: 0.8rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .admin-container {
                margin-left: 0;
                padding: 1rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .quick-actions {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="admin-container">
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <h1 class="dashboard-title">Dashboard Administrativo</h1>
            <p class="dashboard-subtitle">Bienvenido al panel de control del sistema</p>
        </div>

        <!-- Estadísticas Rápidas -->
        <div class="stats-grid">
            <div class="stat-card pedidos" onclick="window.location.href='gestion.php?tab=pedidos'">
                <div class="stat-header">
                    <div>
                        <div class="stat-value" id="totalPedidos">0</div>
                        <div class="stat-label">Total Pedidos</div>
                        <div class="stat-trend trend-up">
                            <i class="fas fa-arrow-up"></i>
                            <span>12% este mes</span>
                        </div>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card usuarios" onclick="window.location.href='gestion.php?tab=usuarios'">
                <div class="stat-header">
                    <div>
                        <div class="stat-value" id="totalUsuarios">0</div>
                        <div class="stat-label">Usuarios Activos</div>
                        <div class="stat-trend trend-up">
                            <i class="fas fa-arrow-up"></i>
                            <span>5% este mes</span>
                        </div>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card productos" onclick="window.location.href='gestion.php?tab=productos'">
                <div class="stat-header">
                    <div>
                        <div class="stat-value" id="totalProductos">0</div>
                        <div class="stat-label">Productos Activos</div>
                        <div class="stat-trend trend-down">
                            <i class="fas fa-arrow-down"></i>
                            <span>2% este mes</span>
                        </div>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-box"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card ingresos">
                <div class="stat-header">
                    <div>
                        <div class="stat-value" id="totalIngresos">$0</div>
                        <div class="stat-label">Ingresos Totales</div>
                        <div class="stat-trend trend-up">
                            <i class="fas fa-arrow-up"></i>
                            <span>18% este mes</span>
                        </div>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Acciones Rápidas -->
        <div class="section-header">
            <h2 class="section-title">Acciones Rápidas</h2>
        </div>
        <div class="quick-actions">
            <a href="gestion.php?tab=pedidos" class="action-card">
                <div class="action-icon">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <div class="action-title">Gestionar Pedidos</div>
                <div class="action-desc">Ver, buscar y administrar todos los pedidos del sistema</div>
            </a>

            <a href="gestion.php?tab=usuarios" class="action-card">
                <div class="action-icon">
                    <i class="fas fa-user-cog"></i>
                </div>
                <div class="action-title">Gestionar Usuarios</div>
                <div class="action-desc">Administrar usuarios, roles y permisos del sistema</div>
            </a>

            <a href="gestion.php?tab=productos" class="action-card">
                <div class="action-icon">
                    <i class="fas fa-box-open"></i>
                </div>
                <div class="action-title">Gestionar Productos</div>
                <div class="action-desc">Administrar productos, precios y disponibilidad</div>
            </a>

            <a href="gestion.php?tab=categorias" class="action-card">
                <div class="action-icon">
                    <i class="fas fa-tags"></i>
                </div>
                <div class="action-title">Gestionar Categorías</div>
                <div class="action-desc">Organizar y administrar categorías de productos</div>
            </a>
        </div>

        <!-- Actividad Reciente -->
        <div class="recent-activity">
            <div class="section-header">
                <h2 class="section-title">Actividad Reciente</h2>
                <a href="gestion.php" class="view-all">Ver todo</a>
            </div>
            <div class="activity-list">
                <div class="activity-item">
                    <div class="activity-icon pedido">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="activity-content">
                        <div class="activity-text">Nuevo pedido #00125 recibido</div>
                        <div class="activity-time">Hace 5 minutos</div>
                    </div>
                </div>
                <div class="activity-item">
                    <div class="activity-icon usuario">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <div class="activity-content">
                        <div class="activity-text">Nuevo usuario registrado: María González</div>
                        <div class="activity-time">Hace 2 horas</div>
                    </div>
                </div>
                <div class="activity-item">
                    <div class="activity-icon producto">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="activity-content">
                        <div class="activity-text">Producto "Hamburguesa Especial" actualizado</div>
                        <div class="activity-time">Hace 4 horas</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<script>
    // Función para formatear el tiempo relativo
    function formatTimeAgo(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diffMs = now - date;
        const diffMins = Math.floor(diffMs / 60000);
        const diffHours = Math.floor(diffMs / 3600000);
        const diffDays = Math.floor(diffMs / 86400000);

        if (diffMins < 1) return 'Hace unos momentos';
        if (diffMins < 60) return `Hace ${diffMins} minuto${diffMins > 1 ? 's' : ''}`;
        if (diffHours < 24) return `Hace ${diffHours} hora${diffHours > 1 ? 's' : ''}`;
        if (diffDays < 7) return `Hace ${diffDays} día${diffDays > 1 ? 's' : ''}`;
        
        return date.toLocaleDateString('es-ES');
    }

    // Función para obtener estadísticas
    async function fetchEstadisticas() {
        try {
            const response = await fetch('../api/estadisticas_api.php');
            const result = await response.json();
            
            if (result.success) {
                const data = result.data;
                
                // Actualizar estadísticas
                document.getElementById('totalPedidos').textContent = data.total_pedidos;
                document.getElementById('totalUsuarios').textContent = data.total_usuarios;
                document.getElementById('totalProductos').textContent = data.total_productos;
                document.getElementById('totalIngresos').textContent = '$' + parseFloat(data.total_ingresos).toFixed(2);
                
                // Actualizar actividad reciente
                const activityList = document.querySelector('.activity-list');
                activityList.innerHTML = '';
                
                data.actividad_reciente.forEach(actividad => {
                    const activityItem = document.createElement('div');
                    activityItem.className = 'activity-item';
                    
                    let iconClass = '';
                    let icon = '';
                    
                    switch(actividad.tipo) {
                        case 'pedido':
                            iconClass = 'pedido';
                            icon = 'fas fa-shopping-cart';
                            break;
                        case 'usuario':
                            iconClass = 'usuario';
                            icon = 'fas fa-user-plus';
                            break;
                        case 'producto':
                            iconClass = 'producto';
                            icon = 'fas fa-box';
                            break;
                    }
                    
                    activityItem.innerHTML = `
                        <div class="activity-icon ${iconClass}">
                            <i class="${icon}"></i>
                        </div>
                        <div class="activity-content">
                            <div class="activity-text">${actividad.texto}</div>
                            <div class="activity-time">${formatTimeAgo(actividad.tiempo)}</div>
                        </div>
                    `;
                    
                    activityList.appendChild(activityItem);
                });
                
            } else {
                console.error('Error al cargar estadísticas:', result.message);
                // Mostrar datos por defecto en caso de error
                setDefaultStats();
            }
        } catch (error) {
            console.error('Error de conexión:', error);
            setDefaultStats();
        }
    }

    // Función para establecer estadísticas por defecto (en caso de error)
    function setDefaultStats() {
        document.getElementById('totalPedidos').textContent = '0';
        document.getElementById('totalUsuarios').textContent = '0';
        document.getElementById('totalProductos').textContent = '0';
        document.getElementById('totalIngresos').textContent = '$0';
        
        const activityList = document.querySelector('.activity-list');
        activityList.innerHTML = `
            <div class="activity-item">
                <div class="activity-content">
                    <div class="activity-text">No se pudieron cargar las actividades recientes</div>
                    <div class="activity-time">Intenta recargar la página</div>
                </div>
            </div>
        `;
    }

    // Cargar estadísticas al iniciar
    document.addEventListener('DOMContentLoaded', function() {
        fetchEstadisticas();
        
        // Actualizar estadísticas cada 2 minutos
        setInterval(fetchEstadisticas, 120000);
    });
</script>
</body>
</html>