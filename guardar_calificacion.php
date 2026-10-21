<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require __DIR__ . "/includes/conexion.php";

if (empty($_POST['publicacion_id']) || empty($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$pub_id = intval($_POST['publicacion_id']);
$usuario = intval($_SESSION['user_id']);
$calificacion = intval($_POST['calificacion']);

if ($calificacion < 1 || $calificacion > 5) {
    echo "Calificación inválida";
    exit;
}

// ✔ Revisar si ya existe una calificación del usuario
$sql = "SELECT id FROM calificaciones WHERE usuario_id=? AND publicacion_id=? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $usuario, $pub_id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    // ✔ Ya existe → actualizar
    $update = $conn->prepare("UPDATE calificaciones SET calificacion=?, fecha=NOW() WHERE usuario_id=? AND publicacion_id=?");
    $update->bind_param("iii", $calificacion, $usuario, $pub_id);
    $update->execute();
} else {
    // ✔ No existe → insertar
    $insert = $conn->prepare("INSERT INTO calificaciones (usuario_id, publicacion_id, calificacion, fecha) VALUES (?, ?, ?, NOW())");
    $insert->bind_param("iii", $usuario, $pub_id, $calificacion);
    $insert->execute();
}

header("Location: ver_publicacion.php?id=$pub_id#comentarios");
exit;
?>
