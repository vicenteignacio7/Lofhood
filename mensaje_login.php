<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include __DIR__ . "/includes/header.php";

$redirect = $_GET['redirect'] ?? 'index.php';
?>

<section style="padding-top:120px; text-align:center;">
  <h2 style="margin-bottom: 10px;">Debes iniciar sesión</h2>
  <p style="margin-bottom:25px;">Para crear una publicación necesitas estar registrado.</p>

  <a href="login.php?redirect=<?= urlencode($redirect) ?>" 
     class="btn-header bordered" 
     style="margin-right:8px;">Iniciar sesión</a>

  <a href="<?= htmlspecialchars($redirect) ?>" 
     class="btn-header bordered" 
     style="background:#ccc;">Seguir navegando</a>
</section>

<?php include __DIR__ . "/includes/footer.php"; ?>
