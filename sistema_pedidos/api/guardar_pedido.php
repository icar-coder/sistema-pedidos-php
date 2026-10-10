<?php
// sistema_pedidos/api/guardar_pedido.php

require_once '../includes/db_connection.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Calcular totales
    $subtotal = 0;
    foreach ($data['productos'] as $producto) {
        $subtotal += $producto['precio'] * $producto['cantidad'];
    }
    
    $iva_porcentaje = 12; // Puedes obtener esto de la tabla configuraciones
    $iva = $subtotal * ($iva_porcentaje / 100);
    $total = $subtotal + $iva;
    
    // Obtener tasa de cambio actual
    $stmt = $pdo->prepare("SELECT valor_dolar FROM tasa_dolar WHERE activo = 1 ORDER BY fecha_actualizacion DESC LIMIT 1");
    $stmt->execute();
    $tasa_dolar = $stmt->fetchColumn();
    $total_bolivares = $total * $tasa_dolar;

    // Insertar pedido con estado 'preparando'
    $stmt = $pdo->prepare("
        INSERT INTO pedidos 
        (id_cliente, id_mesa, tipo_servicio, metodo_pago, total_pedido, total_bolivares, tasa_dolar_aplicada, estado) 
        VALUES (?, ?, ?, ?, ?, ?, ?, 'pendiente')
    ");
    
    $stmt->execute([
        $data['id_cliente'],
        $data['id_mesa'] ?? null,
        $data['tipo_servicio'],
        $data['tipo_pago'],
        $total,
        $total_bolivares,
        $tasa_dolar
    ]);
    
    $id_pedido = $pdo->lastInsertId();

    // Insertar items del pedido
    $stmt = $pdo->prepare("
        INSERT INTO pedidos_items 
        (id_pedido, id_producto, cantidad, precio_unitario, precio_unitario_bolivares) 
        VALUES (?, ?, ?, ?, ?)
    ");
    
    foreach ($data['productos'] as $producto) {
        $precio_unitario_bolivares = $producto['precio'] * $tasa_dolar;
        
        $stmt->execute([
            $id_pedido,
            $producto['id'],
            $producto['cantidad'],
            $producto['precio'],
            $precio_unitario_bolivares
        ]);
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Pedido creado exitosamente y enviado a cocina',
        'pedido_id' => $id_pedido
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'message' => 'Error al guardar el pedido: ' . $e->getMessage()
    ]);
}
?>