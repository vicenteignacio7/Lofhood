<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require __DIR__ . "/includes/conexion.php";

if (empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$publicacion_id = intval($_POST['publicacion_id']);
$usuario_id = $_SESSION['user_id'];
$comentario = trim($_POST['comentario']);
$parent_id = isset($_POST['parent_id']) ? intval($_POST['parent_id']) : null;

$from = $_POST['from'] ?? '';
$query = $_POST['query'] ?? '';

if ($comentario === "") {
    header("Location: ver_publicacion.php?id=$publicacion_id&msg=Comentario vacío&type=error");
    exit;
}

if ($parent_id) {
    $stmt = $conn->prepare("INSERT INTO comentarios (publicacion_id, usuario_id, comentario, parent_id) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iisi", $publicacion_id, $usuario_id, $comentario, $parent_id);
} else {
    $stmt = $conn->prepare("INSERT INTO comentarios (publicacion_id, usuario_id, comentario) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $publicacion_id, $usuario_id, $comentario);
}
$stmt->execute();

header("Location: ver_publicacion.php?id=$publicacion_id&from=$from&$query#comentarios");
exit;
