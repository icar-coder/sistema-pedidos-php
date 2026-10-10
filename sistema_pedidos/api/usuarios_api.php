<?php
// sistema_pedidos/api/usuarios_api.php

require_once '../includes/db_connection.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'data' => null];
$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';

try {
    switch ($action) {
        case 'read':
            $stmt = $pdo->query("SELECT idUsuario AS id_usuario, nombre_usuario, rol, pregunta_seguridad, respuesta_seguridad FROM usuarios ORDER BY nombre_usuario ASC");
            $response['success'] = true;
            $response['message'] = 'Usuarios listados correctamente.';
            $response['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'read_one':
            if (!isset($data['id_usuario'])) {
                throw new Exception("ID de usuario no proporcionado.");
            }
            $stmt = $pdo->prepare("SELECT idUsuario AS id_usuario, nombre_usuario, rol, pregunta_seguridad, respuesta_seguridad FROM usuarios WHERE idUsuario = ?");
            $stmt->execute([$data['id_usuario']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user) {
                $response['success'] = true;
                $response['message'] = 'Usuario encontrado.';
                $response['data'] = $user;
            } else {
                $response['message'] = 'Usuario no encontrado.';
            }
            break;

        case 'add':
            if (!isset($data['nombre_usuario'], $data['password'], $data['rol'])) {
                throw new Exception("Datos de usuario incompletos.");
            }
            
            $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
            
            // Verificar si el usuario ya existe
            $checkStmt = $pdo->prepare("SELECT idUsuario FROM usuarios WHERE nombre_usuario = ?");
            $checkStmt->execute([$data['nombre_usuario']]);
            if ($checkStmt->fetch()) {
                throw new Exception("El nombre de usuario ya existe.");
            }
            
            // Preparar consulta con preguntas de seguridad
            $sql = "INSERT INTO usuarios (nombre_usuario, contrasena, rol";
            $placeholders = "?, ?, ?";
            $params = [$data['nombre_usuario'], $hashed_password, $data['rol']];
            
            if (!empty($data['pregunta_seguridad']) && !empty($data['respuesta_seguridad'])) {
                $sql .= ", pregunta_seguridad, respuesta_seguridad";
                $placeholders .= ", ?, ?";
                $params[] = $data['pregunta_seguridad'];
                $params[] = $data['respuesta_seguridad'];
            }
            
            $sql .= ") VALUES (" . $placeholders . ")";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            $response['success'] = true;
            $response['message'] = 'Usuario añadido correctamente.';
            break;

        case 'edit':
            // CORREGIDO: usar id_usuario consistentemente
            if (!isset($data['id_usuario'], $data['nombre_usuario'], $data['rol'])) {
                throw new Exception("Datos incompletos para editar usuario.");
            }
            
            // Verificar que el usuario existe
            $checkStmt = $pdo->prepare("SELECT idUsuario FROM usuarios WHERE idUsuario = ?");
            $checkStmt->execute([$data['id_usuario']]);
            if (!$checkStmt->fetch()) {
                throw new Exception("Usuario no encontrado.");
            }
            
            $updates = "nombre_usuario = ?, rol = ?";
            $params = [$data['nombre_usuario'], $data['rol']];
            
            // Manejar contraseña si se proporciona
            if (!empty($data['password'])) {
                $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
                $updates .= ", contrasena = ?";
                $params[] = $hashed_password;
            }
            
            // Manejar preguntas de seguridad
            if (isset($data['pregunta_seguridad']) && isset($data['respuesta_seguridad'])) {
                $updates .= ", pregunta_seguridad = ?, respuesta_seguridad = ?";
                $params[] = $data['pregunta_seguridad'];
                $params[] = $data['respuesta_seguridad'];
            }
            
            $params[] = $data['id_usuario'];
            $stmt = $pdo->prepare("UPDATE usuarios SET $updates WHERE idUsuario = ?");
            $stmt->execute($params);
            
            $response['success'] = true;
            $response['message'] = 'Usuario actualizado correctamente.';
            break;

        case 'delete':
            if (!isset($data['id_usuario'])) {
                throw new Exception("ID de usuario no proporcionado.");
            }
            
            // Verificar que no es el último admin
            $checkStmt = $pdo->prepare("SELECT COUNT(*) as admin_count FROM usuarios WHERE rol = 'Admin'");
            $checkStmt->execute();
            $adminCount = $checkStmt->fetch(PDO::FETCH_ASSOC)['admin_count'];
            
            $userStmt = $pdo->prepare("SELECT rol FROM usuarios WHERE idUsuario = ?");
            $userStmt->execute([$data['id_usuario']]);
            $user = $userStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && $user['rol'] === 'Admin' && $adminCount <= 1) {
                throw new Exception("No se puede eliminar el único administrador del sistema.");
            }
            
            $stmt = $pdo->prepare("DELETE FROM usuarios WHERE idUsuario = ?");
            $stmt->execute([$data['id_usuario']]);
            
            $response['success'] = true;
            $response['message'] = 'Usuario eliminado correctamente.';
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