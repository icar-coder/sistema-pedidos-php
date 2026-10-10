<?php
// sistema_pedidos/api/productos_admin_api.php

require_once '../includes/db_connection.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'data' => null];
$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';

try {
    switch ($action) {
        case 'read':
            $query = "SELECT p.*, c.nombre_categoria FROM productos p JOIN categorias c ON p.id_categoria = c.id_categoria ORDER BY c.nombre_categoria, p.nombre_producto";
            $stmt = $pdo->query($query);
            $response['success'] = true;
            $response['message'] = 'Productos listados correctamente.';
            $response['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
        
        case 'read_one':
            if (!isset($data['id'])) {
                throw new Exception("ID de producto no proporcionado.");
            }
            $stmt = $pdo->prepare("SELECT * FROM productos WHERE id_producto = ?");
            $stmt->execute([$data['id']]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($product) {
                $response['success'] = true;
                $response['message'] = 'Producto encontrado.';
                $response['data'] = $product;
            } else {
                $response['message'] = 'Producto no encontrado.';
            }
            break;

        case 'add':
            if (!isset($data['nombre_producto'], $data['descripcion'], $data['precio'], $data['id_categoria'])) {
                throw new Exception("Datos incompletos para añadir producto.");
            }
            $disponible = $data['disponible'] ?? 0;
            $stmt = $pdo->prepare("INSERT INTO productos (nombre_producto, descripcion, precio, id_categoria, disponible) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$data['nombre_producto'], $data['descripcion'], $data['precio'], $data['id_categoria'], $disponible]);
            $response['success'] = true;
            $response['message'] = 'Producto añadido correctamente.';
            break;

        case 'edit':
            if (!isset($data['id_producto'], $data['nombre_producto'], $data['descripcion'], $data['precio'], $data['id_categoria'])) {
                throw new Exception("Datos incompletos para editar producto.");
            }
            // --- CORRECCIÓN AQUÍ ---
            // Leemos directamente el valor de 'disponible' que envía el JS.
            $disponible = $data['disponible'] ?? 0;
            
            $stmt = $pdo->prepare("UPDATE productos SET nombre_producto = ?, descripcion = ?, precio = ?, id_categoria = ?, disponible = ? WHERE id_producto = ?");
            $stmt->execute([$data['nombre_producto'], $data['descripcion'], $data['precio'], $data['id_categoria'], $disponible, $data['id_producto']]);
            $response['success'] = true;
            $response['message'] = 'Producto actualizado correctamente.';
            break;

        case 'delete':
            if (!isset($data['id'])) {
                throw new Exception("ID de producto no proporcionado.");
            }
            $stmt = $pdo->prepare("DELETE FROM productos WHERE id_producto = ?");
            $stmt->execute([$data['id']]);
            $response['success'] = true;
            $response['message'] = 'Producto eliminado correctamente.';
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
?>