<?php
// includes/mailer.php
declare(strict_types=1);

if (!class_exists(\PHPMailer\PHPMailer\PHPMailer::class)) {
    $base = __DIR__ . '/../vendor/phpmailer/phpmailer/src';
    require_once $base . '/PHPMailer.php';
    require_once $base . '/SMTP.php';
    require_once $base . '/Exception.php';
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function mailer_send(string $to, string $subject, string $html, string $altText = ''): bool {
    $fromEmail = $_ENV['MAILER_FROM'] ?? $_ENV['MAILER_USER'] ?? '';
    $fromName  = $_ENV['MAILER_FROM_NAME'] ?? 'Lofhood Soporte';

    if ($altText === '') {
        $altText = trim(preg_replace('/\s+/', ' ', strip_tags($html)));
    }

    $mail = new PHPMailer(true);

    try {
        // Debug si MAILER_DEBUG=1 en .env
        $debug = (int)($_ENV['MAILER_DEBUG'] ?? 0);
        $mail->SMTPDebug  = $debug ? 2 : 0;
        $mail->Debugoutput = 'error_log';

        $mail->isSMTP();
        $mail->Host       = $_ENV['MAILER_HOST'];     // smtp.gmail.com
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['MAILER_USER'];     // tu_correo@gmail.com
        $mail->Password   = $_ENV['MAILER_PASS'];     // APP PASSWORD
        $mail->SMTPSecure = 'tls';                    // siempre TLS en Gmail
        $mail->Port       = 587;                      // siempre 587 en Gmail
        $mail->CharSet    = 'UTF-8';
        $mail->Timeout    = 20;

        // PARA LOCAL: evitar problemas con certificados SSL
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true,
            ]
        ];

        // FROM debe ser el Gmail
        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($to);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $html;
        $mail->AltBody = $altText;

        return $mail->send();

    } catch (Exception $e) {
        error_log('mailer_send SMTP error: ' . $e->getMessage());
        return false;
    }
}
