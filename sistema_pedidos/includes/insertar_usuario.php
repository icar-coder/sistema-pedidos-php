<?php

require_once 'db_connection.php'; // Asegúrate de que la ruta sea correcta

// Función para insertar un usuario de forma segura
function insertarUsuario($pdo, $nombre_usuario, $contrasena, $rol) {
    try {
        // Hash de la contraseña
        $hashed_password = password_hash($contrasena, PASSWORD_DEFAULT);

        // Sentencia SQL preparada para evitar inyecciones
        $sql = "INSERT INTO usuarios (nombre_usuario, contrasena, rol) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        
        // Ejecutar la sentencia con los valores
        $stmt->execute([$nombre_usuario, $hashed_password, $rol]);

        return true; // Éxito
    } catch (PDOException $e) {
        // Manejo de errores
        echo "Error: " . $e->getMessage();
        return false;
    }
}

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre_usuario = $_POST['nombre_usuario'] ?? '';
    $contrasena = $_POST['contrasena'] ?? '';
    $rol = $_POST['rol'] ?? '';

    // Validar que los campos no estén vacíos
    if (!empty($nombre_usuario) && !empty($contrasena) && !empty($rol)) {
        if (insertarUsuario($pdo, $nombre_usuario, $contrasena, $rol)) {
            echo "¡Usuario '$nombre_usuario' insertado con éxito con el rol de '$rol'!";
        } else {
            echo "Hubo un error al insertar el usuario.";
        }
    } else {
        echo "Por favor, complete todos los campos.";
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Insertar Nuevo Usuario</title>
</head>
<body>
    <h2>Formulario para Insertar Usuarios</h2>
    <form action="insertar_usuario.php" method="post">
        <label for="nombre_usuario">Nombre de Usuario:</label><br>
        <input type="text" id="nombre_usuario" name="nombre_usuario" required><br><br>

        <label for="contrasena">Contraseña:</label><br>
        <input type="password" id="contrasena" name="contrasena" required><br><br>

        <label for="rol">Rol:</label><br>
        <select id="rol" name="rol" required>
            <option value="Admin">Admin</option>
            <option value="Mecanico">Mecanico</option>
            <option value="Recepcionista">Recepcionista</option>
        </select><br><br>

        <button type="submit">Insertar Usuario</button>
    </form>
</body>
</html>