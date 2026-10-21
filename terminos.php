<?php include __DIR__ . "/includes/header.php"; ?>

<style>
/* Fondo general suave si no hay contenido detrás */
body {
    background-color: #f4f6f9;
}

.terminos-container {
  max-width: 900px;
  margin: 40px auto;
  background: #ffffff;
  /* Si usas un fondo con imagen en el body, el backdrop-filter se verá genial, si no, queda blanco limpio */
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
  border-radius: 12px;
  padding: 40px 50px;
  box-shadow: 0 10px 30px rgba(0,0,0,0.08);
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  color: #333;
  border: 1px solid rgba(0,0,0,0.05);
}

.terminos-container h2 {
  text-align: center;
  color: #1a1a1a;
  margin-bottom: 15px;
  font-size: 32px;
  font-weight: 800;
  letter-spacing: -0.5px;
}

.subtitulo-fecha {
    text-align: center;
    color: #666;
    font-size: 14px;
    margin-bottom: 30px;
}

.terminos-container h3 {
  margin-top: 30px;
  font-size: 20px;
  color: #222;
  border-left: 5px solid #2cd19b; /* Color de tu marca */
  padding-left: 15px;
  font-weight: 700;
}

.terminos-container p, .terminos-container li {
  line-height: 1.7;
  font-size: 15px;
  color: #444;
}

.terminos-container ul {
  padding-left: 20px;
  margin-top: 10px;
  list-style-type: disc;
}

.terminos-container li {
    margin-bottom: 8px;
}

.terminos-container hr {
  border: none;
  border-top: 1px solid #eee;
  margin: 30px 0;
}

/* Botón Volver Estilizado */
.btn-volver-container {
    text-align: center;
    margin-top: 40px;
}

.btn-volver {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 12px 25px;
  background: #2c2c2c;
  color: #fff;
  border-radius: 50px;
  text-decoration: none;
  font-weight: 600;
  transition: all 0.3s ease;
  box-shadow: 0 4px 15px rgba(44, 44, 44, 0.2);
  border: none;
  cursor: pointer;
  font-size: 16px;
}

.btn-volver:hover {
  background: #000;
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(0,0,0,0.3);
}
</style>

<div class="terminos-container">

    <h2>Términos y Condiciones de Uso</h2>
    <h3></h3>
    <p>Bienvenido/a a <strong>LofHood</strong>. Al registrarte y utilizar nuestra plataforma, aceptas cumplir con los siguientes términos. El objetivo de este espacio es construir una comunidad segura y colaborativa.</p>

    <hr>

    <h3>1. Objetivo de la Plataforma</h3>
    <p>LofHood es una herramienta digital diseñada para conectar personas dentro de una comunidad local. Permitimos la publicación de ventas, servicios, ofertas laborales, actividades recreativas y alertas vecinales para facilitar la interacción y el apoyo mutuo.</p>

    <hr>

    <h3>2. Responsabilidad del Contenido</h3>
    <ul>
        <li>El usuario es el único responsable de la información, imágenes y datos que publica.</li>
        <li>LofHood actúa como intermediario y <strong>no garantiza</strong> la veracidad, calidad o legalidad de las publicaciones.</li>
        <li>Cualquier acuerdo, pago o transacción se realiza exclusivamente entre los usuarios. LofHood no interviene en disputas monetarias.</li>
    </ul>

    <hr>

    <h3>3. Conducta y Seguridad</h3>
    <p>Para mantener un ambiente sano, queda estrictamente prohibido:</p>
    <ul>
        <li>Publicar contenido ofensivo, discriminatorio, violento, sexualmente explícito o ilegal.</li>
        <li>Acosar, amenazar o difundir información privada de otros vecinos.</li>
        <li>Utilizar la plataforma para estafas, spam o actividades fraudulentas.</li>
        <li>Crear múltiples cuentas con el fin de manipular calificaciones o engañar a la comunidad.</li>
    </ul>

    <hr>

    <h3>4. Ventas y Servicios</h3>
    <ul>
        <li><strong>Artículos Prohibidos:</strong> No se permite la venta de armas, drogas, medicamentos recetados, animales en peligro de extinción, o artículos robados.</li>
        <li>LofHood no procesa pagos ni gestiona envíos. Recomendamos realizar las transacciones en lugares públicos y seguros.</li>
    </ul>

    <hr>

    <h3>5. Empleos y Trabajo</h3>
    <ul>
        <li>LofHood <strong>no es un empleador</strong> ni una agencia de reclutamiento. Solo facilitamos el contacto entre quien ofrece un trabajo y quien lo busca.</li>
        <li>Es responsabilidad del postulante verificar la legitimidad de la oferta laboral antes de acudir a entrevistas o enviar datos sensibles.</li>
        <li>Quedan prohibidas las ofertas de trabajo que soliciten dinero por adelantado al postulante.</li>
    </ul>

    <hr>

    <h3>6. Actividades y Eventos</h3>
    <ul>
        <li>El usuario que crea una "Actividad" (Organizador) es responsable de su gestión.</li>
        <li>La asistencia a eventos publicados en LofHood es voluntaria y bajo el propio riesgo de los participantes. LofHood no se hace responsable por accidentes o daños ocurridos durante las actividades presenciales.</li>
    </ul>

    <hr>

    <h3>7. Alertas Vecinales</h3>
    <ul>
        <li>La función de "Alertas" debe usarse con responsabilidad.</li>
        <li>Queda prohibido emitir alertas falsas que puedan generar pánico injustificado en la comunidad. El mal uso de esta función resultará en la suspensión inmediata de la cuenta.</li>
    </ul>

    <hr>

    <h3>8. Privacidad y Datos</h3>
    <ul>
        <li>Respetamos tu privacidad. No vendemos tu información personal a terceros.</li>
        <li>Tu número de contacto y ubicación aproximada solo se muestran en las publicaciones si tú decides hacerlo.</li>
        <li>Al subir imágenes, garantizas que tienes los derechos sobre ellas y otorgas a LofHood permiso para mostrarlas en la plataforma.</li>
    </ul>

    <hr>

    <h3>9. Modificación y Suspensión</h3>
    <p>LofHood se reserva el derecho de:</p>
    <ul>
        <li>Eliminar publicaciones que violen estos términos sin previo aviso.</li>
        <li>Suspender o eliminar permanentemente la cuenta de usuarios que infrinjan las normas de convivencia.</li>
        <li>Actualizar estos términos en cualquier momento. El uso continuo de la plataforma implica la aceptación de los cambios.</li>
    </ul>

    <hr>

    <h3>10. Aceptación</h3>
    <p>Al hacer clic en "Registrarse" o al utilizar nuestros servicios, confirmas que eres mayor de edad (o cuentas con supervisión de un tutor) y que has leído, entendido y aceptado estos Términos y Condiciones.</p>

    <p style="text-align:center; margin-top:40px; font-weight:bold; font-size: 1.1em;">Gracias por preferir LofHood 🧡</p>

    <div class="btn-volver-container">
        <button onclick="history.back()" class="btn-volver">
            <span>←</span> Volver atrás
        </button>
    </div>

</div>

<?php include __DIR__ . "/includes/footer.php"; ?>