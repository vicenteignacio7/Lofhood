<?php
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $nombre = $_POST["nombre"];
  $correo = $_POST["correo"];
  $mensaje = $_POST["mensaje"];

  // Simulación de envío
  echo "<h2>Gracias por contactarte, $nombre</h2>";
  echo "<p>Te responderemos pronto a $correo.</p>";
}
