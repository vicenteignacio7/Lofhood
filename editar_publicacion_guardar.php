<?php
session_start();
require __DIR__ . "/includes/conexion.php";

if (empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$id          = intval($_POST['id'] ?? 0);
$titulo      = trim($_POST['titulo'] ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');
$from        = $_POST['from'] ?? '';
$query       = $_POST['query'] ?? '';
$usuario_id  = $_SESSION['user_id'];

// ==========================================================
// OBLIGATORIO: LA CATEGORÍA NO SE PUEDE CAMBIAR
// ==========================================================
$stmt = $conn->prepare("SELECT categoria, imagen, usuario_id FROM publicaciones WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$pub = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$pub) {
    die("Publicación no encontrada.");
}

$categoria_original = $pub['categoria'];

// ============================
// PERMISOS
// ============================
$isAdmin = (($_SESSION['role'] ?? '') === 'admin');

if (!$isAdmin && $pub['usuario_id'] != $usuario_id) {
    die("No tienes permiso para editar esta publicación.");
}

// ============================================================
// MANEJO DE IMAGEN
// ============================================================
$nueva_imagen = $pub['imagen'];
$eliminar_imagen = intval($_POST['eliminar_imagen'] ?? 0);

// Eliminar imagen anterior
if ($eliminar_imagen === 1 && !empty($pub['imagen'])) {
    $ruta = __DIR__ . "/uploads/" . $pub['imagen'];
    if (file_exists($ruta)) unlink($ruta);
    $nueva_imagen = null;
}

// Subir imagen nueva
if (!empty($_FILES['imagen']['name'])) {

    $allowed_ext = ['jpg','jpeg','png','gif','webp'];
    $allowed_mime = ['image/jpeg','image/png','image/gif','image/webp'];

    $tmp  = $_FILES['imagen']['tmp_name'];
    $ext  = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
    $mime = mime_content_type($tmp);

    if (!in_array($ext, $allowed_ext) || !in_array($mime, $allowed_mime)) {
        header("Location: editar_publicacion.php?id=$id&cat=$categoria_original&from=$from&msg=Formato inválido&type=error");
        exit;
    }

    if ($_FILES['imagen']['size'] > 3 * 1024 * 1024) {
        header("Location: editar_publicacion.php?id=$id&cat=$categoria_original&from=$from&msg=Imagen excede 3MB&type=error");
        exit;
    }

    $nombre_img = time() . "_" . preg_replace('/[^a-zA-Z0-9_\.-]/','_', $_FILES['imagen']['name']);
    $destino = __DIR__ . "/uploads/" . $nombre_img;

    if (move_uploaded_file($tmp, $destino)) {
        $nueva_imagen = $nombre_img;
    }
}

// ============================================================
// VALIDAR CONTACTO
// ============================================================
$contacto = preg_replace('/[^0-9]/', '', $_POST['contacto'] ?? '');

if (strlen($contacto) !== 9) {
    $_SESSION['error_wsp'] = 1;
    header("Location: editar_publicacion.php?id=$id&cat=$categoria_original&from=$from");
    exit;
}

// ============================================================
// ACTUALIZAR SEGÚN TIPO DE PUBLICACIÓN
// ============================================================

if ($categoria_original === 'actividad') {

    $lugar          = trim($_POST['lugar'] ?? '');
    $fecha_evento   = $_POST['fecha_evento'] ?? null;
    $cupos          = ($_POST['cupos'] === '' ? null : intval($_POST['cupos']));
    $tipo_actividad = trim($_POST['tipo_actividad'] ?? '');

    if ($isAdmin) {

        $sql = "UPDATE publicaciones SET 
            titulo=?, descripcion=?, imagen=?, contacto=?, 
            lugar=?, fecha_evento=?, cupos=?, tipo_actividad=?
            WHERE id=?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssssi",
            $titulo, $descripcion, $nueva_imagen, $contacto,
            $lugar, $fecha_evento, $cupos, $tipo_actividad,
            $id
        );

    } else {

        $sql = "UPDATE publicaciones SET 
            titulo=?, descripcion=?, imagen=?, contacto=?, 
            lugar=?, fecha_evento=?, cupos=?, tipo_actividad=?
            WHERE id=? AND usuario_id=?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssssii",
            $titulo, $descripcion, $nueva_imagen, $contacto,
            $lugar, $fecha_evento, $cupos, $tipo_actividad,
            $id, $usuario_id
        );
    }

} else { 
    // ===========================================
    // SERVICIO — VENTA — TRABAJO (COMPARTEN CAMPOS)
    // ===========================================

    $precio          = ($_POST['precio'] === '' ? null : intval($_POST['precio']));
    $estado_producto = trim($_POST['estado_producto'] ?? '');
    $condicion       = trim($_POST['condicion'] ?? '');
    $entrega         = trim($_POST['entrega'] ?? '');
    $metodo_pago     = trim($_POST['metodo_pago'] ?? '');

    if ($isAdmin) {

        $sql = "UPDATE publicaciones SET 
            titulo=?, descripcion=?, imagen=?, contacto=?, 
            precio=?, estado_producto=?, condicion=?, entrega=?, metodo_pago=? 
            WHERE id=?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssissssi",
            $titulo, $descripcion, $nueva_imagen, $contacto,
            $precio, $estado_producto, $condicion, $entrega, $metodo_pago,
            $id
        );

    } else {

        $sql = "UPDATE publicaciones SET 
            titulo=?, descripcion=?, imagen=?, contacto=?, 
            precio=?, estado_producto=?, condicion=?, entrega=?, metodo_pago=? 
            WHERE id=? AND usuario_id=?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssissssii",
            $titulo, $descripcion, $nueva_imagen, $contacto,
            $precio, $estado_producto, $condicion, $entrega, $metodo_pago,
            $id, $usuario_id
        );
    }
}

$stmt->execute();
$stmt->close();

// ============================================================
// REDIRECCIÓN FINAL
// ============================================================
$from = $_SESSION['volver_origen'] ?? 'general';
$query_str = $query ? "&".$query : "";

header("Location: ver_publicacion.php?id={$id}&cat={$categoria_original}&from={$from}{$query_str}&msg=Publicación%20editada&type=success");
exit;

