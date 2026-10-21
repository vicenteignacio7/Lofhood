<?php

header('Content-Type: application/json');
include __DIR__ . '/../includes/conexion.php';
include __DIR__ . '/includes/header_admin.php';

$id     = $_POST['id'] ?? null;
$estado = $_POST['estado'] ?? '';

if (!$id || !in_array($estado, ['disponible', 'agotado'])) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    exit;
}

$stmt = $conn->prepare("UPDATE catalogo_productos SET estado = ? WHERE id = ?");
$stmt->bind_param("si", $estado, $id);
$ok = $stmt->execute();
$stmt->close();

echo json_encode(['success' => $ok]);
