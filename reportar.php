<?php
declare(strict_types=1);

// Ajusta esto según tu estructura real. A veces usas conexion.php, a veces init.php.
// Si init.php ya incluye conexion.php, déjalo así. Si no, agrega la conexión.
if (file_exists(__DIR__ . '/includes/init.php')) {
    require __DIR__ . '/includes/init.php';
} else {
    session_start();
    require __DIR__ . '/includes/conexion.php';
}

require __DIR__ . '/includes/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/includes/PHPMailer/src/SMTP.php';
require __DIR__ . '/includes/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// --- 1. Validar sesión y datos ---
if (empty($_SESSION['user_id'])) {
    header("Location: login.php?msg=Debes iniciar sesión&type=error");
    exit;
}

$publicacion_id = isset($_POST['publicacion_id']) ? (int)$_POST['publicacion_id'] : 0;
$motivo = isset($_POST['motivo']) ? trim($_POST['motivo']) : '';
$usuario_id = (int)$_SESSION['user_id'];

if ($publicacion_id < 1 || strlen($motivo) < 2) {
    header("Location: publicaciones.php?msg=Reporte inválido&type=error");
    exit;
}

// --- 2. Validar que no se auto-reporte ---
$stmt = $conn->prepare("SELECT titulo, usuario_id FROM publicaciones WHERE id = ?");
$stmt->bind_param("i", $publicacion_id);
$stmt->execute();
$stmt->bind_result($titulo_pub, $dueno_id);
if (!$stmt->fetch()) {
    $stmt->close();
    header("Location: publicaciones.php?msg=Publicación no encontrada&type=error");
    exit;
}
$stmt->close();

if ($dueno_id == $usuario_id) {
    header("Location: ver_publicacion.php?id={$publicacion_id}&msg=No puedes reportar tu propia publicación&type=error");
    exit;
}

// --- 3. Evitar duplicados en la BD ---
// Verifica si este usuario ya reportó esta publicación
$check = $conn->prepare("SELECT id FROM reportes WHERE publicacion_id = ? AND usuario_id = ?");
$check->bind_param("ii", $publicacion_id, $usuario_id);
$check->execute();
$check->store_result();
if ($check->num_rows > 0) {
    $check->close();
    header("Location: ver_publicacion.php?id={$publicacion_id}&msg=Ya reportaste esta publicación anteriormente&type=info");
    exit;
}
$check->close();

// --- 4. INSERTAR EN LA BASE DE DATOS (¡ESTO FALTABA!) ---
// Esto es lo que permite que aparezca en el Dashboard
$ins = $conn->prepare("INSERT INTO reportes (publicacion_id, usuario_id, motivo, fecha) VALUES (?, ?, ?, NOW())");
$ins->bind_param("iis", $publicacion_id, $usuario_id, $motivo);

if (!$ins->execute()) {
    // Si falla la BD, abortamos
    header("Location: ver_publicacion.php?id={$publicacion_id}&msg=Error al guardar reporte&type=error");
    exit;
}
$ins->close();

// --- 5. Obtener datos del usuario para el correo ---
$user_stmt = $conn->prepare("SELECT username, email FROM usuarios WHERE id = ?");
$user_stmt->bind_param("i", $usuario_id);
$user_stmt->execute();
$user_stmt->bind_result($nombre_usuario, $correo_usuario);
$user_stmt->fetch();
$user_stmt->close();

// --- 6. Enviar Correo (PHPMailer) ---
$mail = new PHPMailer(true);

try {
    // Configuración del servidor SMTP
    // Asegúrate de que estas variables de entorno estén cargadas (vía .env o init.php)
    // Si no usas variables de entorno, pon los datos 'quemados' aquí temporalmente para probar
    $mail->isSMTP();
    $mail->Host       = $_ENV['MAILER_HOST'] ?? 'smtp.gmail.com'; // Fallback ejemplo
    $mail->SMTPAuth   = true;
    $mail->Username   = $_ENV['MAILER_USER'] ?? 'tu_correo@gmail.com';
    $mail->Password   = $_ENV['MAILER_PASS'] ?? 'tu_contraseña';
    $mail->SMTPSecure = $_ENV['MAILER_SECURE'] ?? 'tls';
    $mail->Port       = (int)($_ENV['MAILER_PORT'] ?? 587);

    // Destinatarios
    $mail->setFrom($_ENV['MAILER_FROM'] ?? $mail->Username, 'LofHood Alertas');
    $mail->addAddress('vicente.sepulvedap@gmail.com', 'Admin LofHood');

    // Contenido
    $mail->isHTML(true);
    $mail->CharSet = 'UTF-8';
    $mail->Subject = "🚨 Reporte: " . substr($titulo_pub, 0, 30) . "...";
    $mail->Body    = "
        <div style='font-family: Arial, sans-serif; color: #333;'>
            <h2 style='color: #d9534f;'>Nueva Publicación Reportada</h2>
            <p>Un usuario ha reportado contenido inapropiado o sospechoso.</p>
            <hr>
            <p><strong>Publicación ID:</strong> {$publicacion_id}</p>
            <p><strong>Título:</strong> {$titulo_pub}</p>
            <p><strong>Reportado por:</strong> {$nombre_usuario} ({$correo_usuario})</p>
            <div style='background: #f9f9f9; padding: 15px; border-left: 4px solid #d9534f; margin: 10px 0;'>
                <strong>Motivo:</strong><br>
                " . nl2br(htmlspecialchars($motivo)) . "
            </div>
            <p>
                <a href='http://localhost/lofhood/ver_publicacion.php?id={$publicacion_id}' 
                   style='background: #333; color: #fff; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>
                   Revisar Publicación
                </a>
            </p>
            <p style='font-size: 12px; color: #777;'>Este reporte también se ha guardado en el Dashboard.</p>
        </div>
    ";

    $mail->send();

} catch (Exception $e) {
    // Si falla el correo, no detenemos el flujo porque YA SE GUARDÓ en la BD.
    // Solo registramos el error en el log de PHP.
    error_log("Error enviando correo de reporte: " . $mail->ErrorInfo);
}

// --- 7. Redirección Final ---
header("Location: ver_publicacion.php?id={$publicacion_id}&msg=Reporte recibido correctamente&type=success");
exit;
?>