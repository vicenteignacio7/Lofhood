<?php
session_start();
require __DIR__ . "/includes/conexion.php";

if(empty($_SESSION['user_id'])) {
    header("Location: login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$publicacion_id = intval($_POST['id'] ?? 0);
$usuario_id     = $_SESSION['user_id'];

/* Obtener publicación */
$stmt = $conn->prepare("
    SELECT TRIM(categoria) AS categoria, TRIM(estado) AS estado, cupos
    FROM publicaciones 
    WHERE id = ? LIMIT 1
");

$stmt->bind_param("i", $publicacion_id);
$stmt->execute();
$pub = $stmt->get_result()->fetch_assoc();
$stmt->close();

if(!$pub){
    header("Location: ver_publicacion.php?id=$publicacion_id&msg=No encontrada&type=error");
    exit;
}

/* FORZAR: Solo actividades se pueden unir */
if($pub['categoria'] !== 'actividad'){
    header("Location: ver_publicacion.php?id=$publicacion_id&msg=No es una actividad&type=error");
    exit;
}

/* FORZAR: Actividad terminada → NO SE UNE */
if($pub['estado'] === 'terminado'){
    header("Location: ver_publicacion.php?id=$publicacion_id&msg=La actividad ha finalizado&type=warning");
    exit;
}

/* Verificar si ya está unido */
$check = $conn->prepare("SELECT 1 FROM actividad_asistentes WHERE usuario_id = ? AND publicacion_id = ?");
$check->bind_param("ii", $usuario_id, $publicacion_id);
$check->execute();
$check->store_result();
$ya_unido = $check->num_rows > 0;
$check->close();

if($ya_unido){
    header("Location: ver_publicacion.php?id=$publicacion_id&msg=Ya estás unido&type=info");
    exit;
}

/* Contar participantes */
$count = $conn->prepare("SELECT COUNT(*) FROM actividad_asistentes WHERE publicacion_id = ?");
$count->bind_param("i", $publicacion_id);
$count->execute();
$count->bind_result($total);
$count->fetch();
$count->close();

/* FORZAR: Cupos llenos → NO SE UNE */
if(!empty($pub['cupos']) && $total >= $pub['cupos']){
    header("Location: ver_publicacion.php?id=$publicacion_id&msg=No quedan cupos&type=warning");
    exit;
}

/* ✅ Registrar participación (SOLO si pasó todas las validaciones) */
$add = $conn->prepare("
    INSERT INTO actividad_asistentes (usuario_id, publicacion_id, fecha_registro)
    VALUES (?, ?, NOW())
");
$add->bind_param("ii", $usuario_id, $publicacion_id);
$add->execute();
$add->close();

header("Location: ver_publicacion.php?id=$publicacion_id&msg=Te uniste&type=success");
exit;
