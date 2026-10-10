<?php
// Datos de conexión asignados por InfinityFree (MySQL Databases)
define('DB_HOST', 'sqlXXX.infinityfree.com'); // Reemplaza por tu MySQL Host Name exacto
define('DB_NAME', 'if0_43135532_sistema_pedidos'); // Tu nombre de base de datos
define('DB_USER', 'if0_43135532'); // Tu usuario MySQL
define('DB_PASS', 'tu_contraseña_de_infinityfree'); // La contraseña de tu cuenta

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
} catch (PDOException $e) {
    die("Error de conexión a la base de datos: " . $e->getMessage());
}
?>