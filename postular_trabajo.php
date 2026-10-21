<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require __DIR__ . "/includes/conexion.php";

if (empty($_SESSION['user_id'])) {
    $redirect = "postular_trabajo.php?id=" . urlencode($_GET['id'] ?? 0);
    header("Location: login.php?redirect={$redirect}&msg=Debes iniciar sesión para postular&type=error");
    exit;
}

$usuario_id = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $publicacion_id   = intval($_POST['publicacion_id'] ?? 0);
    $mensaje          = trim($_POST['mensaje'] ?? '');
    $experiencia_post = trim($_POST['experiencia'] ?? '');
    $disponibilidad   = trim($_POST['disponibilidad'] ?? '');
    $contacto_correo  = trim($_POST['contacto_correo'] ?? '');

    if ($publicacion_id <= 0 || $mensaje === '' || $disponibilidad === '' || $contacto_correo === '') {
        header("Location: postular_trabajo.php?id={$publicacion_id}&msg=Completa todos los campos obligatorios&type=error");
        exit;
    }

    // Verificar que la publicación exista y sea de tipo trabajo
    $stmt = $conn->prepare("SELECT * FROM publicaciones WHERE id = ? AND categoria = 'trabajo' LIMIT 1");
    $stmt->bind_param("i", $publicacion_id);
    $stmt->execute();
    $pub = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$pub) {
        header("Location: publicaciones.php?msg=Trabajo no encontrado&type=error");
        exit;
    }

    // No puede postular si es el dueño
    if ((int)$pub['usuario_id'] === $usuario_id) {
        header("Location: ver_publicacion.php?id={$publicacion_id}&cat=trabajo&msg=No puedes postular a tu propio aviso&type=error");
        exit;
    }

    // Revisar si está cerrado
    if ($pub['estado_trabajo'] === 'cerrado') {
        header("Location: ver_publicacion.php?id={$publicacion_id}&cat=trabajo&msg=El trabajo ya no recibe postulaciones&type=error");
        exit;
    }

    // Revisar si ya alcanzó cupos_trabajo
    $total_post = 0;
    $pt = $conn->prepare("SELECT COUNT(*) AS total FROM trabajo_postulantes WHERE publicacion_id = ?");
    $pt->bind_param("i", $publicacion_id);
    $pt->execute();
    $rpt = $pt->get_result()->fetch_assoc();
    $total_post = (int)($rpt['total'] ?? 0);
    $pt->close();

    if (!empty($pub['cupos_trabajo']) && $total_post >= (int)$pub['cupos_trabajo']) {
        header("Location: ver_publicacion.php?id={$publicacion_id}&cat=trabajo&msg=Se alcanzó el máximo de postulantes&type=error");
        exit;
    }

    // Verificar si ya postuló
    $chk = $conn->prepare("SELECT 1 FROM trabajo_postulantes WHERE publicacion_id = ? AND usuario_id = ? LIMIT 1");
    $chk->bind_param("ii", $publicacion_id, $usuario_id);
    $chk->execute();
    $chk->store_result();
    if ($chk->num_rows > 0) {
        $chk->close();
        header("Location: ver_publicacion.php?id={$publicacion_id}&cat=trabajo&msg=Ya postulaste a este trabajo&type=error");
        exit;
    }
    $chk->close();

    // Subir CV (opcional)
    $cv_url = null;
    if (!empty($_FILES['cv']['name']) && is_uploaded_file($_FILES['cv']['tmp_name'])) {
        $allowed_ext = ['pdf'];
        $filename = $_FILES['cv']['name'];
        $tmp      = $_FILES['cv']['tmp_name'];
        $ext      = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($ext, $allowed_ext)) {
            $upload_dir = __DIR__ . "/uploads/cv";
            if (!is_dir($upload_dir)) { @mkdir($upload_dir, 0775, true); }

            $nombre_cv = time() . "_" . preg_replace('/[^a-zA-Z0-9_\.-]/','_', $filename);
            $destino   = $upload_dir . "/" . $nombre_cv;

            if (move_uploaded_file($tmp, $destino)) {
                // guardamos ruta relativa
                $cv_url = "uploads/cv/" . $nombre_cv;
            }
        }
    }

    // Insertar postulación
    $ins = $conn->prepare("
        INSERT INTO trabajo_postulantes
        (publicacion_id, usuario_id, mensaje, experiencia, disponibilidad, cv_url, contacto_correo)
        VALUES (?,?,?,?,?,?,?)
    ");
    $ins->bind_param(
        "iisssss",
        $publicacion_id,
        $usuario_id,
        $mensaje,
        $experiencia_post,
        $disponibilidad,
        $cv_url,
        $contacto_correo
    );
    $ins->execute();
    $ins->close();

    header("Location: ver_publicacion.php?id={$publicacion_id}&cat=trabajo&msg=Postulación enviada&type=success");
    exit;
}

// ===================
// GET: mostrar formulario
// ===================
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: publicaciones.php?msg=Trabajo no encontrado&type=error");
    exit;
}

$stmt = $conn->prepare("SELECT p.*, u.username FROM publicaciones p INNER JOIN usuarios u ON p.usuario_id=u.id WHERE p.id = ? AND p.categoria='trabajo' LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$pub = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$pub) {
    header("Location: publicaciones.php?msg=Trabajo no encontrado&type=error");
    exit;
}

// Ya está logueado arriba
include __DIR__ . "/includes/header.php";
?>

<section class="form-section">
  <h2 class="form-title">Postular al trabajo</h2>

  <div class="pub-resumen">
    <h3><?= htmlspecialchars($pub['titulo']) ?></h3>
    <p><?= nl2br(htmlspecialchars($pub['descripcion'])) ?></p>
  </div>

  <form action="postular_trabajo.php" method="POST" enctype="multipart/form-data" class="publicar-form">
      <input type="hidden" name="publicacion_id" value="<?= (int)$pub['id'] ?>">

      <label>Mensaje breve (por qué te interesa / presentación):</label>
      <textarea name="mensaje" rows="4" required></textarea>

      <label>Experiencia (opcional):</label>
      <textarea name="experiencia" rows="3" placeholder="Cuenta brevemente tu experiencia relacionada"></textarea>

      <label>Disponibilidad (obligatorio):</label>
      <input type="text" name="disponibilidad" placeholder="Ej: Lunes a viernes desde las 18:00" required>

      <label>Correo de contacto:</label>
      <input type="email" name="contacto_correo" placeholder="tu_correo@ejemplo.com" required>

      <label>Adjuntar CV (solo PDF, opcional):</label>
      <input type="file" name="cv" accept="application/pdf">

      <button type="submit" class="btn-submit">Enviar postulación</button>
  </form>

  <a href="ver_publicacion.php?id=<?= (int)$pub['id'] ?>&cat=trabajo" class="btn-volver form-back">← Volver al aviso</a>
</section>

<?php include __DIR__ . "/includes/footer.php"; ?>
