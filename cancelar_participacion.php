<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require __DIR__ . "/includes/conexion.php";

if (empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$publicacion_id = intval($_POST['id'] ?? 0);
$usuario_id     = $_SESSION['user_id'];

/* ✅ Confirmar que el usuario realmente estaba unido */
$check = $conn->prepare("SELECT 1 FROM actividad_asistentes WHERE usuario_id = ? AND publicacion_id = ?");
$check->bind_param("ii", $usuario_id, $publicacion_id);
$check->execute();
$check->store_result();
$ya_unido = $check->num_rows > 0;
$check->close();

if(!$ya_unido){
    header("Location: ver_publicacion.php?id=$publicacion_id&msg=No estabas unido&type=info");
    exit;
}

/* ✅ Eliminar participación */
$stmt = $conn->prepare("DELETE FROM actividad_asistentes WHERE usuario_id = ? AND publicacion_id = ?");
$stmt->bind_param("ii", $usuario_id, $publicacion_id);
$stmt->execute();
$stmt->close();

/* ✅ Mensaje de éxito */
header("Location: ver_publicacion.php?id=$publicacion_id&msg=Has salido de la actividad&type=success");
exit;
