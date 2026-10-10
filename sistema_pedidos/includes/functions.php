<?php
// sistema_pedidos/includes/functions.php

function getTasaDolarActual($pdo) {
    $stmt = $pdo->prepare("SELECT valor_dolar FROM tasa_dolar WHERE activo = 1 ORDER BY fecha_actualizacion DESC LIMIT 1");
    $stmt->execute();
    $tasa = $stmt->fetch(PDO::FETCH_ASSOC);
    return $tasa ? $tasa['valor_dolar'] : 1; // Default 1 si no hay tasa
}

function getHistorialTasas($pdo, $limite = 10) {
    $stmt = $pdo->prepare("SELECT t.*, u.nombre_usuario 
                          FROM tasa_dolar t 
                          LEFT JOIN usuarios u ON t.usuario_actualizacion = u.idUsuario 
                          ORDER BY t.fecha_actualizacion DESC 
                          LIMIT ?");
    $stmt->bindValue(1, $limite, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function actualizarTasaDolar($pdo, $nueva_tasa, $usuario_id) {
    try {
        // Iniciar transacción
        $pdo->beginTransaction();
        
        // Desactivar todas las tasas anteriores
        $stmt = $pdo->prepare("UPDATE tasa_dolar SET activo = 0 WHERE activo = 1");
        $stmt->execute();
        
        // Insertar nueva tasa activa
        $stmt = $pdo->prepare("INSERT INTO tasa_dolar (valor_dolar, fecha_actualizacion, activo, usuario_actualizacion) VALUES (?, NOW(), 1, ?)");
        $stmt->execute([$nueva_tasa, $usuario_id]);
        
        // Actualizar en configuraciones
        $stmt = $pdo->prepare("UPDATE configuraciones SET valor = ?, fecha_actualizacion = NOW() WHERE clave = 'tasa_dolar'");
        $stmt->execute([$nueva_tasa]);
        
        // Confirmar transacción
        $pdo->commit();
        return true;
        
    } catch (Exception $e) {
        // Revertir en caso de error
        $pdo->rollBack();
        error_log("Error al actualizar tasa dólar: " . $e->getMessage());
        return false;
    }
}

function verificarSesion() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../auth/login.php");
        exit();
    }
}

function tienePermiso($rol_requerido) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $rol_requerido;
}

function registrarLog($pdo, $accion, $modulo, $detalles = null) {
    $stmt = $pdo->prepare("INSERT INTO logs (usuario_id, accion, modulo, detalles, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $_SESSION['user_id'] ?? null,
        $accion,
        $modulo,
        $detalles,
        $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
        $_SERVER['HTTP_USER_AGENT'] ?? 'Desconocido'
    ]);
}
?>	