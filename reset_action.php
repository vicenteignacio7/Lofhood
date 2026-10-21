<?php
// reset_action.php
declare(strict_types=1);
require __DIR__ . '/includes/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: login.php'); exit;
}

$token = $_POST['token'] ?? '';
$pass1 = $_POST['password'] ?? '';
$pass2 = $_POST['password2'] ?? '';

if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
  $_SESSION['login_error'] = 'Token inválido.';
  header('Location: login.php'); exit;
}
if ($pass1 === '' || $pass2 === '' || $pass1 !== $pass2 || strlen($pass1) < 6) {
  $_SESSION['login_error'] = 'Contraseña inválida o no coincide.';
  header('Location: login.php'); exit;
}

$tokenHash = hash('sha256', $token);

// 1) Buscar token válido
$stmt = $conn->prepare("SELECT pr.id, pr.user_id, pr.expires_at, pr.used_at
                        FROM password_resets pr
                        WHERE pr.token_hash = ? LIMIT 1");
$stmt->bind_param("s", $tokenHash);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$stmt->close();

if (!$row) {
  $_SESSION['login_error'] = 'Enlace inválido.';
  header('Location: login.php'); exit;
}

// 2) Validaciones
$now = new DateTime();
$exp = new DateTime($row['expires_at']);
if (!empty($row['used_at']) || $now > $exp) {
  $_SESSION['login_error'] = 'El enlace expiró o ya fue usado.';
  header('Location: login.php'); exit;
}

$userId = (int)$row['user_id'];

// 3) Cambiar contraseña
$newHash = password_hash($pass1, PASSWORD_DEFAULT);
$stmt = $conn->prepare("UPDATE usuarios SET password = ? WHERE id = ? LIMIT 1");
$stmt->bind_param("si", $newHash, $userId);
$stmt->execute();
$stmt->close();

// 4) Marcar token como usado
$usedAt = (new DateTime())->format('Y-m-d H:i:s');
$stmt = $conn->prepare("UPDATE password_resets SET used_at = ? WHERE token_hash = ? LIMIT 1");
$stmt->bind_param("ss", $usedAt, $tokenHash);
$stmt->execute();
$stmt->close();

// 5) Refuerzo de sesión
session_regenerate_id(true);

// 6) Mensaje y a login
$_SESSION['login_error'] = 'Contraseña actualizada. Inicia sesión con tu nueva contraseña.';
header('Location: login.php'); exit;
