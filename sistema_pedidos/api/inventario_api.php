<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../includes/db_connection.php';

// Manejar preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

$response = ['success' => false, 'message' => 'Acción no válida', 'data' => []];

try {
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    
    switch($action) {
        case 'estadisticas':
            $stmt = $pdo->query("
                SELECT 
                    COUNT(*) as total_ingredientes,
                    SUM(CASE WHEN stock_actual <= stock_minimo AND stock_actual > 0 THEN 1 ELSE 0 END) as stock_bajo,
                    SUM(CASE WHEN stock_actual <= 0 THEN 1 ELSE 0 END) as stock_agotado,
                    SUM(costo_dolares * stock_actual) as valor_inventario
                FROM ingredientes
            ");
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $response = [
                'success' => true,
                'data' => [
                    'total_ingredientes' => $stats['total_ingredientes'] ?? 0,
                    'stock_bajo' => $stats['stock_bajo'] ?? 0,
                    'stock_agotado' => $stats['stock_agotado'] ?? 0,
                    'valor_inventario' => $stats['valor_inventario'] ?? 0
                ]
            ];
            break;

        case 'ingredientes':
            $stmt = $pdo->query("
                SELECT *, 
                       (costo_dolares * stock_actual) as costo_bolivares
                FROM ingredientes 
                ORDER BY nombre_ingrediente
            ");
            $ingredientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $response = [
                'success' => true,
                'data' => $ingredientes
            ];
            break;

        case 'movimientos':
            $fecha_desde = $_GET['fecha_desde'] ?? '';
            $fecha_hasta = $_GET['fecha_hasta'] ?? '';
            
            $sql = "
                SELECT m.*, i.nombre_ingrediente, i.unidad_medida, u.nombre_usuario
                FROM movimientos_inventario m
                LEFT JOIN ingredientes i ON m.id_ingrediente = i.id_ingrediente
                LEFT JOIN usuarios u ON m.usuario_id = u.idUsuario
                WHERE 1=1
            ";
            
            $params = [];
            
            if ($fecha_desde) {
                $sql .= " AND DATE(m.fecha_movimiento) >= ?";
                $params[] = $fecha_desde;
            }
            
            if ($fecha_hasta) {
                $sql .= " AND DATE(m.fecha_movimiento) <= ?";
                $params[] = $fecha_hasta;
            }
            
            $sql .= " ORDER BY m.fecha_movimiento DESC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $movimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $response = [
                'success' => true,
                'data' => $movimientos
            ];
            break;

        case 'crear_ingrediente':
            $nombre = $_POST['nombre_ingrediente'] ?? '';
            $descripcion = $_POST['descripcion'] ?? '';
            $costo_dolares = $_POST['costo_dolares'] ?? 0;
            $stock_actual = $_POST['stock_actual'] ?? 0;
            $stock_minimo = $_POST['stock_minimo'] ?? 0;
            $unidad_medida = $_POST['unidad_medida'] ?? '';
            
            if (empty($nombre) || empty($unidad_medida)) {
                throw new Exception('Nombre y unidad de medida son requeridos');
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO ingredientes 
                (nombre_ingrediente, descripcion, costo_dolares, stock_actual, stock_minimo, unidad_medida)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $success = $stmt->execute([
                $nombre, $descripcion, $costo_dolares, $stock_actual, $stock_minimo, $unidad_medida
            ]);
            
            $response = [
                'success' => $success,
                'message' => $success ? 'Ingrediente creado exitosamente' : 'Error al crear ingrediente'
            ];
            break;

        case 'editar_ingrediente':
            // CORRECCIÓN: Cambiar 'id_ingrediente' por 'ingrediente_id'
            $id = $_POST['ingrediente_id'] ?? 0;
            $nombre = $_POST['nombre_ingrediente'] ?? '';
            $descripcion = $_POST['descripcion'] ?? '';
            $costo_dolares = $_POST['costo_dolares'] ?? 0;
            $stock_actual = $_POST['stock_actual'] ?? 0;
            $stock_minimo = $_POST['stock_minimo'] ?? 0;
            $unidad_medida = $_POST['unidad_medida'] ?? '';
            
            $stmt = $pdo->prepare("
                UPDATE ingredientes 
                SET nombre_ingrediente = ?, descripcion = ?, costo_dolares = ?, 
                    stock_actual = ?, stock_minimo = ?, unidad_medida = ?
                WHERE id_ingrediente = ?
            ");
            
            $success = $stmt->execute([
                $nombre, $descripcion, $costo_dolares, $stock_actual, $stock_minimo, $unidad_medida, $id
            ]);
            
            $response = [
                'success' => $success,
                'message' => $success ? 'Ingrediente actualizado exitosamente' : 'Error al actualizar ingrediente'
            ];
            break;

        case 'eliminar_ingrediente':
            // CORRECCIÓN: Cambiar 'id_ingrediente' por 'ingrediente_id'
            $id = $_POST['ingrediente_id'] ?? 0;
            
            $stmt = $pdo->prepare("DELETE FROM ingredientes WHERE id_ingrediente = ?");
            $success = $stmt->execute([$id]);
            
            $response = [
                'success' => $success,
                'message' => $success ? 'Ingrediente eliminado exitosamente' : 'Error al eliminar ingrediente'
            ];
            break;

        case 'registrar_movimiento':
            // CORRECCIÓN: Cambiar 'id_ingrediente' por 'ingrediente_id'
            $ingrediente_id = $_POST['ingrediente_id'] ?? 0;
            $tipo_movimiento = $_POST['tipo_movimiento'] ?? '';
            $cantidad = $_POST['cantidad'] ?? 0;
            $motivo = $_POST['motivo'] ?? '';
            $usuario_id = $_SESSION['user_id'] ?? 0;
            
            // Validaciones
            if (empty($ingrediente_id) || empty($tipo_movimiento) || empty($cantidad) || empty($motivo)) {
                throw new Exception('Todos los campos son requeridos');
            }
            
            // Registrar movimiento
            $stmt = $pdo->prepare("
                INSERT INTO movimientos_inventario 
                (id_ingrediente, tipo_movimiento, cantidad, motivo, usuario_id)
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $success = $stmt->execute([
                $ingrediente_id, $tipo_movimiento, $cantidad, $motivo, $usuario_id
            ]);
            
            // Actualizar stock del ingrediente
            if ($success) {
                $signo = $tipo_movimiento === 'entrada' ? '+' : '-';
                $stmt = $pdo->prepare("
                    UPDATE ingredientes 
                    SET stock_actual = stock_actual $signo ? 
                    WHERE id_ingrediente = ?
                ");
                $stmt->execute([$cantidad, $ingrediente_id]);
            }
            
            $response = [
                'success' => $success,
                'message' => $success ? 'Movimiento registrado exitosamente' : 'Error al registrar movimiento'
            ];
            break;

        default:
            $response = ['success' => false, 'message' => 'Acción no reconocida'];
            break;
    }
    
} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ];
}

// Asegurar que solo se envíe JSON
ob_clean();
echo json_encode($response);
exit();
?>