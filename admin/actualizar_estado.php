<?php
// admin/actualizar_estado.php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../includes/conexion.php';
header('Content-Type: application/json; charset=utf-8');

$id     = isset($_POST['pedido_id']) ? (int)$_POST['pedido_id'] : (int)($_GET['id'] ?? 0);
$estado = trim($_POST['estado'] ?? ($_GET['estado'] ?? ''));

if ($id <= 0 || $estado === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Parámetros inválidos']);
    exit;
}

// Si admin confirma transferencia como pagado → lo marcamos confirmado y pendiente
if ($estado === 'pagado') {
    $stmt = $conn->prepare("UPDATE pedidos SET pago_confirmado=1, estado='pendiente' WHERE id=?");
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['ok'=>false,'error'=>$conn->error]); exit;
    }
    $stmt->bind_param('i', $id);
    $ok = $stmt->execute();
    echo json_encode(['ok' => $ok]);
    exit;
}

// Cambios de estado normales (trabajador mueve de pendiente→preparando→entregado)
$stmt = $conn->prepare("UPDATE pedidos SET estado=? WHERE id=?");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $conn->error]); exit;
}
$stmt->bind_param('si', $estado, $id);
$ok = $stmt->execute();

if (!$ok) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $stmt->error]); exit;
}

echo json_encode(['ok' => true]);
