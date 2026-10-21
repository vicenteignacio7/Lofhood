<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Guardamos desde dónde viene el usuario
$referer = $_SERVER['HTTP_REFERER'] ?? '';

// Cerrar sesión correctamente
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

// Detectar si estaba creando / editando / viendo publicaciones privadas
if (
    strpos($referer, 'crear_publicacion.php') !== false ||
    strpos($referer, 'editar_publicacion.php') !== false ||
    strpos($referer, 'ver_publicacion.php') !== false
) {
    header("Location: publicaciones.php?msg=" . urlencode("Sesión cerrada. Para crear publicaciones debes iniciar sesión.") . "&type=info");
    exit;
}

// Si no estaba en esas páginas → volver donde estaba
if (!empty($referer)) {
    header("Location: $referer");
    exit;
}

// Último fallback
header("Location: index.php");
exit;
