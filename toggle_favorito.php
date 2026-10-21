<?php
session_start();
require __DIR__ . "/includes/conexion.php";
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'msg' => 'Debes iniciar sesión']);
    exit;
}

$usuario_id = $_SESSION['user_id'];
$publicacion_id = intval($_POST['id'] ?? 0);

if ($publicacion_id <= 0) {
    echo json_encode(['status' => 'error', 'msg' => 'ID inválido']);
    exit;
}

// 1. Verificar si ya existe
$check = $conn->prepare("SELECT id FROM favoritos WHERE usuario_id = ? AND publicacion_id = ?");
$check->bind_param("ii", $usuario_id, $publicacion_id);
$check->execute();
$res = $check->get_result();

if ($res->num_rows > 0) {
    // YA EXISTE -> ELIMINAR (Dislike)
    $del = $conn->prepare("DELETE FROM favoritos WHERE usuario_id = ? AND publicacion_id = ?");
    $del->bind_param("ii", $usuario_id, $publicacion_id);
    $del->execute();
    echo json_encode(['status' => 'removed']); // Quitó like
} else {
    // NO EXISTE -> AGREGAR (Like)
    $ins = $conn->prepare("INSERT INTO favoritos (usuario_id, publicacion_id) VALUES (?, ?)");
    $ins->bind_param("ii", $usuario_id, $publicacion_id);
    $ins->execute();
    echo json_encode(['status' => 'added']); // Puso like
}
exit;