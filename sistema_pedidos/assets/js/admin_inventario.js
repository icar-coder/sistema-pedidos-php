// admin_inventario.js - COMPLETO CON TASA DE DÓLAR Y RECETA INTEGRADA

// Variables globales
let ingredientes = [];
let movimientos = [];
let productos = [];
let categorias = [];
let ingredientesReceta = [];
let costoTotalReceta = 0;
let tasaDolar = 0;

// Funciones principales
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM cargado, iniciando inventario...');
    cargarEstadisticas();
    cargarIngredientes();
    cargarProductos();
    cargarCategorias();
    cargarIngredientesSelect();
});

// Función para cargar categorías
async function cargarCategorias() {
    try {
        console.log('Cargando categorías...');
        
        // Usar POST con JSON en lugar de GET
        const response = await fetch('../api/categorias_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'read'
            })
        });
        
        if (!response.ok) {
            throw new Error(`Error HTTP: ${response.status}`);
        }
        
        const result = await response.json();
        console.log('Respuesta de categorías:', result);
        
        if (result.success) {
            categorias = result.data;
            
            // Cargar en el filter del tab productos
            const filterSelect = document.getElementById('filterCategoria');
            if (filterSelect) {
                filterSelect.innerHTML = '<option value="">Todas las categorías</option>';
                
                categorias.forEach(categoria => {
                    const optionFilter = document.createElement('option');
                    optionFilter.value = categoria.id_categoria;
                    optionFilter.textContent = categoria.nombre_categoria;
                    filterSelect.appendChild(optionFilter);
                });
            }
            
            // Cargar en el modal de productos
            const modalSelect = document.getElementById('categoria_producto');
            if (modalSelect) {
                modalSelect.innerHTML = '<option value="">Seleccionar categoría...</option>';
                
                categorias.forEach(categoria => {
                    const optionModal = document.createElement('option');
                    optionModal.value = categoria.id_categoria;
                    optionModal.textContent = categoria.nombre_categoria;
                    modalSelect.appendChild(optionModal);
                });
                
                console.log('Categorías cargadas en modal:', categorias.length);
            }
        } else {
            console.error('Error en respuesta de categorías:', result.message);
            // Cargar categorías por defecto
            await cargarCategoriasDirecto();
        }
    } catch (error) {
        console.error('Error cargando categorías:', error);
        // Fallback: intentar cargar directamente
        await cargarCategoriasDirecto();
    }
}


// Función para cargar la tasa del dólar
async function cargarTasaDolar() {
    try {
        const response = await fetch('../api/configuraciones_api.php?action=obtener_tasa_dolar');
        const result = await response.json();
        
        if (result.success) {
            tasaDolar = parseFloat(result.tasa_dolar);
            document.getElementById('tasaCambioActual').textContent = `$1.00 = Bs ${tasaDolar.toFixed(2)}`;
            calcularPrecioBolivares();
            calcularCostosBolivares();
        } else {
            console.error('Error cargando tasa del dólar:', result.message);
            // Tasa por defecto
            tasaDolar = 36.00;
            document.getElementById('tasaCambioActual').textContent = `$1.00 = Bs ${tasaDolar.toFixed(2)} (default)`;
        }
    } catch (error) {
        console.error('Error cargando tasa del dólar:', error);
        tasaDolar = 36.00;
        document.getElementById('tasaCambioActual').textContent = `$1.00 = Bs ${tasaDolar.toFixed(2)} (error)`;
    }
}

// Función para actualizar la tasa manualmente
async function actualizarTasaCambio() {
    const nuevaTasa = prompt('Ingresa la nueva tasa de cambio (Bs por $1):', tasaDolar.toFixed(2));
    
    if (nuevaTasa && !isNaN(parseFloat(nuevaTasa))) {
        try {
            const formData = new FormData();
            formData.append('action', 'actualizar_tasa_dolar');
            formData.append('tasa_dolar', parseFloat(nuevaTasa));
            
            const response = await fetch('../api/configuraciones_api.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                await cargarTasaDolar();
                alert('Tasa de cambio actualizada exitosamente');
            } else {
                alert('Error actualizando tasa: ' + result.message);
            }
        } catch (error) {
            console.error('Error actualizando tasa:', error);
            alert('Error de conexión al actualizar tasa');
        }
    }
}

// Calcular precio en bolívares basado en dólares
function calcularPrecioBolivares() {
    const precioDolares = parseFloat(document.getElementById('precio_dolares').value) || 0;
    const precioBolivares = precioDolares * tasaDolar;
    
    document.getElementById('precio_bolivares').value = precioBolivares.toFixed(2);
    
    // Actualizar márgenes si ya hay receta
    if (ingredientesReceta.length > 0) {
        calcularMargenGanancia();
    }
}

// Calcular costos en bolívares
function calcularCostosBolivares() {
    const costoTotalBS = costoTotalReceta * tasaDolar;
    document.getElementById('costoTotalRecetaBS').textContent = `Bs ${costoTotalBS.toFixed(2)}`;
    
    calcularMargenGanancia();
}

// Calcular margen de ganancia
function calcularMargenGanancia() {
    const precioDolares = parseFloat(document.getElementById('precio_dolares').value) || 0;
    const margenUSD = precioDolares - costoTotalReceta;
    const margenBS = margenUSD * tasaDolar;
    const porcentajeMargen = costoTotalReceta > 0 ? ((margenUSD / precioDolares) * 100) : 0;
    
    document.getElementById('margenGananciaUSD').textContent = `$${margenUSD.toFixed(2)} USD`;
    document.getElementById('margenGananciaBS').textContent = `Bs ${margenBS.toFixed(2)}`;
    document.getElementById('porcentajeMargen').textContent = `${porcentajeMargen.toFixed(1)}%`;
    
    // Color del margen (rojo si es negativo, verde si es positivo)
    const elementoMargen = document.getElementById('margenGananciaUSD');
    if (margenUSD < 0) {
        elementoMargen.style.color = '#dc3545';
    } else if (margenUSD > 0) {
        elementoMargen.style.color = '#28a745';
    } else {
        elementoMargen.style.color = '#666';
    }
}

// Funciones de tabs
function switchTab(tabName) {
    console.log('Cambiando a tab:', tabName);
    
    // Ocultar todos los tabs
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    document.querySelectorAll('.tab').forEach(tab => {
        tab.classList.remove('active');
    });

    // Mostrar tab seleccionado
    const tabElement = document.getElementById(`tab-${tabName}`);
    const tabButton = document.querySelector(`.tab[onclick="switchTab('${tabName}')"]`);
    
    if (tabElement && tabButton) {
        tabElement.classList.add('active');
        tabButton.classList.add('active');
    } else {
        console.error(`Tab no encontrado: ${tabName}`);
        // Fallback: mostrar el primer tab
        const firstTab = document.querySelector('.tab-content');
        const firstTabButton = document.querySelector('.tab');
        if (firstTab && firstTabButton) {
            firstTab.classList.add('active');
            firstTabButton.classList.add('active');
        }
    }

    // Cargar datos específicos del tab
    switch(tabName) {
        case 'movimientos':
            cargarMovimientos();
            break;
        case 'productos':
            cargarProductos();
            break;
    }
}

// Funciones de modal
function openModal(type) {
    console.log('Abriendo modal:', type);
    const modal = document.getElementById(`modal${type.charAt(0).toUpperCase() + type.slice(1)}`);
    if (modal) {
        modal.style.display = 'block';
        
        // Inicializaciones específicas para cada modal
        if (type === 'ingrediente') {
            document.getElementById('modalIngredienteTitle').textContent = 'Nuevo Ingrediente';
            document.getElementById('ingrediente_id').value = '';
        }
        if (type === 'producto') {
            document.getElementById('modalProductoTitle').textContent = 'Nuevo Producto';
            document.getElementById('producto_id').value = '';
            
            // Limpiar la receta
            ingredientesReceta = [];
            renderRecetaProducto();
            
            // Cargar ingredientes si no están cargados
            if (document.getElementById('selectIngredienteReceta').options.length <= 1) {
                cargarIngredientesReceta();
            }
            
            // Cargar tasa del dólar
            cargarTasaDolar();
        }
    } else {
        console.error('Modal no encontrado:', type);
    }
}

function closeModal(type) {
    console.log('Cerrando modal:', type);
    const modal = document.getElementById(`modal${type.charAt(0).toUpperCase() + type.slice(1)}`);
    if (modal) {
        modal.style.display = 'none';
    }
    
    const form = document.getElementById(`form${type.charAt(0).toUpperCase() + type.slice(1)}`);
    if (form) {
        form.reset();
    }
    
    if (type === 'ingrediente') {
        document.getElementById('ingrediente_id').value = '';
        document.getElementById('modalIngredienteTitle').textContent = 'Nuevo Ingrediente';
    }
    if (type === 'producto') {
        document.getElementById('producto_id').value = '';
        document.getElementById('modalProductoTitle').textContent = 'Nuevo Producto';
        const disponibleCheckbox = document.getElementById('disponible_producto');
        if (disponibleCheckbox) {
            disponibleCheckbox.checked = true;
        }
        // Limpiar receta
        ingredientesReceta = [];
        renderRecetaProducto();
    }
}

// Cargar estadísticas
async function cargarEstadisticas() {
    console.log('Cargando estadísticas...');
    try {
        const url = '../api/inventario_api.php?action=estadisticas';
        console.log('URL de estadísticas:', url);
        
        const response = await fetch(url);
        console.log('Respuesta recibida:', response);
        
        const result = await response.json();
        console.log('Datos de estadísticas:', result);
        
        if (result.success) {
            const stats = result.data;
            document.getElementById('totalIngredientes').textContent = stats.total_ingredientes;
            document.getElementById('stockBajo').textContent = stats.stock_bajo;
            document.getElementById('stockAgotado').textContent = stats.stock_agotado;
            document.getElementById('valorInventario').textContent = `$${parseFloat(stats.valor_inventario).toFixed(2)}`;
        } else {
            console.error('Error en respuesta de estadísticas:', result.message);
        }
    } catch (error) {
        console.error('Error cargando estadísticas:', error);
        alert('Error al cargar estadísticas: ' + error.message);
    }
}

// Cargar ingredientes
async function cargarIngredientes() {
    console.log('Cargando ingredientes...');
    try {
        const url = '../api/inventario_api.php?action=ingredientes';
        console.log('URL de ingredientes:', url);
        
        const response = await fetch(url);
        const result = await response.json();
        console.log('Datos de ingredientes:', result);
        
        if (result.success) {
            ingredientes = result.data;
            renderIngredientes();
        } else {
            console.error('Error en respuesta de ingredientes:', result.message);
        }
    } catch (error) {
        console.error('Error cargando ingredientes:', error);
        alert('Error al cargar ingredientes: ' + error.message);
    }
}

// Renderizar tabla de ingredientes
function renderIngredientes() {
    console.log('Renderizando ingredientes:', ingredientes);
    const tbody = document.getElementById('tbodyIngredientes');
    if (!tbody) return;
    
    tbody.innerHTML = '';

    if (ingredientes.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" style="text-align: center;">No hay ingredientes registrados</td></tr>';
        return;
    }

    ingredientes.forEach(ingrediente => {
        const stockClass = ingrediente.stock_actual <= 0 ? 'stock-low' : 
                          ingrediente.stock_actual <= ingrediente.stock_minimo ? 'stock-low' : 'stock-ok';
        
        const stockText = ingrediente.stock_actual <= 0 ? 'Agotado' : 
                         ingrediente.stock_actual <= ingrediente.stock_minimo ? 'Bajo' : 'Normal';

        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${ingrediente.nombre_ingrediente}</td>
            <td class="${stockClass}">${parseFloat(ingrediente.stock_actual).toFixed(2)}</td>
            <td>${parseFloat(ingrediente.stock_minimo).toFixed(2)}</td>
            <td>${ingrediente.unidad_medida}</td>
            <td>$${parseFloat(ingrediente.costo_dolares).toFixed(2)}</td>
            <td>Bs ${parseFloat(ingrediente.costo_bolivares || 0).toFixed(2)}</td>
            <td class="${stockClass}">${stockText}</td>
            <td class="actions">
                <button class="btn btn-warning btn-sm" onclick="editarIngrediente(${ingrediente.id_ingrediente})">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-danger btn-sm" onclick="eliminarIngrediente(${ingrediente.id_ingrediente})">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
        tbody.appendChild(row);
    });
}

// Filtrar ingredientes
function filtrarIngredientes() {
    const searchTerm = document.getElementById('searchIngredientes').value.toLowerCase();
    const filterStock = document.getElementById('filterStock').value;

    console.log('Filtrando ingredientes:', { searchTerm, filterStock });

    const filtered = ingredientes.filter(ingrediente => {
        const matchesSearch = ingrediente.nombre_ingrediente.toLowerCase().includes(searchTerm);
        let matchesStock = true;

        if (filterStock === 'bajo') {
            matchesStock = ingrediente.stock_actual > 0 && ingrediente.stock_actual <= ingrediente.stock_minimo;
        } else if (filterStock === 'agotado') {
            matchesStock = ingrediente.stock_actual <= 0;
        } else if (filterStock === 'normal') {
            matchesStock = ingrediente.stock_actual > ingrediente.stock_minimo;
        }

        return matchesSearch && matchesStock;
    });

    renderFilteredIngredientes(filtered);
}

function renderFilteredIngredientes(filteredIngredientes) {
    console.log('Renderizando ingredientes filtrados:', filteredIngredientes);
    const tbody = document.getElementById('tbodyIngredientes');
    if (!tbody) return;
    
    tbody.innerHTML = '';

    if (filteredIngredientes.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" style="text-align: center;">No se encontraron ingredientes</td></tr>';
        return;
    }

    filteredIngredientes.forEach(ingrediente => {
        const stockClass = ingrediente.stock_actual <= 0 ? 'stock-low' : 
                          ingrediente.stock_actual <= ingrediente.stock_minimo ? 'stock-low' : 'stock-ok';
        
        const stockText = ingrediente.stock_actual <= 0 ? 'Agotado' : 
                         ingrediente.stock_actual <= ingrediente.stock_minimo ? 'Bajo' : 'Normal';

        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${ingrediente.nombre_ingrediente}</td>
            <td class="${stockClass}">${parseFloat(ingrediente.stock_actual).toFixed(2)}</td>
            <td>${parseFloat(ingrediente.stock_minimo).toFixed(2)}</td>
            <td>${ingrediente.unidad_medida}</td>
            <td>$${parseFloat(ingrediente.costo_dolares).toFixed(2)}</td>
            <td>Bs ${parseFloat(ingrediente.costo_bolivares || 0).toFixed(2)}</td>
            <td class="${stockClass}">${stockText}</td>
            <td class="actions">
                <button class="btn btn-warning btn-sm" onclick="editarIngrediente(${ingrediente.id_ingrediente})">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-danger btn-sm" onclick="eliminarIngrediente(${ingrediente.id_ingrediente})">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
        tbody.appendChild(row);
    });
}

// Cargar movimientos
async function cargarMovimientos() {
    console.log('Cargando movimientos...');
    try {
        const fechaDesde = document.getElementById('fechaDesde').value;
        const fechaHasta = document.getElementById('fechaHasta').value;
        
        let url = '../api/inventario_api.php?action=movimientos';
        if (fechaDesde) url += `&fecha_desde=${fechaDesde}`;
        if (fechaHasta) url += `&fecha_hasta=${fechaHasta}`;

        console.log('URL de movimientos:', url);

        const response = await fetch(url);
        const result = await response.json();
        console.log('Datos de movimientos:', result);
        
        if (result.success) {
            movimientos = result.data;
            renderMovimientos();
        } else {
            console.error('Error en respuesta de movimientos:', result.message);
        }
    } catch (error) {
        console.error('Error cargando movimientos:', error);
        alert('Error al cargar movimientos: ' + error.message);
    }
}

function renderMovimientos() {
    console.log('Renderizando movimientos:', movimientos);
    const tbody = document.getElementById('tbodyMovimientos');
    if (!tbody) return;
    
    tbody.innerHTML = '';

    if (movimientos.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align: center;">No hay movimientos registrados</td></tr>';
        return;
    }

    movimientos.forEach(movimiento => {
        const tipoText = movimiento.tipo_movimiento === 'entrada' ? 'Entrada' : 
                        movimiento.tipo_movimiento === 'salida' ? 'Salida' : 'Ajuste';
        
        const tipoClass = movimiento.tipo_movimiento === 'entrada' ? 'stock-ok' : 'stock-low';

        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${new Date(movimiento.fecha_movimiento).toLocaleDateString()}</td>
            <td>${movimiento.nombre_ingrediente}</td>
            <td class="${tipoClass}">${tipoText}</td>
            <td>${parseFloat(movimiento.cantidad).toFixed(2)} ${movimiento.unidad_medida}</td>
            <td>${movimiento.motivo}</td>
            <td>${movimiento.nombre_usuario || 'Sistema'}</td>
        `;
        tbody.appendChild(row);
    });
}

// Cargar y renderizar productos
// Cargar y renderizar productos
async function cargarProductos() {
    console.log('Cargando productos...');
    try {
        const response = await fetch('../api/productos_admin_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'read'
            })
        });
        
        const result = await response.json();
        console.log('Datos de productos:', result);
        
        if (result.success) {
            productos = result.data;
            renderProductos();
        } else {
            console.error('Error en respuesta de productos:', result.message);
        }
    } catch (error) {
        console.error('Error cargando productos:', error);
        alert('Error al cargar productos: ' + error.message);
    }
}
function renderProductos() {
    console.log('Renderizando productos:', productos);
    const tbody = document.getElementById('tbodyProductos');
    if (!tbody) return;
    
    tbody.innerHTML = '';

    if (productos.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align: center;">No hay productos registrados</td></tr>';
        return;
    }

    productos.forEach(producto => {
        const categoria = categorias.find(c => c.id_categoria == producto.id_categoria);
        const categoriaNombre = categoria ? categoria.nombre_categoria : 'Sin categoría';
        
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${producto.nombre_producto}</td>
            <td>${producto.descripcion || 'Sin descripción'}</td>
            <td>$${parseFloat(producto.precio).toFixed(2)}</td>
            <td>${categoriaNombre}</td>
            <td>${producto.disponible ? 'Sí' : 'No'}</td>
            <td class="actions">
                <button class="btn btn-warning btn-sm" onclick="editarProducto(${producto.id_producto})">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-danger btn-sm" onclick="eliminarProducto(${producto.id_producto})">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
        tbody.appendChild(row);
    });
}

// Filtrar productos
function filtrarProductos() {
    const searchTerm = document.getElementById('searchProductos').value.toLowerCase();
    const filterCategoria = document.getElementById('filterCategoria').value;

    console.log('Filtrando productos:', { searchTerm, filterCategoria });

    const filtered = productos.filter(producto => {
        const matchesSearch = producto.nombre_producto.toLowerCase().includes(searchTerm) || 
                             (producto.descripcion && producto.descripcion.toLowerCase().includes(searchTerm));
        
        let matchesCategoria = true;
        if (filterCategoria) {
            matchesCategoria = producto.id_categoria == filterCategoria;
        }

        return matchesSearch && matchesCategoria;
    });

    renderFilteredProductos(filtered);
}

function renderFilteredProductos(filteredProductos) {
    console.log('Renderizando productos filtrados:', filteredProductos);
    const tbody = document.getElementById('tbodyProductos');
    if (!tbody) return;
    
    tbody.innerHTML = '';

    if (filteredProductos.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align: center;">No se encontraron productos</td></tr>';
        return;
    }

    filteredProductos.forEach(producto => {
        const categoria = categorias.find(c => c.id_categoria == producto.id_categoria);
        const categoriaNombre = categoria ? categoria.nombre_categoria : 'Sin categoría';
        
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${producto.nombre_producto}</td>
            <td>${producto.descripcion || 'Sin descripción'}</td>
            <td>$${parseFloat(producto.precio).toFixed(2)}</td>
            <td>${categoriaNombre}</td>
            <td>${producto.disponible ? 'Sí' : 'No'}</td>
            <td class="actions">
                <button class="btn btn-warning btn-sm" onclick="editarProducto(${producto.id_producto})">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-danger btn-sm" onclick="eliminarProducto(${producto.id_producto})">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
        tbody.appendChild(row);
    });
}

// Cargar ingredientes para select
async function cargarIngredientesSelect() {
    console.log('Cargando ingredientes para select...');
    try {
        const response = await fetch('../api/inventario_api.php?action=ingredientes');
        const result = await response.json();
        console.log('Datos para select:', result);
        
        if (result.success) {
            const select = document.getElementById('movimiento_ingrediente');
            if (select) {
                select.innerHTML = '<option value="">Seleccionar ingrediente...</option>';
                
                result.data.forEach(ingrediente => {
                    const option = document.createElement('option');
                    option.value = ingrediente.id_ingrediente;
                    option.textContent = `${ingrediente.nombre_ingrediente} (Stock: ${parseFloat(ingrediente.stock_actual).toFixed(2)} ${ingrediente.unidad_medida})`;
                    select.appendChild(option);
                });
            }
        } else {
            console.error('Error en respuesta de ingredientes para select:', result.message);
        }
    } catch (error) {
        console.error('Error cargando ingredientes para select:', error);
    }
}

// Cargar ingredientes para el select de recetas
async function cargarIngredientesReceta() {
    try {
        const select = document.getElementById('selectIngredienteReceta');
        select.innerHTML = '<option value="">Seleccionar ingrediente...</option>';
        
        ingredientes.forEach(ingrediente => {
            const option = document.createElement('option');
            option.value = ingrediente.id_ingrediente;
            option.textContent = `${ingrediente.nombre_ingrediente} (Stock: ${parseFloat(ingrediente.stock_actual).toFixed(2)} ${ingrediente.unidad_medida})`;
            select.appendChild(option);
        });
    } catch (error) {
        console.error('Error cargando ingredientes para receta:', error);
    }
}

// Cuando se selecciona un ingrediente, mostrar su unidad de medida
document.getElementById('selectIngredienteReceta').addEventListener('change', function() {
    const ingredienteId = this.value;
    const ingrediente = ingredientes.find(i => i.id_ingrediente == ingredienteId);
    
    if (ingrediente) {
        document.getElementById('unidadReceta').value = ingrediente.unidad_medida;
    } else {
        document.getElementById('unidadReceta').value = '';
    }
});

// Función para agregar ingrediente a la receta
function agregarIngredienteReceta() {
    const ingredienteSelect = document.getElementById('selectIngredienteReceta');
    const cantidadInput = document.getElementById('cantidadReceta');
    
    const ingredienteId = ingredienteSelect.value;
    const cantidad = parseFloat(cantidadInput.value);
    
    // Validaciones
    if (!ingredienteId) {
        alert('Por favor selecciona un ingrediente');
        return;
    }
    
    if (!cantidad || cantidad <= 0) {
        alert('Por favor ingresa una cantidad válida');
        return;
    }
    
    // Buscar el ingrediente seleccionado
    const ingrediente = ingredientes.find(i => i.id_ingrediente == ingredienteId);
    if (!ingrediente) {
        alert('Ingrediente no encontrado');
        return;
    }
    
    // Verificar si el ingrediente ya está en la receta
    const existe = ingredientesReceta.find(item => item.ingrediente_id == ingredienteId);
    if (existe) {
        if (!confirm('Este ingrediente ya está en la receta. ¿Deseas actualizar la cantidad?')) {
            return;
        }
        // Actualizar cantidad
        existe.cantidad_requerida = cantidad;
    } else {
        // Agregar nuevo ingrediente
        ingredientesReceta.push({
            ingrediente_id: ingredienteId,
            nombre_ingrediente: ingrediente.nombre_ingrediente,
            cantidad_requerida: cantidad,
            unidad_medida: ingrediente.unidad_medida,
            costo_unitario: ingrediente.costo_dolares,
            costo_total: cantidad * ingrediente.costo_dolares
        });
    }
    
    // Actualizar la vista
    renderRecetaProducto();
    
    // Limpiar los campos del formulario
    ingredienteSelect.value = '';
    cantidadInput.value = '';
    document.getElementById('unidadReceta').value = '';
}

// Función para renderizar la receta del producto
function renderRecetaProducto() {
    const tbody = document.getElementById('tbodyRecetaProducto');
    const mensaje = document.getElementById('mensajeRecetaVacia');
    
    tbody.innerHTML = '';
    costoTotalReceta = 0;
    
    if (ingredientesReceta.length === 0) {
        mensaje.style.display = 'block';
        document.getElementById('costoTotalRecetaUSD').textContent = '$0.00 USD';
        document.getElementById('costoTotalRecetaBS').textContent = 'Bs 0.00';
        calcularMargenGanancia();
        return;
    }
    
    mensaje.style.display = 'none';
    
    ingredientesReceta.forEach((item, index) => {
        const costoTotal = item.cantidad_requerida * item.costo_unitario;
        costoTotalReceta += costoTotal;
        
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${item.nombre_ingrediente}</td>
            <td>${parseFloat(item.cantidad_requerida).toFixed(2)}</td>
            <td>${item.unidad_medida}</td>
            <td>$${parseFloat(item.costo_unitario).toFixed(2)}</td>
            <td>$${parseFloat(costoTotal).toFixed(2)}</td>
            <td>
                <button type="button" class="btn btn-warning btn-sm" onclick="editarIngredienteReceta(${index})">
                    <i class="fas fa-edit"></i>
                </button>
                <button type="button" class="btn btn-danger btn-sm" onclick="eliminarIngredienteReceta(${index})">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
        tbody.appendChild(row);
    });
    
    document.getElementById('costoTotalRecetaUSD').textContent = `$${parseFloat(costoTotalReceta).toFixed(2)} USD`;
    calcularCostosBolivares();
    calcularMargenGanancia();
}

// Función para eliminar ingrediente de la receta
function eliminarIngredienteReceta(index) {
    ingredientesReceta.splice(index, 1);
    renderRecetaProducto();
}

// Función para editar ingrediente de la receta
function editarIngredienteReceta(index) {
    const item = ingredientesReceta[index];
    
    // Llenar el formulario con los datos del ingrediente
    document.getElementById('selectIngredienteReceta').value = item.ingrediente_id;
    document.getElementById('cantidadReceta').value = item.cantidad_requerida;
    document.getElementById('unidadReceta').value = item.unidad_medida;
    
    // Eliminar el ingrediente de la lista temporal
    eliminarIngredienteReceta(index);
}

// Función para cargar receta existente
async function cargarRecetaExistente(productoId) {
    try {
        const response = await fetch(`../api/inventario_api.php?action=recetas&producto_id=${productoId}`);
        const result = await response.json();
        
        if (result.success) {
            ingredientesReceta = result.data.map(receta => ({
                ingrediente_id: receta.id_ingrediente,
                nombre_ingrediente: receta.nombre_ingrediente,
                cantidad_requerida: receta.cantidad_requerida,
                unidad_medida: receta.unidad_medida,
                costo_unitario: receta.costo_dolares,
                costo_total: receta.cantidad_requerida * receta.costo_dolares
            }));
            
            renderRecetaProducto();
        }
    } catch (error) {
        console.error('Error cargando receta existente:', error);
    }
}

// Función para guardar la receta del producto
async function guardarRecetaProducto(productoId) {
    try {
        // Primero eliminar receta existente si estamos editando
        if (document.getElementById('producto_id').value) {
            const formDataEliminar = new FormData();
            formDataEliminar.append('action', 'eliminar_receta_producto');
            formDataEliminar.append('producto_id', productoId);
            
            await fetch('../api/inventario_api.php', {
                method: 'POST',
                body: formDataEliminar
            });
        }
        
        // Guardar cada ingrediente de la receta
        for (const ingrediente of ingredientesReceta) {
            const formData = new FormData();
            formData.append('action', 'agregar_receta');
            formData.append('producto_id', productoId);
            formData.append('ingrediente_id', ingrediente.ingrediente_id);
            formData.append('cantidad_requerida', ingrediente.cantidad_requerida);
            formData.append('unidad_medida', ingrediente.unidad_medida);
            
            const response = await fetch('../api/inventario_api.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (!result.success) {
                console.error(`Error al agregar ${ingrediente.nombre_ingrediente}:`, result.message);
            }
        }
        
        console.log('Receta guardada exitosamente');
    } catch (error) {
        console.error('Error guardando receta:', error);
        throw error;
    }
}

// Form handlers
document.getElementById('formIngrediente').addEventListener('submit', async function(e) {
    e.preventDefault();
    console.log('Enviando formulario de ingrediente...');
    
    const formData = new FormData(this);
    const ingredienteId = formData.get('ingrediente_id');
    const action = ingredienteId ? 'editar_ingrediente' : 'crear_ingrediente';
    
    formData.append('action', action);
    
    console.log('Datos del formulario:', Object.fromEntries(formData));
    console.log('Acción:', action);

    try {
        const response = await fetch('../api/inventario_api.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        console.log('Respuesta del servidor:', result);
        
        if (result.success) {
            closeModal('ingrediente');
            cargarIngredientes();
            cargarEstadisticas();
            cargarIngredientesSelect();
            alert('Ingrediente guardado exitosamente');
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error de conexión: ' + error.message);
    }
});

document.getElementById('formMovimiento').addEventListener('submit', async function(e) {
    e.preventDefault();
    console.log('Enviando formulario de movimiento...');
    
    const formData = new FormData(this);
    formData.append('action', 'registrar_movimiento');
    
    console.log('Datos del formulario:', Object.fromEntries(formData));

    try {
        const response = await fetch('../api/inventario_api.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        console.log('Respuesta del servidor:', result);
        
        if (result.success) {
            closeModal('movimiento');
            cargarIngredientes();
            cargarEstadisticas();
            cargarMovimientos();
            alert('Movimiento registrado exitosamente');
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error de conexión: ' + error.message);
    }
});

document.getElementById('formProducto').addEventListener('submit', async function(e) {
    e.preventDefault();
    console.log('Enviando formulario de producto con receta...');
    
    // Recolectar datos del formulario
    const formData = new FormData(this);
    const productoId = formData.get('producto_id');
    const action = productoId ? 'edit' : 'add';
    
    // Preparar datos para enviar como JSON
    const datosProducto = {
        action: action,
        nombre_producto: formData.get('nombre_producto'),
        descripcion: formData.get('descripcion_producto'),
        precio: formData.get('precio_dolares'), // Usamos precio_dolares como precio principal
        id_categoria: formData.get('id_categoria'),
        disponible: formData.get('disponible') ? 1 : 0
    };
    
    // Si estamos editando, agregar el ID
    if (productoId) {
        datosProducto.id_producto = productoId;
    }
    
    console.log('Datos del producto a enviar:', datosProducto);
    console.log('Ingredientes en receta:', ingredientesReceta);

    try {
        // 1. Guardar el producto usando JSON
        const response = await fetch('../api/productos_admin_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(datosProducto)
        });
        
        const result = await response.json();
        console.log('Respuesta del servidor producto:', result);
        
        if (result.success) {
            const nuevoProductoId = productoId || result.id_producto;
            
            // 2. Guardar la receta si hay ingredientes
            if (ingredientesReceta.length > 0 && nuevoProductoId) {
                await guardarRecetaProducto(nuevoProductoId);
            }
            
            closeModal('producto');
            cargarProductos();
            alert('Producto y receta guardados exitosamente');
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error de conexión: ' + error.message);
    }
});
// Funciones de edición y eliminación para ingredientes
async function editarIngrediente(id) {
    console.log('Editando ingrediente ID:', id);
    const ingrediente = ingredientes.find(i => i.id_ingrediente === id);
    
    if (ingrediente) {
        document.getElementById('modalIngredienteTitle').textContent = 'Editar Ingrediente';
        document.getElementById('ingrediente_id').value = ingrediente.id_ingrediente;
        document.getElementById('nombre_ingrediente').value = ingrediente.nombre_ingrediente;
        document.getElementById('descripcion').value = ingrediente.descripcion || '';
        document.getElementById('costo_dolares').value = ingrediente.costo_dolares;
        document.getElementById('stock_actual').value = ingrediente.stock_actual;
        document.getElementById('stock_minimo').value = ingrediente.stock_minimo;
        document.getElementById('unidad_medida').value = ingrediente.unidad_medida;
        
        openModal('ingrediente');
    } else {
        console.error('Ingrediente no encontrado:', id);
    }
}

async function eliminarIngrediente(id) {
    console.log('Eliminando ingrediente ID:', id);
    if (!confirm('¿Estás seguro de que deseas eliminar este ingrediente?')) return;

    try {
        const formData = new FormData();
        formData.append('action', 'eliminar_ingrediente');
        formData.append('ingrediente_id', id);
        
        console.log('Enviando eliminación:', Object.fromEntries(formData));

        const response = await fetch('../api/inventario_api.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        console.log('Respuesta de eliminación:', result);
        
        if (result.success) {
            cargarIngredientes();
            cargarEstadisticas();
            alert('Ingrediente eliminado exitosamente');
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error de conexión: ' + error.message);
    }
}

// Funciones de edición y eliminación para productos
async function editarProducto(id) {
    console.log('Editando producto ID:', id);
    
    try {
        // Primero obtener los datos del producto
        const response = await fetch('../api/productos_admin_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'read_one',
                id: id
            })
        });
        
        const result = await response.json();
        
        if (result.success && result.data) {
            const producto = result.data;
            
            document.getElementById('modalProductoTitle').textContent = 'Editar Producto';
            document.getElementById('producto_id').value = producto.id_producto;
            document.getElementById('nombre_producto').value = producto.nombre_producto;
            document.getElementById('descripcion_producto').value = producto.descripcion || '';
            document.getElementById('precio_dolares').value = producto.precio;
            document.getElementById('categoria_producto').value = producto.id_categoria;
            document.getElementById('disponible_producto').checked = producto.disponible == 1;
            
            // Calcular precio en bolívares
            calcularPrecioBolivares();
            
            // Cargar la receta del producto
            await cargarRecetaExistente(producto.id_producto);
            
            openModal('producto');
        } else {
            console.error('Producto no encontrado:', id);
            alert('Producto no encontrado');
        }
    } catch (error) {
        console.error('Error cargando producto para editar:', error);
        alert('Error al cargar producto: ' + error.message);
    }
}

async function eliminarProducto(id) {
    console.log('Eliminando producto ID:', id);
    if (!confirm('¿Estás seguro de que deseas eliminar este producto?')) return;

    try {
        const response = await fetch('../api/productos_admin_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'delete',
                id: id
            })
        });
        
        const result = await response.json();
        console.log('Respuesta de eliminación de producto:', result);
        
        if (result.success) {
            cargarProductos();
            alert('Producto eliminado exitosamente');
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error de conexión: ' + error.message);
    }
}

// Cerrar modal al hacer click fuera
window.onclick = function(event) {
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        if (event.target === modal) {
            modal.style.display = 'none';
            // Resetear formularios al cerrar
            const forms = document.querySelectorAll('form');
            forms.forEach(form => form.reset());
            
            // Resetear campos específicos
            document.getElementById('ingrediente_id').value = '';
            document.getElementById('modalIngredienteTitle').textContent = 'Nuevo Ingrediente';
            document.getElementById('producto_id').value = '';
            document.getElementById('modalProductoTitle').textContent = 'Nuevo Producto';
            const disponibleCheckbox = document.getElementById('disponible_producto');
            if (disponibleCheckbox) {
                disponibleCheckbox.checked = true;
            }
            // Limpiar receta
            ingredientesReceta = [];
            renderRecetaProducto();
        }
    });
};

// Función para debug: probar todas las APIs
async function testAPIs() {
    console.log('=== INICIANDO PRUEBA DE APIS ===');
    
    const endpoints = [
        '../api/inventario_api.php?action=estadisticas',
        '../api/inventario_api.php?action=ingredientes',
        '../api/inventario_api.php?action=movimientos',
        '../api/productos_admin_api.php?action=listar',
        '../api/categorias_api.php?action=listar',
        '../api/configuraciones_api.php?action=obtener_tasa_dolar'
    ];
    
    for (const endpoint of endpoints) {
        try {
            console.log(`Probando: ${endpoint}`);
            const response = await fetch(endpoint);
            const result = await response.json();
            console.log(`Resultado de ${endpoint}:`, result);
        } catch (error) {
            console.error(`Error en ${endpoint}:`, error);
        }
    }
    
    console.log('=== PRUEBA DE APIS COMPLETADA ===');
}