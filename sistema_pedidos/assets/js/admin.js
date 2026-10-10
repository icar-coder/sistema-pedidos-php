// sistema_pedidos/assets/js/admin.js

document.addEventListener('DOMContentLoaded', () => {
    // --- Referencias a elementos del DOM ---
    const tabs = document.querySelectorAll('.tab-btn');
    const sections = document.querySelectorAll('.admin-section');

    // Elementos de Pedidos
    const searchPedidosInput = document.getElementById('searchPedidosInput');
    const searchPedidosBtn = document.getElementById('searchPedidosBtn');
    const listarTodosPedidosBtn = document.getElementById('listarTodosPedidosBtn');
    const pedidosTableContainer = document.getElementById('pedidosTableContainer');

    // Elementos de Usuarios
    const userTableBody = document.querySelector('#usuarios-table tbody');
    const newUserBtn = document.getElementById('newUserBtn');
    const userModal = document.getElementById('userModal');
    const userForm = document.getElementById('userForm');
    const userSecurityQuestion = document.getElementById('userSecurityQuestion');
    const userCustomQuestionGroup = document.getElementById('customQuestionGroup');
    const userCustomQuestion = document.getElementById('userCustomQuestion');
    const userSecurityAnswer = document.getElementById('userSecurityAnswer');

    // Elementos de Productos
    const productTableBody = document.querySelector('#productos-table tbody');
    const newProductBtn = document.getElementById('newProductBtn');
    const productModal = document.getElementById('productModal');
    const productForm = document.getElementById('productForm');
    const productCategorySelect = document.getElementById('productCategory');

    // Elementos de Categorías
    const categoryTableBody = document.querySelector('#categorias-table tbody');
    const newCategoryBtn = document.getElementById('newCategoryBtn');
    const categoryModal = document.getElementById('categoryModal');
    const categoryForm = document.getElementById('categoryForm');

    const closeModalButtons = document.querySelectorAll('.modal .close-button');

    let currentModal;

    // --- Funciones de Modal ---
    const openModal = (modal, title, isEdit = false) => {
        currentModal = modal;
        modal.style.display = 'block';
        if (title) {
            const titleElement = modal.querySelector('h3');
            if (titleElement) titleElement.textContent = title;
        }
    };

    const closeModal = () => {
        if (currentModal) {
            currentModal.style.display = 'none';
        }
    };

    closeModalButtons.forEach(btn => btn.addEventListener('click', closeModal));
    window.addEventListener('click', (e) => {
        if (e.target.classList.contains('modal')) {
            closeModal();
        }
    });

    // --- Gestión de Tabs ---
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            tabs.forEach(t => t.classList.remove('active'));
            sections.forEach(s => s.classList.remove('active'));

            tab.classList.add('active');
            const target = tab.dataset.tab;
            document.getElementById(`${target}-tab`).classList.add('active');

            // Cargar datos al cambiar de pestaña
            if (target === 'usuarios') {
                fetchUsuarios();
            } else if (target === 'productos') {
                fetchProductos();
            } else if (target === 'categorias') {
                fetchCategorias();
            } else if (target === 'pedidos') {
                pedidosTableContainer.innerHTML = '<p>Usa la barra de búsqueda o haz clic en "Listar Todos" para ver el historial.</p>';
            }
        });
    });

    // --- Funciones de API ---
    async function fetchData(url, data) {
        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            return await response.json();
        } catch (error) {
            console.error('Error en la petición:', error);
            return { success: false, message: 'Error de conexión o del servidor.' };
        }
    }
    
    // --- Lógica de Pedidos ---
    searchPedidosBtn.addEventListener('click', () => {
        fetchPedidos(searchPedidosInput.value);
    });

    listarTodosPedidosBtn.addEventListener('click', () => {
        searchPedidosInput.value = '';
        fetchPedidos();
    });

    async function fetchPedidos(searchTerm = '') {
        pedidosTableContainer.innerHTML = '<p>Cargando pedidos...</p>';
        const result = await fetchData('../api/pedidos_admin_api.php', { action: 'read', search_term: searchTerm });
        
        if (result.success) {
            renderPedidosTable(result.data);
        } else {
            pedidosTableContainer.innerHTML = `<p class="alert alert-danger">Error: ${result.message}</p>`;
        }
    }

    function renderPedidosTable(pedidos) {
        if (pedidos.length === 0) {
            pedidosTableContainer.innerHTML = '<p class="alert alert-info">No hay pedidos registrados.</p>';
            return;
        }
        
        const table = document.createElement('table');
        table.className = 'pedidos-table';
        table.innerHTML = `
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Cliente</th>
                    <th>Fecha</th>
                    <th>Tipo de Servicio</th>
                    <th>Productos</th>
                    <th>Total</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody></tbody>
        `;
        const tbody = table.querySelector('tbody');

        pedidos.forEach(pedido => {
            const productosHtml = pedido.productos.map(prod => `<li>${prod.nombre_producto} (x${prod.cantidad})</li>`).join('');
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${pedido.id_pedido}</td>
                <td>${pedido.nombre_cliente} ${pedido.apellido_cliente}</td>
                <td>${new Date(pedido.fecha_pedido).toLocaleString()}</td>
                <td>${pedido.tipo_servicio}</td>
                <td><ul>${productosHtml}</ul></td>
                <td>$${parseFloat(pedido.total_pedido).toFixed(2)}</td>
                <td><span class="status ${pedido.estado}">${pedido.estado}</span></td>
                <td>
                    <button class="btn btn-delete" data-id="${pedido.id_pedido}">Eliminar</button>
                </td>
            `;
            tbody.appendChild(row);
        });

        pedidosTableContainer.innerHTML = '';
        pedidosTableContainer.appendChild(table);

        document.querySelectorAll('.btn-delete').forEach(button => {
            button.addEventListener('click', (e) => {
                const id = e.target.dataset.id;
                if (confirm(`¿Estás seguro de que deseas eliminar el pedido #${id}?`)) {
                    deletePedido(id);
                }
            });
        });
    }

    async function deletePedido(id) {
        const result = await fetchData('../api/pedidos_admin_api.php', { action: 'delete', id_pedido: id });
        if (result.success) {
            alert(result.message);
            fetchPedidos();
        } else {
            alert(`Error al eliminar el pedido: ${result.message}`);
        }
    }

    // --- Lógica de Usuarios con Preguntas de Seguridad ---
    
    // Manejar cambio en pregunta de seguridad
    userSecurityQuestion.addEventListener('change', function() {
        if (this.value === 'otra') {
            userCustomQuestionGroup.style.display = 'block';
            userCustomQuestion.required = true;
        } else {
            userCustomQuestionGroup.style.display = 'none';
            userCustomQuestion.required = false;
        }
    });

    newUserBtn.addEventListener('click', () => {
        userForm.reset();
        document.getElementById('userId').value = '';
        userCustomQuestionGroup.style.display = 'none';
        userCustomQuestion.required = false;
        openModal(userModal, 'Crear Nuevo Usuario');
    });

    userTableBody.addEventListener('click', async (e) => {
        if (e.target.classList.contains('btn-edit')) {
            const id = e.target.dataset.id;
            const result = await fetchData('../api/usuarios_api.php', { action: 'read_one', id_usuario: id });
            if (result.success) {
                const user = result.data;
                console.log('Usuario cargado para editar:', user);
                
                document.getElementById('userId').value = user.idUsuario;
                document.getElementById('userName').value = user.nombre_usuario;
                document.getElementById('userRole').value = user.rol;
                
                // Limpiar el campo de contraseña
                document.getElementById('userPassword').value = '';
                
                // Configurar preguntas de seguridad
                if (user.pregunta_seguridad) {
                    // Verificar si la pregunta está en las opciones predefinidas
                    const predefinedQuestions = Array.from(userSecurityQuestion.options).map(opt => opt.value);
                    if (predefinedQuestions.includes(user.pregunta_seguridad)) {
                        userSecurityQuestion.value = user.pregunta_seguridad;
                        userCustomQuestionGroup.style.display = 'none';
                    } else {
                        userSecurityQuestion.value = 'otra';
                        userCustomQuestionGroup.style.display = 'block';
                        userCustomQuestion.value = user.pregunta_seguridad;
                    }
                    userSecurityAnswer.value = user.respuesta_seguridad || '';
                } else {
                    userSecurityQuestion.value = '';
                    userSecurityAnswer.value = '';
                    userCustomQuestionGroup.style.display = 'none';
                }
                
                openModal(userModal, 'Editar Usuario', true);
            } else {
                alert('Error al cargar datos del usuario.');
            }
        } else if (e.target.classList.contains('btn-delete')) {
            const id = e.target.dataset.id;
            if (confirm('¿Estás seguro de que quieres eliminar este usuario?')) {
                const result = await fetchData('../api/usuarios_api.php', { action: 'delete', id_usuario: id });
                if (result.success) {
                    alert(result.message);
                    fetchUsuarios();
                } else {
                    alert('Error al eliminar usuario: ' + result.message);
                }
            }
        }
    });

    userForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const formData = new FormData(userForm);
        const data = Object.fromEntries(formData.entries());
        
        // DEBUG: Ver qué datos se están enviando
        console.log('Datos del formulario antes de enviar:', data);
        
        // Manejar pregunta de seguridad personalizada
        if (userSecurityQuestion.value === 'otra' && userCustomQuestion.value) {
            data.pregunta_seguridad = userCustomQuestion.value;
        } else {
            data.pregunta_seguridad = userSecurityQuestion.value;
        }
        
        // Si la pregunta está vacía, limpiar también la respuesta
        if (!data.pregunta_seguridad) {
            data.respuesta_seguridad = '';
        }
        
        // Determinar la acción basada en si hay id_usuario
        data.action = data.id_usuario ? 'edit' : 'add';
        
        // Si es edición y la contraseña está vacía, eliminarla del objeto
        if (data.action === 'edit' && !data.password) {
            delete data.password;
        }
        
        console.log('Datos finales enviados:', data);
        
        const result = await fetchData('../api/usuarios_api.php', data);
        if (result.success) {
            alert(result.message);
            closeModal();
            fetchUsuarios();
        } else {
            alert('Error al guardar usuario: ' + result.message);
        }
    });

    async function fetchUsuarios() {
        userTableBody.innerHTML = '<tr><td colspan="5">Cargando usuarios...</td></tr>';
        const result = await fetchData('../api/usuarios_api.php', { action: 'read' });
        if (result.success) {
            renderTable(userTableBody, result.data, 'user');
        } else {
            userTableBody.innerHTML = `<tr><td colspan="5" class="alert alert-danger">Error: ${result.message}</td></tr>`;
        }
    }

    // --- Lógica de Productos ---
    newProductBtn.addEventListener('click', () => {
        productForm.reset();
        document.getElementById('productId').value = '';
        fetchCategoriesForProductSelect();
        openModal(productModal, 'Crear Nuevo Producto');
    });

    productTableBody.addEventListener('click', async (e) => {
        if (e.target.classList.contains('btn-edit')) {
            const id = e.target.dataset.id;
            const result = await fetchData('../api/productos_admin_api.php', { action: 'read_one', id });
            if (result.success) {
                const product = result.data;
                productForm.reset();
                document.getElementById('productId').value = product.id_producto;
                document.getElementById('productName').value = product.nombre_producto;
                document.getElementById('productDesc').value = product.descripcion;
                document.getElementById('productPrice').value = product.precio;
                document.getElementById('productAvailable').checked = product.disponible == 1;
                await fetchCategoriesForProductSelect(product.id_categoria);
                openModal(productModal, 'Editar Producto', true);
            } else {
                alert('Error al cargar datos del producto.');
            }
        } else if (e.target.classList.contains('btn-delete')) {
            const id = e.target.dataset.id;
            if (confirm('¿Estás seguro de que quieres eliminar este producto?')) {
                const result = await fetchData('../api/productos_admin_api.php', { action: 'delete', id: id });
                if (result.success) {
                    alert(result.message);
                    fetchProductos();
                } else {
                    alert('Error al eliminar producto: ' + result.message);
                }
            }
        }
    });

    productForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(productForm);
        const data = Object.fromEntries(formData.entries());
        data.action = data.id_producto ? 'edit' : 'add';
        data.disponible = document.getElementById('productAvailable').checked ? 1 : 0;
        
        const result = await fetchData('../api/productos_admin_api.php', data);
        if (result.success) {
            alert(result.message);
            closeModal();
            fetchProductos();
        } else {
            alert('Error al guardar producto: ' + result.message);
        }
    });

    async function fetchProductos() {
        productTableBody.innerHTML = '<tr><td colspan="6">Cargando productos...</td></tr>';
        const result = await fetchData('../api/productos_admin_api.php', { action: 'read' });
        if (result.success) {
            renderTable(productTableBody, result.data, 'product');
        } else {
            productTableBody.innerHTML = `<tr><td colspan="6" class="alert alert-danger">Error: ${result.message}</td></tr>`;
        }
    }

    async function fetchCategoriesForProductSelect(selectedId = null) {
        const result = await fetchData('../api/categorias_api.php', { action: 'read' });
        if (result.success) {
            productCategorySelect.innerHTML = '';
            result.data.forEach(cat => {
                const option = document.createElement('option');
                option.value = cat.id_categoria;
                option.textContent = cat.nombre_categoria;
                if (selectedId && cat.id_categoria == selectedId) {
                    option.selected = true;
                }
                productCategorySelect.appendChild(option);
            });
        }
    }

    // --- Lógica de Categorías ---
    newCategoryBtn.addEventListener('click', () => {
        categoryForm.reset();
        document.getElementById('categoryId').value = '';
        openModal(categoryModal, 'Crear Nueva Categoría');
    });

    categoryTableBody.addEventListener('click', async (e) => {
        if (e.target.classList.contains('btn-edit')) {
            const id = e.target.dataset.id;
            const result = await fetchData('../api/categorias_api.php', { action: 'read_one', id });
            if (result.success) {
                const cat = result.data;
                document.getElementById('categoryId').value = cat.id_categoria;
                document.getElementById('categoryName').value = cat.nombre_categoria;
                openModal(categoryModal, 'Editar Categoría', true);
            } else {
                alert('Error al cargar datos de la categoría.');
            }
        } else if (e.target.classList.contains('btn-delete')) {
            const id = e.target.dataset.id;
            if (confirm('¿Estás seguro de que quieres eliminar esta categoría?')) {
                const result = await fetchData('../api/categorias_api.php', { action: 'delete', id: id });
                if (result.success) {
                    alert(result.message);
                    fetchCategorias();
                } else {
                    alert('Error al eliminar categoría: ' + result.message);
                }
            }
        }
    });

    categoryForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(categoryForm);
        const data = Object.fromEntries(formData.entries());
        data.action = data.id_categoria ? 'edit' : 'add';
        
        const result = await fetchData('../api/categorias_api.php', data);
        if (result.success) {
            alert(result.message);
            closeModal();
            fetchCategorias();
        } else {
            alert('Error al guardar categoría: ' + result.message);
        }
    });
    
    async function fetchCategorias() {
        categoryTableBody.innerHTML = '<tr><td colspan="2">Cargando categorías...</td></tr>';
        const result = await fetchData('../api/categorias_api.php', { action: 'read' });
        if (result.success) {
            renderTable(categoryTableBody, result.data, 'category');
        } else {
            categoryTableBody.innerHTML = `<tr><td colspan="2" class="alert alert-danger">Error: ${result.message}</td></tr>`;
        }
    }

    // Función genérica para renderizar tablas
    function renderTable(tbody, data, type) {
        tbody.innerHTML = '';
        if (data.length === 0) {
            tbody.innerHTML = `<tr><td colspan="10" class="alert alert-info">No hay ${type}s registrados.</td></tr>`;
            return;
        }

        data.forEach(item => {
            const row = document.createElement('tr');
            if (type === 'user') {
                row.innerHTML = `
                    <td>${item.id_usuario}</td>
                    <td>${item.nombre_usuario}</td>
                    <td>${item.rol}</td>
                    <td>
                        <button class="btn btn-edit" data-id="${item.id_usuario}">Editar</button>
                        <button class="btn btn-delete" data-id="${item.id_usuario}">Eliminar</button>
                    </td>
                `;
            } else if (type === 'product') {
                row.innerHTML = `
                    <td>${item.id_producto}</td>
                    <td>${item.nombre_producto}</td>
                    <td>${item.descripcion || ''}</td>
                    <td>$${parseFloat(item.precio).toFixed(2)}</td>
                    <td>${item.nombre_categoria}</td>
                    <td>${item.disponible == 1 ? 'Sí' : 'No'}</td>
                    <td>
                        <button class="btn btn-edit" data-id="${item.id_producto}">Editar</button>
                        <button class="btn btn-delete" data-id="${item.id_producto}">Eliminar</button>
                    </td>
                `;
            } else if (type === 'category') {
                row.innerHTML = `
                    <td>${item.id_categoria}</td>
                    <td>${item.nombre_categoria}</td>
                    <td>
                        <button class="btn btn-edit" data-id="${item.id_categoria}">Editar</button>
                        <button class="btn btn-delete" data-id="${item.id_categoria}">Eliminar</button>
                    </td>
                `;
            }
            tbody.appendChild(row);
        });
    }

    // Cargar los usuarios por defecto al iniciar la página
    fetchUsuarios();
});