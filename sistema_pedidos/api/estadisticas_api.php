<?php
// sistema_pedidos/api/estadisticas_api.php

require_once '../includes/db_connection.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'data' => null];

try {
    // Total de pedidos
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM pedidos");
    $totalPedidos = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Total de usuarios (todos los usuarios)
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios");
    $totalUsuarios = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Total de productos disponibles
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM productos WHERE disponible = 1");
    $totalProductos = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Ingresos totales (suma de todos los pedidos completados)
    $stmt = $pdo->query("SELECT COALESCE(SUM(total_pedido), 0) as total FROM pedidos WHERE estado = 'completado'");
    $totalIngresos = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Actividad reciente (últimos 5 eventos)
    $actividadReciente = [];
    
    // Últimos pedidos (últimos 3)
    $stmt = $pdo->query("SELECT p.id_pedido, p.fecha_pedido, p.estado, 
                         c.nombre_cliente, c.apellido_cliente
                         FROM pedidos p 
                         LEFT JOIN clientes c ON p.id_cliente = c.id_cliente
                         ORDER BY p.fecha_pedido DESC 
                         LIMIT 3");
    $pedidosRecientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($pedidosRecientes as $pedido) {
        $nombreCliente = $pedido['nombre_cliente'] ? 
                        $pedido['nombre_cliente'] . ' ' . $pedido['apellido_cliente'] : 'Cliente';
        
        $actividadReciente[] = [
            'tipo' => 'pedido',
            'texto' => "Nuevo pedido #" . str_pad($pedido['id_pedido'], 5, '0', STR_PAD_LEFT) . " - " . $nombreCliente,
            'tiempo' => $pedido['fecha_pedido']
        ];
    }

    // Últimos usuarios registrados (últimos 2)
    $stmt = $pdo->query("SELECT nombre_usuario, fechaRegistro 
                         FROM usuarios 
                         ORDER BY fechaRegistro DESC 
                         LIMIT 2");
    $usuariosRecientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($usuariosRecientes as $usuario) {
        $actividadReciente[] = [
            'tipo' => 'usuario',
            'texto' => "Nuevo usuario registrado: " . $usuario['nombre_usuario'],
            'tiempo' => $usuario['fechaRegistro']
        ];
    }

    // Ordenar actividad por fecha (más reciente primero) y limitar a 5
    usort($actividadReciente, function($a, $b) {
        return strtotime($b['tiempo']) - strtotime($a['tiempo']);
    });
    $actividadReciente = array_slice($actividadReciente, 0, 5);

    $response['success'] = true;
    $response['data'] = [
        'total_pedidos' => (int)$totalPedidos,
        'total_usuarios' => (int)$totalUsuarios,
        'total_productos' => (int)$totalProductos,
        'total_ingresos' => (float)$totalIngresos,
        'actividad_reciente' => $actividadReciente
    ];

} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
}

echo json_encode($response);
exit();
?>