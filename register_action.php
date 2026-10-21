<?php
// register_action.php
declare(strict_types=1);

require __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/recaptcha.php';

// 0) Validar reCAPTCHA antes de procesar
$secret          = getenv('RECAPTCHA_SECRET_KEY') ?: ($_ENV['RECAPTCHA_SECRET_KEY'] ?? '');
$captchaResponse = $_POST['g-recaptcha-response'] ?? '';
$ip              = $_SERVER['REMOTE_ADDR'] ?? '';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: register.php');
  exit;
}

if ($secret === '' || !verifyRecaptcha($secret, $captchaResponse, $ip)) {
  $_SESSION['register_error'] = 'Por favor, marca el captcha.';
  header('Location: register.php');
  exit;
}

// 1) Recoger y sanear
$user   = trim($_POST['username']    ?? '');
$email  = trim($_POST['email']       ?? '');
$phone  = trim($_POST['phone']       ?? '');
$pass1  = $_POST['password']         ?? '';
$pass2  = $_POST['password_confirm'] ?? '';

// 2) Validaciones básicas
if ($user === '' || $email === '' || $phone === '' || $pass1 === '' || $pass2 === '') {
  $_SESSION['register_error'] = 'Todos los campos son obligatorios.';
  header('Location: register.php');
  exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  $_SESSION['register_error'] = 'El correo no es válido.';
  header('Location: register.php');
  exit;
}
if ($pass1 !== $pass2) {
  $_SESSION['register_error'] = 'Las contraseñas no coinciden.';
  header('Location: register.php');
  exit;
}

// 3) Comprobar duplicados (usuario o email)
$stmt = $conn->prepare("SELECT id FROM usuarios WHERE username = ? OR email = ?");
$stmt->bind_param("ss", $user, $email);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
  $_SESSION['register_error'] = 'El usuario o el correo ya están registrados.';
  $stmt->close();
  header('Location: register.php');
  exit;
}
$stmt->close();
$acepto = isset($_POST['acepto_terminos']) ? 1 : 0;

// 4) Insertar nuevo usuario
$hash = password_hash($pass1, PASSWORD_DEFAULT);
$role = 'cliente';
$stmt = $conn->prepare("
  INSERT INTO usuarios (username, email, phone, password, role, acepto_terminos, created_at)
  VALUES (?, ?, ?, ?, ?, ?, NOW())
");
$stmt->bind_param("sssssi", $user, $email, $phone, $hash, $role, $acepto);

if (!$stmt->execute()) {
  $_SESSION['register_error'] = 'Error al registrar usuario: ' . $stmt->error;
  $stmt->close();
  header('Location: register.php');
  exit;
}
$newId = $stmt->insert_id;
$stmt->close();

// 5) Auto-login
$_SESSION['user_id']  = $newId;
$_SESSION['username'] = $user;
$_SESSION['role']     = $role;

// 6) Redirigir a inicio
header('Location: index.php');
exit;
