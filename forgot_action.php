<?php
// forgot_action.php
declare(strict_types=1);

require __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/mailer.php'; // helper SMTP (PHPMailer)

// Mensaje genérico (no revelamos si el correo existe o no)
$_SESSION['forgot_flash'] = 'Si el correo existe, te enviaremos un enlace para restablecer tu contraseña. Revisa tu bandeja y spam.';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: forgot.php');
    exit;
}

$email = trim($_POST['email'] ?? '');
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: forgot.php');
    exit;
}

// 1) Buscar usuario por email (traer también posibles nombres de usuario)
$stmt = $conn->prepare("
    SELECT id,
           username
    FROM usuarios
    WHERE email = ?
    LIMIT 1
");
$stmt->bind_param("s", $email);
$stmt->execute();
$res  = $stmt->get_result();
$user = $res->fetch_assoc();
$stmt->close();

if (!$user) {
    // mismo mensaje genérico
    header('Location: forgot.php');
    exit;
}

$userId   = (int)$user['id'];
// Tomar username con fallback a otras columnas habituales
$rawUser  = $user['username'] ?? $user['usuarios'] ?? $user['nombre'] ?? '';
$showUser = $rawUser !== '' ? $rawUser : $email; // última opción: correo
$showUserEsc = htmlspecialchars($showUser, ENT_QUOTES, 'UTF-8');

// 2) Generar token seguro
$rawToken  = bin2hex(random_bytes(32));      // 64 chars público
$tokenHash = hash('sha256', $rawToken);      // lo que guardamos
$expiresAt = (new DateTime('+1 hour'))->format('Y-m-d H:i:s');
$reqIp     = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? ($_SERVER['REMOTE_ADDR'] ?? null);

// (opcional) invalidar tokens previos pendientes del mismo usuario
$conn->query("DELETE FROM password_resets WHERE user_id = {$userId} AND used_at IS NULL");

// 3) Insertar reset
$stmt = $conn->prepare("
    INSERT INTO password_resets (user_id, token_hash, expires_at, request_ip)
    VALUES (?,?,?,?)
");
$stmt->bind_param("isss", $userId, $tokenHash, $expiresAt, $reqIp);
$stmt->execute();
$stmt->close();

// 4) Enviar correo por SMTP (usar includes/mailer.php)
// En local:
$resetUrl = "http://localhost/lofhood/reset.php?token=" . urlencode($rawToken);
// En prod sería: https://tokiramen.cl/reset.php?token=...

$subject = 'Restablecer tu contraseña';
$bodyHtml = '
  <p>Hola <b>'. $showUserEsc .'</b>,</p>
  <p>Recibimos una solicitud para restablecer tu contraseña de Lofhood.</p>
  <p>
    <a href="'. htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8') .'" target="_blank" rel="noopener">
      Haz clic aquí para restablecer tu contraseña
    </a>
  </p>
  <p><small>Enlace válido por 1 hora. Si no fuiste tú, ignora este mensaje.</small></p>
  <p>— Lofhood Soporte</p>
';

// Enviar (si falla, solo registramos en log; el usuario ve mensaje genérico)
if (!mailer_send($email, $subject, $bodyHtml)) {
    error_log('forgot_action: fallo envío SMTP para ' . $email);
}

// Redirigir con el mismo flash genérico
header('Location: forgot.php');
exit;
