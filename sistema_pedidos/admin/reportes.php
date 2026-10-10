<?php
// sistema_pedidos/admin/reportes.php
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
    <title>Reportes - Sistema de Pedidos</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        .reportes-container {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
			margin-left:100px;
        }

        .reportes-header {
            margin-bottom: 2rem;
        }

        .reportes-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--bg-dark);
            margin-bottom: 0.5rem;
        }

        .reportes-subtitle {
            color: var(--secondary-color);
            font-size: 1.1rem;
        }

        /* Filtros */
        .filtros-section {
            background: var(--bg-card);
            padding: 1.5rem;
            border-radius: 1rem;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
        }

        .filtros-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-label {
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
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

        .filtros-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .rango-rapido {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .btn-rango {
            padding: 0.5rem 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 0.75rem;
            background: white;
            color: var(--text-dark);
            cursor: pointer;
            transition: var(--transition);
            font-size: 0.9rem;
            font-weight: 500;
        }

        .btn-rango:hover {
            border-color: var(--primary-color);
            background: #f8fafc;
        }

        .btn-rango.active {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: var(--secondary-color);
            color: white;
        }

        .btn-secondary:hover {
            background: #475569;
        }

        .btn-success {
            background: var(--success-color);
            color: white;
        }

        .btn-success:hover {
            background: #059669;
        }

        /* KPI Cards */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .kpi-card {
            background: var(--bg-card);
            padding: 1.5rem;
            border-radius: 1rem;
            box-shadow: var(--shadow);
            border-left: 4px solid var(--primary-color);
            transition: var(--transition);
        }

        .kpi-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .kpi-card.ventas { border-left-color: var(--success-color); }
        .kpi-card.pedidos { border-left-color: var(--primary-color); }
        .kpi-card.clientes { border-left-color: var(--accent-color); }
        .kpi-card.promedio { border-left-color: var(--danger-color); }

        .kpi-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .kpi-icon {
            width: 3rem;
            height: 3rem;
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }

        .kpi-card.ventas .kpi-icon { background: var(--success-color); }
        .kpi-card.pedidos .kpi-icon { background: var(--primary-color); }
        .kpi-card.clientes .kpi-icon { background: var(--accent-color); }
        .kpi-card.promedio .kpi-icon { background: var(--danger-color); }

        .kpi-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--bg-dark);
            margin-bottom: 0.25rem;
        }

        .kpi-label {
            color: var(--secondary-color);
            font-size: 0.9rem;
            font-weight: 500;
        }

        .kpi-trend {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            font-size: 0.8rem;
            margin-top: 0.5rem;
        }

        .trend-up { color: var(--success-color); }
        .trend-down { color: var(--danger-color); }

        /* Gráficas */
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .chart-card {
            background: var(--bg-card);
            padding: 1.5rem;
            border-radius: 1rem;
            box-shadow: var(--shadow);
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .chart-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--bg-dark);
        }

        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }

        /* Tablas de Reportes */
        .tables-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .table-card {
            background: var(--bg-card);
            padding: 1.5rem;
            border-radius: 1rem;
            box-shadow: var(--shadow);
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .table-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--bg-dark);
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th,
        .data-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        .data-table th {
            background: #f8fafc;
            font-weight: 600;
            color: var(--text-dark);
            font-size: 0.9rem;
        }

        .data-table tr:hover {
            background: #f8fafc;
        }

        .badge {
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge-success {
            background: #dcfce7;
            color: #16a34a;
        }

        .badge-warning {
            background: #fef3c7;
            color: #d97706;
        }

        .badge-danger {
            background: #fecaca;
            color: #dc2626;
        }

        /* Export Section */
        .export-section {
            background: var(--bg-card);
            padding: 1.5rem;
            border-radius: 1rem;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
        }

        .export-options {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        /* Loading States */
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }

        .spinner {
            border: 2px solid #f3f3f3;
            border-top: 2px solid var(--primary-color);
            border-radius: 50%;
            width: 20px;
            height: 20px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .reportes-container {
                margin-left: 0;
                padding: 1rem;
            }

            .charts-grid {
                grid-template-columns: 1fr;
            }

            .tables-grid {
                grid-template-columns: 1fr;
            }

            .filtros-grid {
                grid-template-columns: 1fr;
            }

            .export-options {
                flex-direction: column;
            }

            .rango-rapido {
                flex-direction: column;
            }

            .btn-rango {
                flex: 1;
                text-align: center;
            }
        }
    </style>
  
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="reportes-container">
        <!-- Header -->
        <div class="reportes-header">
            <h1 class="reportes-title">
                <i class="fas fa-chart-line"></i>
                Dashboard de Reportes
            </h1>
            <p class="reportes-subtitle">Análisis completo de ventas, pedidos y clientes</p>
        </div>

        <!-- Filtros -->
        <div class="filtros-section">
            <h3 style="margin-bottom: 1rem; color: var(--text-dark);">Filtros de Reporte</h3>
            
            <!-- Rangos Rápidos -->
            <div style="margin-bottom: 1rem;">
                <label class="form-label">Rango Rápido:</label>
                <div class="rango-rapido">
                    <button class="btn-rango active" data-rango="hoy">Hoy</button>
                    <button class="btn-rango" data-rango="ayer">Ayer</button>
                    <button class="btn-rango" data-rango="esta_semana">Esta Semana</button>
                    <button class="btn-rango" data-rango="semana_pasada">Semana Pasada</button>
                    <button class="btn-rango" data-rango="este_mes">Este Mes</button>
                    <button class="btn-rango" data-rango="mes_pasado">Mes Pasado</button>
                    <button class="btn-rango" data-rango="este_anio">Este Año</button>
                    <button class="btn-rango" data-rango="anio_pasado">Año Pasado</button>
                </div>
            </div>

            <div class="filtros-grid">
                <div class="form-group">
                    <label class="form-label" for="fecha_desde">Fecha Desde</label>
                    <input type="date" id="fecha_desde" class="form-control" readonly>
                </div>
                <div class="form-group">
                    <label class="form-label" for="fecha_hasta">Fecha Hasta</label>
                    <input type="date" id="fecha_hasta" class="form-control" readonly>
                </div>
                <div class="form-group">
                    <label class="form-label" for="tipo_servicio">Tipo de Servicio</label>
                    <select id="tipo_servicio" class="form-control">
                        <option value="">Todos los servicios</option>
                        <option value="Servicio en Mesa">Servicio en Mesa</option>
                        <option value="Para Llevar">Para Llevar</option>
                        <option value="A Domicilio">A Domicilio</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="metodo_pago">Método de Pago</label>
                    <select id="metodo_pago" class="form-control">
                        <option value="">Todos los métodos</option>
                        <option value="Efectivo">Efectivo</option>
                        <option value="Tarjeta de Crédito">Tarjeta de Crédito</option>
                        <option value="Transferencia">Transferencia</option>
                    </select>
                </div>
            </div>
			
            <div class="filtros-actions">
                <button class="btn btn-primary" id="aplicarFiltros">
                    <i class="fas fa-filter"></i>
                    Aplicar Filtros
                </button>
                <button class="btn btn-secondary" id="limpiarFiltros">
                    <i class="fas fa-broom"></i>
                    Limpiar Filtros
                </button>
                <span id="filtrosAplicados" style="color: var(--secondary-color); font-size: 0.9rem;"></span>
            </div>
        </div>
<!-- Sección de Exportación -->
<div class="export-section">
    <h3 style="margin-bottom: 1rem; color: var(--text-dark);">Exportar Reporte</h3>
    <div class="export-options">
        <button class="btn btn-success" id="exportarPDF">
            <i class="fas fa-file-pdf"></i>
            Exportar a PDF
        </button>
        <select id="tipoReportePDF" class="form-control" style="width: auto;">
            <option value="reporte_completo">Reporte Completo</option>
            <option value="kpis">Solo KPIs</option>
            <option value="ventas">Reporte de Ventas</option>
            <option value="productos">Reporte de Productos</option>
			<option value="reporte_simple">Reporte Simple (Sin Gráficas)</option>
        </select>
        <span id="estadoExportacion" style="color: var(--secondary-color); font-size: 0.9rem;"></span>
    </div>
</div>
        <!-- KPI Cards -->
        <div class="kpi-grid">
            <div class="kpi-card ventas">
                <div class="kpi-header">
                    <div>
                        <div class="kpi-value" id="totalVentas">$0.00</div>
                        <div class="kpi-label">Ventas Totales</div>
                        <div class="kpi-trend" id="tendenciaVentasContainer">
                            <i class="fas fa-arrow-up"></i>
                            <span id="tendenciaVentas">0%</span>
                        </div>
                    </div>
                    <div class="kpi-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                </div>
            </div>

            <div class="kpi-card pedidos">
                <div class="kpi-header">
                    <div>
                        <div class="kpi-value" id="totalPedidos">0</div>
                        <div class="kpi-label">Total Pedidos</div>
                        <div class="kpi-trend" id="tendenciaPedidosContainer">
                            <i class="fas fa-arrow-up"></i>
                            <span id="tendenciaPedidos">0%</span>
                        </div>
                    </div>
                    <div class="kpi-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                </div>
            </div>

            <div class="kpi-card clientes">
                <div class="kpi-header">
                    <div>
                        <div class="kpi-value" id="totalClientes">0</div>
                        <div class="kpi-label">Clientes Únicos</div>
                        <div class="kpi-trend" id="tendenciaClientesContainer">
                            <i class="fas fa-arrow-up"></i>
                            <span id="tendenciaClientes">0%</span>
                        </div>
                    </div>
                    <div class="kpi-icon">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>

            <div class="kpi-card promedio">
                <div class="kpi-header">
                    <div>
                        <div class="kpi-value" id="ticketPromedio">$0.00</div>
                        <div class="kpi-label">Ticket Promedio</div>
                        <div class="kpi-trend" id="tendenciaTicketContainer">
                            <i class="fas fa-arrow-up"></i>
                            <span id="tendenciaTicket">0%</span>
                        </div>
                    </div>
                    <div class="kpi-icon">
                        <i class="fas fa-receipt"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráficas -->
        <div class="charts-grid">
            <div class="chart-card">
                <div class="chart-header">
                    <h3 class="chart-title">Ventas por Mes</h3>
                    <select id="chartYear" class="form-control" style="width: auto;">
                        <option value="2024">2024</option>
                        <option value="2025" selected>2025</option>
                    </select>
                </div>
                <div class="chart-container">
                    <canvas id="ventasChart"></canvas>
                </div>
            </div>

            <div class="chart-card">
                <div class="chart-header">
                    <h3 class="chart-title">Distribución por Tipo de Servicio</h3>
                </div>
                <div class="chart-container">
                    <canvas id="serviciosChart"></canvas>
                </div>
            </div>

            <div class="chart-card">
                <div class="chart-header">
                    <h3 class="chart-title">Métodos de Pago</h3>
                </div>
                <div class="chart-container">
                    <canvas id="pagosChart"></canvas>
                </div>
            </div>

            <div class="chart-card">
                <div class="chart-header">
                    <h3 class="chart-title">Productos Más Vendidos</h3>
                </div>
                <div class="chart-container">
                    <canvas id="productosChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Tablas de Datos -->
        <div class="tables-grid">
            <div class="table-card">
                <div class="table-header">
                    <h3 class="table-title">Últimos Pedidos</h3>
                    <button class="btn btn-secondary btn-sm" onclick="cargarPedidosRecientes()">
                        <i class="fas fa-sync"></i>
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Cliente</th>
                                <th>Total</th>
                                <th>Fecha</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody id="tablaPedidos">
                            <!-- Los pedidos se cargarán aquí -->
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="table-card">
                <div class="table-header">
                    <h3 class="table-title">Top Clientes</h3>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Cliente</th>
                                <th>Total Gastado</th>
                                <th>Pedidos</th>
                                <th>Última Visita</th>
                            </tr>
                        </thead>
                        <tbody id="tablaClientes">
                            <!-- Los clientes se cargarán aquí -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Variables globales para los charts
        let ventasChart, serviciosChart, pagosChart, productosChart;
        let rangoActual = 'hoy';

        // Inicializar al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            // Configurar rangos rápidos
            configurarRangosRapidos();
            
            // Establecer rango por defecto (hoy)
            aplicarRango('hoy');
            
            // Cargar datos iniciales
            cargarDatosReportes();
            inicializarCharts();

            // Event listeners
            document.getElementById('aplicarFiltros').addEventListener('click', cargarDatosReportes);
            document.getElementById('limpiarFiltros').addEventListener('click', limpiarFiltros);
            document.getElementById('chartYear').addEventListener('change', cargarDatosReportes);
			   document.getElementById('exportarPDF').addEventListener('click', exportarPDF);
        });

        // Configurar eventos para los botones de rango rápido
        function configurarRangosRapidos() {
            const botonesRango = document.querySelectorAll('.btn-rango');
            botonesRango.forEach(boton => {
                boton.addEventListener('click', function() {
                    // Remover clase active de todos los botones
                    botonesRango.forEach(b => b.classList.remove('active'));
                    // Agregar clase active al botón clickeado
                    this.classList.add('active');
                    
                    const rango = this.dataset.rango;
                    rangoActual = rango;
                    aplicarRango(rango);
                    cargarDatosReportes();
                });
            });
        }

        // Función para aplicar un rango específico
        function aplicarRango(rango) {
            const hoy = new Date();
            let fechaDesde, fechaHasta;

            switch (rango) {
                case 'hoy':
                    fechaDesde = new Date(hoy);
                    fechaHasta = new Date(hoy);
                    break;
                case 'ayer':
                    fechaDesde = new Date(hoy);
                    fechaDesde.setDate(hoy.getDate() - 1);
                    fechaHasta = new Date(fechaDesde);
                    break;
                case 'esta_semana':
                    fechaDesde = new Date(hoy);
                    fechaDesde.setDate(hoy.getDate() - hoy.getDay()); // Domingo de esta semana
                    fechaHasta = new Date(hoy);
                    break;
                case 'semana_pasada':
                    fechaDesde = new Date(hoy);
                    fechaDesde.setDate(hoy.getDate() - hoy.getDay() - 7); // Domingo de la semana pasada
                    fechaHasta = new Date(fechaDesde);
                    fechaHasta.setDate(fechaDesde.getDate() + 6); // Sábado de la semana pasada
                    break;
                case 'este_mes':
                    fechaDesde = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
                    fechaHasta = new Date(hoy);
                    break;
                case 'mes_pasado':
                    fechaDesde = new Date(hoy.getFullYear(), hoy.getMonth() - 1, 1);
                    fechaHasta = new Date(hoy.getFullYear(), hoy.getMonth(), 0);
                    break;
                case 'este_anio':
                    fechaDesde = new Date(hoy.getFullYear(), 0, 1);
                    fechaHasta = new Date(hoy);
                    break;
                case 'anio_pasado':
                    fechaDesde = new Date(hoy.getFullYear() - 1, 0, 1);
                    fechaHasta = new Date(hoy.getFullYear() - 1, 11, 31);
                    break;
                default:
                    fechaDesde = new Date(hoy);
                    fechaHasta = new Date(hoy);
            }

            // Formatear fechas para los inputs
            document.getElementById('fecha_desde').value = fechaDesde.toISOString().split('T')[0];
            document.getElementById('fecha_hasta').value = fechaHasta.toISOString().split('T')[0];
        }

        // Función para cargar todos los datos de reportes
        async function cargarDatosReportes() {
            const filtros = obtenerFiltros();
            mostrarLoading(true);

            try {
                // Cargar KPIs
                await cargarKPIs(filtros);
                
                // Cargar datos para gráficas
                await cargarDatosGraficas(filtros);
                
                // Cargar tablas
                await cargarPedidosRecientes(filtros);
                await cargarTopClientes(filtros);
                
                actualizarTextoFiltros(filtros);
            } catch (error) {
                console.error('Error cargando datos:', error);
                alert('Error al cargar los datos del reporte');
            } finally {
                mostrarLoading(false);
            }
        }
		
// Exportar a PDF

        // Función para obtener los filtros actuales
        function obtenerFiltros() {
            return {
                fecha_desde: document.getElementById('fecha_desde').value,
                fecha_hasta: document.getElementById('fecha_hasta').value,
                tipo_servicio: document.getElementById('tipo_servicio').value,
                metodo_pago: document.getElementById('metodo_pago').value,
                year: document.getElementById('chartYear').value,
                rango: rangoActual
            };
        }

        // Función para limpiar filtros
        function limpiarFiltros() {
            document.getElementById('tipo_servicio').value = '';
            document.getElementById('metodo_pago').value = '';
            
            // Restaurar rango por defecto (hoy)
            const botonesRango = document.querySelectorAll('.btn-rango');
            botonesRango.forEach(b => b.classList.remove('active'));
            document.querySelector('[data-rango="hoy"]').classList.add('active');
            
            aplicarRango('hoy');
            cargarDatosReportes();
        }

        // Función para mostrar/ocultar loading
        function mostrarLoading(mostrar) {
            const elementos = document.querySelectorAll('.kpi-card, .chart-card, .table-card');
            elementos.forEach(el => {
                if (mostrar) {
                    el.classList.add('loading');
                } else {
                    el.classList.remove('loading');
                }
            });
        }

        // Función para actualizar texto de filtros aplicados
        function actualizarTextoFiltros(filtros) {
            let texto = 'Filtros aplicados: ';
            const filtrosActivos = [];

            // Agregar rango actual
            const rangosTexto = {
                'hoy': 'Hoy',
                'ayer': 'Ayer', 
                'esta_semana': 'Esta Semana',
                'semana_pasada': 'Semana Pasada',
                'este_mes': 'Este Mes',
                'mes_pasado': 'Mes Pasado',
                'este_anio': 'Este Año',
                'anio_pasado': 'Año Pasado'
            };
            filtrosActivos.push(rangosTexto[filtros.rango]);

            if (filtros.tipo_servicio) {
                filtrosActivos.push(`Servicio: ${filtros.tipo_servicio}`);
            }
            if (filtros.metodo_pago) {
                filtrosActivos.push(`Pago: ${filtros.metodo_pago}`);
            }

            document.getElementById('filtrosAplicados').textContent = 
                texto + filtrosActivos.join(', ');
        }

        // Función para inicializar charts
        function inicializarCharts() {
            const ventasCtx = document.getElementById('ventasChart').getContext('2d');
            const serviciosCtx = document.getElementById('serviciosChart').getContext('2d');
            const pagosCtx = document.getElementById('pagosChart').getContext('2d');
            const productosCtx = document.getElementById('productosChart').getContext('2d');

            // Configuración inicial de charts
            ventasChart = new Chart(ventasCtx, {
                type: 'line',
                data: { labels: [], datasets: [] },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: true }
                    }
                }
            });

            serviciosChart = new Chart(serviciosCtx, {
                type: 'doughnut',
                data: { labels: [], datasets: [] },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });

            pagosChart = new Chart(pagosCtx, {
                type: 'pie',
                data: { labels: [], datasets: [] },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });

            productosChart = new Chart(productosCtx, {
                type: 'bar',
                data: { labels: [], datasets: [] },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'y'
                }
            });
        }

        // Función para hacer llamadas a la API
        async function fetchReporteData(endpoint, data = {}) {
            const requestData = {
                action: endpoint,
                ...data
            };

            try {
                const response = await fetch('../api/reportes_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(requestData)
                });
                
                const result = await response.json();
                
                if (!result.success) {
                    throw new Error(result.message);
                }
                
                return result.data;
            } catch (error) {
                console.error(`Error fetching ${endpoint}:`, error);
                throw error;
            }
        }

        // Cargar KPIs desde la API
        async function cargarKPIs(filtros) {
            try {
                const kpisData = await fetchReporteData('kpis', filtros);
                
                // Actualizar KPIs en la interfaz
                document.getElementById('totalVentas').textContent = `$${kpisData.total_ventas.toFixed(2)}`;
                document.getElementById('totalPedidos').textContent = kpisData.total_pedidos;
                document.getElementById('totalClientes').textContent = kpisData.total_clientes;
                document.getElementById('ticketPromedio').textContent = `$${kpisData.ticket_promedio.toFixed(2)}`;
                
                // Actualizar tendencias
                actualizarTendencias(kpisData.tendencias);
                
            } catch (error) {
                console.error('Error cargando KPIs:', error);
                // Mostrar valores por defecto en caso de error
                document.getElementById('totalVentas').textContent = '$0.00';
                document.getElementById('totalPedidos').textContent = '0';
                document.getElementById('totalClientes').textContent = '0';
                document.getElementById('ticketPromedio').textContent = '$0.00';
            }
        }

        // Actualizar visualización de tendencias
        function actualizarTendencias(tendencias) {
            const campos = {
                'total_ventas': 'tendenciaVentas',
                'total_pedidos': 'tendenciaPedidos', 
                'total_clientes': 'tendenciaClientes',
                'ticket_promedio': 'tendenciaTicket'
            };

            Object.keys(campos).forEach(campo => {
                const valor = tendencias[campo];
                const elemento = document.getElementById(campos[campo]);
                const container = document.getElementById(campos[campo] + 'Container');
                
                if (elemento && container) {
                    elemento.textContent = `${Math.abs(valor)}%`;
                    
                    // Actualizar icono y color según la tendencia
                    const icon = container.querySelector('i');
                    if (valor > 0) {
                        container.className = 'kpi-trend trend-up';
                        icon.className = 'fas fa-arrow-up';
                    } else if (valor < 0) {
                        container.className = 'kpi-trend trend-down';
                        icon.className = 'fas fa-arrow-down';
                    } else {
                        container.className = 'kpi-trend';
                        icon.className = 'fas fa-minus';
                    }
                }
            });
        }

        // Cargar datos para gráficas
        async function cargarDatosGraficas(filtros) {
            try {
                // Ventas mensuales
                const ventasData = await fetchReporteData('ventas_mensuales', { year: filtros.year });
                actualizarChart(ventasChart, {
                    labels: ventasData.labels,
                    datasets: [{
                        label: `Ventas ${filtros.year}`,
                        data: ventasData.datasets[0],
                        borderColor: '#2563eb',
                        backgroundColor: 'rgba(37, 99, 235, 0.1)',
                        tension: 0.4
                    }]
                });

                // Distribución servicios
                const serviciosData = await fetchReporteData('distribucion_servicios', filtros);
                actualizarChart(serviciosChart, {
                    labels: serviciosData.labels,
                    datasets: [{
                        data: serviciosData.datasets[0],
                        backgroundColor: ['#2563eb', '#10b981', '#f59e0b', '#8b5cf6', '#ec4899']
                    }]
                });

                // Métodos de pago
                const pagosData = await fetchReporteData('metodos_pago', filtros);
                actualizarChart(pagosChart, {
                    labels: pagosData.labels,
                    datasets: [{
                        data: pagosData.datasets[0],
                        backgroundColor: ['#10b981', '#2563eb', '#f59e0b', '#8b5cf6']
                    }]
                });

                // Productos vendidos
                const productosData = await fetchReporteData('productos_vendidos', filtros);
                actualizarChart(productosChart, {
                    labels: productosData.labels,
                    datasets: [{
                        label: 'Unidades Vendidas',
                        data: productosData.datasets[0],
                        backgroundColor: '#2563eb'
                    }]
                });

            } catch (error) {
                console.error('Error cargando gráficas:', error);
            }
        }

        // Función helper para actualizar charts
        function actualizarChart(chart, newData) {
            chart.data = newData;
            chart.update('none');
        }

        // Cargar pedidos recientes
        async function cargarPedidosRecientes(filtros = null) {
            try {
                const pedidosData = await fetchReporteData('pedidos_recientes', filtros || obtenerFiltros());
                const tbody = document.getElementById('tablaPedidos');
                
                tbody.innerHTML = pedidosData.map(pedido => `
                    <tr>
                        <td>#${pedido.id_pedido}</td>
                        <td>${pedido.cliente}</td>
                        <td>$${parseFloat(pedido.total_pedido).toFixed(2)}</td>
                        <td>${pedido.fecha}</td>
                        <td><span class="badge ${pedido.estado === 'completado' ? 'badge-success' : 'badge-warning'}">${pedido.estado}</span></td>
                    </tr>
                `).join('');
                
            } catch (error) {
                console.error('Error cargando pedidos recientes:', error);
                document.getElementById('tablaPedidos').innerHTML = '<tr><td colspan="5">Error al cargar los datos</td></tr>';
            }
        }
// Exportar a PDF
	async function exportarPDF() {
    const btn = this;
    const estadoExportacion = document.getElementById('estadoExportacion');
    const tipoReporte = document.getElementById('tipoReportePDF').value;
    const filtros = obtenerFiltros();
    
    // Mostrar estado de exportación
    estadoExportacion.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generando PDF...';
    btn.disabled = true;
    
    try {
        const response = await fetch('../api/generar_pdf.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                tipo_reporte: tipoReporte,
                filtros: filtros
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            estadoExportacion.innerHTML = '<i class="fas fa-check" style="color: var(--success-color);"></i> PDF generado correctamente';
            
            // Crear enlace para descargar el PDF
            const downloadLink = document.createElement('a');
            downloadLink.href = result.pdf_url;
            downloadLink.download = result.nombre_archivo;
            downloadLink.style.display = 'none';
            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);
            
            // Limpiar mensaje después de 3 segundos
            setTimeout(() => {
                estadoExportacion.innerHTML = '';
            }, 3000);
            
        } else {
            throw new Error(result.message);
        }
        
    } catch (error) {
        console.error('Error generando PDF:', error);
        estadoExportacion.innerHTML = '<i class="fas fa-exclamation-triangle" style="color: var(--danger-color);"></i> Error: ' + error.message;
        
        // Limpiar mensaje después de 5 segundos
        setTimeout(() => {
            estadoExportacion.innerHTML = '';
        }, 5000);
    } finally {
        btn.disabled = false;
    }
};
        // Cargar top clientes
        async function cargarTopClientes(filtros = null) {
            try {
                const clientesData = await fetchReporteData('top_clientes', filtros || obtenerFiltros());
                const tbody = document.getElementById('tablaClientes');
                
                tbody.innerHTML = clientesData.map(cliente => `
                    <tr>
                        <td>${cliente.cliente}</td>
                        <td>$${parseFloat(cliente.total_gastado).toFixed(2)}</td>
                        <td>${cliente.total_pedidos}</td>
                        <td>${cliente.ultima_visita ? cliente.ultima_visita.split(' ')[0] : 'N/A'}</td>
                    </tr>
                `).join('');
                
            } catch (error) {
                console.error('Error cargando top clientes:', error);
                document.getElementById('tablaClientes').innerHTML = '<tr><td colspan="4">Error al cargar los datos</td></tr>';
            }
        }
    </script>
</body>
</html>