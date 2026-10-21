<?php
require_once __DIR__ . '/vendor/autoload.php';

use Transbank\Webpay\WebpayPlus\Transaction;

// Cargar configuración
$config = require __DIR__ . '/transbank_config.php';

$token = $_GET['token_ws'] ?? null;

if (!$token) {
    echo "❌ Token no recibido.";
    exit;
}

try {
    $transaction = new Transaction($config);
    $response = $transaction->commit($token);

    echo "<h1>✅ Pago exitoso</h1>";
    echo "<p><strong>Orden:</strong> " . $response->getBuyOrder() . "</p>";
    echo "<p><strong>Estado:</strong> " . $response->getStatus() . "</p>";
    echo "<p><strong>Fecha:</strong> " . $response->getAccountingDate() . "</p>";
    echo "<p><strong>Monto:</strong> $" . number_format($response->getAmount(), 0, ',', '.') . "</p>";
} catch (Exception $e) {
    echo "❌ Error al procesar el pago: " . $e->getMessage();
}
