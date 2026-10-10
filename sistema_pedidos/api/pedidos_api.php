<?php
// sistema_pedidos/api/pedidos_api.php

require_once '../includes/db_connection.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Manejar método POST para crear pedidos
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    crearPedido();
    exit();
}

// Manejar método PUT para actualizar pedidos
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    actualizarPedido();
    exit();
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Método no permitido']);

function crearPedido() {
    global $pdo;
    
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'JSON inválido']);
        return;
    }

    // Validar datos requeridos
    if (!isset($data['id_cliente']) || !isset($data['tipo_servicio']) || !isset($data['metodo_pago']) || !isset($data['productos'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
        return;
    }

    try {
        $pdo->beginTransaction();

        // Calcular totales
        $subtotal = 0;
        foreach ($data['productos'] as $producto) {
            // Obtener el precio actual del producto desde la base de datos
            $stmt = $pdo->prepare("SELECT precio FROM productos WHERE id_producto = ?");
            $stmt->execute([$producto['id_producto']]);
            $precio_producto = $stmt->fetchColumn();
            
            if (!$precio_producto) {
                throw new Exception("Producto no encontrado con ID: " . $producto['id_producto']);
            }
            
            $subtotal += $precio_producto * $producto['cantidad'];
        }
        
        $iva_porcentaje = 12;
        $iva = $subtotal * ($iva_porcentaje / 100);
        $total = $subtotal + $iva;
        
        // Obtener tasa de cambio actual
        $stmt = $pdo->prepare("SELECT valor_dolar FROM tasa_dolar WHERE activo = 1 ORDER BY fecha_actualizacion DESC LIMIT 1");
        $stmt->execute();
        $tasa_dolar = $stmt->fetchColumn();
        
        if (!$tasa_dolar) {
            // Usar tasa por defecto si no hay tasa configurada
            $tasa_dolar = 36.00;
        }
        
        $total_bolivares = $total * $tasa_dolar;

        // Insertar pedido con estado 'pendiente' - INCLUYENDO CAMPOS DE PAGO MÓVIL
        $stmt = $pdo->prepare("
            INSERT INTO pedidos 
            (id_cliente, id_mesa, tipo_servicio, metodo_pago, referencia_pago_movil, banco_pago_movil, total_pedido, total_bolivares, tasa_dolar_aplicada, estado) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pendiente')
        ");
        
        $stmt->execute([
            $data['id_cliente'],
            $data['id_mesa'] ?? null,
            $data['tipo_servicio'],
            $data['metodo_pago'],
            $data['referencia_pago_movil'] ?? null,
            $data['banco_pago_movil'] ?? null,
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
            // Obtener el precio actual del producto
            $stmt_precio = $pdo->prepare("SELECT precio FROM productos WHERE id_producto = ?");
            $stmt_precio->execute([$producto['id_producto']]);
            $precio_producto = $stmt_precio->fetchColumn();
            
            $precio_unitario_bolivares = $precio_producto * $tasa_dolar;
            
            $stmt->execute([
                $id_pedido,
                $producto['id_producto'],
                $producto['cantidad'],
                $precio_producto,
                $precio_unitario_bolivares
            ]);
        }

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Pedido creado exitosamente y enviado a cocina',
            'pedido_id' => $id_pedido,
            'total' => $total,
            'total_bolivares' => $total_bolivares,
            'tasa_dolar' => $tasa_dolar
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error en crearPedido: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error al guardar el pedido: ' . $e->getMessage()
        ]);
    }
}

function actualizarPedido() {
    global $pdo;
    
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'JSON inválido']);
        return;
    }
    
    if (!isset($data['id_pedido']) || !isset($data['estado'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
        return;
    }
    
    try {
        $pdo->beginTransaction();
        
        $id_pedido = $data['id_pedido'];
        $nuevo_estado = $data['estado'];
        $motivo = $data['motivo'] ?? null;
        
        // Validar estados permitidos
        $estados_permitidos = ['preparando', 'completado', 'pagado', 'cancelado'];
        if (!in_array($nuevo_estado, $estados_permitidos)) {
            throw new Exception("Estado no válido: " . $nuevo_estado);
        }
        
        // Verificar que el pedido existe
        $stmt = $pdo->prepare("SELECT estado, id_mesa FROM pedidos WHERE id_pedido = ?");
        $stmt->execute([$id_pedido]);
        $pedido_actual = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$pedido_actual) {
            throw new Exception("Pedido no encontrado con ID: " . $id_pedido);
        }
        
        $estado_actual = $pedido_actual['estado'];
        $id_mesa = $pedido_actual['id_mesa'];
        
        // Actualizar estado del pedido
        $sql = "UPDATE pedidos SET estado = ?";
        $params = [$nuevo_estado];
        
        // Si hay motivo (para cancelaciones), agregarlo
        if ($motivo) {
            $sql .= ", observaciones = CONCAT(COALESCE(observaciones, ''), ?)";
            $observacion = "\n[" . strtoupper($nuevo_estado) . "] " . date('Y-m-d H:i:s') . ": " . $motivo;
            $params[] = $observacion;
        }
        
        $sql .= " WHERE id_pedido = ?";
        $params[] = $id_pedido;
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute($params);
        
        if (!$result) {
            throw new Exception("Error al actualizar el estado del pedido en la base de datos");
        }
        
        // Si el pedido se marca como pagado y tiene mesa, liberar la mesa
        if ($nuevo_estado === 'pagado' && $id_mesa) {
            $stmt = $pdo->prepare("UPDATE mesas SET estado = 'disponible' WHERE id_mesa = ?");
            $stmt->execute([$id_mesa]);
        }
        
        // Si el pedido se cancela y tiene mesa, liberar la mesa
        if ($nuevo_estado === 'cancelado' && $id_mesa) {
            $stmt = $pdo->prepare("UPDATE mesas SET estado = 'disponible' WHERE id_mesa = ?");
            $stmt->execute([$id_mesa]);
        }
        
        $pdo->commit();
        
        // Log de la acción
        error_log("Pedido #$id_pedido actualizado de '$estado_actual' a '$nuevo_estado'");
        
        echo json_encode([
            'success' => true,
            'message' => "Pedido #$id_pedido actualizado a: " . ucfirst($nuevo_estado),
            'data' => [
                'id_pedido' => $id_pedido,
                'estado_anterior' => $estado_actual,
                'nuevo_estado' => $nuevo_estado
            ]
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error actualizando pedido #$id_pedido: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error al actualizar el pedido: ' . $e->getMessage()
        ]);
    }
}
?>