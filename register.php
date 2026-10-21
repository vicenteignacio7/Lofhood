<?php

// register.php



// 1) Arrancamos sesión y conexión

require __DIR__ . '/includes/init.php';



// 2) Header

include __DIR__ . '/includes/header.php';



// 3) reCAPTCHA (para pintar y mostrar errores)

require_once __DIR__ . '/includes/recaptcha.php';

$siteKey = getenv('RECAPTCHA_SITE_KEY') ?: ($_ENV['RECAPTCHA_SITE_KEY'] ?? '');



$captchaError = $_SESSION['register_error'] ?? '';

unset($_SESSION['register_error']);

?>

<style>

  /* ===== General ===== */

  html, body { height: 100%; margin: 0; padding: 0; }

  body {

    display: flex; flex-direction: column;

    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;

    background: url('assets/imagenes/login.jpeg') no-repeat center center fixed;

    background-size: cover; position: relative;

  }

  body::before {

    content: ''; position: fixed; top: 0; left: 0; width: 100%; height: 100%;

    background-color: rgba(255,255,255,0.3); z-index: -1;

  }

  main.page-content { flex: 1; display: flex; justify-content: center; align-items: center; padding: 2em 0; }



  /* ===== Registro card ===== */

  .register-container {

    background: #fff; padding: 30px 40px; border-radius: 8px;

    box-shadow: 0 2px 8px rgba(0,0,0,0.1); width: 100%; max-width: 400px;

  }

  .register-container h2 { margin-top: 0; text-align: center; color: #333; }

  .register-container label { display: block; margin: 15px 0 5px; font-weight: bold; color: #555; }

  .register-container input {

    width: 100%; padding: 8px 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px;

  }

  .register-container .btn,

  .register-container button {

    display: inline-block; text-align: center; text-decoration: none;

    width: 100%; margin-top: 20px; padding: 10px;

    background: #666666ff; color: #fff; border: none; border-radius: 4px;

    font-size: 16px; cursor: pointer; transition: background .3s;

  }

  .register-container .btn:hover,

  .register-container button:hover { background: #2c2c2cff; }



  .register-error, .error {

    background: #f44336; color: #fff; padding: 10px; border-radius: 4px; text-align: center; margin-bottom: 15px;

  }



  .btn-row { display: flex; gap: 10px; margin-top: 10px; }

  .btn-half { width: 50%; }

</style>



<main class="page-content">

  <section class="register-section">

    <div class="register-container">

      <h2>Crear Cuenta</h2>



      <?php if (!empty($_SESSION['register_error'])): ?>

        <div class="register-error">

          <?= htmlspecialchars($_SESSION['register_error']); unset($_SESSION['register_error']); ?>

        </div>

      <?php endif; ?>



      <?php if (!empty($captchaError)): ?>

        <div class="error"><?= htmlspecialchars($captchaError) ?></div>

      <?php endif; ?>



      <form method="POST" action="register_action.php">

        <label for="username">Nombre de Usuario</label>

        <input type="text" name="username" placeholder="Tu usuario" required>



        <label for="email">Correo Electrónico</label>

        <input type="email" name="email" placeholder="tucorreo@ejemplo.com" required>



        <label for="phone">Teléfono</label>

        <input type="tel" name="phone" placeholder="+56 9 1234 5678" required>



        <label for="password">Contraseña</label>

        <input type="password" name="password" placeholder="********" required>



        <label for="password_confirm">Repetir Contraseña</label>

        <input type="password" name="password_confirm" placeholder="********" required>



        <div class="g-recaptcha" data-sitekey="<?= htmlspecialchars($siteKey) ?>"></div>

        <script src="https://www.google.com/recaptcha/api.js" async defer></script>

<label class="terminos-box" style="margin-top: 15px; font-size:14px;">
  <input type="checkbox" name="acepto_terminos" required>
  Acepto los <a href="terminos.php">Términos y Condiciones</a>
</label>



        <div class="btn-row">

          <button type="submit" class="btn btn-half">Registrar</button>

          <a href="login.php" class="btn btn-half" role="button">Iniciar Sesión</a>

        </div>

      </form>

    </div>

  </section>

</main>



<?php include __DIR__ . '/includes/footer.php'; ?>