<?php
// =========================================================
// 1. LÓGICA PHP (ANTES DE CUALQUIER HTML)
// =========================================================

// Iniciar sesión
if (session_status() === PHP_SESSION_NONE) session_start();

// Conexión a Base de Datos
require __DIR__ . '/includes/init.php';

// --- CONFIGURACIÓN DEL RADAR VECINAL ---

// Coordenadas por defecto (Quilicura)
$default_lat = -33.3644; 
$default_lon = -70.7380;

// Variables finales que usaremos
$mi_lat = $default_lat;
$mi_lon = $default_lon;

// 1. Detectar si el usuario nos envía coordenadas por URL (JS)
if (isset($_GET['lat']) && isset($_GET['lon'])) {
    $mi_lat = $_GET['lat'];
    $mi_lon = $_GET['lon'];
    
    // Guardar en sesión y cookie (1 hora)
    $_SESSION['mi_latitud'] = $mi_lat;
    $_SESSION['mi_longitud'] = $mi_lon;
    
    // ESTO AHORA SÍ FUNCIONA (porque no hay HTML antes)
    setcookie('user_lat', $mi_lat, time() + 3600, "/");
    setcookie('user_lon', $mi_lon, time() + 3600, "/");

} 
// 2. Si no, buscar en Cookies
elseif (isset($_COOKIE['user_lat'])) {
    $mi_lat = $_COOKIE['user_lat'];
    $mi_lon = $_COOKIE['user_lon'];
} 
// 3. Si no, buscar en Sesión
elseif (isset($_SESSION['mi_latitud'])) {
    $mi_lat = $_SESSION['mi_latitud'];
    $mi_lon = $_SESSION['mi_longitud'];
}
// 4. Si no, se queda con el default definido arriba.

$radio_km = 3; // Radio de búsqueda

// =========================================================
// 2. AHORA SÍ CARGAMOS EL DISEÑO (HTML)
// =========================================================
include __DIR__ . '/includes/header.php';
?>

<?php if (!isset($_COOKIE['user_lat']) && !isset($_GET['lat'])): ?>
<script>
document.addEventListener("DOMContentLoaded", function() {
    if (navigator.geolocation) {
        console.log("📍 Solicitando ubicación...");
        navigator.geolocation.getCurrentPosition(
            function(position) {
                var lat = position.coords.latitude;
                var lon = position.coords.longitude;
                // Recargar para guardar cookies
                window.location.href = "?lat=" + lat + "&lon=" + lon;
            },
            function(error) {
                console.log("Ubicación denegada. Usando defecto.");
            }
        );
    }
});
</script>
<?php endif; ?>

<div style="background:#333; color:#fff; text-align:center; padding:5px; font-size:12px;">
    📡 Viendo publicaciones cerca de: 
    <strong><?= ($mi_lat == $default_lat) ? "Ubicación por defecto (Quilicura)" : "Tu Ubicación Actual" ?></strong>
    (<?= round($mi_lat, 4) ?>, <?= round($mi_lon, 4) ?>)
</div>

<section class="hero" style="
  background: url('assets/imagenes/bg-hero.jpg') no-repeat center center;
  background-size: cover;
  height: 600px;
  display: flex;
  justify-content: center;
  align-items: center;
  text-align: center;
  color: #fff;
  position: relative;
">
  <div style="
    position:absolute;
    top:0; left:0; width:100%; height:100%;
    background: rgba(46, 46, 46, 0.4);
  "></div>
  <div class="hero-content" style="position: relative; z-index: 1;">
    <h1>¡Bienvenido a LofHood!</h1>
    <p>Todo para sentirte parte de tu comunidad</p>
  </div>
</section>

<section class="presentacion">
  <div class="presentacion-container">
    <div class="presentacion-imagen">
      <img src="assets/imagenes/motor.png" alt="Presentación LofHood">
    </div>
    <div class="presentacion-texto">
      <h2 class="presentacion-titulo"><span style="color:#a5a5a5;">LOF</span>HOOD</h2>
      <h3 class="presentacion-sub">Conecta con tu comunidad.</h3>
      <p>
        LofHood es una plataforma creada para unir a los vecinos, fomentar la colaboración,
        la seguridad y el apoyo mutuo dentro de tu sector. Comparte actividades, encuentra ofertas locales,
        publica ventas de artículos o busca oportunidades laborales. Somos una comunidad creciendo contigo.
      </p>
      <p>
        Aquí podrás <strong>publicar, comunicarte y participar</strong> de manera directa con tus vecinos.
        Nuestro enfoque es facilitar un entorno seguro, inclusivo y participativo.
      </p>
      <p>En LofHood buscamos reconectar a las personas con su comunidad. Creamos un espacio donde vecinos pueden 
        compartir oportunidades, ayudar y crecer juntos, fortaleciendo el sentido de pertenencia y colaboración local.</p>
    </div>
  </div>
</section>

<style>
.presentacion { width: 100%; display: flex; justify-content: center; padding: 3rem 1rem; }
.presentacion-container { max-width: 1200px; display: flex; align-items: center; gap: 3rem; flex-wrap: wrap; justify-content: center; }
.presentacion-imagen img { width: 350px; height: auto; display: block; }
.presentacion-texto { max-width: 600px; text-align: left; }
.presentacion-titulo { font-size: 2.5rem; font-weight: 800; text-align: left; }
.presentacion-sub { margin-top: -10px; margin-bottom: 20px; font-size: 1.4rem; color: #444; }
@media (max-width: 900px) {
  .presentacion-container { flex-direction: column; text-align: center; }
  .presentacion-texto { text-align: center; }
}
</style>

<?php
$sql = "
    SELECT id, titulo, imagen, categoria, fecha, precio, salario,
    -- Fórmula Haversine
    ( 6371 * acos( 
        cos( radians($mi_lat) ) * cos( radians( latitud ) ) * cos( radians( longitud ) - radians($mi_lon) ) + 
        sin( radians($mi_lat) ) * sin( radians( latitud ) ) 
      ) 
    ) AS distancia
    FROM publicaciones
    WHERE categoria != 'alerta' -- ✅ ESTO FILTRA LAS ALERTAS (para que no salgan aquí)
    HAVING distancia < $radio_km
    ORDER BY distancia ASC, fecha DESC
    LIMIT 20
";
$ultimas = $conn->query($sql);
?>

<section class="ultimas-pubs">
  <h2 class="section-title">Últimas Publicaciones cerca de ti</h2>
  <p class="section-subtitle">Lo más reciente en tu radio de <?= $radio_km ?> km</p>

  <?php if ($ultimas && $ultimas->num_rows > 0): ?>
      <div class="pubs-grid">
        <?php while($p = $ultimas->fetch_assoc()): ?>
         <a href="ver_publicacion.php?id=<?= $p['id'] ?>&cat=<?= urlencode($p['categoria']) ?>&from=index&<?= http_build_query($_GET) ?>">
            <div class="pub-img-box">
              <?php if(!empty($p['imagen'])): ?>
                <img src="uploads/<?= htmlspecialchars($p['imagen']) ?>" alt="Imagen" class="pub-img" loading="lazy">
              <?php else: ?>
    <div class="no-image-box">
        <span class="no-image-icon">✖</span>
        <span class="no-image-text">Sin Foto</span>
    </div>
<?php endif; ?>
            </div>
            <div class="pub-info">
              <h3 class="pub-title"><?= htmlspecialchars($p['titulo']) ?></h3>
              
              <?php if (!empty($p['precio'])): ?>
                  <p style="color:#2cd19b; font-weight:bold; font-size:14px; margin: 4px 0;">
                      $<?= number_format($p['precio'], 0, ',', '.') ?>
                  </p>
              <?php elseif (!empty($p['salario'])): ?>
                  <p style="color:#2b6cb0; font-weight:bold; font-size:13px; margin: 4px 0;">
                      💰 <?= htmlspecialchars($p['salario']) ?>
                  </p>
              <?php endif; ?>
              <span class="pub-cat"><?= ucfirst($p['categoria']) ?></span>
              <span style="font-size:12px; color:#888; display:block; margin-top:5px;">
                  📍 A <?= round($p['distancia'], 1) ?> km de ti
              </span>
            </div>
          </a>
        <?php endwhile; ?>
      </div>
  <?php else: ?>
      <p style="text-align:center; color:#666; margin-top:20px;">
          No hay publicaciones recientes cerca de tu ubicación. <br>
          ¡Sé el primero en publicar algo!
      </p>
  <?php endif; ?>
</section>

<?php
$radio_alertas = 3;
$sqlAlertas = "
    SELECT p.id, p.titulo, p.descripcion, p.fecha, p.categoria, u.username, p.imagen,
    ( 6371 * acos( 
        cos( radians($mi_lat) ) * cos( radians( p.latitud ) ) * cos( radians( p.longitud ) - radians($mi_lon) ) + 
        sin( radians($mi_lat) ) * sin( radians( p.latitud ) ) 
      ) 
    ) AS distancia
    FROM publicaciones p
    INNER JOIN usuarios u ON p.usuario_id = u.id
    WHERE p.categoria = 'alerta'
    HAVING distancia < $radio_alertas
    ORDER BY p.fecha DESC
    LIMIT 4
";
$alertas = $conn->query($sqlAlertas);
?>

<?php if ($alertas && $alertas->num_rows > 0): ?>
<section class="alertas-section">
  <div class="alertas-header">
    <h2>📢 Alertas Vecinales Cercanas</h2>
    <p>Reportes de seguridad a menos de <?= $radio_alertas ?> km</p>
  </div>

  <div class="alertas-grid">
    <?php while($a = $alertas->fetch_assoc()): ?>
      <a href="ver_publicacion.php?id=<?= $a['id'] ?>&cat=alerta&from=index" class="alerta-card">
        <div class="alerta-icon-box">
           <?php if(!empty($a['imagen'])): ?>
              <img src="uploads/<?= htmlspecialchars($a['imagen']) ?>" class="alerta-mini-img">
           <?php else: ?>
              <span class="alerta-emoji">🚨</span>
           <?php endif; ?>
        </div>
        <div class="alerta-info">
           <h4><?= htmlspecialchars($a['titulo']) ?></h4>
           <span class="alerta-meta">Reportado por <b>@<?= htmlspecialchars($a['username']) ?></b></span>
           <span class="alerta-time">Hace <?= round((time() - strtotime($a['fecha'])) / 60) ?> min</span>
           <span style="font-size:11px; color:#d33; font-weight:bold; display:block;">📍 A <?= round($a['distancia'], 1) ?> km</span>
        </div>
      </a>
    <?php endwhile; ?>
  </div>
</section>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>