<?php
session_start();
require __DIR__ . "/includes/conexion.php";

if (empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$id = intval($_GET['id'] ?? 0);
$estado = $_GET['estado'] ?? 'disponible';
$cat = $_GET['cat'] ?? 'venta';
$from = $_GET['from'] ?? 'list';

// Validar estado
if (!in_array($estado, ['disponible','vendido'])) {
    $estado = 'disponible';
}

// Confirmar que es dueño
$stmt = $conn->prepare("UPDATE publicaciones SET estado = ? WHERE id = ? AND usuario_id = ?");
$stmt->bind_param("sii", $estado, $id, $_SESSION['user_id']);
$stmt->execute();

// Regresar según vista
if ($from === 'mis') {
    header("Location: mis_publicaciones.php?msg=Estado actualizado&type=success");
} else {
    header("Location: ver_publicaciones.php?cat=$cat&msg=Estado actualizado&type=success");
}
exit;
