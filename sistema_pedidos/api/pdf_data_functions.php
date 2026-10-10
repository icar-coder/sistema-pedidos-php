<?php
// sistema_pedidos/api/pdf_data_functions.php

require_once '../includes/db_connection.php';

// Función para construir WHERE clause
function buildWhereClausePDF($filtros) {
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

// KPIs principales para PDF
function getKPIsForPDF($pdo, $filtros) {
    $where = buildWhereClausePDF($filtros);
    
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
    
    // Tendencias simples (puedes ajustar esto según tus necesidades)
    $tendencias = [
        'total_ventas' => 0,
        'total_pedidos' => 0,
        'total_clientes' => 0,
        'ticket_promedio' => 0
    ];
    
    return [
        'total_ventas' => (float)$ventas['total_ventas'],
        'total_pedidos' => (int)$pedidos['total_pedidos'],
        'total_clientes' => (int)$clientes['total_clientes'],
        'ticket_promedio' => round($ticket_promedio, 2),
        'tendencias' => $tendencias
    ];
}

// Pedidos recientes para PDF
function getPedidosRecientesForPDF($pdo, $filtros) {
    $where = buildWhereClausePDF($filtros);
    
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
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Top clientes para PDF
function getTopClientesForPDF($pdo, $filtros) {
    $where = buildWhereClausePDF($filtros);
    
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
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Productos vendidos para PDF
function getProductosVendidosForPDF($pdo, $filtros) {
    $where = buildWhereClausePDF($filtros);
    
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
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Distribución servicios para PDF
function getDistribucionServiciosForPDF($pdo, $filtros) {
    $where = buildWhereClausePDF($filtros);
    
    $sql = "SELECT 
                tipo_servicio,
                COUNT(*) as cantidad
            FROM pedidos p
            {$where['clause']}
            GROUP BY tipo_servicio
            ORDER BY cantidad DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($where['params']);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Métodos de pago para PDF
function getMetodosPagoForPDF($pdo, $filtros) {
    $where = buildWhereClausePDF($filtros);
    
    $sql = "SELECT 
                metodo_pago,
                COUNT(*) as cantidad
            FROM pedidos p
            {$where['clause']}
            GROUP BY metodo_pago
            ORDER BY cantidad DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($where['params']);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>