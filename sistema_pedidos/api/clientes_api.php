<?php
// clientes_api.php
require_once '../includes/db_connection.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Permitir métodos POST y GET
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$response = ['success' => false, 'message' => '', 'data' => null];

try {
    // Obtener datos de diferentes formas (POST, GET, JSON)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        if (strpos($contentType, 'application/json') !== false) {
            $input = json_decode(file_get_contents('php://input'), true);
        } else {
            $input = $_POST;
        }
    } else {
        $input = $_GET;
    }

    $action = $input['action'] ?? $_GET['action'] ?? '';

    // Log para debugging
    error_log("Clientes API - Action: " . $action);

    switch ($action) {
        case 'list':
            // Listar clientes con paginación y búsqueda
            $search = $input['search'] ?? '';
            $limit = isset($input['limit']) ? (int)$input['limit'] : 20;
            $offset = isset($input['offset']) ? (int)$input['offset'] : 0;
            
            // Asegurar que limit y offset sean números válidos
            $limit = max(1, min(100, $limit)); // Entre 1 y 100
            $offset = max(0, $offset);
            
            $where = '';
            $params = [];
            
            if (!empty($search)) {
                $where = "WHERE cedula_cliente LIKE ? OR nombre_cliente LIKE ? OR apellido_cliente LIKE ?";
                $searchTerm = "%$search%";
                $params = [$searchTerm, $searchTerm, $searchTerm];
            }
            
            // Obtener total de registros
            $countSql = "SELECT COUNT(*) as total FROM clientes $where";
            $stmt = $pdo->prepare($countSql);
            $stmt->execute($params);
            $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Obtener datos paginados - CORREGIDO: usar parámetros numéricos correctamente
            $sql = "SELECT id_cliente, cedula_cliente, nombre_cliente, apellido_cliente 
                    FROM clientes $where 
                    ORDER BY id_cliente DESC 
                    LIMIT :limit OFFSET :offset";
            
            $stmt = $pdo->prepare($sql);
            
            // Vincular parámetros numéricos
            if (!empty($params)) {
                // Si hay parámetros de búsqueda, vincularlos primero
                foreach ($params as $key => $value) {
                    $stmt->bindValue(($key + 1), $value, PDO::PARAM_STR);
                }
                $stmt->bindValue(count($params) + 1, $limit, PDO::PARAM_INT);
                $stmt->bindValue(count($params) + 2, $offset, PDO::PARAM_INT);
            } else {
                // Sin parámetros de búsqueda
                $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
                $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            }
            
            $stmt->execute();
            $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $response['success'] = true;
            $response['data'] = $clientes;
            $response['total'] = (int)$total;
            $response['current_page'] = $offset / $limit + 1;
            $response['total_pages'] = ceil($total / $limit);
            $response['limit'] = $limit;
            $response['offset'] = $offset;
            break;

        case 'read_one':
            if (!isset($input['id']) && !isset($input['cedula'])) {
                throw new Exception("ID o cédula de cliente no proporcionada.");
            }
            
            if (isset($input['id'])) {
                $stmt = $pdo->prepare("SELECT id_cliente, cedula_cliente, nombre_cliente, apellido_cliente FROM clientes WHERE id_cliente = ?");
                $stmt->execute([$input['id']]);
            } else {
                $stmt = $pdo->prepare("SELECT id_cliente, cedula_cliente, nombre_cliente, apellido_cliente FROM clientes WHERE cedula_cliente = ?");
                $stmt->execute([$input['cedula']]);
            }
            
            $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($cliente) {
                $response['success'] = true;
                $response['message'] = 'Cliente encontrado.';
                $response['data'] = $cliente;
            } else {
                $response['message'] = 'Cliente no encontrado.';
            }
            break;

        case 'add':
        case 'create':
            if (!isset($input['cedula_cliente']) || !isset($input['nombre_cliente']) || !isset($input['apellido_cliente'])) {
                // Intentar con nombres alternativos para compatibilidad
                $cedula = $input['cedula_cliente'] ?? $input['cedula'] ?? '';
                $nombre = $input['nombre_cliente'] ?? $input['nombre'] ?? '';
                $apellido = $input['apellido_cliente'] ?? $input['apellido'] ?? '';
                
                if (empty($cedula) || empty($nombre) || empty($apellido)) {
                    throw new Exception("Datos incompletos para añadir cliente. Se requieren: cédula, nombre y apellido.");
                }
                
                $input['cedula_cliente'] = $cedula;
                $input['nombre_cliente'] = $nombre;
                $input['apellido_cliente'] = $apellido;
            }
            
            // Validar datos
            if (empty($input['cedula_cliente']) || empty($input['nombre_cliente']) || empty($input['apellido_cliente'])) {
                throw new Exception("Todos los campos son obligatorios: cédula, nombre y apellido.");
            }
            
            // Verificar si el cliente ya existe
            $stmt = $pdo->prepare("SELECT id_cliente FROM clientes WHERE cedula_cliente = ?");
            $stmt->execute([$input['cedula_cliente']]);
            $clienteExistente = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($clienteExistente) {
                $response['message'] = 'Ya existe un cliente con esta cédula.';
                $response['data'] = $clienteExistente;
                break;
            }
            
            $stmt = $pdo->prepare("INSERT INTO clientes (cedula_cliente, nombre_cliente, apellido_cliente) VALUES (?, ?, ?)");
            $stmt->execute([
                $input['cedula_cliente'], 
                $input['nombre_cliente'], 
                $input['apellido_cliente']
            ]);
            
            $id = $pdo->lastInsertId();
            $stmt = $pdo->prepare("SELECT id_cliente, cedula_cliente, nombre_cliente, apellido_cliente FROM clientes WHERE id_cliente = ?");
            $stmt->execute([$id]);
            $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

            $response['success'] = true;
            $response['message'] = 'Cliente añadido correctamente.';
            $response['data'] = $cliente;
            break;

        case 'edit':
        case 'update':
            if (!isset($input['id_cliente']) || !isset($input['cedula_cliente']) || !isset($input['nombre_cliente']) || !isset($input['apellido_cliente'])) {
                throw new Exception("Datos incompletos para editar cliente. Se requieren: id_cliente, cédula, nombre y apellido.");
            }
            
            // Verificar si la nueva cédula ya existe en otro cliente
            $stmt = $pdo->prepare("SELECT id_cliente FROM clientes WHERE cedula_cliente = ? AND id_cliente != ?");
            $stmt->execute([$input['cedula_cliente'], $input['id_cliente']]);
            $clienteConMismaCedula = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($clienteConMismaCedula) {
                $response['message'] = 'Ya existe otro cliente con esta cédula.';
                break;
            }
            
            $stmt = $pdo->prepare("UPDATE clientes SET cedula_cliente = ?, nombre_cliente = ?, apellido_cliente = ? WHERE id_cliente = ?");
            $stmt->execute([
                $input['cedula_cliente'], 
                $input['nombre_cliente'], 
                $input['apellido_cliente'], 
                $input['id_cliente']
            ]);
            
            if ($stmt->rowCount() > 0) {
                $stmt = $pdo->prepare("SELECT id_cliente, cedula_cliente, nombre_cliente, apellido_cliente FROM clientes WHERE id_cliente = ?");
                $stmt->execute([$input['id_cliente']]);
                $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

                $response['success'] = true;
                $response['message'] = 'Cliente actualizado correctamente.';
                $response['data'] = $cliente;
            } else {
                $response['message'] = 'No se realizaron cambios o el cliente no existe.';
            }
            break;

        case 'delete':
            if (!isset($input['id'])) {
                throw new Exception("ID de cliente no proporcionado.");
            }
            
            // Verificar si el cliente existe
            $stmt = $pdo->prepare("SELECT id_cliente FROM clientes WHERE id_cliente = ?");
            $stmt->execute([$input['id']]);
            $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$cliente) {
                $response['message'] = 'El cliente no existe.';
                break;
            }
            
            // Verificar si el cliente tiene pedidos asociados
            $stmt = $pdo->prepare("SELECT COUNT(*) as total_pedidos FROM pedidos WHERE id_cliente = ?");
            $stmt->execute([$input['id']]);
            $totalPedidos = $stmt->fetch(PDO::FETCH_ASSOC)['total_pedidos'];
            
            if ($totalPedidos > 0) {
                $response['message'] = 'No se puede eliminar el cliente porque tiene pedidos asociados.';
                break;
            }
            
            $stmt = $pdo->prepare("DELETE FROM clientes WHERE id_cliente = ?");
            $stmt->execute([$input['id']]);
            
            if ($stmt->rowCount() > 0) {
                $response['success'] = true;
                $response['message'] = 'Cliente eliminado correctamente.';
            } else {
                $response['message'] = 'Error al eliminar el cliente.';
            }
            break;

        case 'buscar':
            // Buscar cliente por cédula (compatible con frontend existente)
            if (!isset($input['cedula'])) {
                throw new Exception("Cédula de cliente no proporcionada.");
            }
            
            $cedula = $input['cedula'];
            $stmt = $pdo->prepare("SELECT id_cliente, cedula_cliente, nombre_cliente, apellido_cliente FROM clientes WHERE cedula_cliente = ?");
            $stmt->execute([$cedula]);
            $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($cliente) {
                $response['success'] = true;
                $response['message'] = 'Cliente encontrado.';
                $response['cliente'] = $cliente;
                $response['data'] = $cliente;
            } else {
                $response['message'] = 'Cliente no encontrado.';
            }
            break;

        case 'crear':
            // Crear cliente (compatible con frontend existente)
            if (!isset($input['cedula']) || !isset($input['nombre']) || !isset($input['apellido'])) {
                throw new Exception("Datos incompletos para crear cliente.");
            }
            
            // Verificar si el cliente ya existe
            $stmt = $pdo->prepare("SELECT id_cliente FROM clientes WHERE cedula_cliente = ?");
            $stmt->execute([$input['cedula']]);
            $clienteExistente = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($clienteExistente) {
                $response['message'] = 'Ya existe un cliente con esta cédula.';
                $response['cliente'] = $clienteExistente;
                break;
            }
            
            $stmt = $pdo->prepare("INSERT INTO clientes (cedula_cliente, nombre_cliente, apellido_cliente) VALUES (?, ?, ?)");
            $stmt->execute([
                $input['cedula'], 
                $input['nombre'], 
                $input['apellido']
            ]);
            
            $id = $pdo->lastInsertId();
            $stmt = $pdo->prepare("SELECT id_cliente, cedula_cliente, nombre_cliente, apellido_cliente FROM clientes WHERE id_cliente = ?");
            $stmt->execute([$id]);
            $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

            $response['success'] = true;
            $response['message'] = 'Cliente creado correctamente.';
            $response['cliente'] = $cliente;
            $response['data'] = $cliente;
            break;

        case 'count':
            // Contar total de clientes
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM clientes");
            $stmt->execute();
            $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            $response['success'] = true;
            $response['total'] = (int)$total;
            break;

        case 'search_advanced':
            // Búsqueda avanzada con múltiples criterios
            $cedula = $input['cedula'] ?? '';
            $nombre = $input['nombre'] ?? '';
            $apellido = $input['apellido'] ?? '';
            $limit = isset($input['limit']) ? (int)$input['limit'] : 20;
            $offset = isset($input['offset']) ? (int)$input['offset'] : 0;
            
            // Asegurar que limit y offset sean números válidos
            $limit = max(1, min(100, $limit));
            $offset = max(0, $offset);
            
            $where = [];
            $params = [];
            
            if (!empty($cedula)) {
                $where[] = "cedula_cliente LIKE ?";
                $params[] = "%$cedula%";
            }
            
            if (!empty($nombre)) {
                $where[] = "nombre_cliente LIKE ?";
                $params[] = "%$nombre%";
            }
            
            if (!empty($apellido)) {
                $where[] = "apellido_cliente LIKE ?";
                $params[] = "%$apellido%";
            }
            
            $whereClause = '';
            if (!empty($where)) {
                $whereClause = "WHERE " . implode(" AND ", $where);
            }
            
            // Obtener total
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM clientes $whereClause");
            $stmt->execute($params);
            $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Obtener datos paginados - CORREGIDO
            $sql = "SELECT id_cliente, cedula_cliente, nombre_cliente, apellido_cliente 
                    FROM clientes $whereClause 
                    ORDER BY id_cliente DESC 
                    LIMIT :limit OFFSET :offset";
            
            $stmt = $pdo->prepare($sql);
            
            // Vincular parámetros
            if (!empty($params)) {
                foreach ($params as $key => $value) {
                    $stmt->bindValue(($key + 1), $value, PDO::PARAM_STR);
                }
                $stmt->bindValue(count($params) + 1, $limit, PDO::PARAM_INT);
                $stmt->bindValue(count($params) + 2, $offset, PDO::PARAM_INT);
            } else {
                $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
                $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            }
            
            $stmt->execute();
            $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $response['success'] = true;
            $response['data'] = $clientes;
            $response['total'] = (int)$total;
            break;

        default:
            $response['message'] = 'Acción no válida. Acciones disponibles: list, read_one, add, edit, delete, buscar, crear, count, search_advanced';
            break;
    }
} catch (PDOException $e) {
    $response['message'] = 'Error de base de datos: ' . $e->getMessage();
    error_log("Clientes API PDO Error: " . $e->getMessage());
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
    error_log("Clientes API Error: " . $e->getMessage());
}

echo json_encode($response);
exit();
?>