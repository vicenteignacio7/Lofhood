<?php
// admin/eliminar.php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../includes/conexion.php'; // define $conn (mysqli)

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    http_response_code(400);
    die('❌ ID inválido.');
}

// para volver a la misma vista
$returnParam = $_GET['return_url'] ?? '';
$goBack = function(string $fallback) use ($returnParam) {
    $dest = $returnParam ? urldecode($returnParam) : $fallback;
    if (strpos($dest, '://') !== false) { $dest = $fallback; } // evita open redirect
    header('Location: ' . $dest);
    exit;
};

// 1) Obtener nombre de imagen
$stmt = $conn->prepare("SELECT imagen FROM catalogo_productos WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
$prod = $res->fetch_assoc();
$stmt->close();

if (!$prod) {
    $goBack('index_admin.php?deleted=0&reason=not_found');
}

// 2) Eliminar registro
$del = $conn->prepare("DELETE FROM catalogo_productos WHERE id = ?");
$del->bind_param('i', $id);
$ok = $del->execute();
$del->close();

// 3) Si se eliminó, borro imagen
if ($ok) {
    if (!empty($prod['imagen'])) {
        $imgPath = __DIR__ . '/../assets/imagenes/' . $prod['imagen'];
        if (is_file($imgPath)) { @unlink($imgPath); }
    }
    $goBack('index_admin.php?deleted=1');
}

// Falló
$goBack('index_admin.php?deleted=0');
