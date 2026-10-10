<?php
// sistema_pedidos/api/mesas_api.php

require_once '../includes/db_connection.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'data' => null];
$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';

try {
    switch ($action) {
        case 'get_mesas':
            $stmt = $pdo->prepare("SELECT * FROM mesas ORDER BY numero_mesa");
            $stmt->execute();
            $mesas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $response['success'] = true;
            $response['data'] = $mesas;
            break;

        case 'get_mesa':
            if (!isset($data['id_mesa'])) {
                throw new Exception("ID de mesa no proporcionado.");
            }
            
            $stmt = $pdo->prepare("SELECT * FROM mesas WHERE id_mesa = ?");
            $stmt->execute([$data['id_mesa']]);
            $mesa = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($mesa) {
                $response['success'] = true;
                $response['data'] = $mesa;
            } else {
                throw new Exception("Mesa no encontrada.");
            }
            break;

        case 'create_mesa':
            if (!isset($data['capacidad']) || !isset($data['ubicacion'])) {
                throw new Exception("Datos incompletos para crear la mesa.");
            }
            
            // Obtener el siguiente número de mesa automáticamente
            $stmt = $pdo->prepare("SELECT COALESCE(MAX(numero_mesa), 0) + 1 as siguiente_numero FROM mesas");
            $stmt->execute();
            $siguiente_numero = $stmt->fetch(PDO::FETCH_ASSOC)['siguiente_numero'];
            
            $stmt = $pdo->prepare("
                INSERT INTO mesas (numero_mesa, capacidad, ubicacion, estado) 
                VALUES (?, ?, ?, 'disponible')
            ");
            
            $stmt->execute([
                $siguiente_numero,
                $data['capacidad'],
                $data['ubicacion']
            ]);
            
            $id_mesa = $pdo->lastInsertId();
            
            // Obtener la mesa recién creada
            $stmt = $pdo->prepare("SELECT * FROM mesas WHERE id_mesa = ?");
            $stmt->execute([$id_mesa]);
            $mesa = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $response['success'] = true;
            $response['message'] = "Mesa #{$siguiente_numero} creada correctamente.";
            $response['data'] = $mesa;
            break;

        case 'update_mesa':
            if (!isset($data['id_mesa']) || !isset($data['capacidad']) || !isset($data['ubicacion'])) {
                throw new Exception("Datos incompletos para actualizar la mesa.");
            }
            
            $stmt = $pdo->prepare("
                UPDATE mesas 
                SET capacidad = ?, ubicacion = ? 
                WHERE id_mesa = ?
            ");
            
            $stmt->execute([
                $data['capacidad'],
                $data['ubicacion'],
                $data['id_mesa']
            ]);
            
            $response['success'] = true;
            $response['message'] = "Mesa actualizada correctamente.";
            break;

        case 'delete_mesa':
            if (!isset($data['id_mesa'])) {
                throw new Exception("ID de mesa no proporcionado.");
            }
            
            // Verificar si la mesa tiene pedidos activos
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as pedidos_activos 
                FROM pedidos 
                WHERE id_mesa = ? AND estado IN ('preparando', 'completado')
            ");
            $stmt->execute([$data['id_mesa']]);
            $pedidos_activos = $stmt->fetch(PDO::FETCH_ASSOC)['pedidos_activos'];
            
            if ($pedidos_activos > 0) {
                throw new Exception("No se puede eliminar la mesa porque tiene pedidos activos.");
            }
            
            $stmt = $pdo->prepare("DELETE FROM mesas WHERE id_mesa = ?");
            $stmt->execute([$data['id_mesa']]);
            
            $response['success'] = true;
            $response['message'] = "Mesa eliminada correctamente.";
            break;

        case 'update_estado':
            if (!isset($data['id_mesa']) || !isset($data['estado'])) {
                throw new Exception("Datos incompletos.");
            }
            
            $estados_permitidos = ['disponible', 'ocupada', 'reservada', 'mantenimiento'];
            if (!in_array($data['estado'], $estados_permitidos)) {
                throw new Exception("Estado no válido.");
            }
            
            $stmt = $pdo->prepare("UPDATE mesas SET estado = ? WHERE id_mesa = ?");
            $stmt->execute([$data['estado'], $data['id_mesa']]);
            
            $response['success'] = true;
            $response['message'] = 'Estado de mesa actualizado correctamente.';
            break;

        case 'get_mesa_disponible':
            $stmt = $pdo->prepare("SELECT * FROM mesas WHERE estado = 'disponible' ORDER BY numero_mesa LIMIT 1");
            $stmt->execute();
            $mesa = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $response['success'] = true;
            $response['data'] = $mesa;
            break;

        default:
            $response['message'] = 'Acción no válida.';
            break;
    }
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
}

echo json_encode($response);
exit;
?>