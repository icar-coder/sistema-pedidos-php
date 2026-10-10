<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
header("Location: ../acceso_denegado.php");
exit();
}

// Obtener tab inicial desde URL
$default_tab = $_GET['tab'] ?? 'pedidos';
$valid_tabs = ['pedidos', 'usuarios', 'productos', 'categorias', 'clientes'];
$active_tab = in_array($default_tab, $valid_tabs) ? $default_tab : 'pedidos';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión - Sistema de Pedidos</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2563eb;
            --primary-dark: #1d4ed8;
            --secondary-color: #64748b;
            --accent-color: #f59e0b;
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
        .management-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e2e8f0;
        }

        .header-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--bg-dark);
        }

        .back-dashboard {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: 0.75rem;
            transition: var(--transition);
            font-weight: 500;
        }

        .back-dashboard:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
            text-decoration: none;
            color: white;
        }

        /* Tabs de Gestión */
        .admin-tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 2rem;
            background: var(--bg-card);
            padding: 1rem;
            border-radius: 1rem;
            box-shadow: var(--shadow);
            flex-wrap: wrap;
        }

        .tab-btn {
            padding: 0.75rem 1.5rem;
            border: none;
            background: transparent;
            color: var(--secondary-color);
            border-radius: 0.75rem;
            cursor: pointer;
            transition: var(--transition);
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .tab-btn:hover {
            background: rgba(37, 99, 235, 0.1);
            color: var(--primary-color);
        }

        .tab-btn.active {
            background: var(--primary-color);
            color: white;
        }

        .admin-section {
            display: none;
            animation: fadeIn 0.3s ease;
            background: var(--bg-card);
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: var(--shadow);
        }

        .admin-section.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--bg-dark);
        }

        /* Estilos para tablas y paginación */
        .search-container {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .search-container input {
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            flex: 1;
            min-width: 250px;
        }

        .table-container {
            overflow-x: auto;
            margin-bottom: 1.5rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        th, td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }

        th {
            background-color: #f9fafb;
            font-weight: 600;
            color: #374151;
        }

        tr:hover {
            background-color: #f9fafb;
        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .pagination button {
            padding: 0.5rem 1rem;
            border: 1px solid #d1d5db;
            background: white;
            border-radius: 0.375rem;
            cursor: pointer;
            transition: var(--transition);
        }

        .pagination button:hover:not(:disabled) {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .pagination button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .pagination-info {
            color: #6b7280;
            font-size: 0.875rem;
        }

        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 0.375rem;
            cursor: pointer;
            transition: var(--transition);
            font-weight: 500;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
        }

        .btn-secondary {
            background: #6b7280;
            color: white;
        }

        .btn-secondary:hover {
            background: #4b5563;
        }

        .btn-danger {
            background: #dc2626;
            color: white;
        }

        .btn-danger:hover {
            background: #b91c1c;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .admin-container {
                margin-left: 0;
                padding: 1rem;
            }

            .management-header {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }

            .admin-tabs {
                flex-direction: column;
            }

            .search-container {
                flex-direction: column;
                align-items: stretch;
            }

            .search-container input {
                min-width: auto;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="admin-container">
        <!-- Header de Gestión -->
        <div class="management-header">
            <h1 class="header-title">Gestión del Sistema</h1>
            <a href="index.php" class="back-dashboard">
                <i class="fas fa-arrow-left"></i>
                Volver al Dashboard
            </a>
        </div>

        <!-- Tabs de Gestión -->
        <div class="admin-tabs">
            <button class="tab-btn <?php echo $active_tab === 'pedidos' ? 'active' : ''; ?>" data-tab="pedidos">
                <i class="fas fa-clipboard-list"></i>
                Gestión de Pedidos
            </button>
            <button class="tab-btn <?php echo $active_tab === 'usuarios' ? 'active' : ''; ?>" data-tab="usuarios">
                <i class="fas fa-user-cog"></i>
                Gestión de Usuarios
            </button>
            <button class="tab-btn <?php echo $active_tab === 'productos' ? 'active' : ''; ?>" data-tab="productos">
                <i class="fas fa-box-open"></i>
                Gestión de Productos
            </button>
            <button class="tab-btn <?php echo $active_tab === 'categorias' ? 'active' : ''; ?>" data-tab="categorias">
                <i class="fas fa-tags"></i>
                Gestión de Categorías
            </button>
            <button class="tab-btn <?php echo $active_tab === 'clientes' ? 'active' : ''; ?>" data-tab="clientes">
                <i class="fas fa-users"></i>
                Gestión de Clientes
            </button>
        </div>

        <!-- Sección Pedidos -->
        <div id="pedidos-tab" class="admin-section <?php echo $active_tab === 'pedidos' ? 'active' : ''; ?>">
            <div class="section-header">
                <h2 class="section-title">Gestión de Pedidos</h2>
            </div>
            <div class="panel-section">
                <div class="search-container">
                    <input type="text" id="searchPedidosInput" placeholder="Buscar por ID de pedido o nombre del cliente...">
                    <button type="button" class="btn btn-primary" id="searchPedidosBtn">Buscar Pedido</button>
                    <button type="button" class="btn btn-secondary" id="listarTodosPedidosBtn">Listar Todos</button>
                </div>
                <div id="pedidosTableContainer" style="margin-top: 20px;">
                    <p>Usa la barra de búsqueda o haz clic en "Listar Todos" para ver el historial.</p>
                </div>
            </div>
        </div>

        <!-- Sección Usuarios -->
        <div id="usuarios-tab" class="admin-section <?php echo $active_tab === 'usuarios' ? 'active' : ''; ?>">
            <div class="section-header">
                <h2 class="section-title">Gestión de Usuarios</h2>
                <button class="btn btn-primary" id="newUserBtn">
                    <i class="fas fa-plus"></i>
                    Crear Nuevo Usuario
                </button>
            </div>
            <div class="search-container">
                <input type="text" id="searchUsuariosInput" placeholder="Buscar por nombre de usuario o rol...">
                <button type="button" class="btn btn-primary" id="searchUsuariosBtn">Buscar</button>
                <button type="button" class="btn btn-secondary" id="listarTodosUsuariosBtn">Listar Todos</button>
            </div>
            <div class="table-container">
                <table id="usuarios-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre de Usuario</th>
                            <th>Rol</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
            <div class="pagination" id="usuariosPagination">
                <button id="usuariosPrevBtn" disabled>Anterior</button>
                <span class="pagination-info" id="usuariosPageInfo">Página 1 de 1</span>
                <button id="usuariosNextBtn" disabled>Siguiente</button>
            </div>
        </div>

        <!-- Sección Productos -->
        <div id="productos-tab" class="admin-section <?php echo $active_tab === 'productos' ? 'active' : ''; ?>">
            <div class="section-header">
                <h2 class="section-title">Gestión de Productos</h2>
                <button class="btn btn-primary" id="newProductBtn">
                    <i class="fas fa-plus"></i>
                    Crear Nuevo Producto
                </button>
            </div>
            <div class="search-container">
                <input type="text" id="searchProductosInput" placeholder="Buscar por nombre, descripción o categoría...">
                <button type="button" class="btn btn-primary" id="searchProductosBtn">Buscar</button>
                <button type="button" class="btn btn-secondary" id="listarTodosProductosBtn">Listar Todos</button>
            </div>
            <div class="table-container">
                <table id="productos-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Descripción</th>
                            <th>Precio</th>
                            <th>Categoría</th>
                            <th>Disponible</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
            <div class="pagination" id="productosPagination">
                <button id="productosPrevBtn" disabled>Anterior</button>
                <span class="pagination-info" id="productosPageInfo">Página 1 de 1</span>
                <button id="productosNextBtn" disabled>Siguiente</button>
            </div>
        </div>

        <!-- Sección Categorías -->
        <div id="categorias-tab" class="admin-section <?php echo $active_tab === 'categorias' ? 'active' : ''; ?>">
            <div class="section-header">
                <h2 class="section-title">Gestión de Categorías</h2>
                <button class="btn btn-primary" id="newCategoryBtn">
                    <i class="fas fa-plus"></i>
                    Crear Nueva Categoría
                </button>
            </div>
            <div class="search-container">
                <input type="text" id="searchCategoriasInput" placeholder="Buscar por nombre de categoría...">
                <button type="button" class="btn btn-primary" id="searchCategoriasBtn">Buscar</button>
                <button type="button" class="btn btn-secondary" id="listarTodosCategoriasBtn">Listar Todos</button>
            </div>
            <div class="table-container">
                <table id="categorias-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
            <div class="pagination" id="categoriasPagination">
                <button id="categoriasPrevBtn" disabled>Anterior</button>
                <span class="pagination-info" id="categoriasPageInfo">Página 1 de 1</span>
                <button id="categoriasNextBtn" disabled>Siguiente</button>
            </div>
        </div>

        <!-- Sección Clientes -->
        <div id="clientes-tab" class="admin-section <?php echo $active_tab === 'clientes' ? 'active' : ''; ?>">
            <div class="section-header">
                <h2 class="section-title">Gestión de Clientes</h2>
                <button class="btn btn-primary" id="newClienteBtn">
                    <i class="fas fa-plus"></i>
                    Crear Nuevo Cliente
                </button>
            </div>
            <div class="search-container">
                <input type="text" id="searchClientesInput" placeholder="Buscar por cédula, nombre o apellido...">
                <button type="button" class="btn btn-primary" id="searchClientesBtn">Buscar</button>
                <button type="button" class="btn btn-secondary" id="listarTodosClientesBtn">Listar Todos</button>
            </div>
            <div class="table-container">
                <table id="clientes-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Cédula</th>
                            <th>Nombre</th>
                            <th>Apellido</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
            <div class="pagination" id="clientesPagination">
                <button id="clientesPrevBtn" disabled>Anterior</button>
                <span class="pagination-info" id="clientesPageInfo">Página 1 de 1</span>
                <button id="clientesNextBtn" disabled>Siguiente</button>
            </div>
        </div>
    </div>

<!-- Modal de Usuarios con Preguntas de Seguridad -->
<div id="userModal" class="modal">
    <div class="modal-content">
        <span class="close-button">&times;</span>
        <h3 id="userModalTitle"></h3>
        <form id="userForm">
            <input type="hidden" id="userId" name="id_usuario">
            <div class="form-group">
                <label for="userName">Nombre de Usuario:</label>
                <input type="text" id="userName" name="nombre_usuario" required>
            </div>
            <div class="form-group">
                <label for="userPassword">Contraseña:</label>
                <input type="password" id="userPassword" name="password">
                <small>Deja en blanco para no cambiarla (solo en edición).</small>
            </div>
            <div class="form-group">
                <label for="userRole">Rol:</label>
                <select id="userRole" name="rol" required>
                    <option value="">Seleccionar rol</option>
                    <option value="Admin">Admin</option>
                    <option value="cocina">Cocina</option>
                    <option value="cajero">Cajero</option>
                </select>
            </div>
            
            <!-- SECCIÓN DE PREGUNTAS DE SEGURIDAD -->
            <div class="form-group">
                <label for="userSecurityQuestion">Pregunta de Seguridad:</label>
                <select id="userSecurityQuestion" name="pregunta_seguridad">
                    <option value="">Seleccionar pregunta</option>
                    <option value="¿Cuál es el nombre de tu mascota?">¿Cuál es el nombre de tu mascota?</option>
                    <option value="¿Cuál es tu color favorito?">¿Cuál es tu color favorito?</option>
                    <option value="¿En qué ciudad naciste?">¿En qué ciudad naciste?</option>
                    <option value="¿Cuál es el nombre de tu mejor amigo/a?">¿Cuál es el nombre de tu mejor amigo/a?</option>
                    <option value="¿Cuál es tu comida favorita?">¿Cuál es tu comida favorita?</option>
                    <option value="¿Cómo se llama tu madre?">¿Cómo se llama tu madre?</option>
                    <option value="otra">Escribir mi propia pregunta...</option>
                </select>
            </div>
            
            <div class="form-group" id="customQuestionGroup" style="display: none;">
                <label for="userCustomQuestion">Pregunta Personalizada:</label>
                <input type="text" id="userCustomQuestion" name="pregunta_personalizada" placeholder="Escribe tu pregunta personalizada">
            </div>
            
            <div class="form-group">
                <label for="userSecurityAnswer">Respuesta de Seguridad:</label>
                <input type="text" id="userSecurityAnswer" name="respuesta_seguridad" placeholder="Tu respuesta para recuperar contraseña">
            </div>
            
            <button type="submit" class="btn btn-primary">Guardar</button>
        </form>
    </div>
</div>

    <div id="productModal" class="modal">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <h3 id="productModalTitle"></h3>
            <form id="productForm">
                <input type="hidden" id="productId" name="id_producto">
                <div class="form-group">
                    <label for="productName">Nombre:</label>
                    <input type="text" id="productName" name="nombre_producto" required>
                </div>
                <div class="form-group">
                    <label for="productDesc">Descripción:</label>
                    <textarea id="productDesc" name="descripcion"></textarea>
                </div>
                <div class="form-group">
                    <label for="productPrice">Precio:</label>
                    <input type="number" id="productPrice" name="precio" step="0.01" required>
                </div>
                <div class="form-group">
                    <label for="productCategory">Categoría:</label>
                    <select id="productCategory" name="id_categoria" required></select>
                </div>
                <div class="form-group">
                    <label for="productAvailable">Disponible:</label>
                    <input type="checkbox" id="productAvailable" name="disponible" value="1" checked>
                </div>
                <button type="submit" class="btn btn-primary">Guardar</button>
            </form>
        </div>
    </div>

    <div id="categoryModal" class="modal">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <h3 id="categoryModalTitle"></h3>
            <form id="categoryForm">
                <input type="hidden" id="categoryId" name="id_categoria">
                <div class="form-group">
                    <label for="categoryName">Nombre de Categoría:</label>
                    <input type="text" id="categoryName" name="nombre_categoria" required>
                </div>
                <button type="submit" class="btn btn-primary">Guardar</button>
            </form>
        </div>
    </div>

    <div id="clienteModal" class="modal">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <h3 id="clienteModalTitle"></h3>
            <form id="clienteForm">
                <input type="hidden" id="clienteId" name="id_cliente">
                <div class="form-group">
                    <label for="clienteCedula">Cédula:</label>
                    <input type="text" id="clienteCedula" name="cedula_cliente" required>
                </div>
                <div class="form-group">
                    <label for="clienteNombre">Nombre:</label>
                    <input type="text" id="clienteNombre" name="nombre_cliente" required>
                </div>
                <div class="form-group">
                    <label for="clienteApellido">Apellido:</label>
                    <input type="text" id="clienteApellido" name="apellido_cliente" required>
                </div>
                <button type="submit" class="btn btn-primary">Guardar</button>
            </form>
        </div>
    </div>

    <script>
        // Variables globales para paginación
        const paginationState = {
            usuarios: { currentPage: 1, totalPages: 1, searchTerm: '' },
            productos: { currentPage: 1, totalPages: 1, searchTerm: '' },
            categorias: { currentPage: 1, totalPages: 1, searchTerm: '' },
            clientes: { currentPage: 1, totalPages: 1, searchTerm: '' }
        };

        const RESULTS_PER_PAGE = 20;

        // Función para cambiar tabs
        function showTab(tabName) {
            // Ocultar todas las secciones
            document.querySelectorAll('.admin-section').forEach(section => {
                section.classList.remove('active');
            });
            
            // Remover active de todos los tabs
            document.querySelectorAll('.tab-btn').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Mostrar sección seleccionada
            document.getElementById(tabName + '-tab').classList.add('active');
            
            // Activar tab correspondiente
            document.querySelectorAll('.tab-btn').forEach(tab => {
                if (tab.getAttribute('data-tab') === tabName) {
                    tab.classList.add('active');
                }
            });

            // Actualizar URL sin recargar la página
            const newUrl = `gestion.php?tab=${tabName}`;
            window.history.pushState({ path: newUrl }, '', newUrl);

            // Cargar datos del tab activo
            loadTabData(tabName);
        }

        // Función para cargar datos del tab
        function loadTabData(tabName) {
            switch(tabName) {
                case 'usuarios':
                    loadUsuarios();
                    break;
                case 'productos':
                    loadProductos();
                    break;
                case 'categorias':
                    loadCategorias();
                    break;
                case 'clientes':
                    loadClientes();
                    break;
            }
        }

        // Función genérica para manejar paginación
        function setupPagination(tabName, loadFunction) {
            const prevBtn = document.getElementById(`${tabName}PrevBtn`);
            const nextBtn = document.getElementById(`${tabName}NextBtn`);
            const pageInfo = document.getElementById(`${tabName}PageInfo`);

            prevBtn?.addEventListener('click', () => {
                if (paginationState[tabName].currentPage > 1) {
                    paginationState[tabName].currentPage--;
                    loadFunction();
                }
            });

            nextBtn?.addEventListener('click', () => {
                if (paginationState[tabName].currentPage < paginationState[tabName].totalPages) {
                    paginationState[tabName].currentPage++;
                    loadFunction();
                }
            });
        }

        // Función para actualizar controles de paginación
        function updatePaginationControls(tabName, totalItems) {
            const totalPages = Math.ceil(totalItems / RESULTS_PER_PAGE);
            paginationState[tabName].totalPages = totalPages;
            
            const prevBtn = document.getElementById(`${tabName}PrevBtn`);
            const nextBtn = document.getElementById(`${tabName}NextBtn`);
            const pageInfo = document.getElementById(`${tabName}PageInfo`);

            if (prevBtn && nextBtn && pageInfo) {
                prevBtn.disabled = paginationState[tabName].currentPage <= 1;
                nextBtn.disabled = paginationState[tabName].currentPage >= totalPages;
                pageInfo.textContent = `Página ${paginationState[tabName].currentPage} de ${totalPages}`;
            }
        }

        // Función para cargar clientes
        function loadClientes(searchTerm = '') {
            const currentPage = paginationState.clientes.currentPage;
            const offset = (currentPage - 1) * RESULTS_PER_PAGE;

            fetch(`../api/clientes_api.php?action=list&search=${encodeURIComponent(searchTerm)}&limit=${RESULTS_PER_PAGE}&offset=${offset}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderClientesTable(data.data);
                        updatePaginationControls('clientes', data.total || data.data.length);
                    } else {
                        alert('Error al cargar clientes: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al cargar clientes');
                });
        }

        // Función para renderizar tabla de clientes
        function renderClientesTable(clientes) {
            const tbody = document.querySelector('#clientes-table tbody');
            tbody.innerHTML = '';

            if (clientes.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" style="text-align: center;">No se encontraron clientes</td></tr>';
                return;
            }

            clientes.forEach(cliente => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${cliente.id_cliente}</td>
                    <td>${cliente.cedula_cliente}</td>
                    <td>${cliente.nombre_cliente}</td>
                    <td>${cliente.apellido_cliente}</td>
                    <td>
                        <button class="btn btn-primary edit-cliente" data-id="${cliente.id_cliente}">
                            <i class="fas fa-edit"></i> Editar
                        </button>
                        <button class="btn btn-danger delete-cliente" data-id="${cliente.id_cliente}">
                            <i class="fas fa-trash"></i> Eliminar
                        </button>
                    </td>
                `;
                tbody.appendChild(tr);
            });

            // Agregar event listeners para los botones
            document.querySelectorAll('.edit-cliente').forEach(btn => {
                btn.addEventListener('click', function() {
                    editCliente(this.getAttribute('data-id'));
                });
            });

            document.querySelectorAll('.delete-cliente').forEach(btn => {
                btn.addEventListener('click', function() {
                    deleteCliente(this.getAttribute('data-id'));
                });
            });
        }

        // Funciones para CRUD de clientes
        function newCliente() {
            document.getElementById('clienteModalTitle').textContent = 'Crear Nuevo Cliente';
            document.getElementById('clienteForm').reset();
            document.getElementById('clienteId').value = '';
            document.getElementById('clienteModal').style.display = 'block';
        }

        function editCliente(id) {
            fetch(`../api/clientes_api.php?action=read_one&cedula=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const cliente = data.data;
                        document.getElementById('clienteModalTitle').textContent = 'Editar Cliente';
                        document.getElementById('clienteId').value = cliente.id_cliente;
                        document.getElementById('clienteCedula').value = cliente.cedula_cliente;
                        document.getElementById('clienteNombre').value = cliente.nombre_cliente;
                        document.getElementById('clienteApellido').value = cliente.apellido_cliente;
                        document.getElementById('clienteModal').style.display = 'block';
                    } else {
                        alert('Error al cargar cliente: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al cargar cliente');
                });
        }

        function deleteCliente(id) {
            if (confirm('¿Estás seguro de que deseas eliminar este cliente?')) {
                fetch(`../api/clientes_api.php?action=delete&id=${id}`, {
                    method: 'POST'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Cliente eliminado correctamente');
                        loadClientes(paginationState.clientes.searchTerm);
                    } else {
                        alert('Error al eliminar cliente: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al eliminar cliente');
                });
            }
        }

        // Event listeners para clientes
        document.getElementById('newClienteBtn')?.addEventListener('click', newCliente);
        document.getElementById('searchClientesBtn')?.addEventListener('click', function() {
            const searchTerm = document.getElementById('searchClientesInput').value;
            paginationState.clientes.searchTerm = searchTerm;
            paginationState.clientes.currentPage = 1;
            loadClientes(searchTerm);
        });

        document.getElementById('listarTodosClientesBtn')?.addEventListener('click', function() {
            document.getElementById('searchClientesInput').value = '';
            paginationState.clientes.searchTerm = '';
            paginationState.clientes.currentPage = 1;
            loadClientes();
        });

        document.getElementById('clienteForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const action = formData.get('id_cliente') ? 'edit' : 'add';

            fetch(`../api/clientes_api.php?action=${action}`, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Cliente guardado correctamente');
                    document.getElementById('clienteModal').style.display = 'none';
                    loadClientes(paginationState.clientes.searchTerm);
                } else {
                    alert('Error al guardar cliente: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al guardar cliente');
            });
        });

        // Inicializar paginación para cada tab
        document.addEventListener('DOMContentLoaded', function() {
            setupPagination('usuarios', () => loadUsuarios(paginationState.usuarios.searchTerm));
            setupPagination('productos', () => loadProductos(paginationState.productos.searchTerm));
            setupPagination('categorias', () => loadCategorias(paginationState.categorias.searchTerm));
            setupPagination('clientes', () => loadClientes(paginationState.clientes.searchTerm));

            // Cargar datos del tab activo inicial
            loadTabData('<?php echo $active_tab; ?>');
        });

        // Cerrar modales
        document.querySelectorAll('.close-button').forEach(button => {
            button.addEventListener('click', function() {
                this.closest('.modal').style.display = 'none';
            });
        });

        // Cerrar modal al hacer clic fuera
        window.addEventListener('click', function(event) {
            document.querySelectorAll('.modal').forEach(modal => {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });
        });
    </script>

    <script src="../assets/js/admin.js"></script>
</body>
</html>