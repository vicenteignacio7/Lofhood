<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require __DIR__ . "/includes/conexion.php";
require __DIR__ . "/includes/config_planes.php";

if (empty($_SESSION['user_id'])) { echo "no-session"; exit; }

$id     = intval($_POST['id'] ?? 0);
$estado = $_POST['estado'] ?? '';
$uid    = $_SESSION['user_id'];

if (!$id || !in_array($estado, ['vendido','disponible'])) { echo "invalid"; exit; }

// 🛑 GUARDIÁN ANTI-TRAMPAS (AJAX)
if ($estado === 'disponible') {
    // 1. Verificar categoría
    $stmtCheck = $conn->prepare("SELECT categoria FROM publicaciones WHERE id = ? AND usuario_id = ?");
    $stmtCheck->bind_param("ii", $id, $uid);
    $stmtCheck->execute();
    $d = $stmtCheck->get_result()->fetch_assoc();
    
    if (!$d) { echo "error_owner"; exit; } // No es dueño
    $catReal = $d['categoria'];
    
    // 2. Verificar límites
    if ($catReal === 'actividad') {
        $check = $conn->query("SELECT COUNT(*) as c FROM publicaciones WHERE usuario_id=$uid AND categoria='actividad' AND estado='disponible' AND id != $id")->fetch_assoc();
        if ($check['c'] >= 1) { echo "limit_actividad"; exit; }
    } 
    elseif ($catReal !== 'alerta') {
        $p = $conn->query("SELECT plan_id FROM usuarios WHERE id=$uid")->fetch_assoc();
        $limites = obtenerLimitesPlan($p['plan_id'] ?? 0);
        $check = $conn->query("SELECT COUNT(*) as c FROM publicaciones WHERE usuario_id=$uid AND categoria NOT IN ('alerta','actividad') AND estado='disponible' AND id != $id")->fetch_assoc();
        
        if ($check['c'] >= $limites['limite_pubs']) { echo "limit_plan"; exit; }
    }
}

// Todo bien, actualizar
$sql = "UPDATE publicaciones SET estado = ? WHERE id = ? AND usuario_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sii", $estado, $id, $uid);
$stmt->execute();

echo "ok";
?>