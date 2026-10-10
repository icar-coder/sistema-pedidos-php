<?php
// sistema_padidos/api/generar_pdf.php

require_once '../includes/db_connection.php';
require_once 'pdf_data_functions.php'; // Nuevo archivo con funciones específicas para PDF

// Incluir DOMPDF manualmente
require_once '../includes/dompdf/autoload.inc.php';

// Referenciar el namespace de DOMPDF
use Dompdf\Dompdf;
use Dompdf\Options;

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit();
}

// Obtener datos JSON del cuerpo de la solicitud
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Datos JSON no válidos']);
    exit();
}

$tipo_reporte = $data['tipo_reporte'] ?? '';
$filtros = $data['filtros'] ?? [];

header('Content-Type: application/json');

try {
    // Configurar opciones de DOMPDF
    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', true);
    $options->set('defaultFont', 'Arial');
    
    $dompdf = new Dompdf($options);

    // Generar HTML según el tipo de reporte
    $html = generarHTMLReporte($tipo_reporte, $filtros, $pdo);
    
    if (!$html) {
        throw new Exception('No se pudo generar el contenido del reporte');
    }

    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    // Guardar el PDF en el servidor
    $nombre_archivo = generarNombreArchivo($tipo_reporte, $filtros);
    $ruta_pdf = "../temp_pdfs/" . $nombre_archivo;
    
    // Crear directorio si no existe
    if (!is_dir('../temp_pdfs')) {
        mkdir('../temp_pdfs', 0755, true);
    }

    // Guardar el PDF
    file_put_contents($ruta_pdf, $dompdf->output());
    
    echo json_encode([
        'success' => true,
        'message' => 'PDF generado correctamente',
        'pdf_url' => '../temp_pdfs/' . $nombre_archivo,
        'nombre_archivo' => $nombre_archivo
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Error al generar PDF: ' . $e->getMessage()
    ]);
}
exit();

function generarHTMLReporte($tipo_reporte, $filtros, $pdo) {
    switch ($tipo_reporte) {
        case 'reporte_completo':
            return generarReporteCompleto($filtros, $pdo);
        case 'kpis':
            return generarReporteKPIs($filtros, $pdo);
        case 'ventas':
            return generarReporteVentas($filtros, $pdo);
        case 'productos':
            return generarReporteProductos($filtros, $pdo);
        case 'reporte_simple':
            return generarReporteSimple($filtros, $pdo);
        default:
            return generarReporteCompleto($filtros, $pdo);
    }
}

function generarReporteCompleto($filtros, $pdo) {
    $datos = obtenerDatosCompletos($filtros, $pdo);
    $rango_texto = obtenerTextoRango($filtros['rango'] ?? 'hoy');
    
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Reporte Completo - Sistema de Pedidos</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; color: #333; }
            .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #2563eb; padding-bottom: 20px; }
            .header h1 { color: #2563eb; margin: 0; font-size: 28px; }
            .header .subtitle { color: #666; font-size: 16px; margin: 5px 0; }
            .section { margin-bottom: 30px; page-break-inside: avoid; }
            .section-title { background: #f8fafc; padding: 12px; border-left: 4px solid #2563eb; margin-bottom: 15px; font-size: 18px; font-weight: bold; }
            .kpi-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 20px; }
            .kpi-card { border: 1px solid #e2e8f0; padding: 15px; border-radius: 8px; text-align: center; background: white; }
            .kpi-value { font-size: 24px; font-weight: bold; color: #2563eb; margin-bottom: 5px; }
            .kpi-label { color: #666; font-size: 14px; margin-bottom: 8px; }
            .table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 12px; }
            .table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            .table th { background-color: #f8fafc; font-weight: bold; }
            .badge { padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: bold; }
            .badge-success { background: #dcfce7; color: #16a34a; }
            .badge-warning { background: #fef3c7; color: #d97706; }
            .badge-danger { background: #fecaca; color: #dc2626; }
            .footer { margin-top: 50px; text-align: center; color: #666; font-size: 12px; border-top: 1px solid #ddd; padding-top: 20px; }
            .filtros-info { background: #f1f5f9; padding: 10px; border-radius: 5px; margin-bottom: 20px; font-size: 14px; }
            .text-center { text-align: center; }
            .text-right { text-align: right; }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>Reporte Completo - Sistema de Pedidos</h1>
            <div class="subtitle">Período: ' . $rango_texto . '</div>
            <div class="subtitle">Generado: ' . date('d/m/Y H:i:s') . '</div>
        </div>

        <!-- Información de Filtros -->
        <div class="filtros-info">
            <strong>Filtros aplicados:</strong> ' . $rango_texto;
            
    if (!empty($filtros['tipo_servicio'])) {
        $html .= ' | Servicio: ' . htmlspecialchars($filtros['tipo_servicio']);
    }
    if (!empty($filtros['metodo_pago'])) {
        $html .= ' | Pago: ' . htmlspecialchars($filtros['metodo_pago']);
    }
    
    $html .= '
        </div>

        <!-- KPIs -->
        <div class="section">
            <div class="section-title">Indicadores Clave (KPIs)</div>
            <div class="kpi-grid">';
    
    $html .= '
                <div class="kpi-card">
                    <div class="kpi-value">$' . number_format($datos['kpis']['total_ventas'], 2) . '</div>
                    <div class="kpi-label">Ventas Totales</div>
                </div>';
    
    $html .= '
                <div class="kpi-card">
                    <div class="kpi-value">' . number_format($datos['kpis']['total_pedidos']) . '</div>
                    <div class="kpi-label">Total Pedidos</div>
                </div>';
    
    $html .= '
                <div class="kpi-card">
                    <div class="kpi-value">' . number_format($datos['kpis']['total_clientes']) . '</div>
                    <div class="kpi-label">Clientes Únicos</div>
                </div>';
    
    $html .= '
                <div class="kpi-card">
                    <div class="kpi-value">$' . number_format($datos['kpis']['ticket_promedio'], 2) . '</div>
                    <div class="kpi-label">Ticket Promedio</div>
                </div>';
    
    $html .= '
            </div>
        </div>';

    // Pedidos Recientes
    if (!empty($datos['pedidos_recientes'])) {
        $html .= '
        <div class="section">
            <div class="section-title">Últimos Pedidos</div>
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Cliente</th>
                        <th>Total</th>
                        <th>Fecha</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>';
        
        foreach ($datos['pedidos_recientes'] as $pedido) {
            $estado_badge = '';
            switch ($pedido['estado']) {
                case 'completado':
                    $estado_badge = 'badge-success';
                    break;
                case 'pendiente':
                    $estado_badge = 'badge-warning';
                    break;
                case 'cancelado':
                    $estado_badge = 'badge-danger';
                    break;
                default:
                    $estado_badge = 'badge-warning';
            }
            
            $html .= '
                    <tr>
                        <td>#' . $pedido['id_pedido'] . '</td>
                        <td>' . htmlspecialchars($pedido['cliente']) . '</td>
                        <td class="text-right">$' . number_format($pedido['total_pedido'], 2) . '</td>
                        <td>' . $pedido['fecha'] . '</td>
                        <td><span class="badge ' . $estado_badge . '">' . ucfirst($pedido['estado']) . '</span></td>
                    </tr>';
        }
        
        $html .= '
                </tbody>
            </table>
        </div>';
    }

    // Top Clientes
    if (!empty($datos['top_clientes'])) {
        $html .= '
        <div class="section">
            <div class="section-title">Top Clientes</div>
            <table class="table">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Total Gastado</th>
                        <th>Pedidos</th>
                        <th>Última Visita</th>
                    </tr>
                </thead>
                <tbody>';
        
        foreach ($datos['top_clientes'] as $cliente) {
            $html .= '
                    <tr>
                        <td>' . htmlspecialchars($cliente['cliente']) . '</td>
                        <td class="text-right">$' . number_format($cliente['total_gastado'], 2) . '</td>
                        <td class="text-center">' . $cliente['total_pedidos'] . '</td>
                        <td>' . ($cliente['ultima_visita'] ? date('d/m/Y', strtotime($cliente['ultima_visita'])) : 'N/A') . '</td>
                    </tr>';
        }
        
        $html .= '
                </tbody>
            </table>
        </div>';
    }

    // Productos Más Vendidos
    if (!empty($datos['productos_vendidos'])) {
        $html .= '
        <div class="section">
            <div class="section-title">Productos Más Vendidos</div>
            <table class="table">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Unidades Vendidas</th>
                        <th>Total Ventas</th>
                    </tr>
                </thead>
                <tbody>';
        
        foreach ($datos['productos_vendidos'] as $producto) {
            $html .= '
                    <tr>
                        <td>' . htmlspecialchars($producto['nombre_producto']) . '</td>
                        <td class="text-center">' . number_format($producto['total_vendido']) . '</td>
                        <td class="text-right">$' . number_format($producto['total_ventas'], 2) . '</td>
                    </tr>';
        }
        
        $html .= '
                </tbody>
            </table>
        </div>';
    }

    // Distribución por Servicios
    if (!empty($datos['distribucion_servicios'])) {
        $html .= '
        <div class="section">
            <div class="section-title">Distribución por Tipo de Servicio</div>
            <table class="table">
                <thead>
                    <tr>
                        <th>Tipo de Servicio</th>
                        <th>Cantidad de Pedidos</th>
                        <th>Porcentaje</th>
                    </tr>
                </thead>
                <tbody>';
        
        $total_pedidos = array_sum(array_column($datos['distribucion_servicios'], 'cantidad'));
        
        foreach ($datos['distribucion_servicios'] as $servicio) {
            $porcentaje = $total_pedidos > 0 ? ($servicio['cantidad'] / $total_pedidos) * 100 : 0;
            
            $html .= '
                    <tr>
                        <td>' . htmlspecialchars($servicio['tipo_servicio']) . '</td>
                        <td class="text-center">' . number_format($servicio['cantidad']) . '</td>
                        <td class="text-right">' . number_format($porcentaje, 1) . '%</td>
                    </tr>';
        }
        
        $html .= '
                </tbody>
            </table>
        </div>';
    }

    // Métodos de Pago
    if (!empty($datos['metodos_pago'])) {
        $html .= '
        <div class="section">
            <div class="section-title">Métodos de Pago</div>
            <table class="table">
                <thead>
                    <tr>
                        <th>Método de Pago</th>
                        <th>Cantidad de Pedidos</th>
                        <th>Porcentaje</th>
                    </tr>
                </thead>
                <tbody>';
        
        $total_pagos = array_sum(array_column($datos['metodos_pago'], 'cantidad'));
        
        foreach ($datos['metodos_pago'] as $pago) {
            $porcentaje = $total_pagos > 0 ? ($pago['cantidad'] / $total_pagos) * 100 : 0;
            
            $html .= '
                    <tr>
                        <td>' . htmlspecialchars($pago['metodo_pago']) . '</td>
                        <td class="text-center">' . number_format($pago['cantidad']) . '</td>
                        <td class="text-right">' . number_format($porcentaje, 1) . '%</td>
                    </tr>';
        }
        
        $html .= '
                </tbody>
            </table>
        </div>';
    }

    $html .= '
        <div class="footer">
            <p>Sistema de Pedidos - Reporte generado automáticamente el ' . date('d/m/Y \a \l\a\s H:i:s') . '</p>
        </div>
    </body>
    </html>';
    
    return $html;
}

function obtenerDatosCompletos($filtros, $pdo) {
    $datos = [];
    
    // Obtener KPIs
    $datos['kpis'] = getKPIsForPDF($pdo, $filtros);
    
    // Obtener pedidos recientes
    $datos['pedidos_recientes'] = getPedidosRecientesForPDF($pdo, $filtros);
    
    // Obtener top clientes
    $datos['top_clientes'] = getTopClientesForPDF($pdo, $filtros);
    
    // Obtener productos vendidos
    $datos['productos_vendidos'] = getProductosVendidosForPDF($pdo, $filtros);
    
    // Obtener distribución de servicios
    $datos['distribucion_servicios'] = getDistribucionServiciosForPDF($pdo, $filtros);
    
    // Obtener métodos de pago
    $datos['metodos_pago'] = getMetodosPagoForPDF($pdo, $filtros);
    
    return $datos;
}

function obtenerTextoRango($rango) {
    $rangos = [
        'hoy' => 'Hoy',
        'ayer' => 'Ayer',
        'esta_semana' => 'Esta Semana',
        'semana_pasada' => 'Semana Pasada',
        'este_mes' => 'Este Mes',
        'mes_pasado' => 'Mes Pasado',
        'este_anio' => 'Este Año',
        'anio_pasado' => 'Año Pasado'
    ];
    
    return $rangos[$rango] ?? 'Período no especificado';
}

function generarNombreArchivo($tipo_reporte, $filtros) {
    $rango = $filtros['rango'] ?? 'reporte';
    $fecha = date('Y-m-d_H-i-s');
    
    $nombres_tipos = [
        'reporte_completo' => 'Reporte_Completo',
        'kpis' => 'Reporte_KPIs',
        'ventas' => 'Reporte_Ventas',
        'productos' => 'Reporte_Productos',
        'reporte_simple' => 'Reporte_Simple'
    ];
    
    $tipo_nombre = $nombres_tipos[$tipo_reporte] ?? 'Reporte';
    
    return $tipo_nombre . '_' . $rango . '_' . $fecha . '.pdf';
}

function generarReporteKPIs($filtros, $pdo) {
    $datos = obtenerDatosCompletos($filtros, $pdo);
    $rango_texto = obtenerTextoRango($filtros['rango'] ?? 'hoy');
    
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Reporte de KPIs - Sistema de Pedidos</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; color: #333; }
            .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #2563eb; padding-bottom: 20px; }
            .header h1 { color: #2563eb; margin: 0; font-size: 28px; }
            .header .subtitle { color: #666; font-size: 16px; margin: 5px 0; }
            .kpi-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin: 30px 0; }
            .kpi-card { border: 2px solid #e2e8f0; padding: 25px; border-radius: 10px; text-align: center; background: white; }
            .kpi-value { font-size: 32px; font-weight: bold; color: #2563eb; margin-bottom: 10px; }
            .kpi-label { color: #666; font-size: 16px; margin-bottom: 12px; font-weight: bold; }
            .footer { margin-top: 50px; text-align: center; color: #666; font-size: 12px; border-top: 1px solid #ddd; padding-top: 20px; }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>Reporte de KPIs - Sistema de Pedidos</h1>
            <div class="subtitle">Período: ' . $rango_texto . '</div>
            <div class="subtitle">Generado: ' . date('d/m/Y H:i:s') . '</div>
        </div>

        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-value">$' . number_format($datos['kpis']['total_ventas'], 2) . '</div>
                <div class="kpi-label">VENTAS TOTALES</div>
            </div>
            
            <div class="kpi-card">
                <div class="kpi-value">' . number_format($datos['kpis']['total_pedidos']) . '</div>
                <div class="kpi-label">TOTAL PEDIDOS</div>
            </div>
            
            <div class="kpi-card">
                <div class="kpi-value">' . number_format($datos['kpis']['total_clientes']) . '</div>
                <div class="kpi-label">CLIENTES ÚNICOS</div>
            </div>
            
            <div class="kpi-card">
                <div class="kpi-value">$' . number_format($datos['kpis']['ticket_promedio'], 2) . '</div>
                <div class="kpi-label">TICKET PROMEDIO</div>
            </div>
        </div>

        <div class="footer">
            <p>Sistema de Pedidos - Reporte de KPIs generado automáticamente</p>
        </div>
    </body>
    </html>';
    
    return $html;
}

function generarReporteSimple($filtros, $pdo) {
    $datos = obtenerDatosCompletos($filtros, $pdo);
    $rango_texto = obtenerTextoRango($filtros['rango'] ?? 'hoy');
    
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Reporte Simple - Sistema de Pedidos</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; color: #333; }
            .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #2563eb; padding-bottom: 20px; }
            .header h1 { color: #2563eb; margin: 0; font-size: 28px; }
            .section { margin-bottom: 30px; }
            .section-title { background: #f8fafc; padding: 12px; border-left: 4px solid #2563eb; margin-bottom: 15px; font-size: 18px; font-weight: bold; }
            .table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 12px; }
            .table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            .table th { background-color: #f8fafc; font-weight: bold; }
            .kpi-value { font-size: 24px; font-weight: bold; color: #2563eb; margin-bottom: 5px; }
            .kpi-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 20px; }
            .kpi-card { border: 1px solid #e2e8f0; padding: 15px; border-radius: 8px; text-align: center; background: white; }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>Reporte Simple - Sistema de Pedidos</h1>
            <div class="subtitle">Período: ' . $rango_texto . '</div>
            <div class="subtitle">Generado: ' . date('d/m/Y H:i:s') . '</div>
        </div>';

    // KPIs Resumen
    $html .= '
        <div class="section">
            <div class="section-title">Resumen General</div>
            <div class="kpi-grid">
                <div class="kpi-card">
                    <div class="kpi-value">$' . number_format($datos['kpis']['total_ventas'], 2) . '</div>
                    <div>Ventas Totales</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-value">' . number_format($datos['kpis']['total_pedidos']) . '</div>
                    <div>Total Pedidos</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-value">' . number_format($datos['kpis']['total_clientes']) . '</div>
                    <div>Clientes Únicos</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-value">$' . number_format($datos['kpis']['ticket_promedio'], 2) . '</div>
                    <div>Ticket Promedio</div>
                </div>
            </div>
        </div>';

    // Últimos Pedidos
    if (!empty($datos['pedidos_recientes'])) {
        $html .= '
        <div class="section">
            <div class="section-title">Últimos Pedidos</div>
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Cliente</th>
                        <th>Total</th>
                        <th>Fecha</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>';
        
        foreach ($datos['pedidos_recientes'] as $pedido) {
            $html .= '
                    <tr>
                        <td>#' . $pedido['id_pedido'] . '</td>
                        <td>' . htmlspecialchars($pedido['cliente']) . '</td>
                        <td>$' . number_format($pedido['total_pedido'], 2) . '</td>
                        <td>' . $pedido['fecha'] . '</td>
                        <td>' . ucfirst($pedido['estado']) . '</td>
                    </tr>';
        }
        
        $html .= '
                </tbody>
            </table>
        </div>';
    }

    $html .= '
        <div class="footer">
            <p>Sistema de Pedidos - Reporte generado el ' . date('d/m/Y \a \l\a\s H:i:s') . '</p>
        </div>
    </body>
    </html>';
    
    return $html;
}

function generarReporteVentas($filtros, $pdo) {
    return generarReporteCompleto($filtros, $pdo);
}

function generarReporteProductos($filtros, $pdo) {
    return generarReporteCompleto($filtros, $pdo);
}
?>