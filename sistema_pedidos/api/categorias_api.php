<?php


require_once '../includes/db_connection.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'data' => null];
$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';

try {
    switch ($action) {
        case 'read':
            $stmt = $pdo->query("SELECT * FROM categorias ORDER BY nombre_categoria ASC");
            $response['success'] = true;
            $response['message'] = 'Categorías listadas correctamente.';
            $response['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
        
        case 'read_one':
            if (!isset($data['id'])) {
                throw new Exception("ID de categoría no proporcionado.");
            }
            $stmt = $pdo->prepare("SELECT * FROM categorias WHERE id_categoria = ?");
            $stmt->execute([$data['id']]);
            $category = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($category) {
                $response['success'] = true;
                $response['message'] = 'Categoría encontrada.';
                $response['data'] = $category;
            } else {
                $response['message'] = 'Categoría no encontrada.';
            }
            break;

        case 'add':
            if (!isset($data['nombre_categoria'])) {
                throw new Exception("Nombre de categoría no proporcionado.");
            }
            $stmt = $pdo->prepare("INSERT INTO categorias (nombre_categoria) VALUES (?)");
            $stmt->execute([$data['nombre_categoria']]);
            $response['success'] = true;
            $response['message'] = 'Categoría añadida correctamente.';
            break;

        case 'edit':
            if (!isset($data['id_categoria'], $data['nombre_categoria'])) {
                throw new Exception("Datos incompletos para editar categoría.");
            }
            $stmt = $pdo->prepare("UPDATE categorias SET nombre_categoria = ? WHERE id_categoria = ?");
            $stmt->execute([$data['nombre_categoria'], $data['id_categoria']]);
            $response['success'] = true;
            $response['message'] = 'Categoría actualizada correctamente.';
            break;

        case 'delete':
            if (!isset($data['id'])) {
                throw new Exception("ID de categoría no proporcionado.");
            }
            $stmt = $pdo->prepare("DELETE FROM categorias WHERE id_categoria = ?");
            $stmt->execute([$data['id']]);
            $response['success'] = true;
            $response['message'] = 'Categoría eliminada correctamente.';
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