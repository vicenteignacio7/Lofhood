<?php
// confirmar_pago.php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/vendor/autoload.php';
use Transbank\Webpay\WebpayPlus\Transaction;

// Configuración Transbank
$options = require __DIR__ . '/transbank_config.php';

// Conexión a DB (debe definir $conn = new mysqli(...) )
require_once __DIR__ . '/includes/conexion.php';

// 1) Capturar parámetros de retorno
$wsToken   = $_REQUEST['token_ws']          ?? null; // pago normal
$tbkToken  = $_REQUEST['TBK_TOKEN']         ?? null; // cancelado
$tbkOrder  = $_REQUEST['TBK_ORDEN_COMPRA']  ?? ($_REQUEST['TBK_ORDEN_COMRA'] ?? null);
$tbkSesion = $_REQUEST['TBK_ID_SESION']     ?? null;

$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$ipAddress = $_SERVER['REMOTE_ADDR']     ?? '';
$nowCart   = $_SESSION['carrito'] ?? null;

// 2) Cancelación
if ($tbkToken && $tbkOrder && $tbkSesion) {
    $stmt = $conn->prepare("
        INSERT INTO pagos (order_id, session_id, amount, authorization_code, status, user_agent, ip_address)
        VALUES (?, ?, 0, '', 'CANCELLED', ?, ?)
    ");
    $stmt->bind_param('ssss', $tbkOrder, $tbkSesion, $userAgent, $ipAddress);
    $stmt->execute();
    $stmt->close();

    echo "<h2>⚠️ Pago Cancelado</h2><p>Orden {$tbkOrder} cancelada.</p>";
    exit;
}

// 3) Timeout
if ($tbkSesion && $tbkOrder && !$tbkToken && !$wsToken) {
    $stmt = $conn->prepare("
        INSERT INTO pagos (order_id, session_id, amount, authorization_code, status, user_agent, ip_address)
        VALUES (?, ?, 0, '', 'TIMEOUT', ?, ?)
    ");
    $stmt->bind_param('ssss', $tbkOrder, $tbkSesion, $userAgent, $ipAddress);
    $stmt->execute();
    $stmt->close();

    echo "<h2>⏳ Tiempo agotado</h2><p>No se completó el pago de la orden {$tbkOrder}.</p>";
    exit;
}

// 4) Normal (commit usando token_ws)
if (!$wsToken) {
    http_response_code(400);
    echo "<h2 style='color:red'>❌ No se recibió token_ws</h2>";
    exit;
}

try {
    $tx       = new Transaction($options);
    $response = $tx->commit($wsToken);

    $buyOrder          = (int)$response->getBuyOrder();
    $amount            = (float)$response->getAmount();
    $authorizationCode = (string)$response->getAuthorizationCode();
    $status            = (string)$response->getStatus();
    $sessionId         = session_id();

    // Guardar en tabla pagos
    $stmt = $conn->prepare("
        INSERT INTO pagos (order_id, session_id, amount, authorization_code, status, user_agent, ip_address)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param(
        'ssdssss',
        $buyOrder,
        $sessionId,
        $amount,
        $authorizationCode,
        $status,
        $userAgent,
        $ipAddress
    );
    $stmt->execute();
    $stmt->close();

    // 🔹 Si pago autorizado → marcar pedido confirmado
    if ($status === 'AUTHORIZED') {
        $stmt2 = $conn->prepare("UPDATE pedidos SET pago_confirmado=1, estado='pendiente' WHERE id=?");
        if ($stmt2) {
            $stmt2->bind_param('i', $buyOrder);
            $stmt2->execute();
            $stmt2->close();
        }

        unset($_SESSION['carrito']);
        header('Location: gracias.php?order_id=' . urlencode($buyOrder));
        exit;
    }

    // Rechazado
    $amtFmt = number_format((float)$amount, 0, ',', '.');
    echo "<h2>❌ Pago Rechazado</h2>
          <p>Orden: {$buyOrder}</p>
          <p>Monto: \${$amtFmt}</p>
          <p>Estado: {$status}</p>
          <p><a href='index.php'>Volver</a></p>";

} catch (\Throwable $e) {
    $msg = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    echo "<h2 style='color:red'>❌ Error al procesar el pago</h2><pre>{$msg}</pre>";
}
