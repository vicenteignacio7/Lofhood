<?php 
if (session_status() === PHP_SESSION_NONE) session_start();
require __DIR__ . "/includes/conexion.php";
$usuario_logueado = $_SESSION['user_id'] ?? 0;

$id = intval($_GET['id'] ?? 0);

// Obtener publicación
$sql = "SELECT p.*, u.username 
        FROM publicaciones p
        INNER JOIN usuarios u ON p.usuario_id = u.id
        WHERE p.id = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$pub = $stmt->get_result()->fetch_assoc();

if (!$pub) { 
    include __DIR__ . "/includes/header.php";
    echo "<p class='no-pubs'>Publicación no encontrada.</p>";
    include __DIR__ . "/includes/footer.php";
    exit;
}

$cat  = $_GET['cat']  ?? $pub['categoria'];
// Guardar origen de navegación de forma persistente
if (!empty($_GET['from'])) {
    $_SESSION['volver_origen'] = $_GET['from'];
}
$from = $_SESSION['volver_origen'] ?? 'general';

$query_string = $_GET;
unset($query_string['id'], $query_string['from']); // no volvemos a pasar id/from

// Botón VOLVER robusto, manteniendo filtros o scroll

switch ($from) {
    case 'mis': 
        $volver = "mis_publicaciones.php"; 
        break;
        
    case 'fav': 
        $volver = "mis_favoritos.php"; 
        break;

    case 'notif': 
        $volver = "mis_notificaciones.php"; 
        break;

    case 'dashboard': /* ✅ NUEVO CASO PARA EL ADMIN */
        $volver = "dashboard.php"; 
        break;

    case 'cat': 
    case 'general': 
        $volver = "ver_publicaciones.php?cat=" . urlencode($cat); 
        break;

    case 'index': 
        $volver = "index.php#keep-scroll"; 
        break;

    default: 
        $volver = "publicaciones.php"; 
        break;
}




// ✅ Obtener participantes si es actividad
$participantes = [];
$cantidad_participantes = 0;

if(trim($pub['categoria']) === 'actividad'){
    $q = $conn->prepare("
        SELECT a.usuario_id, a.fecha_registro, u.username
        FROM actividad_asistentes a
        INNER JOIN usuarios u ON a.usuario_id = u.id
        WHERE a.publicacion_id = ?
        ORDER BY a.fecha_registro ASC
    ");
    $q->bind_param("i", $pub['id']);
    $q->execute();
    $res = $q->get_result();
    $participantes = $res->fetch_all(MYSQLI_ASSOC);
    $cantidad_participantes = count($participantes);
    $res->free();  // ✅ importante
    $q->close();   // ✅ importante
}
// 🔎 Detectar si el usuario ya está unido usando la lista ya cargada
$ya_unido = false;
if (trim($pub['categoria']) === 'actividad' && !empty($_SESSION['user_id'])) {
    $idsParticipantes = array_map('intval', array_column($participantes, 'usuario_id'));
    $ya_unido = in_array((int)$usuario_logueado, $idsParticipantes, true);

    // Fallback por si la lista vino vacía por algún motivo raro
    if (!$ya_unido) {
        $chk = $conn->prepare("SELECT 1 FROM actividad_asistentes WHERE usuario_id = ? AND publicacion_id = ?");
        $uid = (int)$usuario_logueado; $pid = (int)$pub['id'];
        $chk->bind_param("ii", $uid, $pid);
        $chk->execute();
        $chk->store_result();
        $ya_unido = ($chk->num_rows > 0);
        $chk->close();
    }
}

// ===============================
// POSTULANTES — SOLO PARA TRABAJO
// ===============================
$total_postulantes = 0;
$ya_postulado = false;

if (trim($pub['categoria']) === 'trabajo') {

    // Total de postulantes
    $pt = $conn->prepare("SELECT COUNT(*) AS total FROM trabajo_postulantes WHERE publicacion_id = ?");
    $pt->bind_param("i", $pub['id']);
    $pt->execute();
    $rpt = $pt->get_result()->fetch_assoc();
    $total_postulantes = (int)($rpt['total'] ?? 0);
    $pt->close();

    // ¿Ya postuló este usuario?
    if (!empty($_SESSION['user_id'])) {
        $chk = $conn->prepare("SELECT 1 FROM trabajo_postulantes WHERE publicacion_id = ? AND usuario_id = ? LIMIT 1");
        $chk->bind_param("ii", $pub['id'], $_SESSION['user_id']);
        $chk->execute();
        $chk->store_result();
        $ya_postulado = ($chk->num_rows > 0);
        $chk->close();
    }
}


include __DIR__ . "/includes/header.php";?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<style>
    #map { height: 250px; width: 100%; border-radius: 10px; z-index: 1; margin-top: 15px; border: 1px solid #ddd; }
</style>



<a href="<?= $volver ?>" class="btn-back">← Volver</a>



<section class="pub-detalle">

  <?php if (!empty($pub['imagen'])): ?>
    <img src="uploads/<?= htmlspecialchars($pub['imagen']) ?>" class="detalle-img">
  <?php endif; ?>

  <div class="pub-info-detalle">

   <?php if ($pub['categoria'] === 'actividad' && $pub['estado'] === 'terminado'): ?>
  <span class="badge-terminado">TERMINADO</span>
<?php elseif ($pub['categoria'] !== 'actividad' && $pub['estado'] === 'vendido'): ?>
  <span class="badge-vendido">VENDIDO</span>
<?php endif; ?>


<h2 class="pub-title"><?= htmlspecialchars($pub['titulo']) ?></h2>

<?php
$prom = $conn->query("SELECT AVG(calificacion) AS p FROM calificaciones WHERE publicacion_id=".$pub['id']);
$promedio = round($prom->fetch_assoc()['p'], 1);
?>
<p class="promedio-stars">⭐ Promedio: <b><?= $promedio ?: '0.0' ?></b> / 5</p>

<div class="pub-descripcion-box">
  <h3>Descripción</h3>
  <p><?= nl2br(htmlspecialchars($pub['descripcion'])) ?></p>
  
</div>
<?php 
   $lat = $pub['latitud'] ?? 0;
   $lon = $pub['longitud'] ?? 0;
   if ($lat != 0 && $lon != 0): 
   ?>
   <div class="info-box">
       <div class="info-row"><span class="icon">📍</span><b>Ubicación aproximada</b></div>
       <div id="map"></div>
       <p style="font-size:12px; color:#666; margin-top:5px;">* El radio indica la zona aproximada.</p>
   </div>
   <?php endif; ?>
<div class="info-box">

    <?php if($pub['categoria'] === 'servicio'): ?>

        <?php if(!empty($pub['precio'])): ?>
        <div class="info-row">
            <span class="icon">💲</span>
            <b>Precio desde:</b> $<?= number_format((float)$pub['precio'], 0, ',', '.') ?>
        </div>
        <?php endif; ?>

        <?php if(!empty($pub['ubicacion'])): ?>
        <div class="info-row">
            <span class="icon">📍</span>
            <b>Zona:</b> <?= htmlspecialchars($pub['ubicacion']) ?>
        </div>
        <?php endif; ?>

        <?php if(!empty($pub['horario'])): ?>
        <div class="info-row">
            <span class="icon">🕒</span>
            <b>Horario:</b> <?= htmlspecialchars($pub['horario']) ?>
        </div>
        <?php endif; ?>

    <?php endif; ?>


    <?php if($pub['categoria'] === 'venta'): ?>

        <?php if(!empty($pub['precio'])): ?>
        <div class="info-row">
            <span class="icon">💲</span>
            <b>Precio:</b> $<?= number_format((float)$pub['precio'], 0, ',', '.') ?>
        </div>
        <?php endif; ?>

        <?php if(!empty($pub['estado_producto'])): ?>
        <div class="info-row">
            <span class="icon">📌</span>
            <b>Estado:</b> <?= htmlspecialchars($pub['estado_producto']) ?>
        </div>
        <?php endif; ?>

        <?php if(!empty($pub['condicion'])): ?>
        <div class="info-row">
            <span class="icon">🔍</span>
            <b>Condición:</b> <?= htmlspecialchars($pub['condicion']) ?>
        </div>
        <?php endif; ?>

        <?php if(!empty($pub['entrega'])): ?>
        <div class="info-row">
            <span class="icon">🚚</span>
            <b>Entrega:</b> <?= htmlspecialchars($pub['entrega']) ?>
        </div>
        <?php endif; ?>

        <?php if(!empty($pub['metodo_pago'])): ?>
        <div class="info-row">
            <span class="icon">💳</span>
            <b>Método de pago:</b> <?= htmlspecialchars($pub['metodo_pago']) ?>
        </div>
        <?php endif; ?>

    <?php endif; ?>


    <?php if($pub['categoria'] === 'actividad'): ?>

        <?php if(!empty($pub['lugar'])): ?>
        <div class="info-row">
            <span class="icon">📍</span>
            <b>Lugar:</b> <?= htmlspecialchars($pub['lugar']) ?>
        </div>
        <?php endif; ?>

        <?php if(!empty($pub['fecha_evento'])): ?>
        <div class="info-row">
            <span class="icon">🗓</span>
            <b>Fecha:</b> <?= date("d/m/Y H:i", strtotime($pub['fecha_evento'])) ?>
        </div>
        <?php endif; ?>

        <?php if(!empty($pub['cupos'])): ?>
        <div class="info-row">
            <span class="icon">👥</span>
            <b>Cupos:</b> <?= intval($pub['cupos']) ?>
        </div>
        <?php endif; ?>

        <?php if(!empty($pub['tipo_actividad'])): ?>
        <div class="info-row">
            <span class="icon">🏷</span>
            <b>Tipo:</b> <?= htmlspecialchars($pub['tipo_actividad']) ?>
        </div>
        <?php endif; ?>

    <?php endif; ?>


    <?php if($pub['categoria'] === 'trabajo'): ?>

          <?php if(!empty($pub['tipo_trabajo'])): ?>
          <div class="info-row">
              <span class="icon">🏷</span>
              <b>Tipo de trabajo:</b> <?= htmlspecialchars($pub['tipo_trabajo']) ?>
          </div>
          <?php endif; ?>

          <?php if(!empty($pub['modalidad'])): ?>
          <div class="info-row">
              <span class="icon">🏡</span>
              <b>Modalidad:</b> <?= htmlspecialchars($pub['modalidad']) ?>
          </div>
          <?php endif; ?>

          <?php if(!empty($pub['zona_cobertura'])): ?>
          <div class="info-row">
              <span class="icon">📍</span>
              <b>Zonas:</b> <?= htmlspecialchars($pub['zona_cobertura']) ?>
          </div>
          <?php endif; ?>

          <?php if(!empty($pub['salario'])): ?>
          <div class="info-row">
              <span class="icon">💰</span>
              <b>Salario:</b> <?= htmlspecialchars($pub['salario']) ?>
          </div>
          <?php endif; ?>

          <?php if(!empty($pub['materiales_incluidos'])): ?>
          <div class="info-row">
              <span class="icon">🧰</span>
              <b>Materiales / herramientas incluidos:</b> <?= htmlspecialchars($pub['materiales_incluidos']) ?>
          </div>
          <?php endif; ?>

          <?php if(!empty($pub['requisitos'])): ?>
          <div class="info-row">
              <span class="icon">📋</span>
              <b>Requisitos:</b> <?= nl2br(htmlspecialchars($pub['requisitos'])) ?>
          </div>
          <?php endif; ?>

          <?php if(!empty($pub['experiencia'])): ?>
          <div class="info-row">
              <span class="icon">🧑‍💼</span>
              <b>Experiencia deseada:</b> <?= nl2br(htmlspecialchars($pub['experiencia'])) ?>
          </div>
          <?php endif; ?>

          <div class="info-row">
              <span class="icon">👥</span>
              <b>Máximo de postulantes:</b> <?= $pub['cupos_trabajo'] ?: 'Sin límite' ?>
          </div>

    <?php endif; ?>
</div>






    <?php if(in_array($pub['categoria'], ['venta', 'servicio', 'trabajo'])): ?>
<?php if($pub['categoria'] === 'servicio'): ?>



<?php endif; ?>
<button id="btnShare" class="btn-share" type="button">
    ↪️ Compartir
</button>
<?php endif; ?>

<?php
  // Obtener teléfono del vendedor
  $stmtTel = $conn->prepare("SELECT phone FROM usuarios WHERE id = ?");
  $stmtTel->bind_param("i", $pub['usuario_id']);
  $stmtTel->execute();
  $stmtTel->store_result();
  $stmtTel->bind_result($telefonoBD);
  $stmtTel->fetch();
  $stmtTel->close();

  $phone = $pub['contacto'] ?: $telefonoBD;
  $phone = preg_replace('/[^0-9]/', '', $phone); // Limpiamos el número inmediatamente

  // ✅ Definimos el título según la categoría
  $cat_actual = strtolower(trim($pub['categoria'])); 
  $titulo_contacto = "Contactar con el vendedor"; 

  if ($cat_actual === 'trabajo') {
      $titulo_contacto = "Contactar con el empleador";
  } 
  elseif ($cat_actual === 'actividad') {
      $titulo_contacto = "Contactar con el dueño de la publicación";
  } 
  elseif ($cat_actual === 'alerta') {
      $titulo_contacto = "Contactar con el creador de la alerta";
  }
  ?>

  <?php if($usuario_logueado != $pub['usuario_id']): ?>
    
    <?php if(!empty($phone)): ?>
        <h3><?= $titulo_contacto ?></h3>
        <hr>

        <a href="https://wa.me/56<?= $phone ?>?text=Hola, vi tu publicación en LofHood y me interesa: <?= urlencode($pub['titulo']) ?>"
           target="_blank"
           class="wsp-button">
           <img src="assets/imagenes/whatsapp.png" alt="WhatsApp" class="wsp-icon">
        </a>
    <?php endif; ?>

    <p class="pub-fecha-final">
      📅 Publicado el: <?= date("d/m/Y H:i", strtotime($pub['fecha'])) ?>
    </p>

  <?php endif; ?>
  
    <!-- ✅ Mostrar cantidad de participantes -->
    <?php if(trim($pub['categoria']) === 'actividad'): ?>
        <p class="participantes-count">👥 Participantes: <b><?= $cantidad_participantes ?></b></p>
    <?php endif; ?>
<!-- ✅ UNIRSE / CANCELAR -->
<?php if (trim($pub['categoria']) === 'actividad'): ?>

    <?php
    $actividad_terminada = (trim($pub['estado']) === 'terminado');
    $sin_cupos = (!empty($pub['cupos']) && $cantidad_participantes >= (int)$pub['cupos']);
    $es_dueno = ((int)$usuario_logueado === (int)$pub['usuario_id']);
    ?>

    <hr>

    <?php if (!empty($_SESSION['user_id']) && $es_dueno): ?>
        <!-- Organizador -->
        <p class="ya-unido">👤 Eres el organizador</p>

    <?php elseif (!empty($_SESSION['user_id']) && $ya_unido): ?>
        <!-- ✅ YA UNIDO: SIEMPRE permitir cancelar (aunque esté terminado o sin cupos) -->
        <form action="cancelar_participacion.php" method="POST">
            <input type="hidden" name="id" value="<?= (int)$pub['id'] ?>">
            <button type="submit" class="btn-cancelar">❌ Cancelar participación</button>
        </form>

    <?php elseif (empty($_SESSION['user_id'])): ?>
        <!-- No logueado -->
        <p class="aviso-login">⚠ Inicia sesión para unirte</p>

    <?php elseif ($actividad_terminada): ?>
        <!-- Evento terminado (no mostrar unirse) -->
        <p class="sin-cupos" style="color:#d33; font-weight:bold; margin-top:12px;">
            ⚠ Esta actividad ha finalizado.
        </p>

    <?php elseif ($sin_cupos): ?>
        <!-- Sin cupos (no mostrar unirse) -->
        <p class="sin-cupos" style="color:#d33; font-weight:bold; margin-top:12px;">
            ⚠ No quedan cupos disponibles.
        </p>

    <?php else: ?>
        <!-- Disponible para unirse -->
        <form action="unirse_actividad.php" method="POST">
            <input type="hidden" name="id" value="<?= (int)$pub['id'] ?>">
            <button type="submit" class="btn-unirse">🤝 Unirse</button>
        </form>
    <?php endif; ?>

<?php endif; ?>
<!-- ============================
     TRABAJO — POSTULAR
============================ -->
<?php if(trim($pub['categoria']) === 'trabajo'): ?>

<?php
    $es_dueno = ((int)$usuario_logueado === (int)$pub['usuario_id']);
    $trabajo_cerrado = ($pub['estado_trabajo'] === 'cerrado');
    $sin_cupos = (!empty($pub['cupos_trabajo']) && $total_postulantes >= (int)$pub['cupos_trabajo']);
?>

<p class="participantes-count">👥 Postulantes: <b><?= $total_postulantes ?></b></p>


<?php if ($es_dueno): ?>

  <div style="margin-top:5px;">
    <a href="ver_postulantes.php?id=<?= $pub['id'] ?>" class="btn-unirse">
        Ver postulantes
    </a>
</div>



<?php if(!$trabajo_cerrado): ?>

    <form action="cerrar_postulaciones.php" method="POST" style="display:inline;">
        <input type="hidden" name="id" value="<?= $pub['id'] ?>">
        <div style="margin-top:15px;">
            <button type="submit" class="btn-cancelar">🛑 Cerrar postulaciones</button>
        </div>
    </form>

<?php else: ?>

    <form action="abrir_postulaciones.php" method="POST" style="display:inline;">
        <input type="hidden" name="id" value="<?= $pub['id'] ?>">
        <div style="margin-top:15px;">
            <button type="submit" class="btn-unirse" style="background:#1c9c3c;">🔓 Reabrir postulaciones</button>
        </div>
    </form>

<?php endif; ?>



<?php else: ?>

    <?php if(empty($_SESSION['user_id'])): ?>
        <p class="aviso-login">⚠ Inicia sesión para postular</p>

    <?php elseif($trabajo_cerrado): ?>
        <p class="sin-cupos" style="color:#d33"><b>⚠ Postulaciones cerradas</b></p>

    <?php elseif($sin_cupos): ?>
        <p class="sin-cupos" style="color:#d33"><b>⚠ Se alcanzó el máximo de postulantes</b></p>

   <?php elseif(($_SESSION['role'] ?? '') === 'admin'): ?>
    <p class="sin-cupos" style="color:#d33;"><b>⚠ Los administradores no pueden postular a trabajos</b></p>

<?php elseif($ya_postulado): ?>
    <p class="ya-unido">✔ Ya postulaste a este trabajo</p>

<?php else: ?>
    <a href="postular_trabajo.php?id=<?= $pub['id'] ?>" class="btn-unirse">💼 Postular al trabajo</a>
<?php endif; ?>


<?php endif; ?>

<?php endif; ?>




    <!-- ✅ Lista de participantes SOLO para el dueño -->
    <?php if(trim($pub['categoria']) === 'actividad' && $usuario_logueado == $pub['usuario_id']): ?>
        <h4 style="margin-top:30px;">👥 Participantes</h4>

        <?php if($cantidad_participantes == 0): ?>
            <p>No hay participantes aún.</p>
        <?php else: ?>
        <table class="tabla-participantes">
            <tr>
              <th>Usuario</th>
              <th>Fecha de unión</th>
            </tr>
            <?php foreach ($participantes as $p): ?>
            <tr>
              <td>@<?= htmlspecialchars($p['username']) ?></td>
              <td><?= date("d/m/Y H:i", strtotime($p['fecha_registro'])) ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>
    <?php endif; ?>


    

      <?php
                    // Verificar si el usuario ya calificó esta publicación
                    $calificada = false;
                    if (!empty($_SESSION['user_id'])) {
                        $c = $conn->prepare("SELECT calificacion FROM calificaciones WHERE usuario_id=? AND publicacion_id=? LIMIT 1");
                        $c->bind_param("ii", $_SESSION['user_id'], $pub['id']);
                        $c->execute();
                        $calificada = $c->get_result()->fetch_assoc();
                    }
                  ?>
    <h3 id="comentarios" style="margin-top:25px; margin-bottom:10px;">Comentarios</h3>
   
<?php if (!empty($_SESSION['user_id']) && $_SESSION['user_id'] != $pub['usuario_id']): ?>

  <?php if (!$calificada): ?>
      <div class="rating-box">
          <h4>⭐ Evalúa esta publicación antes de comentar</h4>

          <form action="guardar_calificacion.php" method="POST" class="rating-form">
              <input type="hidden" name="publicacion_id" value="<?= $pub['id'] ?>">

            <div class="star-rating">
    <input type="hidden" name="calificacion" id="rating-value" required>

    <span class="star" data-value="1">★</span>
    <span class="star" data-value="2">★</span>
    <span class="star" data-value="3">★</span>
    <span class="star" data-value="4">★</span>
    <span class="star" data-value="5">★</span>
</div>


              <button type="submit" class="btn-comentar">Enviar calificación</button>
          </form>
          <div id="alerta" class="alerta-rating"></div>

      </div>
  <?php else: ?>
      <div class="ya-calificada">
    Calificaste esta publicación con <?= $calificada['calificacion'] ?> estrellas⭐

    <button class="btn-cambiar" id="btnCambiarRating">Cambiar calificación</button>
</div>

<!-- Caja para volver a calificar (oculta al inicio) -->
<div class="rating-box" id="ratingChangeBox" style="display:none;">
    <h4>⭐ Cambiar calificación</h4>

    <form action="guardar_calificacion.php" method="POST" class="rating-form">
        <input type="hidden" name="publicacion_id" value="<?= $pub['id'] ?>">

        <div class="star-rating">
            <input type="hidden" name="calificacion" id="rating-value-change">

            <span class="star" data-value="1">★</span>
            <span class="star" data-value="2">★</span>
            <span class="star" data-value="3">★</span>
            <span class="star" data-value="4">★</span>
            <span class="star" data-value="5">★</span>
        </div>

        <button type="submit" class="btn-comentar">Actualizar</button>
    </form>
</div>

<script>
document.getElementById("btnCambiarRating")?.addEventListener("click", () => {
    document.getElementById("ratingChangeBox").style.display = "block";
});
</script>

  <?php endif; ?>

<?php endif; ?>

   


                 
 <?php if (!empty($_SESSION['user_id']) && ($calificada || $_SESSION['user_id'] == $pub['usuario_id'])): ?>
      <form action="comentar.php" method="POST" class="coment-form">
        <input type="hidden" name="publicacion_id" value="<?= $pub['id'] ?>">
        <input type="hidden" name="from" value="<?= $from ?>">
        <input type="hidden" name="query" value="<?= http_build_query($_GET) ?>">
        <textarea name="comentario" placeholder="Escribe un comentario..." required></textarea>
        <button type="submit" class="btn-comentar">Publicar</button>
      </form>
    <?php endif; ?>
<?php
// 1. Función auxiliar (sin cambios)
function obtenerNombreUsuario($conn, $coment_id){
    $stmt = $conn->prepare("SELECT u.username FROM comentarios c INNER JOIN usuarios u ON c.usuario_id = u.id WHERE c.id = ?");
    $stmt->bind_param("i", $coment_id);
    $stmt->execute();
    $resultado = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $resultado ? $resultado['username'] : null;
}

// 2. Función recursiva HÍBRIDA (Foto o Letra)
function renderComentarios($conn, $parent_id, $pub_id, $nivel = 0) {
    // Traemos username e imagen
    $sql = ($parent_id === null)
        ? "SELECT c.*, u.username, u.imagen 
           FROM comentarios c 
           INNER JOIN usuarios u ON c.usuario_id = u.id 
           WHERE c.publicacion_id = $pub_id AND c.parent_id IS NULL 
           ORDER BY c.fecha ASC"
        : "SELECT c.*, u.username, u.imagen 
           FROM comentarios c 
           INNER JOIN usuarios u ON c.usuario_id = u.id 
           WHERE c.parent_id = $parent_id 
           ORDER BY c.fecha ASC";

    $res = $conn->query($sql);
    if (!$res || $res->num_rows === 0) return;

    while($c = $res->fetch_assoc()):
        $respondeA = ($parent_id !== null) ? obtenerNombreUsuario($conn, $parent_id) : null;
        
        // --- LÓGICA DE FOTO VS LETRA ---
        $tiene_foto = false;
        $foto_db = $c['imagen'];
        $ruta_relativa = 'uploads/perfiles/' . $foto_db; 
        $ruta_absoluta = __DIR__ . '/' . $ruta_relativa;

        // Verificamos si existe la foto real
        if (!empty($foto_db) && file_exists($ruta_absoluta)) {
            $src_foto = $ruta_relativa;
            $tiene_foto = true;
        }
        // Si no, usaremos la letra
        $inicial = strtoupper(substr($c['username'], 0, 1));
?> 

    <div class="comentario <?= $nivel > 0 ? 'es-respuesta' : '' ?>" 
         style="margin-left: <?= $nivel > 0 ? 40 : 0 ?>px;">
      
      <div class="avatar" style="
           width:40px; height:40px; min-width:40px; border-radius:50%; 
           display:flex; align-items:center; justify-content:center; 
           font-weight:bold; color:#fff; overflow:hidden;
           <?= !$tiene_foto ? 'background-color:#ccc;' : '' /* Solo pone fondo gris si es letra */ ?>
      ">
          <?php if ($tiene_foto): ?>
              <img src="<?= $src_foto ?>" alt="Foto" style="width: 100%; height: 100%; object-fit: cover;">
          <?php else: ?>
              <span style="font-size:18px; color:#555;"><?= $inicial ?></span>
          <?php endif; ?>
      </div>

      <div class="contenido">
        <b><?= htmlspecialchars($c['username']) ?></b>

        <?php if($respondeA): ?>
          <span class="responde-a">↳ Respondiendo a @<?= htmlspecialchars($respondeA) ?></span>
        <?php endif; ?>

        <p><?= nl2br(htmlspecialchars($c['comentario'])) ?></p>

        <div class="meta">
          <span class="fecha"><?= date("d/m/Y H:i", strtotime($c['fecha'])) ?></span>

          <?php if(!empty($_SESSION['user_id'])): ?>
            · <button class="btn-responder" data-id="<?= $c['id'] ?>">Responder</button>

            <?php if($_SESSION['user_id'] == $c['usuario_id']): ?>
              · <button class="btn-editar-coment" data-id="<?= $c['id'] ?>" data-text="<?= htmlspecialchars($c['comentario']) ?>">Editar</button>
              · <button class="btn-eliminar-coment" data-id="<?= $c['id'] ?>" data-from="<?= $GLOBALS['from'] ?? 'index' ?>" data-query="<?= http_build_query($_GET) ?>">Eliminar</button>
            <?php endif; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>

<?php 
        renderComentarios($conn, $c['id'], $pub_id, $nivel + 1);
    endwhile;
}
?>

<div class="comentarios-lista">
    <?php renderComentarios($conn, null, $pub['id']); ?>
</div>


<?php 
// Verificar si es dueño O si es admin
$es_admin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');
$es_dueno = ($usuario_logueado == $pub['usuario_id']);

if ($es_dueno || $es_admin): 
?>
  <div class="owner-actions">
    <a href="editar_publicacion.php?id=<?= $pub['id'] ?>&cat=<?= $cat ?>&from=<?= urlencode($from) ?>&<?= http_build_query($query_string) ?>" class="btn-edit">
      ✏ Editar
    </a>

  <button class="btn-item delete btn-eliminar"
        data-id="<?= $pub['id'] ?>" 
        data-cat="<?= $cat ?>" 
        data-from="<?= $from ?>"> 🗑 Eliminar
</button>
  </div>
<?php endif; ?>
<?php if (!empty($_SESSION['user_id']) && $usuario_logueado != $pub['usuario_id']): ?>
  <!-- 🚨 Botón Reportar -->
  <button class="btn-reportar" id="openReportModal">🚨 Reportar publicación</button>
<?php endif; ?>


  </div>
  
</section>

<script>
// Responder
document.addEventListener("click", function(e) {
  if (!e.target.classList.contains("btn-responder")) return;
  const id = e.target.dataset.id;
  const card = e.target.closest(".comentario");
  card.insertAdjacentHTML("afterend", `
   <form action="comentar.php" method="POST" class="coment-form respuesta-form" style="margin-left:${card.style.marginLeft};">
      <input type="hidden" name="publicacion_id" value="<?= $pub['id'] ?>">
      <input type="hidden" name="parent_id" value="${id}">
      <input type="hidden" name="from" value="<?= $from ?>">
      <input type="hidden" name="query" value="<?= http_build_query($_GET) ?>">
      <textarea name="comentario" required></textarea>
      <button type="submit" class="btn-comentar">Responder</button>
   </form>
  `);
});

// Editar
document.addEventListener("click", function(e){
  if(!e.target.classList.contains("btn-editar-coment")) return;
  const id = e.target.dataset.id;
  const texto = e.target.dataset.text;
  const card = e.target.closest(".comentario");
  card.insertAdjacentHTML("afterend", `
    <form action="editar_comentario.php" method="POST" class="coment-form respuesta-form" style="margin-left:${card.style.marginLeft};">
      <input type="hidden" name="id" value="${id}">
      <input type="hidden" name="pub" value="<?= $pub['id'] ?>">
      <input type="hidden" name="from" value="<?= $from ?>">
      <input type="hidden" name="query" value="<?= http_build_query($_GET) ?>">
      <textarea name="comentario" required>${texto}</textarea>
      <button type="submit" class="btn-comentar">Guardar</button>
    </form>
  `);
});

// Eliminar
document.addEventListener("click", function(e) {
  if(!e.target.classList.contains("btn-eliminar-coment")) return;
  const id = e.target.dataset.id;
  Swal.fire({
    title: "¿Eliminar comentario?",
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#d33",
    cancelButtonColor: "#3085d6",
    confirmButtonText: "Eliminar"
  }).then(res => {
    if(res.isConfirmed){
      window.location.href = `eliminar_comentario.php?id=${id}&pub=<?= $pub['id'] ?>&from=<?= $from ?>&query=<?= http_build_query($_GET) ?>#comentarios`;
    }
  });
});
</script>
<script>
document.addEventListener("DOMContentLoaded", () => {
  const modal = document.getElementById("imgModal");
  const modalImg = document.getElementById("modalImg");
  const img = document.querySelector(".detalle-img");
  const close = document.querySelector(".img-close");

  if(img){
    img.addEventListener("click", function(){
      modal.style.display = "block";
      modalImg.src = this.src;
    });
  }

  close.addEventListener("click", function(){
    modal.style.display = "none";
  });

  modal.addEventListener("click", function(e){
    if(e.target === modal){
      modal.style.display = "none";
    }
  });
});
</script>

<!-- Modal para ver imagen en grande -->
<div id="imgModal" class="img-modal">
  <span class="img-close">&times;</span>
  <img class="img-modal-content" id="modalImg">
</div>

<!-- Modal Reportar -->
<div id="reportModal" class="modal-report" style="display:none;">
  <div class="modal-content-report">
    <h3>Reportar Publicación</h3>
    <form action="reportar.php" method="POST">
      <input type="hidden" name="publicacion_id" value="<?= (int)$pub['id'] ?>">
      <textarea name="motivo" placeholder="Describe el problema..." required
                style="width:100%;min-height:120px;resize:vertical;"></textarea>

      <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:10px;">
        <button type="submit" class="btn-enviar-reporte">Enviar</button>
        <button type="button" class="btn-cerrar-modal" id="closeReportModal">Cancelar</button>
      </div>
    </form>
  </div>
</div>

<script>
(function(){
  const modal = document.getElementById("reportModal");
  const btnOpen = document.getElementById("openReportModal");
  const btnClose = document.getElementById("closeReportModal");

  if (btnOpen && modal) btnOpen.addEventListener("click", () => { modal.style.display = "flex"; });
  if (btnClose && modal) btnClose.addEventListener("click", () => { modal.style.display = "none"; });
  if (modal) modal.addEventListener("click", (e) => { if (e.target === modal) modal.style.display = "none"; });
})();
</script>

<script>
document.addEventListener("DOMContentLoaded", function () {
    // Fuerza el scroll al inicio SIEMPRE
    window.scrollTo(0, 0);
    
    // Si la URL tiene hash, se limpia para evitar que baje
    if (window.location.hash) {
        history.replaceState(null, null, window.location.pathname + window.location.search);
    }
});
</script>
<script>
// ===================
// GUARDAR SCROLL ANTES DE ACCIONES
// ===================
function saveScroll() {
    sessionStorage.setItem("scrollPos", window.scrollY);
}

// Guardar scroll al enviar formularios que NO deben volver arriba
document.addEventListener("click", function(e) {

    // ✔ Formularios de comentar
    if (e.target.closest("form")?.action.includes("comentar.php")) {
        saveScroll();
    }
// ✔ Guardar scroll cuando calificas
if (e.target.closest("form")?.action.includes("guardar_calificacion.php")) {
    saveScroll();
}

    // ✔ Formularios de editar comentario
    if (e.target.closest("form")?.action.includes("editar_comentario.php")) {
        saveScroll();
    }

    // ✔ Botón eliminar comentario
    if (e.target.classList.contains("btn-eliminar-coment")) {
        saveScroll();
    }

    // ✔ Unirse actividad
    if (e.target.closest("form")?.action.includes("unirse_actividad.php")) {
        saveScroll();
    }

    // ✔ Cancelar participación
    if (e.target.closest("form")?.action.includes("cancelar_participacion.php")) {
        saveScroll();
    }

});

// ===================
// RESTAURAR SCROLL
// ===================
document.addEventListener("DOMContentLoaded", function() {

    // Si existe scroll guardado, restaurarlo
    const pos = sessionStorage.getItem("scrollPos");

    if (pos !== null) {
        window.scrollTo(0, parseInt(pos));
        sessionStorage.removeItem("scrollPos"); // limpiar para evitar efectos en visitas normales
        return;
    }

    // Si NO hay scroll guardado, entrar arriba
    window.scrollTo(0, 0);

    // Eliminar hash para evitar scroll automático
    if (window.location.hash) {
        history.replaceState(null, null, window.location.pathname + window.location.search);
    }
});
</script>
<script>
  document.addEventListener("DOMContentLoaded", () => {
    const stars = document.querySelectorAll(".star-rating .star");
    const ratingInput = document.getElementById("rating-value");
    let currentRating = 0;

    stars.forEach(star => {
        // Hover para mostrar selección provisional
        star.addEventListener("mouseover", function () {
            stars.forEach(s => s.classList.remove("hover"));
            const value = parseInt(this.dataset.value);

            for (let i = 0; i < value; i++) {
                stars[i].classList.add("hover");
            }
        });

        // Quitar hover si sale del contenedor
        star.parentElement.addEventListener("mouseleave", function () {
            stars.forEach(s => s.classList.remove("hover"));
        });

        // Click para seleccionar
        star.addEventListener("click", function () {
            currentRating = parseInt(this.dataset.value);
            ratingInput.value = currentRating;

            stars.forEach(s => s.classList.remove("selected"));
            for (let i = 0; i < currentRating; i++) {
                stars[i].classList.add("selected");
            }
        });
    });
});

</script>

<script>
  document.addEventListener("DOMContentLoaded", () => {
    const formRating = document.querySelector(".rating-form");
    const ratingInput = document.getElementById("rating-value");

    formRating.addEventListener("submit", function (e) {
        if (!ratingInput.value || ratingInput.value < 1) {
            e.preventDefault();
            mostrarMensaje("Debes seleccionar una calificación antes de continuar");
        }
    });
});

</script>
<script>
  function mostrarMensaje(texto) {
    const alerta = document.getElementById("alerta");
    alerta.innerText = texto;
    alerta.style.display = "block";

    setTimeout(() => {
        alerta.style.display = "none";
    }, 3000); // ⏱ 3 segundos
}

</script>
<script>
document.addEventListener("DOMContentLoaded", () => {
    const starsChange = document.querySelectorAll("#ratingChangeBox .star-rating .star");
    const ratingInputChange = document.getElementById("rating-value-change");
    let currentRatingChange = 0;

    if (starsChange.length > 0) {
        starsChange.forEach(star => {

            // Hover visual
            star.addEventListener("mouseover", function () {
                const value = parseInt(this.dataset.value);

                starsChange.forEach(s => s.classList.remove("hover"));
                for (let i = 0; i < value; i++) {
                    starsChange[i].classList.add("hover");
                }
            });

            // Quitar hover
            star.parentElement.addEventListener("mouseleave", function () {
                starsChange.forEach(s => s.classList.remove("hover"));
            });

            // Seleccionar nueva calificación
            star.addEventListener("click", function () {
                currentRatingChange = parseInt(this.dataset.value);
                ratingInputChange.value = currentRatingChange;

                starsChange.forEach(s => s.classList.remove("selected"));
                for (let i = 0; i < currentRatingChange; i++) {
                    starsChange[i].classList.add("selected");
                }
            });

        });
    }
});

document.addEventListener("click", function (e) {
    const btnEliminar = e.target.closest(".btn-eliminar");
    
    if (btnEliminar) {
        e.preventDefault();
        e.stopPropagation();
        
        const id = btnEliminar.dataset.id;
        const cat = btnEliminar.dataset.cat;
        const from = btnEliminar.dataset.from;

        Swal.fire({
            title: "¿Eliminar publicación?",
            text: "Esta acción no se puede deshacer.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            cancelButtonColor: "#3085d6",
            confirmButtonText: "Sí, eliminar",
            cancelButtonText: "Cancelar"
        }).then((result) => {
            if (result.isConfirmed) {
                // No guardamos scroll porque nos vamos de la página
                window.location.href = `eliminar_publicacion.php?id=${id}&cat=${encodeURIComponent(cat)}&from=${from}`;
            }
        });
    }
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar Mapa
    const lat = <?= $pub['latitud'] ?? 0 ?>;
    const lon = <?= $pub['longitud'] ?? 0 ?>;

    if (lat != 0 && lon != 0) {
        const map = L.map('map').setView([lat, lon], 15);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap'
        }).addTo(map);

        // Marcador y Círculo
        L.marker([lat, lon]).addTo(map).bindPopup('Ubicación aproximada');
        L.circle([lat, lon], {
            color: '#4267B2',
            fillColor: '#4267B2',
            fillOpacity: 0.2,
            radius: 500
        }).addTo(map);
    }
    
});
</script>
<script>
// 1. Inicializar Mapa (Solo si hay coordenadas)
document.addEventListener('DOMContentLoaded', function() {
    const lat = <?= $pub['latitud'] ?? 0 ?>;
    const lon = <?= $pub['longitud'] ?? 0 ?>;

    if (lat != 0 && lon != 0) {
        const map = L.map('map').setView([lat, lon], 15);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap'
        }).addTo(map);
        L.marker([lat, lon]).addTo(map).bindPopup('Ubicación aproximada').openPopup();
        L.circle([lat, lon], { color: '#4267B2', fillColor: '#4267B2', fillOpacity: 0.2, radius: 500 }).addTo(map);
    }
});

// 2. DELEGACIÓN DE EVENTOS GLOBAL (Maneja todos los clics de la página)
document.addEventListener("click", function(e) {
    
    // --- A. ELIMINAR PUBLICACIÓN ---
    const btnEliminar = e.target.closest(".btn-eliminar");
    if (btnEliminar) {
        e.preventDefault();
        e.stopPropagation();
        
        const id = btnEliminar.dataset.id;
        const cat = btnEliminar.dataset.cat;
        const from = btnEliminar.dataset.from; // Ahora tomará 'dashboard', 'fav', etc.

        Swal.fire({
            title: "¿Eliminar publicación?",
            text: "Esta acción no se puede deshacer.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            cancelButtonColor: "#3085d6",
            confirmButtonText: "Sí, eliminar",
            cancelButtonText: "Cancelar"
        }).then((result) => {
            if (result.isConfirmed) {
                // Redirección usando la variable 'from' correcta
                window.location.href = `eliminar_publicacion.php?id=${id}&cat=${encodeURIComponent(cat)}&from=${from}`;
            }
        });
        return; // Detener ejecución aquí
    }

    // --- B. CAMBIAR ESTADO ---
    const btnEstado = e.target.closest('.btn-toggle-estado');
    if (btnEstado) {
        e.preventDefault();
        e.stopPropagation();
        const { id, estado, cat } = btnEstado.dataset;
        // Usamos 'cat' para volver a la misma publicación tras recargar
        window.location.href = `cambiar_estado.php?id=${id}&estado=${estado}&cat=${encodeURIComponent(cat)}&from=cat`;
        return;
    }
    
    // --- C. RESPONDER COMENTARIO ---
    if (e.target.classList.contains("btn-responder")) {
        const id = e.target.dataset.id;
        const card = e.target.closest(".comentario");
        // Evitar duplicados
        if(card.nextElementSibling && card.nextElementSibling.classList.contains('respuesta-form')) return;
        
        card.insertAdjacentHTML("afterend", `
           <form action="comentar.php" method="POST" class="coment-form respuesta-form" style="margin-left:${card.style.marginLeft}; margin-top:10px;">
              <input type="hidden" name="publicacion_id" value="<?= $pub['id'] ?>">
              <input type="hidden" name="parent_id" value="${id}">
              <input type="hidden" name="from" value="<?= $from ?>">
              <input type="hidden" name="query" value="<?= http_build_query($_GET) ?>">
              <textarea name="comentario" required placeholder="Escribe tu respuesta..."></textarea>
              <button type="submit" class="btn-comentar">Responder</button>
           </form>
        `);
    }
});

// 3. Modal Reporte
const btnReport = document.getElementById("openReportModal");
const modalReport = document.getElementById("reportModal");
const closeReport = document.getElementById("closeReportModal");

if (btnReport && modalReport) {
    btnReport.addEventListener("click", () => modalReport.style.display = "flex");
}
if (closeReport && modalReport) {
    closeReport.addEventListener("click", () => modalReport.style.display = "none");
}

// 4. Estrellas (Rating)
document.addEventListener("DOMContentLoaded", () => {
    const stars = document.querySelectorAll(".star-rating .star");
    const ratingInput = document.getElementById("rating-value");
    
    if(stars.length > 0){
        stars.forEach(star => {
            star.addEventListener("mouseover", function () {
                const value = parseInt(this.dataset.value);
                stars.forEach(s => s.classList.remove("hover"));
                for (let i = 0; i < value; i++) stars[i].classList.add("hover");
            });
            star.parentElement.addEventListener("mouseleave", function () {
                stars.forEach(s => s.classList.remove("hover"));
            });
            star.addEventListener("click", function () {
                const val = parseInt(this.dataset.value);
                if(ratingInput) ratingInput.value = val;
                stars.forEach(s => s.classList.remove("selected"));
                for (let i = 0; i < val; i++) stars[i].classList.add("selected");
            });
        });
    }
    
    // Validación simple
    const formRating = document.querySelector(".rating-form");
    if(formRating && ratingInput) {
        formRating.addEventListener("submit", function(e){
            if(!ratingInput.value) {
                e.preventDefault();
                alert("Debes seleccionar una calificación");
            }
        });
    }
});
</script>
<script>
document.getElementById('btnShare').addEventListener('click', async () => {
    const shareData = {
        title: '<?= htmlspecialchars($pub['titulo']) ?>',
        text: 'Mira esta publicación en LofHood: <?= htmlspecialchars($pub['titulo']) ?>',
        url: window.location.href
    };

    // 1. Si es celular o navegador moderno, abrir menú nativo (WhatsApp, etc.)
    if (navigator.share) {
        try {
            await navigator.share(shareData);
        } catch (err) {
            console.log('Error al compartir o cancelado por usuario');
        }
    } else {
        // 2. Si es PC, copiar al portapapeles
        try {
            await navigator.clipboard.writeText(window.location.href);
            // Usamos tu SweetAlert que ya tienes instalado
            Swal.fire({
                icon: 'success',
                title: '¡Enlace copiado!',
                text: 'Listo para pegar y compartir.',
                timer: 1500,
                showConfirmButton: false
            });
        } catch (err) {
            alert('No se pudo copiar el enlace.');
        }
    }
});
</script>
<?php include __DIR__ . "/includes/footer.php"; ?>
