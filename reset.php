<?php
// reset.php
require __DIR__ . '/includes/init.php';
include __DIR__ . '/includes/header.php';

$token = $_GET['token'] ?? '';
$valid = false;

if ($token && preg_match('/^[a-f0-9]{64}$/', $token)) {
  $tokenHash = hash('sha256', $token);
  $stmt = $conn->prepare("SELECT pr.id, pr.user_id, pr.expires_at, pr.used_at, u.email
                          FROM password_resets pr
                          JOIN usuarios u ON u.id = pr.user_id
                          WHERE pr.token_hash = ? LIMIT 1");
  $stmt->bind_param("s", $tokenHash);
  $stmt->execute();
  $res = $stmt->get_result();
  $row = $res->fetch_assoc();
  $stmt->close();

  if ($row) {
    $now = new DateTime();
    $exp = new DateTime($row['expires_at']);
    if (empty($row['used_at']) && $now <= $exp) {
      $valid  = true;
      $resetId = (int)$row['id'];
      $userId  = (int)$row['user_id'];
    }
  }
}
?>
<style>
  .reset-container { background:#fff; padding:30px 40px; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,.1); width:100%; max-width:400px; margin:40px auto; }
  .reset-container h2 { margin-top:0; text-align:center; color:#333; }
  .reset-container label { display:block; margin:15px 0 5px; font-weight:bold; color:#555; }
  .reset-container input { width:100%; padding:8px 10px; border:1px solid #ccc; border-radius:4px; font-size:14px; }
  .reset-container .btn { display:block; width:100%; margin-top:20px; padding:10px; background:#43a047; color:#fff; border:none; border-radius:4px; font-size:16px; cursor:pointer; }
  .error { background:#f44336; color:#fff; padding:10px; border-radius:4px; text-align:center; margin-bottom:15px; }
</style>

<div class="reset-container">
  <h2>Restablecer Contraseña</h2>

  <?php if (!$valid): ?>
    <div class="error">El enlace no es válido o ha expirado.</div>
    <div style="text-align:center;"><a href="forgot.php">Solicitar un nuevo enlace</a></div>
  <?php else: ?>
    <form method="POST" action="reset_action.php">
      <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
      <label for="password">Nueva contraseña</label>
      <input type="password" name="password" id="password" required minlength="6" placeholder="********">
      <label for="password2">Repetir contraseña</label>
      <input type="password" name="password2" id="password2" required minlength="6" placeholder="********">
      <button type="submit" class="btn">Guardar</button>
      <div style="margin-top:10px; text-align:center;">
        <a href="login.php">Volver al login</a>
      </div>
    </form>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
