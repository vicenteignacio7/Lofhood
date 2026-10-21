<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require __DIR__ . "/includes/conexion.php";

if (empty($_SESSION['user_id'])) {
    header("Location: login.php?msg=Debes iniciar sesión&type=error");
    exit;
}

$usuario_id = (int)$_SESSION['user_id'];
$id = intval($_POST['id'] ?? 0);

// 1. Verificar si es Admin
$es_admin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');

if ($id <= 0) {
    header("Location: publicaciones.php?msg=Publicación no válida&type=error");
    exit;
}

// Verificar que el trabajo exista
$stmt = $conn->prepare("SELECT usuario_id, categoria FROM publicaciones WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$pub = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$pub || $pub['categoria'] !== 'trabajo') {
    header("Location: publicaciones.php?msg=Trabajo no encontrado&type=error");
    exit;
}

// 2. MODIFICACIÓN: Permitir si es dueño O si es admin
if ((int)$pub['usuario_id'] !== $usuario_id && !$es_admin) {
    // Si NO es el dueño Y TAMPOCO es admin, lo sacamos
    header("Location: ver_publicacion.php?id={$id}&cat=trabajo&msg=No tienes permiso para cerrar este trabajo&type=error");
    exit;
}

// Cerrar postulaciones
$up = $conn->prepare("UPDATE publicaciones SET estado_trabajo='cerrado' WHERE id = ?");
$up->bind_param("i", $id);
$up->execute();
$up->close();

header("Location: ver_publicacion.php?id={$id}&cat=trabajo&msg=Postulaciones cerradas&type=success");
exit;
?>