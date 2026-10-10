<?php
// sistema_pedidos/api/pedidos_admin_api.php

require_once '../includes/db_connection.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'data' => null];
$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';

try {
    switch ($action) {
        case 'read':
            $searchTerm = $data['search_term'] ?? '';
            $query = "
                SELECT 
                    p.id_pedido, 
                    p.fecha_pedido, 
                    p.tipo_servicio, 
                    p.metodo_pago,
                    p.total_pedido,
                    p.estado,
                    c.nombre_cliente,
                    c.apellido_cliente
                FROM pedidos p
                JOIN clientes c ON p.id_cliente = c.id_cliente
            ";
            
            $params = [];
            if (!empty($searchTerm)) {
                $query .= " WHERE p.id_pedido LIKE ? OR c.nombre_cliente LIKE ? OR c.apellido_cliente LIKE ?";
                $likeTerm = "%" . $searchTerm . "%";
                $params = [$likeTerm, $likeTerm, $likeTerm];
            }

            $query .= " ORDER BY p.fecha_pedido DESC";
            
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $pedidos_listos = [];
            foreach ($pedidos as $pedido) {
                $stmt_items = $pdo->prepare("
                    SELECT prod.nombre_producto, pi.cantidad
                    FROM pedidos_items pi
                    JOIN productos prod ON pi.id_producto = prod.id_producto
                    WHERE pi.id_pedido = ?
                ");
                $stmt_items->execute([$pedido['id_pedido']]);
                $productos = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

                $pedido['productos'] = $productos;
                $pedidos_listos[] = $pedido;
            }

            $response['success'] = true;
            $response['message'] = 'Pedidos obtenidos correctamente.';
            $response['data'] = $pedidos_listos;
            break;

        case 'delete':
            if (!isset($data['id_pedido'])) {
                throw new Exception("ID de pedido no proporcionado.");
            }
            
            $pdo->beginTransaction();
            $stmt_items = $pdo->prepare("DELETE FROM pedidos_items WHERE id_pedido = ?");
            $stmt_items->execute([$data['id_pedido']]);
            $stmt_pedido = $pdo->prepare("DELETE FROM pedidos WHERE id_pedido = ?");
            $stmt_pedido->execute([$data['id_pedido']]);
            $pdo->commit();
            
            $response['success'] = true;
            $response['message'] = 'Pedido eliminado correctamente.';
            break;

        default:
            $response['message'] = 'Acción no válida.';
            break;
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $response['message'] = 'Error: ' . $e->getMessage();
}

echo json_encode($response);
exit();
?>