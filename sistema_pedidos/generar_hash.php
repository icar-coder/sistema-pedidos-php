<?php
// generar_hash.php
$password = '123456'; // Cambia '123456' por la contraseña que quieras

$hashed_password = password_hash($password, PASSWORD_DEFAULT);

echo "La contraseña original es: " . $password . "<br>";
echo "El hash generado es: " . $hashed_password;
?>