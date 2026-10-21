<?php
session_start();
require __DIR__ . "/includes/conexion.php";

// 1. Validar sesión
if (empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$id   = intval($_GET['id'] ?? 0);
$cat  = $_GET['cat'] ?? '';
$from = $_GET['from'] ?? 'general'; // Aquí recibimos 'dashboard', 'mis', 'fav', etc.

if ($id <= 0) {
    header("Location: publicaciones.php?msg=ID inválido&type=error");
    exit;
}

$usuario_actual_id = $_SESSION['user_id'];

// 2. Obtener dueño y rol
$stmt = $conn->prepare("
    SELECT p.usuario_id AS creador_id,
           (SELECT role FROM usuarios WHERE id = ?) AS role_actual
    FROM publicaciones p
    WHERE p.id = ?
");
$stmt->bind_param("ii", $usuario_actual_id, $id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if (!$data) {
    header("Location: publicaciones.php?msg=Publicación no encontrada&type=error");
    exit;
}

$creador_id = $data['creador_id'];
$es_admin   = ($data['role_actual'] === 'admin');
$es_dueno   = ($creador_id == $usuario_actual_id);

// 3. Validación de permisos
if (!$es_admin && !$es_dueno) {
    header("Location: publicaciones.php?msg=No tienes permiso&type=error");
    exit;
}

// 4. ELIMINAR (Primero notificaciones y favoritos por si acaso no hay CASCADE, luego la pub)
$conn->query("DELETE FROM notificaciones WHERE publicacion_id = $id");
$conn->query("DELETE FROM favoritos WHERE publicacion_id = $id");
$conn->query("DELETE FROM reportes WHERE publicacion_id = $id");

$del = $conn->prepare("DELETE FROM publicaciones WHERE id = ?");
$del->bind_param("i", $id);
$ok = $del->execute();

$msg  = $ok ? "Publicación eliminada correctamente" : "Error al eliminar";
$type = $ok ? "success" : "error";

// 5. REDIRECCIÓN INTELIGENTE (Aquí está la magia)
$msg_param = "msg=" . urlencode($msg) . "&type=" . $type;

switch ($from) {
    case 'mis':
        $redir = "mis_publicaciones.php?$msg_param";
        break;
        
    case 'dashboard':
        $redir = "dashboard.php?$msg_param";
        break;
        
    case 'fav':
        // Si la borraste, volvemos a la lista de favoritos (ya no estará ahí)
        $redir = "mis_favoritos.php?$msg_param";
        break;
        
    case 'notif':
        // Si vienes de una notificación, volvemos a la lista de notificaciones
        $redir = "mis_notificaciones.php?$msg_param";
        break;

    default:
        // Por defecto (cat, general, index) vamos al listado de la categoría
        $redir = "ver_publicaciones.php?cat=" . urlencode($cat) . "&" . $msg_param;
        break;
}

header("Location: $redir");
exit;
?>