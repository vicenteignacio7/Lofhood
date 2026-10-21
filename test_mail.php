<?php
require __DIR__ . '/includes/init.php';
require __DIR__ . '/includes/mailer.php';

header('Content-Type: text/plain; charset=utf-8');

$to  = $_ENV['MAILER_USER'] ?? 'soporte@tokiramen.cl';
$ok  = mailer_send($to, 'Prueba SMTP Toki Ramen', '<p>Si lees esto, SMTP funciona.</p>');

echo $ok ? "OK: enviado a $to\n" : "ERROR: revisar public_html/error_log\n";
