<?php
session_start();
require __DIR__ . "/includes/conexion.php";

if (empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$id = intval($_POST['id'] ?? 0);

// Verificar que existe la publicación y obtener dueño
$sql = "SELECT usuario_id FROM publicaciones WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$pub = $stmt->get_result()->fetch_assoc();

if (!$pub) {
    header("Location: publicaciones.php?msg=Publicación no encontrada&type=error");
    exit;
}

// Solo el dueño o admin puede reabrir
if ($_SESSION['user_id'] != $pub['usuario_id'] && ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: publicaciones.php?msg=No tienes permiso&type=error");
    exit;
}

// Reabrir postulaciones
$upd = $conn->prepare("UPDATE publicaciones SET estado_trabajo='abierto' WHERE id=?");
$upd->bind_param("i", $id);
$upd->execute();

// Redirigir a la publicación
header("Location: ver_publicacion.php?id=$id&msg=Postulaciones%20reabiertas&type=success");
exit;
