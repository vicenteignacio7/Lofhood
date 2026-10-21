<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require __DIR__ . "/includes/conexion.php";
require __DIR__ . "/includes/config_planes.php"; 

// 1. Recibir y limpiar datos
$id = intval($_GET['id'] ?? 0);
$nuevo_estado = strtolower(trim($_GET['estado'] ?? ''));
$cat = $_GET['cat'] ?? '';
$from = $_GET['from'] ?? '';
$query = $_GET['query'] ?? '';

// Construimos la URL de retorno base
$url_retorno = ($from === 'mis') ? "mis_publicaciones.php" : "ver_publicaciones.php?cat=" . urlencode($cat);

// Ajuste para Dashboard (si el admin viene de allá)
if ($from === 'dashboard') {
    $url_retorno = "dashboard.php";
}

if (!empty($query)) {
    $conector = (strpos($url_retorno, '?') !== false) ? "&" : "?";
    $url_retorno .= $conector . $query;
}

if (!$id || !$nuevo_estado || empty($_SESSION['user_id'])) {
    header("Location: publicaciones.php");
    exit;
}

$usuario_actual_id = $_SESSION['user_id'];
// 👑 DETECTAR SI ES ADMIN
$es_admin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');

// 2. OBTENER DATOS
$sql_check = "SELECT usuario_id, TRIM(categoria) AS categoria FROM publicaciones WHERE id = ?";
$stmt = $conn->prepare($sql_check);
$stmt->bind_param("i", $id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

// 🔴 MODIFICACIÓN CLAVE: Permitir acceso si es dueño O si es admin
if (!$data || ($data['usuario_id'] != $usuario_actual_id && !$es_admin)) {
    header("Location: publicaciones.php?msg=Acceso denegado&type=error");
    exit;
}

$categoria_real = strtolower($data['categoria']); 
// Detectamos si el usuario quiere ACTIVAR (ocupar cupo)
$intentando_activar = ($nuevo_estado === 'disponible' || $nuevo_estado === 'abierto');

// ===========================================================================
// 🛡️ GUARDIÁN ANTI-TRAMPAS (LÓGICA BLINDADA)
// ===========================================================================

// 👑 MODIFICACIÓN: Si es Admin, se salta el chequeo de límites (puede activar lo que quiera)
if ($intentando_activar && !$es_admin) {
    
    // --- CASO A: ACTIVIDAD (Límite: 1) ---
    if ($categoria_real === 'actividad') {
        $sqlC = "SELECT COUNT(*) as total FROM publicaciones 
                 WHERE usuario_id = ? 
                 AND categoria = 'actividad' 
                 AND estado = 'disponible' 
                 AND id != ?";
                 
        $stmtC = $conn->prepare($sqlC);
        $stmtC->bind_param("ii", $usuario_actual_id, $id);
        $stmtC->execute();
        $total = $stmtC->get_result()->fetch_assoc()['total'];

        if ($total >= 1) {
            $errorMsg = "⚠️ Solo puedes tener 1 actividad activa a la vez.";
            $conector = (strpos($url_retorno, '?') !== false) ? "&" : "?";
            header("Location: " . $url_retorno . $conector . "msg=" . urlencode($errorMsg) . "&type=error");
            exit;
        }
    } 
    
    // --- CASO B: PLAN GENERAL (Ventas, Servicios, Trabajo) ---
    elseif ($categoria_real !== 'alerta') {
        
        $stmtPlan = $conn->prepare("SELECT plan_id FROM usuarios WHERE id = ?");
        $stmtPlan->bind_param("i", $usuario_actual_id);
        $stmtPlan->execute();
        $userPlanId = $stmtPlan->get_result()->fetch_assoc()['plan_id'] ?? 0;
        $reglas = obtenerLimitesPlan($userPlanId);

        $sqlC = "SELECT COUNT(*) as total FROM publicaciones 
                 WHERE usuario_id = ? 
                 AND categoria NOT IN ('alerta', 'actividad') 
                 AND id != ? 
                 AND (
                    (categoria IN ('venta', 'servicio') AND estado = 'disponible')
                    OR 
                    (categoria = 'trabajo' AND estado_trabajo = 'abierto')
                 )";
                 
        $stmtC = $conn->prepare($sqlC);
        $stmtC->bind_param("ii", $usuario_actual_id, $id);
        $stmtC->execute();
        $total = $stmtC->get_result()->fetch_assoc()['total'];

        if ($total >= $reglas['limite_pubs']) {
            $errorMsg = "⛔ Límite del plan alcanzado ({$reglas['limite_pubs']} publicaciones). Marca otras como vendidas/cerradas.";
            $conector = (strpos($url_retorno, '?') !== false) ? "&" : "?";
            header("Location: " . $url_retorno . $conector . "msg=" . urlencode($errorMsg) . "&type=error");
            exit;
        }
    }
}

// ===========================================================================
// 📝 EJECUTAR ACTUALIZACIÓN
// ===========================================================================

$sql_update = "";
$param_estado = "";

if ($categoria_real === 'trabajo') {
    $param_estado = ($nuevo_estado == 'abierto') ? 'abierto' : 'cerrado';
    $sql_update = "UPDATE publicaciones SET estado_trabajo = ? WHERE id = ?";
} elseif ($categoria_real === 'actividad') {
    $param_estado = ($nuevo_estado == 'disponible') ? 'disponible' : 'terminado';
    $sql_update = "UPDATE publicaciones SET estado = ? WHERE id = ?";
} else {
    $param_estado = ($nuevo_estado == 'vendido') ? 'vendido' : 'disponible';
    $sql_update = "UPDATE publicaciones SET estado = ? WHERE id = ?";
}

$upd = $conn->prepare($sql_update);
$upd->bind_param("si", $param_estado, $id);
$upd->execute();

// Retorno Éxito
$conector = (strpos($url_retorno, '?') !== false) ? "&" : "?";
header("Location: " . $url_retorno . $conector . "msg=" . urlencode("Estado actualizado") . "&type=success");
exit;
?>