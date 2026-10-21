<?php
declare(strict_types=1);
session_start();

use Transbank\Webpay\WebpayPlus\Transaction;
use Transbank\Webpay\Options;

require_once __DIR__ . '/vendor/autoload.php';

try {
  $options = require __DIR__ . '/transbank_config.php';
  if (!$options instanceof Options) {
    throw new RuntimeException('transbank_config.php no retornó Options.');
  }
} catch (Throwable $e) {
  http_response_code(500);
  echo "<h2>Error de configuración Transbank</h2>";
  echo "<pre>".htmlspecialchars($e->getMessage())."</pre>";
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo "<h3>Método no permitido</h3>";
  exit;
}

$buyOrder  = trim($_POST['buy_order'] ?? '');
$amountStr = trim($_POST['amount'] ?? '');
$amount    = (int)preg_replace('/[^\d]/','', $amountStr);

if ($buyOrder === '' || $amount <= 0) {
  http_response_code(400);
  echo "<h3>Faltan datos para crear la transacción.</h3>";
  exit;
}

$sessionId = session_id();


try {
  $tx   = new Transaction($options);
  $resp = $tx->create($buyOrder, $sessionId, $amount, $returnUrl);
  $url   = $resp->getUrl();
  $token = $resp->getToken();
} catch (Throwable $e) {
  http_response_code(500);
  echo "<h2>Error al crear la transacción</h2>";
  echo "<pre>".htmlspecialchars($e->getMessage())."</pre>";
  exit;
}
?>
<!doctype html>
<html lang="es">
<head><meta charset="utf-8"><title>Redirigiendo a Webpay</title></head>
<body onload="document.forms[0].submit()" style="font-family:sans-serif;padding:40px;text-align:center;">
  <p>Redirigiendo a Webpay…</p>
  <form action="<?= htmlspecialchars($url, ENT_QUOTES) ?>" method="post">
    <input type="hidden" name="token_ws" value="<?= htmlspecialchars($token, ENT_QUOTES) ?>">
    <noscript><button type="submit">Continuar al pago</button></noscript>
  </form>
</body>
</html>
