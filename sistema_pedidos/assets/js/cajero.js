// sistema_pedidos/assets/js/cajero.js - VERSIÓN COMPLETA CON PAGO MÓVIL

// --- Variables Globales ---
let tasaDolar = 36.00;
let totalPedidoBS = 0;
let productosSeleccionados = {};
let clienteSeleccionado = null;
let productosDisponibles = [];
let categoriasDisponibles = [];

// --- Inicialización Segura ---
document.addEventListener('DOMContentLoaded', function() {
    console.log('Inicializando módulo de cajero...');
    
    // Verificar que los elementos esenciales existen
    if (!verificarElementosEsenciales()) {
        console.error('Elementos esenciales del DOM no encontrados');
        mostrarNotificacion('Error: Elementos de la interfaz no encontrados', 'error');
        return;
    }
    
    inicializarCajero();
});

function verificarElementosEsenciales() {
    const elementosRequeridos = [
        'openClientModalBtn',
        'openProductsModalBtn',
        'finalizarPedidoBtn',
        'limpiarPedidoBtn',
        'pedidosRealtimeContainer'
    ];
    
    for (const id of elementosRequeridos) {
        if (!document.getElementById(id)) {
            console.error(`Elemento requerido no encontrado: ${id}`);
            return false;
        }
    }
    return true;
}

async function inicializarCajero() {
    try {
        await cargarTasaDolar();
        await cargarCategorias();
        await cargarProductos();
        cargarPedidosRealtime();
        iniciarAutoRefreshPedidos();
        configurarEventListeners();
        
        console.log('Módulo de cajero inicializado correctamente');
    } catch (error) {
        console.error('Error inicializando cajero:', error);
        mostrarNotificacion('Error inicializando el sistema: ' + error.message, 'error');
    }
}

// --- Configuración de Event Listeners Segura ---
function configurarEventListeners() {
    // Modal de Clientes
    const openClientModalBtn = document.getElementById('openClientModalBtn');
    const closeClientModalBtn = document.querySelector('#clientModal .close-button');
    const buscarClienteBtn = document.getElementById('buscarClienteBtn');
    const clientForm = document.getElementById('clientForm');

    if (openClientModalBtn) {
        openClientModalBtn.addEventListener('click', abrirModalClientes);
    }

    if (closeClientModalBtn) {
        closeClientModalBtn.addEventListener('click', () => cerrarModal('clientModal'));
    }

    if (buscarClienteBtn) {
        buscarClienteBtn.addEventListener('click', buscarClientePorCedula);
    }

    if (clientForm) {
        clientForm.addEventListener('submit', registrarCliente);
    }

    // Modal de Productos
    const openProductsModalBtn = document.getElementById('openProductsModalBtn');
    const closeProductsModalBtn = document.querySelector('#productosModal .close-button');
    const addSelectedProductsBtn = document.getElementById('addSelectedProductsBtn');
    const searchInput = document.getElementById('searchInput');
    const categoryFilter = document.getElementById('categoryFilter');

    if (openProductsModalBtn) {
        openProductsModalBtn.addEventListener('click', abrirModalProductos);
    }

    if (closeProductsModalBtn) {
        closeProductsModalBtn.addEventListener('click', () => cerrarModal('productosModal'));
    }

    if (addSelectedProductsBtn) {
        addSelectedProductsBtn.addEventListener('click', agregarProductosSeleccionados);
    }

    if (searchInput) {
        searchInput.addEventListener('input', renderProductosModal);
    }

    if (categoryFilter) {
        categoryFilter.addEventListener('change', renderProductosModal);
    }

    // Botones principales
    const finalizarPedidoBtn = document.getElementById('finalizarPedidoBtn');
    const limpiarPedidoBtn = document.getElementById('limpiarPedidoBtn');
    const refreshPedidosBtn = document.getElementById('refreshPedidosBtn');

    if (finalizarPedidoBtn) {
        finalizarPedidoBtn.addEventListener('click', finalizarPedido);
    }

    if (limpiarPedidoBtn) {
        limpiarPedidoBtn.addEventListener('click', limpiarPedido);
    }

    if (refreshPedidosBtn) {
        refreshPedidosBtn.addEventListener('click', function() {
            cargarPedidosRealtime();
            this.innerHTML = '<i class="fas fa-sync-alt fa-spin"></i> Actualizando...';
            setTimeout(() => {
                this.innerHTML = '<i class="fas fa-sync-alt"></i> Actualizar';
            }, 1000);
        });
    }

    // Enter en búsqueda de cédula
    const cedulaBuscarInput = document.getElementById('cedula_buscar');
    if (cedulaBuscarInput) {
        cedulaBuscarInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                buscarClientePorCedula();
            }
        });
    }

    // Botón de mesas
    const openMesasModalBtn = document.getElementById('openMesasModalBtn');
    if (openMesasModalBtn) {
        openMesasModalBtn.addEventListener('click', cargarMesas);
    }

    // Cambios en tipo de servicio
    const tipoServicioSelect = document.getElementById('tipo_servicio');
    if (tipoServicioSelect) {
        tipoServicioSelect.addEventListener('change', function() {
            if (this.value !== 'Servicio en Mesa') {
                // Limpiar selección de mesa si no es servicio en mesa
                document.getElementById('numero_mesa_actual').textContent = 'No seleccionada';
                document.getElementById('id_mesa_seleccionada').value = '';
            }
        });
    }

    // Cambios en método de pago para mostrar/ocultar campos de pago móvil
    const tipoPagoSelect = document.getElementById('tipo_pago');
    if (tipoPagoSelect) {
        tipoPagoSelect.addEventListener('change', mostrarCamposPagoMovil);
    }
}

// --- Funciones para Pago Móvil ---
function mostrarCamposPagoMovil() {
    const tipoPago = document.getElementById('tipo_pago').value;
    const camposPagoMovil = document.getElementById('campos_pago_movil');
    const bancoInput = document.getElementById('banco_pago_movil');
    const referenciaInput = document.getElementById('referencia_pago_movil');
    
    if (tipoPago === 'Pago Móvil') {
        camposPagoMovil.style.display = 'block';
        bancoInput.required = true;
        referenciaInput.required = true;
    } else {
        camposPagoMovil.style.display = 'none';
        bancoInput.required = false;
        referenciaInput.required = false;
        // Limpiar campos cuando no es pago móvil
        bancoInput.value = '';
        referenciaInput.value = '';
    }
}

// --- Funciones de Clientes ---
function abrirModalClientes() {
    const modal = document.getElementById('clientModal');
    if (modal) {
        modal.style.display = 'flex';
        // Limpiar resultados anteriores
        const clienteResult = document.getElementById('clienteResult');
        if (clienteResult) {
            clienteResult.style.display = 'none';
            clienteResult.innerHTML = '';
        }
        // Limpiar formulario
        const clientForm = document.getElementById('clientForm');
        if (clientForm) clientForm.reset();
    }
}

async function buscarClientePorCedula() {
    const tipoCedula = document.getElementById('tipo_cedula_buscar').value;
    const cedula = document.getElementById('cedula_buscar').value.trim();
    
    if (!cedula) {
        mostrarNotificacion('Por favor ingrese una cédula', 'error');
        return;
    }

    const cedulaCompleta = tipoCedula + '-' + cedula;

    try {
        const response = await fetch('../api/clientes_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'buscar',
                cedula: cedulaCompleta
            })
        });

        const data = await response.json();
        const resultDiv = document.getElementById('clienteResult');

        if (data.success && data.cliente) {
            resultDiv.innerHTML = `
                <div style="color: #059669; margin-bottom: 1rem;">
                    <i class="fas fa-check-circle"></i>
                    <strong>Cliente encontrado:</strong> ${data.cliente.nombre_cliente} ${data.cliente.apellido_cliente}
                </div>
                <button type="button" class="btn btn-success" onclick="seleccionarCliente(${data.cliente.id_cliente}, '${data.cliente.nombre_cliente}', '${data.cliente.apellido_cliente}', '${data.cliente.cedula_cliente}')">
                    <i class="fas fa-check"></i>
                    Usar este cliente
                </button>
            `;
            resultDiv.className = 'client-result success';
            resultDiv.style.display = 'block';
        } else {
            resultDiv.innerHTML = `
                <div style="color: #dc2626;">
                    <i class="fas fa-exclamation-circle"></i>
                    Cliente no encontrado. Puede registrarlo a continuación.
                </div>
            `;
            resultDiv.className = 'client-result error';
            resultDiv.style.display = 'block';
            
            // Rellenar la cédula en el formulario de registro
            document.getElementById('cedula_cliente_modal').value = cedulaCompleta;
        }
    } catch (error) {
        console.error('Error buscando cliente:', error);
        mostrarNotificacion('Error al buscar cliente', 'error');
    }
}

function seleccionarCliente(idCliente, nombre, apellido, cedula) {
    clienteSeleccionado = { 
        id_cliente: idCliente, 
        nombre_cliente: nombre, 
        apellido_cliente: apellido, 
        cedula_cliente: cedula 
    };
    
    // Actualizar la interfaz
    document.getElementById('nombreClienteActual').textContent = `${nombre} ${apellido}`;
    document.getElementById('cedulaClienteActual').textContent = cedula;
    document.getElementById('idClienteActual').textContent = idCliente;
    document.getElementById('id_cliente_seleccionado').value = idCliente;
    
    // Cerrar modal
    cerrarModal('clientModal');
    
    mostrarNotificacion(`Cliente ${nombre} ${apellido} seleccionado`, 'success');
}

async function registrarCliente(event) {
    event.preventDefault();
    
    const cedula = document.getElementById('cedula_cliente_modal').value.trim();
    const nombre = document.getElementById('nombre_cliente_modal').value.trim();
    const apellido = document.getElementById('apellido_cliente_modal').value.trim();

    if (!cedula || !nombre || !apellido) {
        mostrarNotificacion('Todos los campos son requeridos', 'error');
        return;
    }

    try {
        const response = await fetch('../api/clientes_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'crear',
                cedula: cedula,
                nombre: nombre,
                apellido: apellido
            })
        });

        const data = await response.json();

        if (data.success && data.cliente) {
            seleccionarCliente(
                data.cliente.id_cliente, 
                data.cliente.nombre_cliente, 
                data.cliente.apellido_cliente, 
                data.cliente.cedula_cliente
            );
            // Limpiar formulario
            document.getElementById('clientForm').reset();
        } else {
            mostrarNotificacion('Error al registrar cliente: ' + (data.message || 'Error desconocido'), 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarNotificacion('Error al registrar cliente', 'error');
    }
}

// --- Funciones de Productos ---
async function cargarCategorias() {
    try {
        const response = await fetch('../api/categorias_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'read'
            })
        });

        const data = await response.json();

        if (data.success) {
            categoriasDisponibles = data.data || [];
            renderCategoryFilter();
        } else {
            console.error('Error cargando categorías:', data.message);
            categoriasDisponibles = [];
        }
    } catch (error) {
        console.error('Error cargando categorías:', error);
        categoriasDisponibles = [];
    }
}

function renderCategoryFilter() {
    const categoryFilter = document.getElementById('categoryFilter');
    if (!categoryFilter) return;
    
    categoryFilter.innerHTML = '<option value="">Todas las categorías</option>';
    categoriasDisponibles.forEach(cat => {
        const option = document.createElement('option');
        option.value = cat.id_categoria;
        option.textContent = cat.nombre_categoria;
        categoryFilter.appendChild(option);
    });
}

async function cargarProductos() {
    try {
        const response = await fetch('../api/productos.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'read'
            })
        });

        const data = await response.json();

        if (data.success && Array.isArray(data.data)) {
            productosDisponibles = data.data.filter(prod => 
                prod && prod.id_producto && prod.nombre_producto
            );
        } else {
            console.error('Error cargando productos:', data.message);
            productosDisponibles = [];
        }
    } catch (error) {
        console.error('Error cargando productos:', error);
        productosDisponibles = [];
    }
}

function abrirModalProductos() {
    const modal = document.getElementById('productosModal');
    if (modal) {
        modal.style.display = 'flex';
        renderProductosModal();
    }
}

function renderProductosModal() {
    const productosListModal = document.getElementById('productos-list-modal');
    if (!productosListModal) return;
    
    productosListModal.innerHTML = '';
    
    if (productosDisponibles.length === 0) {
        productosListModal.innerHTML = '<p>No hay productos disponibles.</p>';
        return;
    }

    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const categoryId = document.getElementById('categoryFilter').value;

    const filteredProducts = productosDisponibles.filter(prod => {
        const matchesSearch = prod.nombre_producto.toLowerCase().includes(searchTerm);
        const matchesCategory = categoryId === "" || prod.id_categoria == categoryId;
        return matchesSearch && matchesCategory;
    });

    if (filteredProducts.length === 0) {
        productosListModal.innerHTML = '<p>No se encontraron productos que coincidan con los criterios de búsqueda.</p>';
        return;
    }

    const productosPorCategoria = {};
    filteredProducts.forEach(prod => {
        if (!productosPorCategoria[prod.id_categoria]) {
            productosPorCategoria[prod.id_categoria] = [];
        }
        productosPorCategoria[prod.id_categoria].push(prod);
    });

    for (const catId in productosPorCategoria) {
        const categoria = categoriasDisponibles.find(c => c.id_categoria == catId);
        const categoriaDiv = document.createElement('div');
        categoriaDiv.classList.add('categoria');
        
        const nombreCategoria = categoria ? categoria.nombre_categoria : 'Categoría Desconocida';
        categoriaDiv.innerHTML = `<h4>${nombreCategoria}</h4>`;

        productosPorCategoria[catId].forEach(prod => {
            const precioBS = (prod.precio || 0) * tasaDolar;
            const cantidadActual = productosSeleccionados[prod.id_producto] ? productosSeleccionados[prod.id_producto].cantidad : 0;
            
            const productItem = document.createElement('div');
            productItem.classList.add('product-item');
            productItem.innerHTML = `
                <div class="product-info">
                    <h5>${prod.nombre_producto}</h5>
                    <p>${prod.descripcion || 'Sin descripción'}</p>
                    <div style="font-size: 0.9rem; color: #64748b; margin-top: 0.25rem;">
                        <span style="color: #3b82f6;">$${parseFloat(prod.precio || 0).toFixed(2)} USD</span>
                        <span style="margin: 0 0.5rem;">•</span>
                        <span style="color: #059669;">Bs ${precioBS.toFixed(2)}</span>
                    </div>
                </div>
                <div class="product-controls">
                    <input type="number" data-id="${prod.id_producto}" value="${cantidadActual}" min="0" style="width: 60px; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.375rem;">
                </div>
            `;
            categoriaDiv.appendChild(productItem);
        });
        productosListModal.appendChild(categoriaDiv);
    }

    // Configurar event listeners para los inputs de cantidad
    const quantityInputs = productosListModal.querySelectorAll('input[type="number"]');
    quantityInputs.forEach(input => {
        input.addEventListener('change', (e) => {
            const id = e.target.dataset.id;
            const cantidad = parseInt(e.target.value, 10);
            const producto = productosDisponibles.find(p => p.id_producto == id);
            
            if (producto && cantidad > 0) {
                productosSeleccionados[id] = {
                    id_producto: producto.id_producto,
                    nombre: producto.nombre_producto,
                    precio: parseFloat(producto.precio || 0),
                    cantidad: cantidad
                };
            } else if (productosSeleccionados[id]) {
                delete productosSeleccionados[id];
            }
        });
    });
}

function agregarProductosSeleccionados() {
    cerrarModal('productosModal');
    renderSelectedProducts();
    calcularTotal();
}

function renderSelectedProducts() {
    const selectedProductsList = document.getElementById('selected-products-list');
    if (!selectedProductsList) return;
    
    selectedProductsList.innerHTML = '';
    
    if (Object.keys(productosSeleccionados).length === 0) {
        selectedProductsList.innerHTML = `
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-shopping-basket"></i>
                </div>
                <h4>No hay productos agregados</h4>
                <p>Agrega productos al pedido para continuar</p>
            </div>
        `;
        return;
    }

    Object.values(productosSeleccionados).forEach(prod => {
        const precioTotalUSD = prod.precio * prod.cantidad;
        const precioTotalBS = precioTotalUSD * tasaDolar;
        
        const productItem = document.createElement('div');
        productItem.classList.add('product-item');
        productItem.innerHTML = `
            <div class="product-info">
                <div class="product-name">${prod.nombre}</div>
                <div class="product-details">
                    <span>Precio: $${prod.precio.toFixed(2)} USD</span>
                    <span style="margin: 0 0.5rem;">•</span>
                    <span>Bs ${(prod.precio * tasaDolar).toFixed(2)}</span>
                </div>
                <div style="font-size: 0.9rem; color: #3b82f6; margin-top: 0.25rem;">
                    Total: $${precioTotalUSD.toFixed(2)} USD • Bs ${precioTotalBS.toFixed(2)}
                </div>
            </div>
            <div class="product-actions">
                <div class="quantity-controls">
                    <button class="quantity-btn" onclick="decrementarCantidad(${prod.id_producto})">
                        <i class="fas fa-minus"></i>
                    </button>
                    <span class="quantity-value">${prod.cantidad}</span>
                    <button class="quantity-btn" onclick="incrementarCantidad(${prod.id_producto})">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
                <button class="remove-btn" onclick="eliminarProducto(${prod.id_producto})">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        `;
        selectedProductsList.appendChild(productItem);
    });
}

// --- Funciones para manejar cantidades ---
function incrementarCantidad(idProducto) {
    if (productosSeleccionados[idProducto]) {
        productosSeleccionados[idProducto].cantidad++;
        renderSelectedProducts();
        calcularTotal();
    }
}

function decrementarCantidad(idProducto) {
    if (productosSeleccionados[idProducto]) {
        productosSeleccionados[idProducto].cantidad--;
        if (productosSeleccionados[idProducto].cantidad <= 0) {
            delete productosSeleccionados[idProducto];
        }
        renderSelectedProducts();
        calcularTotal();
    }
}

function eliminarProducto(idProducto) {
    if (productosSeleccionados[idProducto]) {
        delete productosSeleccionados[idProducto];
        renderSelectedProducts();
        calcularTotal();
    }
}

// --- Cálculos y Totales ---
function calcularTotal() {
    let subtotal = 0;
    Object.values(productosSeleccionados).forEach(prod => {
        subtotal += prod.precio * prod.cantidad;
    });
    
    const ivaPorcentaje = 12;
    const iva = subtotal * (ivaPorcentaje / 100);
    const total = subtotal + iva;
    totalPedidoBS = total * tasaDolar;

    // Actualizar displays
    const subtotalElement = document.getElementById('subtotal-pedido');
    const ivaElement = document.getElementById('iva-pedido');
    const totalElement = document.getElementById('total-pedido');
    const totalBSElement = document.getElementById('total-pedido-bs');

    if (subtotalElement) subtotalElement.textContent = `$${subtotal.toFixed(2)}`;
    if (ivaElement) ivaElement.textContent = `$${iva.toFixed(2)}`;
    if (totalElement) totalElement.textContent = `$${total.toFixed(2)}`;
    if (totalBSElement) totalBSElement.textContent = `Bs ${totalPedidoBS.toFixed(2)}`;
}

// --- Funciones de Tasa de Cambio ---
async function cargarTasaDolar() {
    try {
        const response = await fetch('../api/configuraciones_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=obtener_tasa_dolar'
        });

        const data = await response.json();
        
        if (data && data.success) {
            tasaDolar = parseFloat(data.tasa_dolar);
            actualizarDisplayTasa();
            calcularTotal();
        } else {
            console.warn('No se pudo cargar la tasa del dólar, usando valor por defecto');
            tasaDolar = 36.00;
            actualizarDisplayTasa();
        }
    } catch (error) {
        console.error('Error cargando tasa del dólar:', error);
        tasaDolar = 36.00;
        actualizarDisplayTasa();
    }
}

function actualizarDisplayTasa() {
    const tasaSpan = document.getElementById('tasa-cambio-actual');
    if (tasaSpan) {
        tasaSpan.textContent = `$1.00 = Bs ${tasaDolar.toFixed(2)}`;
    }
}

async function actualizarTasaCambio() {
    const nuevaTasa = prompt('Ingresa la nueva tasa de cambio (Bs por $1):', tasaDolar.toFixed(2));
    
    if (nuevaTasa && !isNaN(parseFloat(nuevaTasa)) && parseFloat(nuevaTasa) > 0) {
        try {
            const formData = new URLSearchParams();
            formData.append('action', 'actualizar_tasa_dolar');
            formData.append('tasa_dolar', parseFloat(nuevaTasa));
            
            const response = await fetch('../api/configuraciones_api.php', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/x-www-form-urlencoded' 
                },
                body: formData
            });
            
            const result = await response.json();
            
            if (result && result.success) {
                tasaDolar = parseFloat(nuevaTasa);
                actualizarDisplayTasa();
                calcularTotal();
                mostrarNotificacion('Tasa de cambio actualizada exitosamente', 'success');
            } else {
                mostrarNotificacion('Error actualizando tasa: ' + (result ? result.message : 'Error desconocido'), 'error');
            }
        } catch (error) {
            console.error('Error actualizando tasa:', error);
            mostrarNotificacion('Error de conexión al actualizar tasa', 'error');
        }
    } else if (nuevaTasa !== null) {
        alert('Por favor ingresa un valor válido para la tasa de cambio.');
    }
}

// --- Funciones de Pedidos ---
async function finalizarPedido() {
    console.log('=== INICIANDO PROCESO DE PEDIDO ===');
    
    // Validar cliente
    if (!clienteSeleccionado) {
        mostrarNotificacion('Por favor, seleccione un cliente antes de finalizar el pedido.', 'error');
        return;
    }
    
    // Validar productos
    const productosParaEnviar = Object.values(productosSeleccionados).map(prod => ({
        id_producto: prod.id_producto,
        cantidad: prod.cantidad
    }));

    if (productosParaEnviar.length === 0) {
        mostrarNotificacion('Por favor, añada al menos un producto al pedido.', 'error');
        return;
    }

    // Validar mesa si es servicio en mesa
    const tipoServicio = document.getElementById('tipo_servicio').value;
    const idMesaInput = document.getElementById('id_mesa_seleccionada');
    
    if (tipoServicio === 'Servicio en Mesa' && (!idMesaInput || !idMesaInput.value)) {
        mostrarNotificacion('Para servicio en mesa debe seleccionar una mesa.', 'error');
        return;
    }

    // Validar pago móvil si es seleccionado
    const metodoPago = document.getElementById('tipo_pago').value;
    const bancoPagoMovil = document.getElementById('banco_pago_movil').value;
    const referenciaPagoMovil = document.getElementById('referencia_pago_movil').value;
    
    if (metodoPago === 'Pago Móvil') {
        if (!bancoPagoMovil) {
            mostrarNotificacion('Por favor, seleccione el banco para el pago móvil.', 'error');
            return;
        }
        if (!referenciaPagoMovil) {
            mostrarNotificacion('Por favor, ingrese el número de referencia del pago móvil.', 'error');
            return;
        }
        if (!/^\d+$/.test(referenciaPagoMovil)) {
            mostrarNotificacion('El número de referencia debe contener solo dígitos.', 'error');
            return;
        }
    }

    // Preparar datos del pedido
    const data = {
        id_cliente: clienteSeleccionado.id_cliente,
        tipo_servicio: tipoServicio,
        metodo_pago: metodoPago,
        productos: productosParaEnviar
    };

    // Agregar datos de pago móvil si aplica
    if (metodoPago === 'Pago Móvil') {
        data.banco_pago_movil = bancoPagoMovil;
        data.referencia_pago_movil = referenciaPagoMovil;
    }

    // Agregar mesa si está seleccionada
    if (idMesaInput && idMesaInput.value) {
        data.id_mesa = idMesaInput.value;
        
        // Ocupar la mesa
        try {
            const mesaResponse = await fetch('../api/mesas_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'update_estado',
                    id_mesa: idMesaInput.value,
                    estado: 'ocupada'
                })
            });

            const mesaData = await mesaResponse.json();
            if (!mesaData.success) {
                console.error('Error ocupando mesa:', mesaData.message);
            }
        } catch (error) {
            console.error('Error ocupando mesa:', error);
        }
    }

    console.log('Datos a enviar:', data);

    try {
        const response = await fetch('../api/pedidos_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();
        console.log('Respuesta del servidor:', result);
        
        if (result && result.success) {
            mostrarNotificacion('Pedido enviado a cocina correctamente', 'success');
            limpiarPedido();
            
            // Recargar lista de pedidos en tiempo real
            cargarPedidosRealtime();
        } else {
            const errorMsg = result ? result.message : 'Error desconocido';
            console.error('Error del servidor:', errorMsg);
            mostrarNotificacion('Error al tomar el pedido: ' + errorMsg, 'error');
            
            // Liberar la mesa si hubo error
            if (idMesaInput && idMesaInput.value) {
                await liberarMesa(idMesaInput.value);
            }
        }
    } catch (error) {
        console.error('Error en fetch:', error);
        mostrarNotificacion('Error de conexión: ' + error.message, 'error');
        
        // Liberar la mesa si hubo error
        if (idMesaInput && idMesaInput.value) {
            await liberarMesa(idMesaInput.value);
        }
    }
}

function limpiarPedido() {
    if (confirm('¿Está seguro de que desea limpiar el pedido actual? Se perderán todos los productos seleccionados.')) {
        productosSeleccionados = {};
        clienteSeleccionado = null;
        renderSelectedProducts();
        calcularTotal();
        
        // Limpiar cliente
        document.getElementById('nombreClienteActual').textContent = 'No seleccionado';
        document.getElementById('cedulaClienteActual').textContent = 'N/A';
        document.getElementById('idClienteActual').textContent = 'N/A';
        document.getElementById('id_cliente_seleccionado').value = '';
        
        // Limpiar mesa
        document.getElementById('numero_mesa_actual').textContent = 'No seleccionada';
        document.getElementById('id_mesa_seleccionada').value = '';
        
        // Limpiar campos de pago móvil
        document.getElementById('banco_pago_movil').value = '';
        document.getElementById('referencia_pago_movil').value = '';
        document.getElementById('campos_pago_movil').style.display = 'none';
        
        // Resetear formulario
        const pedidoForm = document.getElementById('pedidoForm');
        if (pedidoForm) pedidoForm.reset();
        
        mostrarNotificacion('Pedido limpiado correctamente', 'success');
    }
}

// --- Funciones de Pedidos en Tiempo Real ---
async function cargarPedidosRealtime() {
    const container = document.getElementById('pedidosRealtimeContainer');
    if (!container) return;

    container.innerHTML = `
        <div class="loading-state">
            <i class="fas fa-spinner fa-spin"></i>
            Cargando pedidos...
        </div>
    `;

    try {
        // Usar el endpoint específico para el cajero que incluya pedidos completados
        const response = await fetch('../api/pedidos_cocina_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'get_pedidos_cajero'
            })
        });

        const data = await response.json();
        
        console.log('Respuesta API Cajero:', data); // Debug

        if (data.success && data.data) {
            mostrarPedidosRealtime(data.data);
        } else {
            throw new Error(data.message || 'Error al cargar pedidos');
        }
    } catch (error) {
        console.error('Error cargando pedidos:', error);
        container.innerHTML = `
            <div class="empty-state-pedidos">
                <div class="empty-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h4>Error al cargar pedidos</h4>
                <p>${error.message}</p>
            </div>
        `;
    }
}

function mostrarPedidosRealtime(pedidos) {
    const container = document.getElementById('pedidosRealtimeContainer');
    if (!container) return;
    
    console.log('Mostrando pedidos:', pedidos);
    
    const pedidosCompletados = pedidos.filter(p => p.estado === 'completado');
    const pedidosPreparando = pedidos.filter(p => p.estado === 'preparando');
    
    if (pedidosCompletados.length === 0 && pedidosPreparando.length === 0) {
        container.innerHTML = `
            <div class="empty-state-pedidos">
                <div class="empty-icon">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <h4>No hay pedidos activos</h4>
                <p>Los pedidos aparecerán aquí cuando estén listos para cobrar</p>
            </div>
        `;
        return;
    }

    let html = '';
    
    // Pedidos COMPLETADOS (listos para cobrar)
    if (pedidosCompletados.length > 0) {
        html += `
            <div style="margin-bottom: 2rem;">
                <h4 style="color: #059669; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-check-circle"></i>
                    Listos para Cobrar (${pedidosCompletados.length})
                </h4>
        `;
        
        pedidosCompletados.forEach(pedido => {
            const totalBS = (parseFloat(pedido.total_pedido || 0) * tasaDolar).toFixed(2);
            const tieneMesa = pedido.numero_mesa && pedido.numero_mesa !== 'null';
            const esPagoMovil = pedido.metodo_pago === 'Pago Móvil';
            const infoPagoMovil = esPagoMovil && pedido.banco_pago_movil ? 
                `<br><small style="color: #6b7280;">Banco: ${pedido.banco_pago_movil} - Ref: ${pedido.referencia_pago_movil || 'N/A'}</small>` : '';
            
            html += `
                <div class="pedido-card completado" id="pedido-${pedido.id_pedido}">
                    <div class="pedido-header">
                        <div class="pedido-info">
                            <div class="pedido-numero">Pedido #${pedido.id_pedido}</div>
                            <div class="pedido-cliente">${pedido.nombre_cliente || 'Cliente'}</div>
                            <div class="tiempo-transcurrido">
                                <i class="fas fa-clock"></i>
                                ${pedido.fecha_pedido ? new Date(pedido.fecha_pedido).toLocaleString() : 'Fecha no disponible'}
                                ${tieneMesa ? ` • Mesa: ${pedido.numero_mesa}` : ''}
                            </div>
                        </div>
                        <div class="pedido-estado estado-completado">
                            LISTO PARA COBRAR
                        </div>
                    </div>
                    
                    <div class="pedido-detalles">
                        <div class="pedido-productos">
                            <div class="detalle-label">Productos:</div>
                            ${(pedido.productos || []).map(producto => `
                                <div class="producto-item">
                                    <span class="producto-nombre">${producto.nombre_producto || 'Producto'}</span>
                                    <span class="producto-cantidad">${producto.cantidad || 1}x</span>
                                </div>
                            `).join('')}
                        </div>
                        
                        <div class="pedido-servicio">
                            <div class="detalle-label">Servicio:</div>
                            <div class="detalle-value">${pedido.tipo_servicio || 'Servicio'}</div>
                        </div>
                        
                        <div class="pedido-pago">
                            <div class="detalle-label">Pago:</div>
                            <div class="detalle-value">
                                ${pedido.metodo_pago || 'Efectivo'}
                                ${infoPagoMovil}
                            </div>
                        </div>
                    </div>
                    
                    <div class="pedido-total">
                        Total: $${parseFloat(pedido.total_pedido || 0).toFixed(2)} USD • Bs ${totalBS}
                    </div>
                    
                    <div class="pedido-actions">
                        <button class="btn btn-success btn-sm" onclick="cobrarPedido(${pedido.id_pedido})">
                            <i class="fas fa-cash-register"></i>
                            Cobrar Pedido ${tieneMesa ? 'y Liberar Mesa' : ''}
                        </button>
                        <button class="btn btn-secondary btn-sm" onclick="verDetallesPedido(${pedido.id_pedido})">
                            <i class="fas fa-eye"></i>
                            Ver Detalles
                        </button>
                    </div>
                </div>
            `;
        });
        
        html += `</div>`;
    }
    
    // Pedidos EN PREPARACIÓN
    if (pedidosPreparando.length > 0) {
        html += `
            <div>
                <h4 style="color: #3b82f6; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-utensils"></i>
                    En Preparación (${pedidosPreparando.length})
                </h4>
        `;
        
        pedidosPreparando.forEach(pedido => {
            const totalBS = (parseFloat(pedido.total_pedido || 0) * tasaDolar).toFixed(2);
            const fechaCreacion = pedido.fecha_pedido ? new Date(pedido.fecha_pedido) : new Date();
            const minutosTranscurridos = Math.floor((new Date() - fechaCreacion) / (1000 * 60));
            const esUrgente = minutosTranscurridos > 15;
            const claseUrgente = esUrgente ? 'urgente' : '';
            const tieneMesa = pedido.numero_mesa && pedido.numero_mesa !== 'null';
            const esPagoMovil = pedido.metodo_pago === 'Pago Móvil';
            const infoPagoMovil = esPagoMovil && pedido.banco_pago_movil ? 
                `<br><small style="color: #6b7280;">Banco: ${pedido.banco_pago_movil} - Ref: ${pedido.referencia_pago_movil || 'N/A'}</small>` : '';
            
            html += `
                <div class="pedido-card preparando ${claseUrgente}" id="pedido-${pedido.id_pedido}">
                    <div class="pedido-header">
                        <div class="pedido-info">
                            <div class="pedido-numero">Pedido #${pedido.id_pedido}</div>
                            <div class="pedido-cliente">${pedido.nombre_cliente || 'Cliente'}</div>
                            <div class="tiempo-transcurrido">
                                <i class="fas fa-clock"></i>
                                Hace ${minutosTranscurridos} minutos
                                ${tieneMesa ? ` • Mesa: ${pedido.numero_mesa}` : ''}
                                ${esUrgente ? '<span style="color: var(--danger-color); margin-left: 0.5rem;">⚠️ Urgente</span>' : ''}
                            </div>
                        </div>
                        <div class="pedido-estado estado-preparando">
                            EN PREPARACIÓN
                        </div>
                    </div>
                    
                    <div class="pedido-detalles">
                        <div class="pedido-productos">
                            <div class="detalle-label">Productos:</div>
                            ${(pedido.productos || []).map(producto => `
                                <div class="producto-item">
                                    <span class="producto-nombre">${producto.nombre_producto || 'Producto'}</span>
                                    <span class="producto-cantidad">${producto.cantidad || 1}x</span>
                                </div>
                            `).join('')}
                        </div>
                        
                        <div class="pedido-servicio">
                            <div class="detalle-label">Servicio:</div>
                            <div class="detalle-value">${pedido.tipo_servicio || 'Servicio'}</div>
                        </div>
                        
                        <div class="pedido-pago">
                            <div class="detalle-label">Pago:</div>
                            <div class="detalle-value">
                                ${pedido.metodo_pago || 'Efectivo'}
                                ${infoPagoMovil}
                            </div>
                        </div>
                    </div>
                    
                    <div class="pedido-total">
                        Total: $${parseFloat(pedido.total_pedido || 0).toFixed(2)} USD • Bs ${totalBS}
                    </div>
                    
                    <div class="pedido-actions">
                        <button class="btn btn-secondary btn-sm" onclick="verDetallesPedido(${pedido.id_pedido})">
                            <i class="fas fa-eye"></i>
                            Ver Detalles
                        </button>
                        <button class="btn btn-danger btn-sm" onclick="cancelarPedido(${pedido.id_pedido})">
                            <i class="fas fa-times"></i>
                            Cancelar Pedido
                        </button>
                    </div>
                </div>
            `;
        });
        
        html += `</div>`;
    }
    
    container.innerHTML = html;
}

function iniciarAutoRefreshPedidos() {
    setInterval(() => {
        cargarPedidosRealtime();
    }, 10000);
}

// --- Funciones para acciones de pedidos ---
async function cobrarPedido(pedidoId) {
    console.log('Iniciando cobro del pedido:', pedidoId);
    
    if (!pedidoId) {
        mostrarNotificacion('ID de pedido inválido', 'error');
        return;
    }

    if (!confirm(`¿Está seguro de que desea cobrar el pedido #${pedidoId}?\n\nEsta acción marcará el pedido como PAGADO y liberará la mesa asignada.`)) {
        return;
    }

    try {
        const response = await fetch('../api/pedidos_api.php', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                id_pedido: parseInt(pedidoId),
                estado: 'pagado'
            })
        });

        const result = await response.json();
        console.log('Respuesta del servidor:', result);

        if (result.success) {
            mostrarNotificacion(`Pedido #${pedidoId} cobrado exitosamente. Mesa liberada.`, 'success');
            cargarPedidosRealtime();
        } else {
            mostrarNotificacion('Error al cobrar el pedido: ' + (result.message || 'Error desconocido'), 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarNotificacion('Error de conexión al cobrar pedido: ' + error.message, 'error');
    }
}

async function cancelarPedido(pedidoId) {
    const motivo = prompt('Ingrese el motivo de la cancelación:');
    
    if (motivo === null) return;
    
    if (!motivo.trim()) {
        alert('Debe ingresar un motivo para cancelar el pedido.');
        return;
    }

    try {
        const response = await fetch('../api/pedidos_api.php', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                id_pedido: pedidoId,
                estado: 'cancelado',
                motivo: motivo
            })
        });

        const result = await response.json();

        if (result.success) {
            mostrarNotificacion(`Pedido #${pedidoId} cancelado exitosamente`, 'success');
            cargarPedidosRealtime();
        } else {
            mostrarNotificacion('Error al cancelar pedido: ' + result.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarNotificacion('Error de conexión al cancelar pedido', 'error');
    }
}

function verDetallesPedido(pedidoId) {
    alert(`Detalles del Pedido #${pedidoId}\n\nEsta funcionalidad puede expandirse para mostrar información detallada.`);
}

// --- Funciones para Mesas ---
async function cargarMesas() {
    try {
        const response = await fetch('../api/mesas_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'get_mesas'
            })
        });

        const data = await response.json();

        if (data.success) {
            mostrarModalMesas(data.data);
        } else {
            throw new Error(data.message || 'Error al cargar mesas');
        }
    } catch (error) {
        console.error('Error cargando mesas:', error);
        mostrarNotificacion('Error al cargar las mesas', 'error');
    }
}

function mostrarModalMesas(mesas) {
    // Crear modal de mesas si no existe
    let modal = document.getElementById('mesasModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'mesasModal';
        modal.className = 'modal';
        modal.innerHTML = `
            <div class="modal-content">
                <span class="close-button">&times;</span>
                <h3>Seleccionar Mesa</h3>
                <div class="mesas-grid" id="mesasGrid">
                    <!-- Las mesas se cargarán aquí -->
                </div>
                <div style="margin-top: 1rem; text-align: center;">
                    <button class="btn btn-secondary" onclick="cerrarModal('mesasModal')">
                        Cancelar
                    </button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);

        // Configurar cerrar modal
        const closeBtn = modal.querySelector('.close-button');
        closeBtn.addEventListener('click', () => cerrarModal('mesasModal'));
    }

    const mesasGrid = document.getElementById('mesasGrid');
    mesasGrid.innerHTML = '';

    if (mesas.length === 0) {
        mesasGrid.innerHTML = '<p>No hay mesas disponibles</p>';
    } else {
        mesas.forEach(mesa => {
            const mesaItem = document.createElement('div');
            mesaItem.className = `mesa-item ${mesa.estado}`;
            mesaItem.innerHTML = `
                <div class="mesa-numero">Mesa ${mesa.numero_mesa}</div>
                <div class="mesa-info">
                    Capacidad: ${mesa.capacidad} personas<br>
                    <span class="mesa-estado estado-${mesa.estado}">${mesa.estado.toUpperCase()}</span>
                </div>
            `;

            if (mesa.estado === 'disponible') {
                mesaItem.style.cursor = 'pointer';
                mesaItem.addEventListener('click', () => seleccionarMesa(mesa));
            } else {
                mesaItem.style.opacity = '0.6';
                mesaItem.style.cursor = 'not-allowed';
                
                // Agregar tooltip para mesas no disponibles
                mesaItem.title = `Mesa ${mesa.estado}. No se puede seleccionar.`;
            }

            mesasGrid.appendChild(mesaItem);
        });
    }

    modal.style.display = 'flex';
}

function seleccionarMesa(mesa) {
    // Actualizar la interfaz
    document.getElementById('numero_mesa_actual').textContent = `Mesa ${mesa.numero_mesa}`;
    document.getElementById('id_mesa_seleccionada').value = mesa.id_mesa;
    
    // Actualizar el estado visual
    const estadoElement = document.getElementById('estado_mesa_actual');
    if (estadoElement) {
        estadoElement.textContent = mesa.estado;
        // Color según estado
        switch(mesa.estado) {
            case 'disponible':
                estadoElement.style.color = '#86efac';
                break;
            case 'ocupada':
                estadoElement.style.color = '#fca5a5';
                break;
            case 'reservada':
                estadoElement.style.color = '#fcd34d';
                break;
            case 'mantenimiento':
                estadoElement.style.color = '#d1d5db';
                break;
            default:
                estadoElement.style.color = '#ffffff';
        }
    }
    
    // Actualizar tipo de servicio si es necesario
    const tipoServicioSelect = document.getElementById('tipo_servicio');
    if (tipoServicioSelect && tipoServicioSelect.value !== 'Servicio en Mesa') {
        tipoServicioSelect.value = 'Servicio en Mesa';
    }
    
    cerrarModal('mesasModal');
    mostrarNotificacion(`Mesa ${mesa.numero_mesa} seleccionada`, 'success');
}

async function liberarMesa(idMesa) {
    try {
        const response = await fetch('../api/mesas_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'update_estado',
                id_mesa: idMesa,
                estado: 'disponible'
            })
        });

        const data = await response.json();
        
        if (!data.success) {
            console.error('Error liberando mesa:', data.message);
        }
    } catch (error) {
        console.error('Error liberando mesa:', error);
    }
}

// --- Funciones de Utilidad ---
function cerrarModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
    }
}

function mostrarNotificacion(mensaje, tipo = 'info') {
    const notification = document.createElement('div');
    const bgColor = tipo === 'success' ? '#10b981' : tipo === 'error' ? '#ef4444' : '#3b82f6';
    
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${bgColor};
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 0.5rem;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 10000;
        animation: slideInRight 0.3s ease;
        max-width: 300px;
    `;
    
    notification.innerHTML = `
        <div style="display: flex; align-items: center; gap: 0.5rem;">
            <i class="fas fa-${tipo === 'success' ? 'check-circle' : tipo === 'error' ? 'exclamation-triangle' : 'info-circle'}"></i>
            <div>${mensaje}</div>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Agregar estilos CSS para las animaciones
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    @keyframes slideOutRight {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
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
    .pedido-pago .detalle-value small {
        display: block;
        margin-top: 0.25rem;
        font-size: 0.8rem;
    }
`;
document.head.appendChild(style);