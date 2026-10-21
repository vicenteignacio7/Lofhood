<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require __DIR__ . "/includes/conexion.php";

if (empty($_SESSION['user_id'])) {
    header("Location: login.php?msg=Debes iniciar sesión&type=error");
    exit;
}

$usuario_id = (int)$_SESSION['user_id'];
$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    header("Location: publicaciones.php?msg=Trabajo no válido&type=error");
    exit;
}

// Verificar que el trabajo exista y que el usuario sea dueño
$stmt = $conn->prepare("
    SELECT p.*, u.username 
    FROM publicaciones p
    INNER JOIN usuarios u ON p.usuario_id = u.id
    WHERE p.id = ? AND p.categoria = 'trabajo'
    LIMIT 1
");
$stmt->bind_param("i", $id);
$stmt->execute();
$pub = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$pub) {
    header("Location: publicaciones.php?msg=Trabajo no encontrado&type=error");
    exit;
}

if ((int)$pub['usuario_id'] !== $usuario_id) {
    header("Location: ver_publicacion.php?id={$id}&cat=trabajo&msg=No puedes ver estos postulantes&type=error");
    exit;
}

// Obtener postulantes
$q = $conn->prepare("
    SELECT t.*, u.username
    FROM trabajo_postulantes t
    INNER JOIN usuarios u ON t.usuario_id = u.id
    WHERE t.publicacion_id = ?
    ORDER BY t.fecha_postulacion ASC
");
$q->bind_param("i", $id);
$q->execute();
$res = $q->get_result();
$postulantes = $res->fetch_all(MYSQLI_ASSOC);
$res->free();
$q->close();

include __DIR__ . "/includes/header.php";
?>

<section class="form-section">
  <h2 class="form-title">Postulantes a: <span><?= htmlspecialchars($pub['titulo']) ?></span></h2>

  <?php if (empty($postulantes)): ?>
      <p>No hay postulantes aún.</p>
  <?php else: ?>
      <table class="tabla-participantes">
          <tr>
            <th>Usuario</th>
            <th>Fecha</th>
            <th>Mensaje</th>
            <th>Experiencia</th>
            <th>Disponibilidad</th>
            <th>Correo</th>
            <th>CV</th>
          </tr>
          <?php foreach ($postulantes as $p): ?>
          <tr>
            <td>@<?= htmlspecialchars($p['username']) ?></td>
            <td><?= date("d/m/Y H:i", strtotime($p['fecha_postulacion'])) ?></td>
            <td><?= nl2br(htmlspecialchars($p['mensaje'])) ?></td>
            <td><?= nl2br(htmlspecialchars($p['experiencia'])) ?></td>
            <td><?= htmlspecialchars($p['disponibilidad']) ?></td>
            <td><?= htmlspecialchars($p['contacto_correo']) ?></td>
            <td>
              <?php if (!empty($p['cv_url'])): ?>
                <a href="<?= htmlspecialchars($p['cv_url']) ?>" target="_blank">Ver CV</a>
              <?php else: ?>
                -
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
      </table>
  <?php endif; ?>

  <a href="ver_publicacion.php?id=<?= (int)$pub['id'] ?>&cat=trabajo" class="btn-volver form-back">← Volver al aviso</a>
</section>

<?php include __DIR__ . "/includes/footer.php"; ?>
