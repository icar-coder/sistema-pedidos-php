<?php
// sistema_pedidos/api/configuraciones_api.php

require_once '../includes/db_connection.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$data = $_POST; // Usar $_POST para FormData

$action = $data['action'] ?? '';

try {
    switch ($action) {
        case 'obtener_tasa_dolar':
            // Obtener la tasa activa más reciente
            $stmt = $pdo->prepare("
                SELECT valor_dolar 
                FROM tasa_dolar 
                WHERE activo = 1 
                ORDER BY fecha_actualizacion DESC 
                LIMIT 1
            ");
            $stmt->execute();
            $tasa = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($tasa) {
                echo json_encode([
                    'success' => true,
                    'tasa_dolar' => floatval($tasa['valor_dolar'])
                ]);
            } else {
                // Si no hay tasa activa, obtener de configuraciones
                $stmt = $pdo->prepare("SELECT valor FROM configuraciones WHERE clave = 'tasa_dolar'");
                $stmt->execute();
                $config_tasa = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $tasa_default = $config_tasa ? floatval($config_tasa['valor']) : 36.00;
                
                echo json_encode([
                    'success' => true,
                    'tasa_dolar' => $tasa_default
                ]);
            }
            break;

        case 'actualizar_tasa_dolar':
            if (!isset($data['tasa_dolar'])) {
                throw new Exception("Tasa de cambio no proporcionada.");
            }
            
            $nueva_tasa = floatval($data['tasa_dolar']);
            $usuario_id = $data['usuario_id'] ?? 1; // Default al usuario admin
            
            if ($nueva_tasa <= 0) {
                throw new Exception("La tasa de cambio debe ser mayor a cero.");
            }
            
            $pdo->beginTransaction();
            
            try {
                // Desactivar todas las tasas anteriores
                $stmt = $pdo->prepare("UPDATE tasa_dolar SET activo = 0 WHERE activo = 1");
                $stmt->execute();
                
                // Insertar nueva tasa
                $stmt = $pdo->prepare("
                    INSERT INTO tasa_dolar (valor_dolar, usuario_actualizacion) 
                    VALUES (?, ?)
                ");
                $stmt->execute([$nueva_tasa, $usuario_id]);
                
                // Actualizar también en configuraciones
                $stmt = $pdo->prepare("
                    UPDATE configuraciones 
                    SET valor = ?, fecha_actualizacion = NOW() 
                    WHERE clave = 'tasa_dolar'
                ");
                $stmt->execute([$nueva_tasa]);
                
                $pdo->commit();
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Tasa de cambio actualizada exitosamente',
                    'nueva_tasa' => $nueva_tasa
                ]);
                
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
            break;
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>