<?php
// Incluir el archivo de conexión a la base de datos
require_once '../includes/db_connection.php';

echo "<h1>Probando Conexión y Datos</h1>";

// --- PRUEBA 1: Estado de la Conexión ---
if (isset($pdo) && $pdo) {
    echo "<p>✅ Conexión a la base de datos establecida correctamente.</p>";
} else {
    echo "<p style='color: red;'>❌ Error: La conexión PDO no está disponible.</p>";
}

echo "<h2>Categorías del Menú:</h2>";

try {
    // --- PRUEBA 2: Obtener datos de la tabla 'categorias' ---
    // Insertar algunas categorías de prueba si no existen
    $stmt_check = $pdo->query("SELECT COUNT(*) FROM categorias");
    $count = $stmt_check->fetchColumn();

    if ($count == 0) {
        echo "<p>No hay categorías en la base de datos. Insertando algunas de prueba...</p>";
        $pdo->exec("INSERT INTO categorias (nombre_categoria) VALUES ('Pizzas'), ('Bebidas'), ('Entradas'), ('Postres')");
        echo "<p>Categorías de prueba insertadas.</p>";
    }

    // Preparar y ejecutar la consulta para obtener todas las categorías
    $stmt = $pdo->query("SELECT id_categoria, nombre_categoria FROM categorias ORDER BY nombre_categoria");
    $categorias = $stmt->fetchAll();

    if (count($categorias) > 0) {
        echo "<ul>";
        foreach ($categorias as $categoria) {
            echo "<li>ID: " . $categoria->id_categoria . " - Nombre: " . htmlspecialchars($categoria->nombre_categoria) . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>No se encontraron categorías en la base de datos.</p>";
    }

} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Error al consultar categorías: " . $e->getMessage() . "</p>";
}

echo "<h2>Productos del Menú:</h2>";

try {
    // --- PRUEBA 3: Obtener datos de la tabla 'productos' (opcional, si ya tienes productos) ---
    // Opcionalmente, puedes insertar un producto de prueba si no hay ninguno.
    // Asegúrate de que exista al menos una categoría con ID 1 (Pizzas, si la insertaste arriba)
    $stmt_check_products = $pdo->query("SELECT COUNT(*) FROM productos");
    $product_count = $stmt_check_products->fetchColumn();

    if ($product_count == 0 && $count > 0) { // Solo inserta si no hay productos y sí hay categorías
        $categoria_pizza_id = $pdo->query("SELECT id_categoria FROM categorias WHERE nombre_categoria = 'Pizzas'")->fetchColumn();
        if ($categoria_pizza_id) {
            echo "<p>No hay productos en la base de datos. Insertando algunos de prueba...</p>";
            $stmt_insert_product = $pdo->prepare("INSERT INTO productos (nombre_producto, descripcion, precio, id_categoria) VALUES (?, ?, ?, ?)");
            $stmt_insert_product->execute(['Pizza Margarita', 'Tomate, mozzarella, albahaca', 12.50, $categoria_pizza_id]);
            $stmt_insert_product->execute(['Refresco Cola', 'Refresco carbonatado de cola', 2.00, $pdo->query("SELECT id_categoria FROM categorias WHERE nombre_categoria = 'Bebidas'")->fetchColumn()]);
            echo "<p>Productos de prueba insertados.</p>";
        } else {
            echo "<p>No se pudo insertar productos de prueba: la categoría 'Pizzas' o 'Bebidas' no existe aún.</p>";
        }
    }


    $stmt_productos = $pdo->query("SELECT p.id_producto, p.nombre_producto, p.precio, c.nombre_categoria FROM productos p LEFT JOIN categorias c ON p.id_categoria = c.id_categoria ORDER BY p.nombre_producto");
    $productos = $stmt_productos->fetchAll();

    if (count($productos) > 0) {
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<thead><tr><th>ID</th><th>Nombre</th><th>Precio</th><th>Categoría</th></tr></thead>";
        echo "<tbody>";
        foreach ($productos as $producto) {
            echo "<tr>";
            echo "<td>" . $producto->id_producto . "</td>";
            echo "<td>" . htmlspecialchars($producto->nombre_producto) . "</td>";
            echo "<td>" . number_format($producto->precio, 2) . "</td>";
            echo "<td>" . htmlspecialchars($producto->nombre_categoria ?? 'Sin Categoría') . "</td>";
            echo "</tr>";
        }
        echo "</tbody>";
        echo "</table>";
    } else {
        echo "<p>No se encontraron productos en la base de datos.</p>";
    }

} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Error al consultar productos: " . $e->getMessage() . "</p>";
}

?>