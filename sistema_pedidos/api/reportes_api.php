<?php
// sistema_pedidos/api/reportes_api.php

// Habilitar reporte de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../includes/db_connection.php';
// ... resto del código

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

$response = ['success' => false, 'message' => '', 'data' => null];
$data = json_decode(file_get_contents('php://input'), true);
$action = $_GET['action'] ?? $data['action'] ?? '';

try {
    switch ($action) {
        case 'kpis':
            $filtros = buildFilters($data);
            $response = getKPIs($pdo, $filtros);
            break;

        case 'ventas_mensuales':
            $year = $data['year'] ?? date('Y');
            $response = getVentasMensuales($pdo, $year);
            break;

        case 'distribucion_servicios':
            $filtros = buildFilters($data);
            $response = getDistribucionServicios($pdo, $filtros);
            break;

        case 'metodos_pago':
            $filtros = buildFilters($data);
            $response = getMetodosPago($pdo, $filtros);
            break;

        case 'productos_vendidos':
            $filtros = buildFilters($data);
            $response = getProductosVendidos($pdo, $filtros);
            break;

        case 'pedidos_recientes':
            $filtros = buildFilters($data);
            $response = getPedidosRecientes($pdo, $filtros);
            break;

        case 'top_clientes':
            $filtros = buildFilters($data);
            $response = getTopClientes($pdo, $filtros);
            break;

        default:
            $response['message'] = 'Acción no válida.';
            break;
    }
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
}

echo json_encode($response);
exit();

// Función para construir filtros
function buildFilters($data) {
    return [
        'fecha_desde' => $data['fecha_desde'] ?? null,
        'fecha_hasta' => $data['fecha_hasta'] ?? null,
        'tipo_servicio' => $data['tipo_servicio'] ?? null,
        'metodo_pago' => $data['metodo_pago'] ?? null
    ];
}

// Función para construir WHERE clause
function buildWhereClause($filtros) {
    $where = [];
    $params = [];

    if (!empty($filtros['fecha_desde']) && !empty($filtros['fecha_hasta'])) {
        $where[] = "p.fecha_pedido BETWEEN ? AND ?";
        $params[] = $filtros['fecha_desde'] . ' 00:00:00';
        $params[] = $filtros['fecha_hasta'] . ' 23:59:59';
    }

    if (!empty($filtros['tipo_servicio'])) {
        $where[] = "p.tipo_servicio = ?";
        $params[] = $filtros['tipo_servicio'];
    }

    if (!empty($filtros['metodo_pago'])) {
        $where[] = "p.metodo_pago = ?";
        $params[] = $filtros['metodo_pago'];
    }

    return [
        'clause' => empty($where) ? '' : 'WHERE ' . implode(' AND ', $where),
        'params' => $params
    ];
}

// KPIs principales
function getKPIs($pdo, $filtros) {
    $where = buildWhereClause($filtros);
    
    // Ventas totales
    $sql_ventas = "SELECT COALESCE(SUM(total_pedido), 0) as total_ventas FROM pedidos p {$where['clause']}";
    $stmt = $pdo->prepare($sql_ventas);
    $stmt->execute($where['params']);
    $ventas = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Total pedidos
    $sql_pedidos = "SELECT COUNT(*) as total_pedidos FROM pedidos p {$where['clause']}";
    $stmt = $pdo->prepare($sql_pedidos);
    $stmt->execute($where['params']);
    $pedidos = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Clientes únicos
    $sql_clientes = "SELECT COUNT(DISTINCT id_cliente) as total_clientes FROM pedidos p {$where['clause']}";
    $stmt = $pdo->prepare($sql_clientes);
    $stmt->execute($where['params']);
    $clientes = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Ticket promedio
    $ticket_promedio = $pedidos['total_pedidos'] > 0 ? $ventas['total_ventas'] / $pedidos['total_pedidos'] : 0;
    
    // Cálculo de tendencias (comparación con período anterior)
    $tendencias = calcularTendencias($pdo, $filtros);
    
    return [
        'success' => true,
        'data' => [
            'total_ventas' => (float)$ventas['total_ventas'],
            'total_pedidos' => (int)$pedidos['total_pedidos'],
            'total_clientes' => (int)$clientes['total_clientes'],
            'ticket_promedio' => round($ticket_promedio, 2),
            'tendencias' => $tendencias
        ]
    ];
}

// Ventas mensuales
function getVentasMensuales($pdo, $year) {
    $sql = "SELECT 
                MONTH(fecha_pedido) as mes,
                COALESCE(SUM(total_pedido), 0) as total_ventas
            FROM pedidos 
            WHERE YEAR(fecha_pedido) = ? 
            GROUP BY MONTH(fecha_pedido)
            ORDER BY mes";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$year]);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Crear array completo de 12 meses
    $ventas_mensuales = array_fill(1, 12, 0);
    foreach ($resultados as $row) {
        $ventas_mensuales[(int)$row['mes']] = (float)$row['total_ventas'];
    }
    
    return [
        'success' => true,
        'data' => [
            'labels' => ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
            'datasets' => [array_values($ventas_mensuales)]
        ]
    ];
}

// Distribución por servicios
function getDistribucionServicios($pdo, $filtros) {
    $where = buildWhereClause($filtros);
    
    $sql = "SELECT 
                tipo_servicio,
                COUNT(*) as cantidad,
                SUM(total_pedido) as total_ventas
            FROM pedidos p
            {$where['clause']}
            GROUP BY tipo_servicio
            ORDER BY cantidad DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($where['params']);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $labels = [];
    $data = [];
    
    foreach ($resultados as $row) {
        $labels[] = $row['tipo_servicio'];
        $data[] = (int)$row['cantidad'];
    }
    
    return [
        'success' => true,
        'data' => [
            'labels' => $labels,
            'datasets' => [$data]
        ]
    ];
}

// Métodos de pago
function getMetodosPago($pdo, $filtros) {
    $where = buildWhereClause($filtros);
    
    $sql = "SELECT 
                metodo_pago,
                COUNT(*) as cantidad,
                SUM(total_pedido) as total_ventas
            FROM pedidos p
            {$where['clause']}
            GROUP BY metodo_pago
            ORDER BY cantidad DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($where['params']);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $labels = [];
    $data = [];
    
    foreach ($resultados as $row) {
        $labels[] = $row['metodo_pago'];
        $data[] = (int)$row['cantidad'];
    }
    
    return [
        'success' => true,
        'data' => [
            'labels' => $labels,
            'datasets' => [$data]
        ]
    ];
}

// Productos más vendidos
function getProductosVendidos($pdo, $filtros) {
    $where = buildWhereClause($filtros);
    
    $sql = "SELECT 
                pr.nombre_producto,
                SUM(pi.cantidad) as total_vendido,
                SUM(pi.cantidad * pi.precio_unitario) as total_ventas
            FROM pedidos_items pi
            JOIN pedidos p ON pi.id_pedido = p.id_pedido
            JOIN productos pr ON pi.id_producto = pr.id_producto
            {$where['clause']}
            GROUP BY pr.id_producto, pr.nombre_producto
            ORDER BY total_vendido DESC
            LIMIT 10";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($where['params']);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $labels = [];
    $data = [];
    
    foreach ($resultados as $row) {
        $labels[] = $row['nombre_producto'];
        $data[] = (int)$row['total_vendido'];
    }
    
    return [
        'success' => true,
        'data' => [
            'labels' => $labels,
            'datasets' => [$data]
        ]
    ];
}

// Pedidos recientes
function getPedidosRecientes($pdo, $filtros) {
    $where = buildWhereClause($filtros);
    
    $sql = "SELECT 
                p.id_pedido,
                CONCAT(c.nombre_cliente, ' ', c.apellido_cliente) as cliente,
                p.total_pedido,
                DATE(p.fecha_pedido) as fecha,
                p.estado
            FROM pedidos p
            JOIN clientes c ON p.id_cliente = c.id_cliente
            {$where['clause']}
            ORDER BY p.fecha_pedido DESC
            LIMIT 10";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($where['params']);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'success' => true,
        'data' => $resultados
    ];
}

// Top clientes
function getTopClientes($pdo, $filtros) {
    $where = buildWhereClause($filtros);
    
    $sql = "SELECT 
                c.id_cliente,
                CONCAT(c.nombre_cliente, ' ', c.apellido_cliente) as cliente,
                COUNT(p.id_pedido) as total_pedidos,
                SUM(p.total_pedido) as total_gastado,
                MAX(p.fecha_pedido) as ultima_visita
            FROM clientes c
            JOIN pedidos p ON c.id_cliente = p.id_cliente
            {$where['clause']}
            GROUP BY c.id_cliente, c.nombre_cliente, c.apellido_cliente
            ORDER BY total_gastado DESC
            LIMIT 10";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($where['params']);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'success' => true,
        'data' => $resultados
    ];
}

// Calcular tendencias comparando con período anterior
// Calcular tendencias comparando con período anterior
function calcularTendencias($pdo, $filtros) {
    if (empty($filtros['fecha_desde']) || empty($filtros['fecha_hasta'])) {
        return [
            'total_ventas' => 0, 
            'total_pedidos' => 0, 
            'total_clientes' => 0, 
            'ticket_promedio' => 0
        ];
    }
    
    $fecha_desde = new DateTime($filtros['fecha_desde']);
    $fecha_hasta = new DateTime($filtros['fecha_hasta']);
    $diferencia = $fecha_desde->diff($fecha_hasta);
    
    // Período anterior
    $periodo_anterior_desde = clone $fecha_desde;
    $periodo_anterior_hasta = clone $fecha_hasta;
    
    if ($diferencia->days <= 31) { // Período mensual
        $periodo_anterior_desde->modify('-1 month');
        $periodo_anterior_hasta->modify('-1 month');
    } else { // Período anual
        $periodo_anterior_desde->modify('-1 year');
        $periodo_anterior_hasta->modify('-1 year');
    }
    
    $filtros_anteriores = [
        'fecha_desde' => $periodo_anterior_desde->format('Y-m-d'),
        'fecha_hasta' => $periodo_anterior_hasta->format('Y-m-d'),
        'tipo_servicio' => $filtros['tipo_servicio'],
        'metodo_pago' => $filtros['metodo_pago']
    ];
    
    // Obtener KPIs del período actual sin recursión
    $kpis_actual = obtenerKPIsDirectos($pdo, $filtros);
    $kpis_anterior = obtenerKPIsDirectos($pdo, $filtros_anteriores);
    
    // Calcular porcentajes de cambio
    $tendencias = [];
    $campos = ['total_ventas', 'total_pedidos', 'total_clientes', 'ticket_promedio'];
    
    foreach ($campos as $campo) {
        $actual = $kpis_actual[$campo];
        $anterior = $kpis_anterior[$campo];
        
        if ($anterior > 0) {
            $tendencias[$campo] = round((($actual - $anterior) / $anterior) * 100, 1);
        } else {
            $tendencias[$campo] = $actual > 0 ? 100 : 0;
        }
    }
    
    return $tendencias;
}

// Función auxiliar para obtener KPIs sin recursión
function obtenerKPIsDirectos($pdo, $filtros) {
    $where = buildWhereClause($filtros);
    
    // Ventas totales
    $sql_ventas = "SELECT COALESCE(SUM(total_pedido), 0) as total_ventas FROM pedidos p {$where['clause']}";
    $stmt = $pdo->prepare($sql_ventas);
    $stmt->execute($where['params']);
    $ventas = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Total pedidos
    $sql_pedidos = "SELECT COUNT(*) as total_pedidos FROM pedidos p {$where['clause']}";
    $stmt = $pdo->prepare($sql_pedidos);
    $stmt->execute($where['params']);
    $pedidos = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Clientes únicos
    $sql_clientes = "SELECT COUNT(DISTINCT id_cliente) as total_clientes FROM pedidos p {$where['clause']}";
    $stmt = $pdo->prepare($sql_clientes);
    $stmt->execute($where['params']);
    $clientes = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Ticket promedio
    $ticket_promedio = $pedidos['total_pedidos'] > 0 ? $ventas['total_ventas'] / $pedidos['total_pedidos'] : 0;
    
    return [
        'total_ventas' => (float)$ventas['total_ventas'],
        'total_pedidos' => (int)$pedidos['total_pedidos'],
        'total_clientes' => (int)$clientes['total_clientes'],
        'ticket_promedio' => round($ticket_promedio, 2)
    ];
}
?>