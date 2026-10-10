<?php
// sistema_pedidos/api/productos.php

require_once '../includes/db_connection.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'data' => null];

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? '';

    if ($action === 'read') {
        $stmt = $pdo->query("SELECT p.id_producto, p.nombre_producto, p.descripcion, p.precio, p.id_categoria, c.nombre_categoria 
                             FROM productos p 
                             JOIN categorias c ON p.id_categoria = c.id_categoria 
                             WHERE p.disponible = 1 
                             ORDER BY c.nombre_categoria, p.nombre_producto");
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $response['success'] = true;
        $response['message'] = 'Productos obtenidos correctamente.';
        $response['data'] = $products;
    } else {
        $response['message'] = 'Acción no válida.';
    }
} catch (PDOException $e) {
    $response['message'] = 'Error en la base de datos: ' . $e->getMessage();
}

echo json_encode($response);
?>