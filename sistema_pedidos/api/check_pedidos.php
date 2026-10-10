<?php


require_once '../includes/db_connection.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'data' => null];
$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';

try {
    if ($action === 'check_new') {
        $last_id = $data['last_id'] ?? 0;
        
        $query = "SELECT id_pedido, fecha_pedido, tipo_servicio 
                  FROM pedidos 
                  WHERE id_pedido > ? 
                  ORDER BY id_pedido DESC 
                  LIMIT 10";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$last_id]);
        $nuevos_pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $response['success'] = true;
        $response['message'] = 'Pedidos verificados correctamente.';
        $response['data'] = $nuevos_pedidos;
    } else {
        $response['message'] = 'Acción no válida.';
    }
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
}

echo json_encode($response);
exit();
?>