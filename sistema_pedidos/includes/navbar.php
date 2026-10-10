<?php
// sistema_pedidos/includes/navbar.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$current_role = $_SESSION['role'] ?? '';
$current_user = $_SESSION['user_name'] ?? 'Usuario';

// Obtener la ruta actual para determinar el módulo activo
$current_file = basename($_SERVER['PHP_SELF']);
$current_directory = basename(dirname($_SERVER['PHP_SELF']));

// Determinar qué módulo está activo
$active_module = '';
if ($current_directory == 'admin') {
    $active_module = 'admin';
} elseif ($current_directory == 'cajero') {
    $active_module = 'cajero';
} elseif ($current_directory == 'cocina') {
    $active_module = 'cocina';
}

// Estado del sidebar (guardado en sesión)
if (!isset($_SESSION['sidebar_collapsed'])) {
    $_SESSION['sidebar_collapsed'] = false;
}
$sidebar_collapsed = $_SESSION['sidebar_collapsed'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Variables de colores */
        :root {
            --primary-color: #2563eb;
            --primary-dark: #1d4ed8;
            --secondary-color: #64748b;
            --accent-color: #f59e0b;
            --text-light: #f8fafc;
            --text-dark: #1e293b;
            --bg-dark: #0f172a;
            --bg-darker: #020617;
            --bg-card: #ffffff;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --transition: all 0.3s ease;
        }

        /* Navbar Lateral */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            width: 280px;
            background: linear-gradient(180deg, var(--bg-darker) 0%, var(--bg-dark) 100%);
            color: var(--text-light);
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.3);
            z-index: 1000;
            transition: var(--transition);
            display: flex;
            flex-direction: column;
            border-right: 3px solid var(--accent-color);
        }

        /* Sidebar colapsado */
        .sidebar.collapsed {
            width: 80px;
        }

        .sidebar.collapsed .nav-text,
        .sidebar.collapsed .section-label,
        .sidebar.collapsed .user-info,
        .sidebar.collapsed .brand-text,
        .sidebar.collapsed .logout-text {
            display: none;
        }

        .sidebar.collapsed .sidebar-brand {
            justify-content: center;
            padding: 0;
        }

        .sidebar.collapsed .nav-link {
            justify-content: center;
            padding: 0.875rem 0;
        }

        .sidebar.collapsed .user-profile {
            justify-content: center;
            padding: 0.75rem 0;
        }

        .sidebar.collapsed .logout-btn {
            justify-content: center;
            padding: 0.875rem 0;
        }

        .sidebar.collapsed .nav-link.active::after {
            right: 0.5rem;
        }

        /* Header del Sidebar */
        .sidebar-header {
            padding: 2rem 1.5rem 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
            position: relative;
        }

        .sidebar.collapsed .sidebar-header {
            padding: 2rem 0.5rem 1.5rem;
        }

        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
            color: var(--text-light);
            font-weight: 700;
            font-size: 1.4rem;
            transition: var(--transition);
            justify-content: flex-start;
        }

        .sidebar.collapsed .sidebar-brand {
            justify-content: center;
        }

        .brand-logo {
            font-size: 2rem;
            color: var(--accent-color);
            flex-shrink: 0;
        }

        .brand-text {
            transition: opacity 0.3s ease;
        }

        .sidebar.collapsed .brand-text {
            opacity: 0;
        }

        /* Botón de colapsar */
        .collapse-btn {
            position: absolute;
            top: 1.5rem;
            right: 1rem;
            background: rgba(255, 255, 255, 0.1);
            border: none;
            color: var(--text-light);
            width: 2rem;
            height: 2rem;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .collapse-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: rotate(180deg);
        }

        .sidebar.collapsed .collapse-btn {
            right: 0.5rem;
            transform: rotate(180deg);
        }

        .sidebar.collapsed .collapse-btn:hover {
            transform: rotate(0deg);
        }

        /* Menú de Navegación */
        .sidebar-menu {
            flex: 1;
            padding: 1.5rem 0;
            overflow-y: auto;
        }

        .sidebar.collapsed .sidebar-menu {
            padding: 1.5rem 0.25rem;
        }

        .nav-section {
            margin-bottom: 1.5rem;
        }

        .section-label {
            padding: 0.5rem 1.5rem;
            font-size: 0.75rem;
            text-transform: uppercase;
            color: var(--secondary-color);
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: opacity 0.3s ease;
        }

        .sidebar.collapsed .section-label {
            padding: 0.5rem 0.25rem;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin: 0 0.5rem 1rem;
        }

        .nav-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .nav-item {
            margin: 0.25rem 0.75rem;
        }

        .sidebar.collapsed .nav-item {
            margin: 0.25rem 0.5rem;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.875rem 1rem;
            text-decoration: none;
            color: var(--text-light);
            border-radius: 0.75rem;
            transition: var(--transition);
            font-weight: 500;
            position: relative;
            overflow: hidden;
        }

        .nav-link::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
            transition: left 0.5s;
        }

        .nav-link:hover::before {
            left: 100%;
        }

        .nav-link:hover {
            background: rgba(255, 255, 255, 0.1);
            color: var(--accent-color);
            transform: translateX(5px);
        }

        .nav-link.active {
            background: var(--primary-color);
            color: white;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
            transform: translateX(5px);
        }

        .nav-link.active::after {
            content: '';
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            width: 6px;
            height: 6px;
            background: var(--accent-color);
            border-radius: 50%;
        }

        .nav-icon {
            font-size: 1.2rem;
            width: 20px;
            text-align: center;
            flex-shrink: 0;
        }

        .nav-text {
            flex: 1;
            transition: opacity 0.3s ease;
        }

        /* Tooltips para sidebar colapsado */
        .nav-link .tooltip {
            position: absolute;
            left: 100%;
            top: 50%;
            transform: translateY(-50%);
            background: var(--bg-darker);
            color: var(--text-light);
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            box-shadow: var(--shadow);
            z-index: 1001;
            margin-left: 1rem;
        }

        .nav-link .tooltip::before {
            content: '';
            position: absolute;
            right: 100%;
            top: 50%;
            transform: translateY(-50%);
            border: 6px solid transparent;
            border-right-color: var(--bg-darker);
        }

        .sidebar.collapsed .nav-link:hover .tooltip {
            opacity: 1;
            visibility: visible;
        }

        /* Footer del Sidebar */
        .sidebar-footer {
            padding: 1.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(0, 0, 0, 0.2);
        }

        .sidebar.collapsed .sidebar-footer {
            padding: 1.5rem 0.5rem;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 0.75rem;
            transition: var(--transition);
            cursor: pointer;
        }

        .user-profile:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .user-avatar {
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
            flex-shrink: 0;
        }

        .user-info {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            transition: opacity 0.3s ease;
        }

        .user-name {
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 0.1rem;
        }

        .user-role {
            font-size: 0.75rem;
            opacity: 0.8;
            background: var(--primary-color);
            padding: 0.2rem 0.5rem;
            border-radius: 1rem;
            color: white;
        }

        .logout-btn {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            width: 100%;
            padding: 0.875rem 1rem;
            background: rgba(220, 38, 38, 0.1);
            color: #fca5a5;
            border: none;
            border-radius: 0.75rem;
            cursor: pointer;
            transition: var(--transition);
            font-weight: 500;
            margin-top: 0.75rem;
        }

        .logout-btn:hover {
            background: rgba(220, 38, 38, 0.2);
            color: #fecaca;
            transform: translateX(5px);
        }

        .logout-text {
            transition: opacity 0.3s ease;
        }

        /* Contenido Principal */
        .main-content {
            margin-left: 280px;
            transition: var(--transition);
            min-height: 100vh;
            background: #f8fafc;
        }

        .main-content.expanded {
            margin-left: 80px;
        }

        /* Responsive */
        .sidebar-toggle {
            display: none;
            position: fixed;
            top: 1rem;
            left: 1rem;
            z-index: 1001;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 0.5rem;
            width: 3rem;
            height: 3rem;
            font-size: 1.25rem;
            cursor: pointer;
            box-shadow: var(--shadow);
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                width: 280px;
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .sidebar.collapsed {
                width: 280px;
                transform: translateX(-100%);
            }

            .sidebar.collapsed.active {
                transform: translateX(0);
                width: 280px;
            }

            .sidebar.collapsed .nav-text,
            .sidebar.collapsed .section-label,
            .sidebar.collapsed .user-info,
            .sidebar.collapsed .brand-text,
            .sidebar.collapsed .logout-text {
                display: block;
            }

            .sidebar.collapsed .sidebar-brand {
                justify-content: flex-start;
                padding: 0;
            }

            .sidebar.collapsed .nav-link {
                justify-content: flex-start;
                padding: 0.875rem 1rem;
            }

            .sidebar.collapsed .user-profile {
                justify-content: flex-start;
                padding: 0.75rem;
            }

            .sidebar.collapsed .logout-btn {
                justify-content: flex-start;
                padding: 0.875rem 1rem;
            }

            .main-content {
                margin-left: 0;
            }

            .main-content.expanded {
                margin-left: 0;
            }

            .sidebar-toggle {
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .sidebar-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.5);
                z-index: 999;
            }

            .sidebar.active + .sidebar-overlay,
            .sidebar.collapsed.active + .sidebar-overlay {
                display: block;
            }
        }

        /* Scrollbar personalizado */
        .sidebar-menu::-webkit-scrollbar {
            width: 4px;
        }

        .sidebar-menu::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
        }

        .sidebar-menu::-webkit-scrollbar-thumb {
            background: var(--accent-color);
            border-radius: 2px;
        }
    </style>
</head>
<body>
    <!-- Botón toggle para móvil -->
    <button class="sidebar-toggle" id="sidebarToggle">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Navbar Lateral -->
    <nav class="sidebar <?php echo $sidebar_collapsed ? 'collapsed' : ''; ?>" id="sidebar">
        <!-- Header -->
        <div class="sidebar-header">
            <a href="../admin/index.php" class="sidebar-brand">
                <i class="fas fa-concierge-bell brand-logo"></i>
                <span class="brand-text">Sistema Pedidos</span>
            </a>
            <button class="collapse-btn" id="collapseBtn" title="<?php echo $sidebar_collapsed ? 'Expandir' : 'Colapsar'; ?>">
                <i class="fas fa-chevron-left"></i>
            </button>
        </div>

        <!-- Menú de Navegación -->
        <div class="sidebar-menu">
            <!-- Sección Principal -->
            <div class="nav-section">
                <div class="section-label">Navegación Principal</div>
                <ul class="nav-list">
                    <?php if ($current_role == 'Admin'): ?>
                    <li class="nav-item">
                        <a href="../admin/index.php" class="nav-link <?php echo $active_module == 'admin' ? 'active' : ''; ?>">
                            <i class="fas fa-cogs nav-icon"></i>
                            <span class="nav-text">Panel Administración</span>
                            <span class="tooltip">Panel Administración</span>
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if ($current_role == 'Admin'): ?>
                    <li class="nav-item">
                        <a href="../admin/inventario.php" class="nav-link <?php echo $current_file == 'inventario.php' ? 'active' : ''; ?>">
                            <i class="fas fa-boxes nav-icon"></i>
                            <span class="nav-text">Gestión de Inventario</span>
                            <span class="tooltip">Gestión de Inventario</span>
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if ($current_role == 'cajero' || $current_role == 'Admin'): ?>
                    <li class="nav-item">
                        <a href="../cajero/index.php" class="nav-link <?php echo $active_module == 'cajero' ? 'active' : ''; ?>">
                            <i class="fas fa-cash-register nav-icon"></i>
                            <span class="nav-text">Módulo Cajero</span>
                            <span class="tooltip">Módulo Cajero</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php if ($current_role == 'cocina' || $current_role == 'Admin'): ?>
                    <li class="nav-item">
                        <a href="../cocina/index.php" class="nav-link <?php echo $active_module == 'cocina' ? 'active' : ''; ?>">
                            <i class="fas fa-utensils nav-icon"></i>
                            <span class="nav-text">Módulo Cocina</span>
                            <span class="tooltip">Módulo Cocina</span>
                        </a>
                    </li>
                    <?php endif; ?>

                    <!-- Nueva opción: Gestión de Mesas para Cajero y Admin -->
                    <?php if ($current_role == 'cajero' || $current_role == 'Admin'): ?>
                    <li class="nav-item">
                        <a href="../cajero/mesas.php" class="nav-link <?php echo $current_file == 'mesas.php' ? 'active' : ''; ?>">
                            <i class="fas fa-chair nav-icon"></i>
                            <span class="nav-text">Gestión de Mesas</span>
                            <span class="tooltip">Gestión de Mesas</span>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
            

            <!-- Sección Reportes -->
            <div class="nav-section">
                <div class="section-label">Reportes</div>
                <ul class="nav-list">
                    <li class="nav-item">
                        <a href="../admin/reportes.php" class="nav-link <?php echo $current_file == 'reportes.php' ? 'active' : ''; ?>">
                            <i class="fas fa-chart-bar nav-icon"></i>
                            <span class="nav-text">Dashboard</span>
                            <span class="tooltip">Dashboard</span>
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Sección Configuración -->
            <div class="nav-section">
                <div class="section-label">Configuración</div>
                <ul class="nav-list">
                    <li class="nav-item">
                        <a href="../admin/ajustes.php" class="nav-link <?php echo $current_file == 'ajustes.php' ? 'active' : ''; ?>">
                            <i class="fas fa-sliders-h nav-icon"></i>
                            <span class="nav-text">Ajustes</span>
                            <span class="tooltip">Ajustes</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="../admin/gestion.php" class="nav-link <?php echo $current_file == 'gestion.php' ? 'active' : ''; ?>">
                            <i class="fas fa-cog nav-icon"></i>
                            <span class="nav-text">Gestión del Sistema</span>
                            <span class="tooltip">Gestión del Sistema</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Footer con información de usuario -->
        <div class="sidebar-footer">
            <div class="user-profile" onclick="event.preventDefault(); document.getElementById('profileForm').submit();">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($current_user, 0, 1)); ?>
                </div>
                <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars($current_user); ?></div>
                    <div class="user-role"><?php echo htmlspecialchars($current_role); ?></div>
                </div>
            </div>
            
            <form id="logoutForm" action="../auth/logout.php" method="POST" style="display: none;"></form>
            <form id="profileForm" action="../auth/profile.php" method="GET" style="display: none;"></form>
            <form id="sidebarForm" action="toggle_sidebar.php" method="POST" style="display: none;">
                <input type="hidden" name="sidebar_collapsed" id="sidebarCollapsedInput" value="<?php echo $sidebar_collapsed ? '1' : '0'; ?>">
            </form>
            
            <button class="logout-btn" onclick="event.preventDefault(); document.getElementById('logoutForm').submit();">
                <i class="fas fa-sign-out-alt"></i>
                <span class="logout-text">Cerrar Sesión</span>
            </button>
        </div>
    </nav>

    <!-- Overlay para móvil -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <script>
        // Toggle del sidebar en móvil
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        const collapseBtn = document.getElementById('collapseBtn');
        const mainContent = document.querySelector('.main-content');
        const sidebarCollapsedInput = document.getElementById('sidebarCollapsedInput');

        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });

        sidebarOverlay.addEventListener('click', function() {
            sidebar.classList.remove('active');
        });

        // Toggle colapsar/expandir sidebar
        collapseBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const isCollapsed = sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded', isCollapsed);
            
            // Actualizar el ícono del botón
            const icon = collapseBtn.querySelector('i');
            if (isCollapsed) {
                icon.className = 'fas fa-chevron-right';
                collapseBtn.title = 'Expandir';
            } else {
                icon.className = 'fas fa-chevron-left';
                collapseBtn.title = 'Colapsar';
            }
            
            // Guardar estado en el formulario y enviar
            sidebarCollapsedInput.value = isCollapsed ? '1' : '0';
            document.getElementById('sidebarForm').submit();
        });

        // Cerrar sidebar al hacer clic en un enlace en móvil
        const navLinks = document.querySelectorAll('.nav-link');
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 768) {
                    sidebar.classList.remove('active');
                }
            });
        });

        // Ajustar contenido al redimensionar
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                sidebar.classList.remove('active');
            }
        });

        // Efecto de carga suave
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(() => {
                sidebar.style.transform = 'translateX(0)';
            }, 100);
        });

        // Mejorar la detección de enlaces activos
        document.addEventListener('DOMContentLoaded', function() {
            const navLinks = document.querySelectorAll('.nav-link');
            const currentUrl = window.location.href;
            
            navLinks.forEach(link => {
                const linkUrl = link.href;
                
                // Verificar si el enlace coincide con la URL actual
                if (currentUrl === linkUrl) {
                    link.classList.add('active');
                }
                
                // Para enlaces que pueden estar en subdirectorios
                if (currentUrl.includes(linkUrl) && linkUrl !== '') {
                    // Solo marcar como activo si no es la raíz
                    const linkPath = new URL(linkUrl).pathname;
                    const currentPath = new URL(currentUrl).pathname;
                    
                    if (currentPath.startsWith(linkPath) && linkPath !== '/') {
                        link.classList.add('active');
                    }
                }
            });
            
            // Remover active de todos los enlaces del módulo si hay múltiples activos
            const moduleLinks = document.querySelectorAll('.nav-link[href*="/admin/"], .nav-link[href*="/cajero/"], .nav-link[href*="/cocina/"]');
            let activeModuleLinks = Array.from(moduleLinks).filter(link => link.classList.contains('active'));
            
            if (activeModuleLinks.length > 1) {
                // Encontrar el enlace más específico (más largo)
                activeModuleLinks.sort((a, b) => b.href.length - a.href.length);
                
                // Mantener solo el primero (más específico) como activo
                for (let i = 1; i < activeModuleLinks.length; i++) {
                    activeModuleLinks[i].classList.remove('active');
                }
            }
        });

        // Persistir estado del sidebar en localStorage (opcional)
        document.addEventListener('DOMContentLoaded', function() {
            const savedState = localStorage.getItem('sidebarCollapsed');
            if (savedState === 'true' && !sidebar.classList.contains('collapsed')) {
                sidebar.classList.add('collapsed');
                mainContent.classList.add('expanded');
                const icon = collapseBtn.querySelector('i');
                icon.className = 'fas fa-chevron-right';
                collapseBtn.title = 'Expandir';
            }
        });

        // Guardar estado en localStorage cuando cambie
        collapseBtn.addEventListener('click', function() {
            const isCollapsed = sidebar.classList.contains('collapsed');
            localStorage.setItem('sidebarCollapsed', isCollapsed);
        });
    </script>
</body>
</html>