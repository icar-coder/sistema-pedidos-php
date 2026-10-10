// sistema_pedidos/assets/js/cocina.js - VERSIÓN COMPLETA CON DOS SECCIONES

document.addEventListener('DOMContentLoaded', () => {
    console.log('Inicializando módulo de cocina...');
    
    // Elementos del DOM
    const pedidosPendientesList = document.getElementById('pedidos-pendientes-list');
    const pedidosPreparandoList = document.getElementById('pedidos-preparando-list');
    const noPedidosPendientes = document.getElementById('no-pedidos-pendientes');
    const noPedidosPreparando = document.getElementById('no-pedidos-preparando');
    const totalPedidosElement = document.getElementById('totalPedidos');
    const pedidosPendientesElement = document.getElementById('pedidosPendientes');
    const pedidosPreparandoElement = document.getElementById('pedidosPreparando');
    const contadorPendientes = document.getElementById('contadorPendientes');
    const contadorPreparando = document.getElementById('contadorPreparando');
    const refreshBtn = document.getElementById('refreshBtn');
    const autoRefreshToggle = document.getElementById('autoRefreshToggle');

    // Verificar que los elementos esenciales existen
    if (!verificarElementosEsenciales()) {
        console.error('Elementos esenciales del DOM no encontrados');
        mostrarNotificacion('Error: Elementos de la interfaz no encontrados', 'error');
        return;
    }

    // Variables de estado
    let autoRefreshInterval = null;
    let estaCargando = false;

    // Inicializar módulo
    inicializarCocina();

    function verificarElementosEsenciales() {
        const elementosRequeridos = [
            'pedidos-pendientes-list',
            'pedidos-preparando-list',
            'no-pedidos-pendientes',
            'no-pedidos-preparando',
            'totalPedidos',
            'pedidosPendientes',
            'pedidosPreparando',
            'contadorPendientes',
            'contadorPreparando',
            'refreshBtn',
            'autoRefreshToggle'
        ];
        
        for (const id of elementosRequeridos) {
            if (!document.getElementById(id)) {
                console.error(`Elemento requerido no encontrado: ${id}`);
                return false;
            }
        }
        return true;
    }

    async function inicializarCocina() {
        try {
            // Configurar event listeners
            configurarEventListeners();
            
            // Cargar datos iniciales
            await cargarDatosIniciales();
            
            // Iniciar auto-refresh
            iniciarAutoRefresh();
            
            console.log('Módulo de cocina inicializado correctamente');
        } catch (error) {
            console.error('Error inicializando cocina:', error);
            mostrarNotificacion('Error inicializando el módulo de cocina', 'error');
        }
    }

    function configurarEventListeners() {
        // Botón de actualización manual
        if (refreshBtn) {
            refreshBtn.addEventListener('click', async () => {
                if (estaCargando) return;
                
                refreshBtn.classList.add('spinning');
                await cargarPedidosPendientes();
                setTimeout(() => refreshBtn.classList.remove('spinning'), 1000);
            });
        }

        // Toggle de auto-refresh
        if (autoRefreshToggle) {
            autoRefreshToggle.addEventListener('click', toggleAutoRefresh);
        }
    }

    // Función genérica para peticiones AJAX
    async function fetchData(url, data) {
        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }
            
            return await response.json();
        } catch (error) {
            console.error('Error en la petición:', error);
            return { 
                success: false, 
                message: 'Error de conexión o del servidor: ' + error.message 
            };
        }
    }

    // Función para obtener estadísticas
    async function fetchStats() {
        const result = await fetchData('../api/pedidos_cocina_api.php', { action: 'get_stats' });
        
        if (result.success) {
            totalPedidosElement.textContent = result.data.total || 0;
            pedidosPendientesElement.textContent = result.data.pendientes || 0;
            pedidosPreparandoElement.textContent = result.data.preparando || 0;
            
            // Actualizar contadores de secciones
            contadorPendientes.textContent = result.data.pendientes || 0;
            contadorPreparando.textContent = result.data.preparando || 0;
        } else {
            console.error('Error al obtener estadísticas:', result.message);
            // Establecer valores por defecto en caso de error
            totalPedidosElement.textContent = '0';
            pedidosPendientesElement.textContent = '0';
            pedidosPreparandoElement.textContent = '0';
            contadorPendientes.textContent = '0';
            contadorPreparando.textContent = '0';
        }
    }

    // Función para obtener y renderizar los pedidos
    async function cargarPedidosPendientes() {
        if (estaCargando) return;
        
        estaCargando = true;
        
        try {
            const result = await fetchData('../api/pedidos_cocina_api.php', { action: 'read' });
            
            console.log('Datos recibidos de cocina:', result);
            
            if (result.success && result.data && result.data.length > 0) {
                renderPedidosSeparados(result.data);
            } else {
                mostrarEstadosVacios();
            }
            
            // Actualizar estadísticas también
            await fetchStats();
        } catch (error) {
            console.error('Error cargando pedidos:', error);
            mostrarNotificacion('Error al cargar los pedidos', 'error');
            mostrarEstadosVacios();
        } finally {
            estaCargando = false;
        }
    }

    function mostrarEstadosVacios() {
        // Mostrar estados vacíos para ambas secciones
        if (pedidosPendientesList) {
            pedidosPendientesList.innerHTML = '';
            noPedidosPendientes.style.display = 'block';
        }
        if (pedidosPreparandoList) {
            pedidosPreparandoList.innerHTML = '';
            noPedidosPreparando.style.display = 'block';
        }
    }

    // Función para renderizar los pedidos en las dos secciones separadas
    function renderPedidosSeparados(pedidos) {
        if (!pedidosPendientesList || !pedidosPreparandoList) return;
        
        // Limpiar listas
        pedidosPendientesList.innerHTML = '';
        pedidosPreparandoList.innerHTML = '';
        
        // Separar pedidos por estado
        const pedidosPendientes = pedidos.filter(p => p.estado === 'pendiente');
        const pedidosPreparando = pedidos.filter(p => p.estado === 'preparando');
        
        console.log('Pedidos separados:', {
            pendientes: pedidosPendientes.length,
            preparando: pedidosPreparando.length
        });

        // Renderizar pedidos pendientes
        if (pedidosPendientes.length > 0) {
            noPedidosPendientes.style.display = 'none';
            pedidosPendientes.forEach(pedido => {
                const pedidoCard = crearTarjetaPedido(pedido);
                if (pedidoCard) {
                    pedidosPendientesList.appendChild(pedidoCard);
                }
            });
        } else {
            noPedidosPendientes.style.display = 'block';
        }

        // Renderizar pedidos en preparación
        if (pedidosPreparando.length > 0) {
            noPedidosPreparando.style.display = 'none';
            pedidosPreparando.forEach(pedido => {
                const pedidoCard = crearTarjetaPedido(pedido);
                if (pedidoCard) {
                    pedidosPreparandoList.appendChild(pedidoCard);
                }
            });
        } else {
            noPedidosPreparando.style.display = 'block';
        }

        // Añadir eventos a los botones después de renderizar
        configurarEventosBotones();
    }

    function crearTarjetaPedido(pedido) {
        if (!pedido || !pedido.id_pedido) {
            console.error('Pedido inválido:', pedido);
            return null;
        }

        const pedidoCard = document.createElement('div');
        
        // Determinar clases y estilos según el estado
        const esUrgente = calcularEsUrgente(pedido);
        const estadoInfo = obtenerInfoEstado(pedido.estado);
        
        pedidoCard.className = `pedido-card ${esUrgente ? 'urgent' : ''}`;
        
        // Información de mesa si existe
        const infoMesa = pedido.numero_mesa && pedido.numero_mesa !== 'null' ? 
            `<span class="meta-item">
                <i class="fas fa-chair"></i>
                Mesa ${pedido.numero_mesa}
            </span>` : '';

        // Calcular minutos transcurridos
        const minutosTranscurridos = calcularMinutosTranscurridos(pedido.fecha_pedido);

        // Crear HTML para los productos
        const productosHtml = (pedido.productos || []).map(prod => `
            <div class="producto-item">
                <div class="producto-info">
                    <div class="producto-cantidad">${prod.cantidad || 1}</div>
                    <div class="producto-nombre">${prod.nombre_producto || 'Producto'}</div>
                </div>
                <div class="producto-estado ${estadoInfo.clase}">${estadoInfo.texto}</div>
            </div>
        `).join('');

        pedidoCard.innerHTML = `
            <div class="pedido-header">
                <div class="pedido-info">
                    <div class="pedido-id">Pedido #${pedido.id_pedido}</div>
                    <div class="pedido-meta">
                        <span class="meta-item">
                            <i class="fas fa-clock"></i>
                            ${minutosTranscurridos} min
                        </span>
                        <span class="meta-item">
                            <i class="fas fa-${obtenerIconoServicio(pedido.tipo_servicio)}"></i>
                            ${pedido.tipo_servicio || 'Servicio'}
                        </span>
                        ${infoMesa}
                    </div>
                </div>
                <div class="pedido-timer">
                    <i class="fas fa-stopwatch"></i>
                    ${minutosTranscurridos}m
                    ${esUrgente ? '<span class="urgent-badge">URGENTE</span>' : ''}
                </div>
            </div>
            
            <div class="pedido-body">
                <div class="cliente-info">
                    <div class="cliente-avatar">
                        ${(pedido.nombre_cliente || 'C').charAt(0).toUpperCase()}
                    </div>
                    <div class="cliente-details">
                        <div class="cliente-nombre">${pedido.nombre_cliente || 'Cliente no registrado'}</div>
                        <div class="cliente-servicio">
                            <i class="fas fa-${obtenerIconoPago(pedido.metodo_pago)}"></i>
                            ${pedido.metodo_pago || 'Efectivo'} • $${parseFloat(pedido.total_pedido || 0).toFixed(2)}
                        </div>
                    </div>
                </div>
                
                <div class="productos-list">
                    ${productosHtml}
                </div>
            </div>
            
            <div class="pedido-actions">
                <button class="btn ${estadoInfo.botonPrincipal.clase}" 
                        data-id="${pedido.id_pedido}" 
                        data-action="${estadoInfo.botonPrincipal.accion}">
                    <i class="fas ${estadoInfo.botonPrincipal.icono}"></i>
                    ${estadoInfo.botonPrincipal.texto}
                </button>
                ${estadoInfo.botonSecundario ? `
                <button class="btn ${estadoInfo.botonSecundario.clase}" 
                        data-id="${pedido.id_pedido}" 
                        data-action="${estadoInfo.botonSecundario.accion}">
                    <i class="fas ${estadoInfo.botonSecundario.icono}"></i>
                    ${estadoInfo.botonSecundario.texto}
                </button>
                ` : ''}
            </div>
        `;
        
        return pedidoCard;
    }

    function calcularEsUrgente(pedido) {
        const minutosTranscurridos = calcularMinutosTranscurridos(pedido.fecha_pedido);
        return minutosTranscurridos > 15;
    }

    function calcularMinutosTranscurridos(fechaPedido) {
        if (!fechaPedido) return 0;
        
        try {
            const fechaCreacion = new Date(fechaPedido);
            const ahora = new Date();
            const diferenciaMs = ahora - fechaCreacion;
            return Math.floor(diferenciaMs / (1000 * 60));
        } catch (error) {
            console.error('Error calculando minutos transcurridos:', error);
            return 0;
        }
    }

    function obtenerInfoEstado(estado) {
        const estados = {
            'pendiente': {
                clase: 'estado-pendiente',
                texto: 'Pendiente',
                botonPrincipal: {
                    texto: 'Comenzar Preparación',
                    clase: 'btn-primary',
                    icono: 'fa-play',
                    accion: 'start'
                },
                botonSecundario: {
                    texto: 'Marcar como Listo',
                    clase: 'btn-success',
                    icono: 'fa-check',
                    accion: 'complete'
                }
            },
            'preparando': {
                clase: 'estado-preparando',
                texto: 'En Preparación',
                botonPrincipal: {
                    texto: 'Marcar como Completado',
                    clase: 'btn-success',
                    icono: 'fa-check',
                    accion: 'complete'
                },
                botonSecundario: {
                    texto: 'Reportar Problema',
                    clase: 'btn-warning',
                    icono: 'fa-exclamation-triangle',
                    accion: 'problema'
                }
            }
        };

        return estados[estado] || estados['pendiente'];
    }

    function obtenerIconoServicio(tipoServicio) {
        const iconos = {
            'Servicio en Mesa': 'chair',
            'Para Llevar': 'shopping-bag',
            'Domicilio': 'home'
        };
        return iconos[tipoServicio] || 'utensils';
    }

    function obtenerIconoPago(metodoPago) {
        const iconos = {
            'Efectivo': 'money-bill',
            'Tarjeta de Crédito': 'credit-card',
            'Transferencia': 'exchange-alt'
        };
        return iconos[metodoPago] || 'money-bill';
    }

    function configurarEventosBotones() {
        document.querySelectorAll('.pedido-actions .btn').forEach(button => {
            button.addEventListener('click', async (e) => {
                const buttonElement = e.target.closest('.btn');
                const idPedido = buttonElement.dataset.id;
                const action = buttonElement.dataset.action;
                
                if (!idPedido || !action) {
                    console.error('Botón sin datos válidos:', buttonElement);
                    return;
                }

                switch (action) {
                    case 'start':
                        if (confirm(`¿Comenzar preparación del pedido #${idPedido}?`)) {
                            await actualizarEstadoPedido(idPedido, 'preparando');
                        }
                        break;
                    case 'complete':
                        const mensajeConfirmacion = `¿Marcar pedido #${idPedido} como COMPLETADO?\n\nEl pedido pasará a estar listo para cobrar en el cajero.`;
                        if (confirm(mensajeConfirmacion)) {
                            await actualizarEstadoPedido(idPedido, 'completado');
                        }
                        break;
                    case 'problema':
                        const problema = prompt('Describe el problema con el pedido:');
                        if (problema && problema.trim()) {
                            await reportarProblema(idPedido, problema.trim());
                        }
                        break;
                }
            });
        });
    }

    // Función para actualizar el estado del pedido
    async function actualizarEstadoPedido(idPedido, nuevoEstado) {
        if (!idPedido || !nuevoEstado) {
            mostrarNotificacion('Datos inválidos para actualizar pedido', 'error');
            return;
        }

        console.log(`Actualizando pedido #${idPedido} a estado: ${nuevoEstado}`);

        const result = await fetchData('../api/pedidos_cocina_api.php', {
            action: 'update_status',
            id_pedido: idPedido,
            nuevo_estado: nuevoEstado
        });

        console.log('Respuesta de actualización:', result);

        if (result.success) {
            let mensaje = '';
            if (nuevoEstado === 'preparando') {
                mensaje = `Pedido #${idPedido} ahora está en preparación`;
            } else if (nuevoEstado === 'completado') {
                mensaje = `¡Pedido #${idPedido} completado! Listo para cobrar en cajero`;
            }
            
            mostrarNotificacion(mensaje, 'success');
            // Recargar la lista después de un breve delay
            setTimeout(() => cargarPedidosPendientes(), 1000);
        } else {
            mostrarNotificacion('Error: ' + (result.message || 'No se pudo actualizar el pedido'), 'error');
        }
    }

    // Función para reportar problemas
    async function reportarProblema(idPedido, problema) {
        console.log(`Problema reportado para pedido #${idPedido}:`, problema);
        
        const result = await fetchData('../api/pedidos_cocina_api.php', {
            action: 'report_problem',
            id_pedido: idPedido,
            problema: problema
        });

        if (result.success) {
            mostrarNotificacion(`Problema reportado para pedido #${idPedido}`, 'warning');
        } else {
            mostrarNotificacion('Error reportando problema: ' + result.message, 'error');
        }
    }

    // Función para mostrar notificaciones
    function mostrarNotificacion(mensaje, tipo = 'info') {
        // Crear elemento de notificación
        const notificacion = document.createElement('div');
        notificacion.className = `notificacion ${tipo}`;
        
        const icono = tipo === 'success' ? 'fa-check-circle' : 
                     tipo === 'error' ? 'fa-exclamation-circle' : 
                     tipo === 'warning' ? 'fa-exclamation-triangle' : 'fa-info-circle';
        
        notificacion.innerHTML = `
            <div class="notificacion-contenido">
                <i class="fas ${icono}"></i>
                <span>${mensaje}</span>
            </div>
        `;
        
        // Estilos para la notificación
        notificacion.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${tipo === 'success' ? '#10b981' : 
                         tipo === 'error' ? '#ef4444' : 
                         tipo === 'warning' ? '#f59e0b' : '#3b82f6'};
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 0.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            z-index: 10000;
            animation: slideInRight 0.3s ease;
            max-width: 400px;
            border-left: 4px solid ${tipo === 'success' ? '#059669' : 
                                 tipo === 'error' ? '#dc2626' : 
                                 tipo === 'warning' ? '#d97706' : '#2563eb'};
        `;
        
        document.body.appendChild(notificacion);
        
        // Remover después de 4 segundos
        setTimeout(() => {
            notificacion.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => {
                if (notificacion.parentNode) {
                    notificacion.parentNode.removeChild(notificacion);
                }
            }, 300);
        }, 4000);
    }

    // Control de auto-refresh
    function iniciarAutoRefresh() {
        if (!autoRefreshInterval) {
            autoRefreshInterval = setInterval(cargarPedidosPendientes, 8000); // 8 segundos
            if (autoRefreshToggle) {
                autoRefreshToggle.innerHTML = '<i class="fas fa-pause"></i> Pausar';
                autoRefreshToggle.style.background = '#ef4444';
            }
            console.log('Auto-refresh iniciado');
        }
    }
    
    function detenerAutoRefresh() {
        if (autoRefreshInterval) {
            clearInterval(autoRefreshInterval);
            autoRefreshInterval = null;
            if (autoRefreshToggle) {
                autoRefreshToggle.innerHTML = '<i class="fas fa-play"></i> Auto-Refresh';
                autoRefreshToggle.style.background = '#10b981';
            }
            console.log('Auto-refresh detenido');
        }
    }

    function toggleAutoRefresh() {
        if (autoRefreshInterval) {
            detenerAutoRefresh();
            mostrarNotificacion('Auto-refresh pausado', 'info');
        } else {
            iniciarAutoRefresh();
            mostrarNotificacion('Auto-refresh activado', 'success');
        }
    }

    // Cargar datos iniciales
    async function cargarDatosIniciales() {
        await cargarPedidosPendientes();
    }

    // Agregar estilos CSS para las animaciones si no existen
    if (!document.querySelector('#cocina-animations')) {
        const style = document.createElement('style');
        style.id = 'cocina-animations';
        style.textContent = `
            @keyframes slideInRight {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOutRight {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
            .notificacion-contenido {
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
            @keyframes pulse {
                0% { opacity: 1; }
                50% { opacity: 0.5; }
                100% { opacity: 1; }
            }
        `;
        document.head.appendChild(style);
    }

    // Exponer funciones globalmente para debugging
    window.cocinaModule = {
        cargarPedidosPendientes,
        detenerAutoRefresh,
        iniciarAutoRefresh,
        toggleAutoRefresh,
        actualizarEstadoPedido,
        reportarProblema
    };

    console.log('Módulo de cocina cargado correctamente');
});