<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require __DIR__ . "/includes/conexion.php";

if (empty($_SESSION['user_id'])) exit;

$id = intval($_GET['id'] ?? 0);
$usuario_id = $_SESSION['user_id'];
$publicacion_id = intval($_GET['pub'] ?? 0);
$from = $_GET['from'] ?? '';
$query = $_GET['query'] ?? '';

$stmt = $conn->prepare("DELETE FROM comentarios WHERE id = ? AND usuario_id = ?");
$stmt->bind_param("ii", $id, $usuario_id);
$stmt->execute();

header("Location: ver_publicacion.php?id=$publicacion_id&from=$from&$query#comentarios");
exit;
