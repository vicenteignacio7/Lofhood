<?php
session_start();
require __DIR__ . "/includes/conexion.php";
require __DIR__ . "/includes/config_planes.php"; 

if (empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $usuario_id  = $_SESSION['user_id'];
    $categoria   = trim($_POST['categoria'] ?? '');
    
    // 👑 DETECTAR ADMIN
    $es_admin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');

    // =================================================================
    // 🛑 VALIDACIÓN DE LÍMITES
    // =================================================================
    
    if (!$es_admin) {
        // CASO 1: ACTIVIDAD
        if ($categoria === 'actividad') {
            $sqlCount = "SELECT COUNT(*) as total FROM publicaciones 
                          WHERE usuario_id = ? 
                          AND categoria = 'actividad' 
                          AND estado NOT IN ('vendido', 'cerrado', 'terminado')";
            $stmtC = $conn->prepare($sqlCount);
            $stmtC->bind_param("i", $usuario_id);
            $stmtC->execute();
            $activas = $stmtC->get_result()->fetch_assoc()['total'];

            if ($activas >= 1) {
                $msjError = "Error: Solo puedes tener 1 actividad activa.";
                header("Location: publicar.php?cat=$categoria&msg=" . urlencode($msjError) . "&type=error");
                exit;
            }
        }
        // CASO 2: VENTA, SERVICIO O TRABAJO (Aquí usamos la lógica unificada corregida)
        elseif ($categoria !== 'alerta') {
            $stmtPlan = $conn->prepare("SELECT plan_id FROM usuarios WHERE id = ?");
            $stmtPlan->bind_param("i", $usuario_id);
            $stmtPlan->execute();
            $userPlanId = $stmtPlan->get_result()->fetch_assoc()['plan_id'] ?? 0;
            $reglas = obtenerLimitesPlan($userPlanId);

            // CONTADOR CORREGIDO (Mira estado O estado_trabajo)
            $sqlCount = "SELECT COUNT(*) as total FROM publicaciones 
                          WHERE usuario_id = ? 
                          AND categoria NOT IN ('alerta', 'actividad') 
                          AND (
                            (categoria != 'trabajo' AND estado = 'disponible')
                            OR 
                            (categoria = 'trabajo' AND estado_trabajo = 'abierto')
                          )";
            $stmtC = $conn->prepare($sqlCount);
            $stmtC->bind_param("i", $usuario_id);
            $stmtC->execute();
            $activas = $stmtC->get_result()->fetch_assoc()['total'];

            if ($activas >= $reglas['limite_pubs']) {
                $msjError = "Error: Límite de publicaciones alcanzado. Gestiona tus avisos antiguos.";
                header("Location: publicar.php?cat=$categoria&msg=" . urlencode($msjError) . "&type=error");
                exit;
            }
        }
    }

    // =================================================================
    // 📝 PROCESAMIENTO DE DATOS
    // =================================================================

    $titulo      = trim($_POST['titulo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $lat         = $_POST['latitud'] ?? 0.0;
    $lon         = $_POST['longitud'] ?? 0.0;

    // Campos comunes
    $precio      = !empty($_POST['precio']) ? $_POST['precio'] : null;
    $ubicacion   = $_POST['ubicacion'] ?? null;
    $contacto    = !empty($_POST['contacto']) ? $_POST['contacto'] : null;
    $horario     = $_POST['horario'] ?? null;

    // Campos específicos
    $estado_producto = $_POST['estado_producto'] ?? null;
    $condicion       = $_POST['condicion'] ?? null;
    $entrega         = $_POST['entrega'] ?? null;
    $metodo_pago     = $_POST['metodo_pago'] ?? null;

    $lugar           = $_POST['lugar'] ?? null;
    $fecha_evento    = $_POST['fecha_evento'] ?? null;
    $cupos           = !empty($_POST['cupos']) ? intval($_POST['cupos']) : null;
    $tipo_actividad  = $_POST['tipo_actividad'] ?? null;

    $zona_cobertura       = $_POST['zona_cobertura'] ?? null;
    $modalidad            = $_POST['modalidad'] ?? null;
    $experiencia_public   = $_POST['experiencia'] ?? null;
    $tipo_trabajo         = $_POST['tipo_trabajo'] ?? null;
    $salario              = $_POST['salario'] ?? null;
    $materiales_incluidos = $_POST['materiales_incluidos'] ?? null;
    $requisitos           = $_POST['requisitos'] ?? null;
    $cupos_trabajo        = !empty($_POST['cupos_trabajo']) ? intval($_POST['cupos_trabajo']) : null;

    // 🟢 CORRECCIÓN PRINCIPAL: Definir estados iniciales explícitamente
    $estado_general = 'disponible'; // Por defecto para Venta, Servicio, Actividad
    $estado_trabajo = null;

    if ($categoria === 'trabajo') {
        $estado_trabajo = 'abierto';
        // Para trabajo, el 'estado' general no es tan relevante, pero lo dejamos disponible para evitar bugs
    } 

    // Subir imagen
    $nombre_img = null;
    if (!empty($_FILES['imagen']['name']) && is_uploaded_file($_FILES['imagen']['tmp_name'])) {
        $allowed_ext = ['jpg','jpeg','png','gif','webp'];
        $filename = $_FILES['imagen']['name'];
        $tmp      = $_FILES['imagen']['tmp_name'];
        $ext      = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($ext, $allowed_ext)) {
            $upload_dir = __DIR__ . "/uploads";
            if (!is_dir($upload_dir)) { @mkdir($upload_dir, 0775, true); }
            $nombre_img = time() . "_" . preg_replace('/[^a-zA-Z0-9_\.-]/','_', $filename);
            $destino    = $upload_dir . "/" . $nombre_img;
            if (@getimagesize($tmp)) { move_uploaded_file($tmp, $destino); }
        }
    }

    // 🟢 INSERT CORREGIDO: Agregamos la columna 'estado' y la variable $estado_general
    $stmt = $conn->prepare("
        INSERT INTO publicaciones 
        (usuario_id, titulo, descripcion, categoria,
         precio, estado_producto, condicion, entrega, metodo_pago,
         ubicacion, zona_cobertura, modalidad, experiencia, tipo_trabajo,
         salario, materiales_incluidos, requisitos,
         contacto, horario,
         lugar, fecha_evento, cupos, tipo_actividad,
         imagen, estado_trabajo, cupos_trabajo, latitud, longitud, fecha, estado)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?, NOW(), ?)
    ");
    
// Hemos corregido la cadena de tipos para que coincida con las 29 variables
   // BLOQUE CORREGIDO
    $stmt->bind_param(
        "issssssssssssssssssssisssidds", // 29 caracteres exactos
        $usuario_id, $titulo, $descripcion, $categoria,
        $precio, $estado_producto, $condicion, $entrega, $metodo_pago,
        $ubicacion, $zona_cobertura, $modalidad, $experiencia_public, $tipo_trabajo,
        $salario, $materiales_incluidos, $requisitos,
        $contacto, $horario,
        $lugar, $fecha_evento, $cupos, $tipo_actividad,
        $nombre_img, $estado_trabajo, $cupos_trabajo, $lat, $lon,
        $estado_general
    );

    if ($stmt->execute()) {
        $new_pub_id = $stmt->insert_id;

        // NOTIFICACIONES
        $mensaje_notif = "Nueva publicación cerca de ti: " . substr($titulo, 0, 25) . "...";
        $resUsers = $conn->query("SELECT id FROM usuarios WHERE id != $usuario_id");
        
        if ($resUsers) {
            $stmtNotif = $conn->prepare("INSERT INTO notificaciones (usuario_id, publicacion_id, mensaje, fecha) VALUES (?, ?, ?, NOW())");
            while ($u = $resUsers->fetch_assoc()) {
                $destinatario_id = $u['id'];
                $stmtNotif->bind_param("iis", $destinatario_id, $new_pub_id, $mensaje_notif);
                $stmtNotif->execute();
            }
            $stmtNotif->close();
        }

        header("Location: ver_publicaciones.php?cat={$categoria}&msg=Publicación creada con éxito&type=success");
        exit;
    } else {
        header("Location: publicar.php?cat={$categoria}&msg=Error al crear: " . urlencode($stmt->error) . "&type=error");
        exit;
    }
}
?>