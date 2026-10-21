<?php
// includes/recaptcha.php
function verifyRecaptcha(string $secret, string $response, string $ip = ''): bool {
    if ($response === '' || $secret === '') return false;

    $endpoint = 'https://www.google.com/recaptcha/api/siteverify';
    $postData = http_build_query([
        'secret'   => $secret,
        'response' => $response,
        'remoteip' => $ip,
    ]);

    // Usa cURL si está disponible
    if (function_exists('curl_init')) {
        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $postData,
            CURLOPT_TIMEOUT        => 10,
        ]);
        $out = curl_exec($ch);
        curl_close($ch);
    } else {
        // Fallback a file_get_contents
        $context = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => $postData,
                'timeout' => 10,
            ]
        ]);
        $out = @file_get_contents($endpoint, false, $context);
    }

    if (!$out) return false;
    $json = json_decode($out, true);
    return !empty($json['success']);
}
