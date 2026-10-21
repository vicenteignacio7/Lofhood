<?php
// login.php

// 1) Iniciar sesión y cargar entorno/DB
require __DIR__ . '/includes/init.php';

// 2) Header
include __DIR__ . '/includes/header.php';

// 3) reCAPTCHA
require_once __DIR__ . '/includes/recaptcha.php';
$siteKey = getenv('RECAPTCHA_SITE_KEY') ?: ($_ENV['RECAPTCHA_SITE_KEY'] ?? '');

$captchaError = $_SESSION['login_error'] ?? '';
unset($_SESSION['login_error']);
?>
<style>
  html, body { height: 100%; margin: 0; padding: 0; }
  body {
    display: flex; flex-direction: column;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: url('assets/imagenes/login.jpeg') no-repeat center center fixed;
    background-size: cover;
    position: relative;
  }
  body::before {
    content: ''; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background-color: rgba(255,255,255,0.3); z-index: -1;
  }
  main.page-content { flex: 1; display: flex; justify-content: center; align-items: center; padding: 2em; }

  .login-container {
    background: #fff; padding: 30px 40px; border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1); width: 100%; max-width: 400px;
  }
  .login-container h2 { margin-top: 0; text-align: center; color: #333; }
  .login-container label { display: block; margin: 15px 0 5px; font-weight: bold; color: #555; }
  .login-container input {
    width: 100%; padding: 8px 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px;
  }

  /* ✅ BOTONES CORREGIDOS */
  .login-container .btn, .login-container button {
    display: flex !important;            /* Usar Flexbox */
    justify-content: center !important;  /* Centrado Horizontal */
    align-items: center !important;      /* Centrado Vertical */
    width: 100%; 
    margin-top: 20px; 
    padding: 10px;
    background: #666666ff; 
    color: #fff; 
    border: none; 
    border-radius: 4px;
    font-size: 16px; 
    cursor: pointer; 
    transition: background .3s;
    text-decoration: none; /* Quitar subrayado */
    height: 45px; /* Altura fija para que sean iguales */
  }
  
  .login-container .btn:hover, .login-container button:hover { background: #2c2c2cff; }

  .login-error, .error {
    background: #f44336; color: #fff; padding: 10px; border-radius: 4px; text-align: center; margin-bottom: 15px;
  }

  .btn-row { display: flex; gap: 10px; margin-top: 10px; }
  
  /* Ajuste de ancho para que quepan los dos */
  .btn-half { width: calc(50% - 5px) !important; } 
</style>

<main class="page-content">
  <section class="login-section">
    <div class="login-container">
      <h2>Iniciar Sesión</h2>

      <?php if (!empty($captchaError)): ?>
        <div class="login-error"><?= htmlspecialchars($captchaError) ?></div>
      <?php endif; ?>

      <form method="POST" action="login_action.php">
        <?php
        $redirectFrom = $_GET['redirect'] ?? ($_SERVER['HTTP_REFERER'] ?? '');
        $redirectSafe = (!str_contains($redirectFrom, 'login.php') && !str_contains($redirectFrom, 'register.php'))
            ? $redirectFrom
            : 'index.php';
        ?>
        <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirectSafe) ?>">

        <label>Usuario</label>
        <input type="text" name="username" required>

        <label>Contraseña</label>
        <input type="password" name="password" required>

        <div style="margin-top:10px; text-align:left; color:blue;">
          <a href="forgot.php">¿Olvidaste tu contraseña?</a>
        </div>

        <div class="g-recaptcha" data-sitekey="<?= htmlspecialchars($siteKey) ?>"></div>
        <script src="https://www.google.com/recaptcha/api.js" async defer></script>

        <div class="btn-row">
          <button type="submit" class="btn btn-half">Entrar</button>
          <a href="register.php" class="btn btn-half">Registrarse</a>
        </div>
      </form>

    </div>
  </section>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>