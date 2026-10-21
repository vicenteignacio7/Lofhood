<?php
require __DIR__ . '/../includes/init.php';
require __DIR__ . '/../includes/mailer.php';

header('Content-Type: text/plain; charset=utf-8');

$to = 'tu_gmail_o_correo_destino@loquesea.com'; // pon aquí tu correo de prueba
$ok = mailer_send($to, 'Prueba SMTP Toki Ramen', '<p>Si lees esto, el envío funciona.</p>');

echo $ok ? "OK: enviado a $to\n" : "ERROR: no se pudo enviar. Revisa /public_html/error_log\n";
