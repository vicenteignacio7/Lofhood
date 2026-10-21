<?php
// admin/eliminar_pedido.php
declare(strict_types=1);
session_start();

if (($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../includes/conexion.php';
$mysqli = $conn;

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$return = $_POST['return_url'] ?? 'index_admin.php';

if ($id <= 0) {
    header("Location: " . $return);
    exit;
}

// Verificar que el pedido existe y está ENTREGADO
$stmt = $mysqli->prepare("SELECT estado FROM pedidos WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
if (!$res || !$res->num_rows) {
    $stmt->close();
    header("Location: " . $return);
    exit;
}
$row = $res->fetch_assoc();
$stmt->close();

if ($row['estado'] !== 'entregado') {
    // No permitir borrar si no está entregado
    header("Location: " . $return);
    exit;
}

// Borramos detalle y luego el pedido (dejamos pagos como trazabilidad)
$mysqli->begin_transaction();
try {
    $d1 = $mysqli->prepare("DELETE FROM detalle_pedido WHERE pedido_id = ?");
    $d1->bind_param('i', $id);
    $d1->execute();
    $d1->close();

    $d2 = $mysqli->prepare("DELETE FROM pedidos WHERE id = ?");
    $d2->bind_param('i', $id);
    $d2->execute();
    $d2->close();

    $mysqli->commit();
} catch (Throwable $e) {
    $mysqli->rollback();
}

header("Location: " . $return);
exit;
