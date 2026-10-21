<?php
// contacto.php

// 1) Arrancamos sesión (si fuera necesario) e incluimos header
require __DIR__ . '/includes/header.php';
?>

<!-- 2) Carga tu CSS específico de contacto -->
<link rel="stylesheet" href="assets/css/style.css">

<section class="contacto" id="contacto">
  <h2>Contáctanos</h2>

  <div class="local-container">
    <!-- Local Quilicura -->
    <div class="local-box">
      <h3>Local Quilicura</h3>
      <img src="assets/imagenes/quilicura.jpeg"
           alt="Foto Local Quilicura"
           class="imagen-local">
      <p><strong>Dirección:</strong> Av Manuel Antonio Matta 1001, local 1</p>
      <p>
        <strong>Horario:</strong><br>
        Lunes a sábado: 12:00 - 20:00<br>
        Domingo: 11:00 - 19:00
      </p>
      <a href="https://wa.me/56911111111"
         target="_blank"
         class="btn-whatsapp">
        <img src="assets/imagenes/wsp.jpeg"
             alt="WhatsApp Quilicura"
             class="wsp-icon">
      </a>
      <div class="mapa">
        <iframe
          src="https://www.google.com/maps?q=Av+Manuel+Antonio+Matta+1001,+Quilicura,+Chile&output=embed"
          allowfullscreen
          loading="lazy"
        ></iframe>
      </div>
    </div>

    <!-- Local Puente Alto -->
    <div class="local-box">
      <h3>Local Puente Alto</h3>
      <img src="assets/imagenes/puente.jpeg"
           alt="Foto Local Puente Alto"
           class="imagen-local">
      <p><strong>Dirección:</strong> Av Concha y Toro 2241, local 2</p>
      <p>
        <strong>Horario:</strong><br>
        Lunes a sábado: 12:00 - 20:00<br>
        Domingo: 11:00 - 19:00
      </p>
      <a href="https://wa.me/56922222222"
         target="_blank"
         class="btn-whatsapp">
        <img src="assets/imagenes/wsp.jpeg"
             alt="WhatsApp Puente Alto"
             class="wsp-icon">
      </a>
      <div class="mapa">
        <iframe
          src="https://www.google.com/maps?q=Av+Concha+y+Toro+2241,+Puente+Alto,+Chile&output=embed"
          allowfullscreen
          loading="lazy"
        ></iframe>
      </div>
    </div>
  </div>
</section>

<?php
// 3) Incluimos footer
require __DIR__ . '/includes/footer.php';
?>
