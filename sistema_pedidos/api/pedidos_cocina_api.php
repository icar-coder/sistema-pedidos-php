<?php
// sistema_pedidos/api/pedidos_cocina_api.php

require_once '../includes/db_connection.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit();
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'JSON inválido']);
    exit();
}

$action = $data['action'] ?? '';

try {
    switch ($action) {
        case 'read':
            getPedidosCocina();
            break;
        case 'get_stats':
            getEstadisticas();
            break;
        case 'update_status':
            actualizarEstadoPedido($data);
            break;
        case 'report_problem':
            reportarProblema($data);
            break;
        case 'get_pedidos_cajero':
            getPedidosCajero();
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
    }
} catch (Exception $e) {
    error_log("Error en pedidos_cocina_api: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor: ' . $e->getMessage()]);
}

function getPedidosCocina() {
    global $pdo;
    
    try {
        // Obtener pedidos en estado 'pendiente' y 'preparando' para COCINA
        $sql = "
            SELECT 
                p.*,
                c.nombre_cliente,
                c.apellido_cliente,
                m.numero_mesa,
                CONCAT(c.nombre_cliente, ' ', c.apellido_cliente) as nombre_completo_cliente,
                TIMESTAMPDIFF(MINUTE, p.fecha_pedido, NOW()) as minutos_transcurridos
            FROM pedidos p 
            LEFT JOIN clientes c ON p.id_cliente = c.id_cliente 
            LEFT JOIN mesas m ON p.id_mesa = m.id_mesa 
            WHERE p.estado IN ('pendiente', 'preparando')
            ORDER BY 
                CASE 
                    WHEN p.estado = 'preparando' THEN 1
                    WHEN p.estado = 'pendiente' THEN 2
                    ELSE 3
                END,
                p.fecha_pedido ASC
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Obtener productos para cada pedido
        foreach ($pedidos as &$pedido) {
            $stmt = $pdo->prepare("
                SELECT pi.*, pr.nombre_producto 
                FROM pedidos_items pi 
                JOIN productos pr ON pi.id_producto = pr.id_producto 
                WHERE pi.id_pedido = ?
            ");
            $stmt->execute([$pedido['id_pedido']]);
            $pedido['productos'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Marcar como urgente si lleva más de 15 minutos
            $pedido['es_urgente'] = $pedido['minutos_transcurridos'] > 15;
        }
        
        echo json_encode([
            'success' => true,
            'data' => $pedidos
        ]);
        
    } catch (Exception $e) {
        error_log("Error obteniendo pedidos de cocina: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Error al obtener los pedidos: ' . $e->getMessage()
        ]);
    }
}

function getPedidosCajero() {
    global $pdo;
    
    try {
        // Obtener pedidos en estado 'completado' y 'preparando' para CAJERO - INCLUYENDO CAMPOS PAGO MÓVIL
        $sql = "
            SELECT 
                p.id_pedido,
                p.id_cliente,
                p.id_mesa,
                p.tipo_servicio,
                p.metodo_pago,
                p.referencia_pago_movil,
                p.banco_pago_movil,
                p.total_pedido,
                p.total_bolivares,
                p.tasa_dolar_aplicada,
                p.estado,
                p.fecha_pedido,
                c.nombre_cliente,
                c.apellido_cliente,
                m.numero_mesa,
                CONCAT(c.nombre_cliente, ' ', c.apellido_cliente) as nombre_completo_cliente,
                TIMESTAMPDIFF(MINUTE, p.fecha_pedido, NOW()) as minutos_transcurridos
            FROM pedidos p 
            LEFT JOIN clientes c ON p.id_cliente = c.id_cliente 
            LEFT JOIN mesas m ON p.id_mesa = m.id_mesa 
            WHERE p.estado IN ('completado', 'preparando')
            ORDER BY 
                CASE 
                    WHEN p.estado = 'completado' THEN 1
                    WHEN p.estado = 'preparando' THEN 2
                    ELSE 3
                END,
                p.fecha_pedido DESC
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Obtener productos para cada pedido
        foreach ($pedidos as &$pedido) {
            $stmt = $pdo->prepare("
                SELECT 
                    pi.id_producto,
                    pi.cantidad,
                    pi.precio_unitario,
                    pi.precio_unitario_bolivares,
                    pr.nombre_producto,
                    pr.descripcion
                FROM pedidos_items pi 
                JOIN productos pr ON pi.id_producto = pr.id_producto 
                WHERE pi.id_pedido = ?
            ");
            $stmt->execute([$pedido['id_pedido']]);
            $pedido['productos'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Marcar como urgente si lleva más de 15 minutos (solo para preparando)
            $pedido['es_urgente'] = $pedido['minutos_transcurridos'] > 15 && $pedido['estado'] === 'preparando';
        }
        
        echo json_encode([
            'success' => true,
            'data' => $pedidos
        ]);
        
    } catch (Exception $e) {
        error_log("Error obteniendo pedidos para cajero: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Error al obtener los pedidos: ' . $e->getMessage()
        ]);
    }
}

function getEstadisticas() {
    global $pdo;
    
    try {
        // Estadísticas para COCINA (solo pendientes y preparando)
        $sql = "
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
                SUM(CASE WHEN estado = 'preparando' THEN 1 ELSE 0 END) as preparando
            FROM pedidos 
            WHERE estado IN ('pendiente', 'preparando')
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => [
                'total' => (int)$stats['total'],
                'pendientes' => (int)$stats['pendientes'],
                'preparando' => (int)$stats['preparando']
            ]
        ]);
        
    } catch (Exception $e) {
        error_log("Error obteniendo estadísticas: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Error al obtener estadísticas'
        ]);
    }
}

function actualizarEstadoPedido($data) {
    global $pdo;
    
    if (!isset($data['id_pedido']) || !isset($data['nuevo_estado'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
        return;
    }
    
    $id_pedido = $data['id_pedido'];
    $nuevo_estado = $data['nuevo_estado'];
    
    // Validar estados permitidos para COCINA
    $estados_permitidos = ['preparando', 'completado'];
    if (!in_array($nuevo_estado, $estados_permitidos)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Estado no válido: ' . $nuevo_estado]);
        return;
    }
    
    try {
        $pdo->beginTransaction();
        
        // Verificar que el pedido existe y está en un estado válido para la transición
        $stmt = $pdo->prepare("SELECT estado, id_mesa FROM pedidos WHERE id_pedido = ?");
        $stmt->execute([$id_pedido]);
        $pedido_actual = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$pedido_actual) {
            throw new Exception("Pedido no encontrado con ID: " . $id_pedido);
        }
        
        $estado_actual = $pedido_actual['estado'];
        $id_mesa = $pedido_actual['id_mesa'];
        
        // Validar transiciones de estado
        if ($nuevo_estado === 'preparando' && $estado_actual !== 'pendiente') {
            throw new Exception("Solo se pueden comenzar a preparar pedidos pendientes. Estado actual: " . $estado_actual);
        }
        
        if ($nuevo_estado === 'completado' && $estado_actual !== 'preparando') {
            throw new Exception("Solo se pueden completar pedidos en preparación. Estado actual: " . $estado_actual);
        }
        
        // Actualizar estado del pedido
        $stmt = $pdo->prepare("UPDATE pedidos SET estado = ? WHERE id_pedido = ?");
        $result = $stmt->execute([$nuevo_estado, $id_pedido]);
        
        if (!$result) {
            throw new Exception("Error al actualizar el estado del pedido en la base de datos");
        }
        
        // Si el pedido se completa y tiene mesa, mantener la mesa ocupada hasta que se cobre
        // (La mesa se libera cuando el cajero marca como pagado)
        
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
        error_log("Error actualizando estado del pedido #$id_pedido: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Error al actualizar el estado: ' . $e->getMessage()
        ]);
    }
}

function reportarProblema($data) {
    global $pdo;
    
    if (!isset($data['id_pedido']) || !isset($data['problema'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
        return;
    }
    
    $id_pedido = $data['id_pedido'];
    $problema = trim($data['problema']);
    
    if (empty($problema)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'El problema no puede estar vacío']);
        return;
    }
    
    try {
        // Verificar que el pedido existe
        $stmt = $pdo->prepare("SELECT id_pedido FROM pedidos WHERE id_pedido = ?");
        $stmt->execute([$id_pedido]);
        $pedido = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$pedido) {
            throw new Exception("Pedido no encontrado con ID: " . $id_pedido);
        }
        
        // Guardar en campo de observaciones
        $stmt = $pdo->prepare("UPDATE pedidos SET observaciones = CONCAT(COALESCE(observaciones, ''), ?) WHERE id_pedido = ?");
        $observacion = "\n[PROBLEMA] " . date('Y-m-d H:i:s') . ": " . $problema;
        $stmt->execute([$observacion, $id_pedido]);
        
        // Log del problema
        error_log("Problema reportado para pedido #$id_pedido: $problema");
        
        echo json_encode([
            'success' => true,
            'message' => 'Problema reportado correctamente para el pedido #' . $id_pedido,
            'data' => [
                'id_pedido' => $id_pedido,
                'problema' => $problema,
                'fecha_reporte' => date('Y-m-d H:i:s')
            ]
        ]);
        
    } catch (Exception $e) {
        error_log("Error reportando problema para pedido #$id_pedido: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Error al reportar el problema: ' . $e->getMessage()
        ]);
    }
}
?>