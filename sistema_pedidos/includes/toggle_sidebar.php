<?php
// sistema_pedidos/includes/toggle_sidebar.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $collapsed = $_POST['sidebar_collapsed'] === '1';
    $_SESSION['sidebar_collapsed'] = $collapsed;
    
    // Redirigir de vuelta a la página anterior
    $referer = $_SERVER['HTTP_REFERER'] ?? '../admin/index.php';
    header('Location: ' . $referer);
    exit;
}