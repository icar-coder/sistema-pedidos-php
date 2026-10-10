

<?php

// Definir las constantes de conexión a la base de datos
// Por favor, cambia estos valores por los de tu configuración.
define('DB_HOST', 'localhost'); // Generalmente 'localhost' para desarrollo
define('DB_NAME', 'sistema_pedidos'); // El nombre de la base de datos que creamos
define('DB_USER', 'root'); // Tu nombre de usuario de MySQL (por defecto 'root' en XAMPP/WAMP)
define('DB_PASS', ''); // Tu contraseña de MySQL (por defecto vacía en XAMPP/WAMP)

// Intentar establecer la conexión usando PDO
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS);

    // Configurar PDO para que lance excepciones en caso de errores
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Configurar el modo de obtención de resultados por defecto a objetos (opcional, pero útil)
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);

    //echo "Conexión a la base de datos exitosa."; // Puedes descomentar esto para probar la conexión
} catch (PDOException $e) {
    // Si la conexión falla, mostrar un mensaje de error y terminar el script
    die("Error de conexión a la base de datos: " . $e->getMessage());
}

?>