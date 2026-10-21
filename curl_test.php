<?php
$url = "https://webpay3gint.transbank.cl/rswebpaytransaction/api/webpayplus/v1";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_NOBODY, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($response === false) {
    echo "❌ Error al conectar con Transbank: " . $error;
} else {
    echo "✅ Conexión exitosa. Código HTTP: $httpCode";
}
?>
