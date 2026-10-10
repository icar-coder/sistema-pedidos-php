// sistema_pedidos/assets/js/mesas.js

// Variables globales
let todasLasMesas = [];
let filtroActual = 'todas';
let mesaSeleccionada = null;

// Función global para obtener una mesa por ID
function obtenerMesaPorId(idMesa) {
    return todasLasMesas.find(m => m.id_mesa === idMesa);
}

// Función fetchData global
async function fetchData(url, data) {
    try {
        const response = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        return await response.json();
    } catch (error) {
        console.error('Error:', error);
        return { success: false, message: 'Error de conexión.' };
    }
}

// Función mostrarNotificacion global
function mostrarNotificacion(mensaje, tipo) {
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${tipo === 'success' ? '#10b981' : '#ef4444'};
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 0.5rem;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 10000;
        animation: slideInRight 0.3s ease;
    `;
    
    notification.innerHTML = `
        <div style="display: flex; align-items: center; gap: 0.5rem;">
            <i class="fas fa-${tipo === 'success' ? 'check-circle' : 'exclamation-triangle'}"></i>
            <div>${mensaje}</div>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Función obtenerTextoEstado global
function obtenerTextoEstado(estado) {
    const estados = {
        'disponible': 'Disponible',
        'ocupada': 'Ocupada',
        'reservada': 'Reservada',
        'mantenimiento': 'Mantenimiento'
    };
    return estados[estado] || estado;
}

// Función mostrarError global
function mostrarError(mensaje) {
    const mesasGrid = document.getElementById('mesasGrid');
    if (mesasGrid) {
        mesasGrid.innerHTML = `
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h4>Error al cargar mesas</h4>
                <p>${mensaje}</p>
                <button class="refresh-btn" onclick="cargarMesas()" style="margin-top: 1rem;">
                    <i class="fas fa-sync-alt"></i>
                    Reintentar
                </button>
            </div>
        `;
    }
}

// Función cargarInformacionPedidos global
async function cargarInformacionPedidos() {
    try {
        // Cargar pedidos activos para saber qué mesas están ocupadas
        const response = await fetch('../api/pedidos_api.php?estado=preparando,completado');
        const pedidosActivos = await response.json();
        
        if (Array.isArray(pedidosActivos)) {
            // Marcar mesas como ocupadas si tienen pedidos activos
            todasLasMesas.forEach(mesa => {
                const pedidoMesa = pedidosActivos.find(pedido => pedido.id_mesa == mesa.id_mesa);
                if (pedidoMesa) {
                    mesa.estado = 'ocupada';
                    mesa.pedido_actual = {
                        'id_pedido': pedidoMesa.id_pedido,
                        'nombre_cliente': pedidoMesa.nombre_completo_cliente || 'Cliente',
                        'minutos_transcurridos': Math.floor((new Date() - new Date(pedidoMesa.fecha_pedido)) / (1000 * 60))
                    };
                }
            });
        }
    } catch (error) {
        console.error('Error cargando información de pedidos:', error);
    }
}

// Función actualizarEstadisticas global
function actualizarEstadisticas() {
    const stats = {
        disponible: todasLasMesas.filter(m => m.estado === 'disponible').length,
        ocupada: todasLasMesas.filter(m => m.estado === 'ocupada').length,
        reservada: todasLasMesas.filter(m => m.estado === 'reservada').length,
        mantenimiento: todasLasMesas.filter(m => m.estado === 'mantenimiento').length
    };

    const disponiblesElem = document.getElementById('total-disponibles');
    const ocupadasElem = document.getElementById('total-ocupadas');
    const reservadasElem = document.getElementById('total-reservadas');
    const mantenimientoElem = document.getElementById('total-mantenimiento');

    if (disponiblesElem) disponiblesElem.textContent = stats.disponible;
    if (ocupadasElem) ocupadasElem.textContent = stats.ocupada;
    if (reservadasElem) reservadasElem.textContent = stats.reservada;
    if (mantenimientoElem) mantenimientoElem.textContent = stats.mantenimiento;
}

// Función filtrarMesas global
function filtrarMesas() {
    let mesasFiltradas = todasLasMesas;

    if (filtroActual !== 'todas') {
        mesasFiltradas = todasLasMesas.filter(mesa => mesa.estado === filtroActual);
    }

    renderMesas(mesasFiltradas);
}

// Función renderMesas global
function renderMesas(mesas) {
    const mesasGrid = document.getElementById('mesasGrid');
    if (!mesasGrid) return;

    if (mesas.length === 0) {
        mesasGrid.innerHTML = `
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-chair"></i>
                </div>
                <h4>No hay mesas que coincidan</h4>
                <p>No se encontraron mesas con el filtro aplicado</p>
            </div>
        `;
        return;
    }

    mesasGrid.innerHTML = mesas.map(mesa => `
        <div class="mesa-card ${mesa.estado}" onclick="manejarClickMesa(${mesa.id_mesa})">
            <div class="mesa-header">
                <div class="mesa-numero">Mesa ${mesa.numero_mesa}</div>
                <div class="mesa-estado estado-${mesa.estado}">
                    ${obtenerTextoEstado(mesa.estado)}
                </div>
            </div>
            <div class="mesa-info">
                <div class="mesa-capacidad">
                    <i class="fas fa-users"></i>
                    Capacidad: ${mesa.capacidad} personas
                </div>
                <div class="mesa-ubicacion">
                    <i class="fas fa-map-marker-alt"></i>
                    ${mesa.ubicacion || 'Sin ubicación'}
                </div>
                ${mesa.pedido_actual ? `
                    <div class="mesa-pedido-info">
                        <div class="pedido-cliente">
                            <i class="fas fa-user"></i>
                            ${mesa.pedido_actual.nombre_cliente}
                        </div>
                        <div class="pedido-tiempo">
                            <i class="fas fa-clock"></i>
                            Hace ${mesa.pedido_actual.minutos_transcurridos} min
                        </div>
                    </div>
                ` : ''}
            </div>
            <div class="mesa-actions">
                ${mesa.estado !== 'ocupada' ? `
                    <button class="btn-sm btn-success" onclick="event.stopPropagation(); liberarMesa(${mesa.id_mesa})" title="Marcar como Disponible">
                        <i class="fas fa-check"></i>
                    </button>
                    <button class="btn-sm btn-warning" onclick="event.stopPropagation(); reservarMesa(${mesa.id_mesa})" title="Reservar Mesa">
                        <i class="fas fa-clock"></i>
                    </button>
                    <button class="btn-sm btn-danger" onclick="event.stopPropagation(); mantenimientoMesa(${mesa.id_mesa})" title="Marcar en Mantenimiento">
                        <i class="fas fa-tools"></i>
                    </button>
                ` : `
                    <button class="btn-sm btn-secondary" onclick="event.stopPropagation(); verDetallesPedidoMesa(${mesa.pedido_actual.id_pedido})" title="Ver Pedido">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn-sm btn-success" onclick="event.stopPropagation(); liberarMesa(${mesa.id_mesa})" title="Liberar Mesa">
                        <i class="fas fa-door-open"></i>
                    </button>
                `}
                <button class="btn-sm btn-info" onclick="event.stopPropagation(); abrirModalEditarMesa(${mesa.id_mesa})" title="Editar Mesa">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn-sm btn-danger" onclick="event.stopPropagation(); eliminarMesa(${mesa.id_mesa})" title="Eliminar Mesa">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    `).join('');
}

// Función cargarMesas definida GLOBALMENTE desde el inicio
window.cargarMesas = async function() {
    try {
        const response = await fetchData('../api/mesas_api.php', { action: 'get_mesas' });
        
        if (response.success) {
            todasLasMesas = response.data;
            // Enriquecer datos con información de pedidos activos
            await cargarInformacionPedidos();
            actualizarEstadisticas();
            filtrarMesas();
        } else {
            throw new Error(response.message);
        }
    } catch (error) {
        console.error('Error cargando mesas:', error);
        mostrarError('Error al cargar las mesas: ' + error.message);
    }
}

// Función cambiarEstadoMesa global
async function cambiarEstadoMesa(idMesa, nuevoEstado) {
    try {
        const response = await fetchData('../api/mesas_api.php', {
            action: 'update_estado',
            id_mesa: idMesa,
            estado: nuevoEstado
        });

        if (response.success) {
            mostrarNotificacion(`Mesa actualizada a: ${obtenerTextoEstado(nuevoEstado)}`, 'success');
            cargarMesas();
        } else {
            throw new Error(response.message);
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarNotificacion('Error al cambiar estado: ' + error.message, 'error');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Elementos del DOM
    const mesasGrid = document.getElementById('mesasGrid');
    const refreshBtn = document.getElementById('refreshMesasBtn');
    const filterBtns = document.querySelectorAll('.filter-btn');
    const estadoModal = document.getElementById('estadoModal');
    const modalMesaInfo = document.getElementById('modalMesaInfo');
    
    // Inicializar - AHORA cargarMesas está disponible globalmente
    cargarMesas();
    setupEventListeners();

    function setupEventListeners() {
        // Botón de actualizar
        if (refreshBtn) {
            refreshBtn.addEventListener('click', function() {
                cargarMesas();
                this.innerHTML = '<i class="fas fa-sync-alt fa-spin"></i> Actualizando...';
                setTimeout(() => {
                    this.innerHTML = '<i class="fas fa-sync-alt"></i> Actualizar';
                }, 1000);
            });
        }

        // Filtros
        if (filterBtns.length > 0) {
            filterBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    filterBtns.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    filtroActual = this.dataset.filter;
                    filtrarMesas();
                });
            });
        }

        // Cerrar modal
        if (estadoModal) {
            const closeBtn = estadoModal.querySelector('.close-button');
            if (closeBtn) {
                closeBtn.addEventListener('click', cerrarModal);
            }

            // Botones de cambio de estado
            const estadoBtns = estadoModal.querySelectorAll('[data-estado]');
            estadoBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const nuevoEstado = this.dataset.estado;
                    cambiarEstadoMesa(mesaSeleccionada, nuevoEstado);
                    cerrarModal();
                });
            });
        }
    }

    function manejarClickMesa(idMesa) {
        const mesa = obtenerMesaPorId(idMesa);
        if (mesa && mesa.estado !== 'ocupada') {
            abrirModalEstado(mesa);
        }
    }

    function abrirModalEstado(mesa) {
        mesaSeleccionada = mesa.id_mesa;
        if (modalMesaInfo) {
            modalMesaInfo.innerHTML = `
                <div style="text-align: center; margin-bottom: 1.5rem;">
                    <div style="font-size: 2rem; margin-bottom: 0.5rem;">Mesa ${mesa.numero_mesa}</div>
                    <div style="color: #64748b;">${mesa.ubicacion || 'Sin ubicación'} • Capacidad: ${mesa.capacidad} personas</div>
                    <div style="margin-top: 0.5rem;">
                        <span class="mesa-estado estado-${mesa.estado}">
                            Estado actual: ${obtenerTextoEstado(mesa.estado)}
                        </span>
                    </div>
                </div>
            `;
        }
        if (estadoModal) {
            estadoModal.style.display = 'flex';
        }
    }

    function cerrarModal() {
        if (estadoModal) {
            estadoModal.style.display = 'none';
        }
        mesaSeleccionada = null;
    }

    // Auto-refresh cada 30 segundos
    setInterval(() => {
        cargarMesas();
    }, 30000);
});

// === FUNCIONES GLOBALES ===

// Función para mostrar el próximo número disponible
window.mostrarProximoNumero = async function() {
    try {
        const response = await fetchData('../api/mesas_api.php', { action: 'get_mesas' });
        if (response.success) {
            const maxNumero = Math.max(...response.data.map(m => m.numero_mesa));
            return maxNumero + 1;
        }
    } catch (error) {
        console.error('Error obteniendo próximo número:', error);
    }
    return 1;
}

// Función para abrir modal de nueva mesa
window.abrirModalNuevaMesa = async function() {
    const proximoNumero = await mostrarProximoNumero();
    
    const modalHTML = `
        <div id="nuevaMesaModal" class="modal" style="display: block;">
            <div class="modal-content" style="max-width: 500px;">
                <span class="close-button" onclick="cerrarModalNuevaMesa()">&times;</span>
                <h3>Agregar Nueva Mesa</h3>
                <div style="background: #dbeafe; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem;">
                    <p style="margin: 0; color: #1e40af;">
                        <i class="fas fa-info-circle"></i>
                        Se creará como Mesa #${proximoNumero}
                    </p>
                </div>
                <form id="formNuevaMesa" onsubmit="guardarNuevaMesa(event)">
                    <div class="form-group">
                        <label for="capacidad">Capacidad:</label>
                        <input type="number" id="capacidad" name="capacidad" required 
                               min="1" max="20" class="form-control" placeholder="Número de personas">
                    </div>
                    <div class="form-group">
                        <label for="ubicacion">Ubicación:</label>
                        <input type="text" id="ubicacion" name="ubicacion" required 
                               class="form-control" placeholder="Ej: Terraza exterior, Salón principal, Barra, etc."
                               maxlength="50">
                        <small style="color: #64748b; font-size: 0.8rem;">
                            Describe la ubicación de la mesa (máx. 50 caracteres)
                        </small>
                    </div>
                    <div class="sugerencias-ubicacion">
                        <small style="color: #6b7280;">Sugerencias: </small>
                        <span class="sugerencia-chip" onclick="seleccionarSugerencia('Terraza')">Terraza</span>
                        <span class="sugerencia-chip" onclick="seleccionarSugerencia('Interior')">Interior</span>
                        <span class="sugerencia-chip" onclick="seleccionarSugerencia('Barra')">Barra</span>
                        <span class="sugerencia-chip" onclick="seleccionarSugerencia('Sala Privada')">Sala Privada</span>
                        <span class="sugerencia-chip" onclick="seleccionarSugerencia('Jardín')">Jardín</span>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="cerrarModalNuevaMesa()">
                            Cancelar
                        </button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i>
                            Crear Mesa
                        </button>
                    </div>
                </form>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modalHTML);
}

// Función para cerrar modal de nueva mesa
window.cerrarModalNuevaMesa = function() {
    const modal = document.getElementById('nuevaMesaModal');
    if (modal) {
        modal.remove();
    }
}

// Función para guardar nueva mesa
window.guardarNuevaMesa = async function(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const datosMesa = {
        capacidad: parseInt(formData.get('capacidad')),
        ubicacion: formData.get('ubicacion').trim()
    };
    
    if (!datosMesa.ubicacion) {
        mostrarNotificacion('La ubicación es requerida', 'error');
        return;
    }
    
    try {
        const response = await fetchData('../api/mesas_api.php', {
            action: 'create_mesa',
            ...datosMesa
        });
        
        if (response.success) {
            mostrarNotificacion(response.message, 'success');
            cerrarModalNuevaMesa();
            cargarMesas();
        } else {
            throw new Error(response.message);
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarNotificacion('Error al crear mesa: ' + error.message, 'error');
    }
}

// Función para abrir modal de editar mesa
window.abrirModalEditarMesa = function(idMesa) {
    const mesa = obtenerMesaPorId(idMesa);
    if (!mesa) {
        mostrarNotificacion('Mesa no encontrada', 'error');
        return;
    }
    
    const modalHTML = `
        <div id="editarMesaModal" class="modal" style="display: block;">
            <div class="modal-content" style="max-width: 500px;">
                <span class="close-button" onclick="cerrarModalEditarMesa()">&times;</span>
                <h3>Editar Mesa ${mesa.numero_mesa}</h3>
                <div style="background: #f0f9ff; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem;">
                    <p style="margin: 0; color: #0369a1;">
                        <i class="fas fa-info-circle"></i>
                        Mesa #${mesa.numero_mesa} • El número no se puede modificar
                    </p>
                </div>
                <form id="formEditarMesa" onsubmit="guardarEdicionMesa(event, ${mesa.id_mesa})">
                    <div class="form-group">
                        <label for="edit_capacidad">Capacidad:</label>
                        <input type="number" id="edit_capacidad" name="capacidad" required 
                               value="${mesa.capacidad}" min="1" max="20" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="edit_ubicacion">Ubicación:</label>
                        <input type="text" id="edit_ubicacion" name="ubicacion" required 
                               value="${mesa.ubicacion || ''}" class="form-control" 
                               placeholder="Ej: Terraza exterior, Salón principal, Barra, etc."
                               maxlength="50">
                        <small style="color: #64748b; font-size: 0.8rem;">
                            Describe la ubicación de la mesa (máx. 50 caracteres)
                        </small>
                    </div>
                    <div class="sugerencias-ubicacion">
                        <small style="color: #6b7280;">Sugerencias: </small>
                        <span class="sugerencia-chip" onclick="seleccionarSugerencia('Terraza', true)">Terraza</span>
                        <span class="sugerencia-chip" onclick="seleccionarSugerencia('Interior', true)">Interior</span>
                        <span class="sugerencia-chip" onclick="seleccionarSugerencia('Barra', true)">Barra</span>
                        <span class="sugerencia-chip" onclick="seleccionarSugerencia('Sala Privada', true)">Sala Privada</span>
                        <span class="sugerencia-chip" onclick="seleccionarSugerencia('Jardín', true)">Jardín</span>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="cerrarModalEditarMesa()">
                            Cancelar
                        </button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i>
                            Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modalHTML);
}

// Función para cerrar modal de editar mesa
window.cerrarModalEditarMesa = function() {
    const modal = document.getElementById('editarMesaModal');
    if (modal) {
        modal.remove();
    }
}

// Función para guardar edición de mesa
window.guardarEdicionMesa = async function(event, idMesa) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const datosMesa = {
        id_mesa: idMesa,
        capacidad: parseInt(formData.get('capacidad')),
        ubicacion: formData.get('ubicacion').trim()
    };
    
    if (!datosMesa.ubicacion) {
        mostrarNotificacion('La ubicación es requerida', 'error');
        return;
    }
    
    try {
        const response = await fetchData('../api/mesas_api.php', {
            action: 'update_mesa',
            ...datosMesa
        });
        
        if (response.success) {
            mostrarNotificacion(response.message, 'success');
            cerrarModalEditarMesa();
            cargarMesas();
        } else {
            throw new Error(response.message);
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarNotificacion('Error al editar mesa: ' + error.message, 'error');
    }
}

// Función para eliminar mesa
window.eliminarMesa = async function(idMesa) {
    const mesa = obtenerMesaPorId(idMesa);
    if (!mesa) {
        mostrarNotificacion('Mesa no encontrada', 'error');
        return;
    }
    
    if (!confirm(`¿Está seguro de que desea eliminar la Mesa ${mesa.numero_mesa}?\n\nEsta acción no se puede deshacer.`)) {
        return;
    }
    
    try {
        const response = await fetchData('../api/mesas_api.php', {
            action: 'delete_mesa',
            id_mesa: idMesa
        });
        
        if (response.success) {
            mostrarNotificacion(response.message, 'success');
            cargarMesas();
        } else {
            throw new Error(response.message);
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarNotificacion('Error al eliminar mesa: ' + error.message, 'error');
    }
}

// Funciones para cambiar estado
window.liberarMesa = function(idMesa) {
    cambiarEstadoMesa(idMesa, 'disponible');
}

window.reservarMesa = function(idMesa) {
    cambiarEstadoMesa(idMesa, 'reservada');
}

window.mantenimientoMesa = function(idMesa) {
    cambiarEstadoMesa(idMesa, 'mantenimiento');
}

// Función para ver detalles del pedido
window.verDetallesPedidoMesa = function(idPedido) {
    alert(`Mostrando detalles del pedido #${idPedido}\n\nEsta funcionalidad puede expandirse para mostrar información detallada del pedido.`);
}

// Función para seleccionar sugerencias de ubicación
window.seleccionarSugerencia = function(sugerencia, esEdicion = false) {
    const inputId = esEdicion ? 'edit_ubicacion' : 'ubicacion';
    const input = document.getElementById(inputId);
    if (input) {
        input.value = sugerencia;
        input.focus();
    }
}

// Función global para manejar click en mesa
window.manejarClickMesa = function(idMesa) {
    const mesa = obtenerMesaPorId(idMesa);
    if (mesa && mesa.estado !== 'ocupada') {
        // Aquí puedes implementar la lógica para el modal de estado si es necesario
        console.log('Mesa clickeada:', mesa);
    }
}

// Agregar estilos CSS para las animaciones
if (!document.querySelector('#mesas-styles')) {
    const style = document.createElement('style');
    style.id = 'mesas-styles';
    style.textContent = `
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOutRight {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
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
    `;
    document.head.appendChild(style);
}