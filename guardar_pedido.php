<?php
// guardar_pedido.php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');
session_start();

require __DIR__ . '/includes/conexion.php';
$mysqli = $conn;

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

if (empty($data['items']) || !is_array($data['items'])) {
    http_response_code(400);
    echo json_encode(['success'=>false,'error'=>'Carrito vacío o datos inválidos.']);
    exit;
}

$items          = $data['items'];
$envio          = trim($data['envio'] ?? '');
$nombreEnvio    = trim($data['nombre'] ?? '');
$direccionEnvio = trim($data['direccion'] ?? '');
$comunaEnvio    = trim($data['comuna'] ?? '');
$telefonoEnvio  = trim($data['telefono'] ?? '');
$metodoPago     = trim($data['metodo_pago'] ?? 'webpay'); // 'webpay' | 'transferencia'
$userId         = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

// Invitado: nombre y teléfono obligatorios
if ($userId === null && ($nombreEnvio === '' || $telefonoEnvio === '')) {
    http_response_code(400);
    echo json_encode(['success'=>false,'error'=>'Ingresa tu nombre y teléfono para continuar.']);
    exit;
}

// Total productos
$total = 0.0;
foreach ($items as $it) {
    $p = (float)($it['precio'] ?? 0);
    $c = (int)($it['cantidad'] ?? 0);
    if ($p > 0 && $c > 0) $total += $p * $c;
}
if ($total <= 0) {
    http_response_code(400);
    echo json_encode(['success'=>false,'error'=>'Total de productos inválido.']);
    exit;
}

// Envíos y tarifas
$permitidos = ['retiro-quilicura','domicilio-quilicura','retiro-puente','paket'];
if (!in_array($envio, $permitidos, true)) {
    http_response_code(400);
    echo json_encode(['success'=>false,'error'=>'Opción de envío inválida.']);
    exit;
}

$shipping = 0.0;
if ($envio === 'paket') {
    $shipping = 3000.0;
    if ($nombreEnvio === '' || $direccionEnvio === '' || $comunaEnvio === '' || $telefonoEnvio === '') {
        http_response_code(400);
        echo json_encode(['success'=>false,'error'=>'Completa nombre, dirección, comuna y teléfono para Paket.']);
        exit;
    }
} elseif ($envio === 'domicilio-quilicura') {
    if ($direccionEnvio === '' || $comunaEnvio === '' || $telefonoEnvio === '') {
        http_response_code(400);
        echo json_encode(['success'=>false,'error'=>'Completa dirección, sector y teléfono para Quilicura.']);
        exit;
    }
    $sector = mb_strtolower($comunaEnvio,'UTF-8');
    if ($sector === 'quilicura') {
        $shipping = 2500.0; $comunaEnvio = 'Quilicura';
    } elseif ($sector === 'valle grande') {
        $shipping = 4000.0; $comunaEnvio = 'Valle Grande';
    } elseif ($sector === 'valle lo campino' || $sector === 'valle lo campiño') {
        $shipping = 3000.0; $comunaEnvio = 'Valle Lo Campino';
    } else {
        http_response_code(400);
        echo json_encode(['success'=>false,'error'=>'Sector de Quilicura inválido.']);
        exit;
    }
} else {
    $shipping = 0.0; // retiros
}

$totalConEnvio = $total + $shipping;

// Estado según método de pago
$estado = ($metodoPago === 'transferencia') ? 'pendiente-transferencia' : 'pendiente';

try {
    $mysqli->begin_transaction();

    $stmt = $mysqli->prepare("
        INSERT INTO pedidos
        (fecha, total, estado, envio, usuario_id, nombre_envio, direccion_envio, comuna_envio, telefono_envio)
        VALUES (NOW(), ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    if (!$stmt) throw new Exception("Prepare pedidos: ".$mysqli->error);

    // d ss i s s s s
    $stmt->bind_param(
        'dssissss',
        $totalConEnvio,
        $estado,
        $envio,
        $userId,
        $nombreEnvio,
        $direccionEnvio,
        $comunaEnvio,
        $telefonoEnvio
    );
    if (!$stmt->execute()) throw new Exception("Execute pedidos: ".$stmt->error);
    $pedido_id = $stmt->insert_id;
    $stmt->close();

    $stmtD = $mysqli->prepare("
        INSERT INTO detalle_pedido (pedido_id, nombre_producto, precio, cantidad, subtotal)
        VALUES (?, ?, ?, ?, ?)
    ");
    if (!$stmtD) throw new Exception("Prepare detalle: ".$mysqli->error);

    foreach ($items as $it) {
        $nombreProd = (string)$it['nombre'];
        $precio     = (float)$it['precio'];
        $cantidad   = (int)$it['cantidad'];
        $subtotal   = $precio * $cantidad;
        $stmtD->bind_param('isdid', $pedido_id, $nombreProd, $precio, $cantidad, $subtotal);
        if (!$stmtD->execute()) throw new Exception("Execute detalle: ".$stmtD->error);
    }
    $stmtD->close();

    $mysqli->commit();

    echo json_encode([
        'success'   => true,
        'pedido_id' => $pedido_id,
        'total'     => number_format($totalConEnvio, 0, ',', '.')
    ]);
} catch (Exception $e) {
    $mysqli->rollback();
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>'Error al guardar pedido: '.$e->getMessage()]);
}

$mysqli->close();
