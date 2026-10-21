<?php
if (isset($_GET['token_ws'])) {
    // Redirige el token vía POST a confirmar_pago.php
    echo '
    <form id="form" method="POST" action="confirmar_pago.php">
        <input type="hidden" name="token_ws" value="' . htmlspecialchars($_GET['token_ws']) . '">
    </form>
    <script>document.getElementById("form").submit();</script>
    ';
} else {
    echo "Error: No se recibió token de pago.";
}
