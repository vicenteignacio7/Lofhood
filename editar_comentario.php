<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require __DIR__ . "/includes/conexion.php";

if (empty($_SESSION['user_id'])) exit;

$id = intval($_POST['id'] ?? 0);
$usuario_id = $_SESSION['user_id'];
$texto = trim($_POST['comentario']);
$pub = intval($_POST['pub']);
$from = $_POST['from'] ?? '';
$query = $_POST['query'] ?? '';

if ($texto !== "") {
    $stmt = $conn->prepare("UPDATE comentarios SET comentario = ? WHERE id = ? AND usuario_id = ?");
    $stmt->bind_param("sii", $texto, $id, $usuario_id);
    $stmt->execute();
}

header("Location: ver_publicacion.php?id=$pub&from=$from&$query#comentarios");
exit;
