<?php
// TIENDA/gracias.php
declare(strict_types=1);

// Inicia sesión si quieres mostrar datos de usuario (opcional)
session_start();

// Recupera el order_id desde la URL
$orderId = $_GET['order_id'] ?? '';

// Formatea el mensaje
$orderEscaped = htmlspecialchars($orderId);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>¡Gracias por tu compra!</title>
  <style>
    body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; text-align: center; }
    h1 { color: #2c3e50; }
    p { font-size: 1.1rem; }
    .btn { display: inline-block; margin-top: 20px; padding: 10px 20px; background: #27ae60; color: #fff; text-decoration: none; border-radius: 5px; }
    .btn:hover { background: #229954; }
  </style>
</head>
<body>
  <h1>¡Gracias por tu compra!</h1>
  <?php if ($orderId): ?>
    <p>Tu orden <strong>#<?= $orderEscaped ?></strong> ha sido procesada exitosamente.</p>
  <?php else: ?>
    <p>Hemos recibido tu pago correctamente.</p>
  <?php endif; ?>
  <a href="index.php" class="btn">Seguir comprando</a>
</body>
</html>
