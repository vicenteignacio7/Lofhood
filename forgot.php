<?php
// forgot.php
require __DIR__ . '/includes/init.php';
include __DIR__ . '/includes/header.php';

// Mensajes flash (siempre genérico)
$flash = $_SESSION['forgot_flash'] ?? '';
unset($_SESSION['forgot_flash']);
?>
<style>
  .forgot-container { background:#fff; padding:30px 40px; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,.1); width:100%; max-width:400px; margin:40px auto; }
  .forgot-container h2 { margin-top:0; text-align:center; color:#333; }
  .forgot-container label { display:block; margin:15px 0 5px; font-weight:bold; color:#555; }
  .forgot-container input { width:100%; padding:8px 10px; border:1px solid #ccc; border-radius:4px; font-size:14px; }
  .forgot-container .btn { display:block; width:100%; margin-top:20px; padding:10px; background:#43a047; color:#fff; border:none; border-radius:4px; font-size:16px; cursor:pointer; }
  .alert { background:#e8f5e9; color:#2e7d32; padding:10px; border-radius:4px; margin-bottom:15px; text-align:center; }
</style>

<div class="forgot-container">
  <h2>Recuperar Contraseña</h2>

  <?php if ($flash): ?>
    <div class="alert"><?= htmlspecialchars($flash) ?></div>
  <?php endif; ?>

  <form method="POST" action="forgot_action.php" autocomplete="off">
    <label for="email">Correo registrado</label>
    <input type="email" name="email" id="email" required placeholder="tucorreo@ejemplo.com">
    <button type="submit" class="btn">Enviar enlace</button>

    <div style="margin-top:10px; text-align:center;">
      <a href="login.php">Volver al login</a>
    </div>
  </form>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
