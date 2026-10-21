<?php
// login_action.php
declare(strict_types=1);
session_start();

// Cargar entorno y conexión
require __DIR__ . '/includes/init.php';

// reCAPTCHA
require_once __DIR__ . '/includes/recaptcha.php';

// Validar reCAPTCHA
$secret = getenv('RECAPTCHA_SECRET_KEY') ?: ($_ENV['RECAPTCHA_SECRET_KEY'] ?? '');
$captchaResponse = $_POST['g-recaptcha-response'] ?? '';
$ip = $_SERVER['REMOTE_ADDR'] ?? '';

if ($secret === '' || !verifyRecaptcha($secret, $captchaResponse, $ip)) {
    $_SESSION['login_error'] = 'Por favor, marca el captcha.';
    header('Location: login.php');
    exit;
}

// Recibir datos
$user = $_POST['username'] ?? '';
$pass = $_POST['password'] ?? '';

$stmt = $conn->prepare("SELECT id, password, role FROM usuarios WHERE username = ?");
$stmt->bind_param("s", $user);
$stmt->execute();
$stmt->bind_result($id, $hash, $role);

// Login OK
if ($stmt->fetch() && password_verify($pass, $hash)) {
    $_SESSION['user_id'] = $id;
    $_SESSION['username'] = $user;
    $_SESSION['role'] = $role;
    $stmt->close();

 $redirect = trim($_POST['redirect'] ?? '');

$prohibidos = ['login.php', 'register.php', 'logout.php'];

if ($redirect && !preg_match('/https?:\/\//', $redirect)) {
    $bloquear = false;
    foreach ($prohibidos as $p) {
        if (str_contains($redirect, $p)) {
            $bloquear = true;
            break;
        }
    }
    if (!$bloquear) {
        header("Location: $redirect");
        exit;
    }
}

// Intento extra: revisar página anterior
if (!empty($_SERVER['HTTP_REFERER'])) {
    $ref = $_SERVER['HTTP_REFERER'];
    foreach ($prohibidos as $p) {
        if (str_contains($ref, $p)) {
            $ref = '';
            break;
        }
    }
    if ($ref) {
        header("Location: $ref");
        exit;
    }
}

// Último fallback seguro
header("Location: index.php");
exit;


// Evitar redirigir de vuelta a login o register
$prohibidos = ['login.php', 'register.php', 'logout.php'];
foreach ($prohibidos as $p) {
    if (str_contains($redirect, $p)) {
        $redirect = '';
        break;
    }
}

// Si existe redirect válido → usarlo
if ($redirect !== '' && strpos($redirect, '://') === false) {
    header("Location: $redirect");
    exit;
}

// Si no, intentar usar la página anterior
if (!empty($_SERVER['HTTP_REFERER'])) {
    $ref = $_SERVER['HTTP_REFERER'];
    $bloquear = false;
    foreach ($prohibidos as $p) {
        if (str_contains($ref, $p)) $bloquear = true;
    }
    if (!$bloquear) {
        header("Location: $ref");
        exit;
    }
}

// Último recurso → Inicio
header("Location: index.php");
exit;


// Si viene desde modal o desde cualquier parte → volver allí
if ($redirect !== '' && strpos($redirect, '://') === false) {
    header("Location: $redirect");
    exit;
}

// Si no hay redirect, intentar regresar a la página anterior
if (!empty($_SERVER['HTTP_REFERER'])) {
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit;
}

// Último recurso: al inicio
header("Location: index.php");
exit;


// Si no había redirect → volvemos al inicio
header("Location: index.php");
exit;

}

$stmt->close();
$_SESSION['login_error'] = "Usuario o contraseña incorrectos.";
header("Location: login.php");
exit;
