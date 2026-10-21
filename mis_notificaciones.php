<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require __DIR__ . "/includes/conexion.php";

if (empty($_SESSION['user_id'])) {
    header("Location: login.php?redirect=mis_notificaciones.php");
    exit;
}

$usuario_id = $_SESSION['user_id'];

// =========================================================
// 1. OBTENER UBICACIÓN DEL USUARIO (IGUAL QUE EN INDEX)
// =========================================================

// Coordenadas por defecto (Quilicura) si no hay nada guardado
$default_lat = -33.3644; 
$default_lon = -70.7380;

$mi_lat = $default_lat;
$mi_lon = $default_lon;

// Prioridad: 1. Cookie, 2. Sesión
if (isset($_COOKIE['user_lat'])) {
    $mi_lat = $_COOKIE['user_lat'];
    $mi_lon = $_COOKIE['user_lon'];
} elseif (isset($_SESSION['mi_latitud'])) {
    $mi_lat = $_SESSION['mi_latitud'];
    $mi_lon = $_SESSION['mi_longitud'];
}

// Configuración del Radar
$radio_km = 3; // Radio de 3 KM (Igual que index)
$dias_atras = 7; // Mostrar notificaciones de los últimos 7 días

// =========================================================
// 2. CONSULTA "REAL" (Geolocalizada + Reciente)
// =========================================================

$sql = "
    SELECT p.id as publicacion_id, p.titulo, p.categoria, p.fecha, u.username as autor,
    -- Fórmula Haversine para calcular distancia
    ( 6371 * acos( 
        cos( radians($mi_lat) ) * cos( radians( p.latitud ) ) * cos( radians( p.longitud ) - radians($mi_lon) ) + 
        sin( radians($mi_lat) ) * sin( radians( p.latitud ) ) 
      ) 
    ) AS distancia
    FROM publicaciones p
    INNER JOIN usuarios u ON p.usuario_id = u.id
    WHERE p.usuario_id != ?  -- No notificarme de mis propias cosas
    AND p.fecha >= DATE_SUB(NOW(), INTERVAL $dias_atras DAY) -- Solo cosas recientes
    HAVING distancia < $radio_km
    ORDER BY p.fecha DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();

include __DIR__ . "/includes/header.php";
?>

<section class="notificaciones-section" style="max-width: 800px; margin: 40px auto; padding: 20px;">
    
    <div style="text-align:center; margin-top: 40px;">
        <h2 class="categories-title">🔔 Actividad Reciente</h2>
        <p class="categories-subtitle">
            Lo que ha pasado a <strong><?= $radio_km ?> km</strong> de ti en los últimos <strong><?= $dias_atras ?> días</strong>.
        </p>
        <p style="font-size:12px; color:#888;">
            📍 Tu ubicación ref: (<?= round($mi_lat, 4) ?>, <?= round($mi_lon, 4) ?>)
        </p>
    </div>

    <div class="notificaciones-lista" style="margin-top:30px;">
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                
                <?php 
                    // Generar mensaje dinámico según categoría
                    $mensaje = "";
                    $icono = "";
                    $es_alerta = ($row['categoria'] === 'alerta');
                    
                    switch($row['categoria']) {
                        case 'venta':
                            $icono = "🛍️";
                            $mensaje = "<strong>@{$row['autor']}</strong> ha publicado una nueva venta cerca de ti: <em>{$row['titulo']}</em>";
                            break;
                        case 'trabajo':
                            $icono = "💼";
                            $mensaje = "<strong>@{$row['autor']}</strong> ofrece una oportunidad laboral: <em>{$row['titulo']}</em>";
                            break;
                        case 'actividad':
                            $icono = "🎉";
                            $mensaje = "Nueva actividad vecinal organizada por <strong>@{$row['autor']}</strong>: <em>{$row['titulo']}</em>";
                            break;
                        case 'alerta':
                            $icono = "🚨";
                            $mensaje = "<strong>¡ALERTA DE SEGURIDAD!</strong> Reportada por @{$row['autor']}: <em>{$row['titulo']}</em>";
                            break;
                        case 'servicio':
                            $icono = "🛠️";
                            $mensaje = "<strong>@{$row['autor']}</strong> ofrece un servicio en el barrio: <em>{$row['titulo']}</em>";
                            break;
                        default:
                            $icono = "📢";
                            $mensaje = "Nueva publicación cercana: <em>{$row['titulo']}</em>";
                    }

                    // Estilos según tipo
                    $clase_extra = $es_alerta ? 'is-alert' : 'nueva';
                ?>

                <div class="notificacion-card <?= $clase_extra ?>">
                    <div class="notif-icon">
                        <?= $icono ?>
                    </div>
                    
                    <div class="notif-content">
                        <p class="notif-mensaje">
                            <?= $mensaje ?>
                        </p>
                        <span class="notif-fecha">
                            📅 <?= date("d/m/Y H:i", strtotime($row['fecha'])) ?> 
                            <span style="margin-left:10px; color:#2cd19b;">📍 A <?= round($row['distancia'], 2) ?> km</span>
                        </span>
                    </div>

                    <div class="notif-action">
                        <a href="ver_publicacion.php?id=<?= $row['publicacion_id'] ?>&cat=<?= $row['categoria'] ?>&from=notif" class="btn-ver-notif">
                            Ver
                        </a>
                    </div>
                </div>

            <?php endwhile; ?>
        <?php else: ?>
            <div class="no-notif">
                <p>🔕 No hay actividad reciente cerca de tu ubicación actual.</p>
                <p style="font-size:13px;">(Radio: <?= $radio_km ?>km)</p>
                <a href="publicaciones.php" class="btn-volver" style="margin-top:15px;">Ver todas las zonas</a>
            </div>
        <?php endif; ?>
    </div>

</section>

<style>
/* Estilos Generales */
.notificaciones-lista {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.notificacion-card {
    display: flex;
    align-items: center;
    background: #fff;
    padding: 15px;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    border-left: 5px solid #ccc;
    transition: transform 0.2s;
}

/* Notificación Estándar */
.notificacion-card.nueva {
    border-left-color: #2cd19b; /* Verde LofHood */
    background: #fff;
}

/* 🚨 Notificación de ALERTA (Rojo) */
.notificacion-card.is-alert {
    border-left-color: #dc3545;
    background: #fff5f5;       
    border: 1px solid #ffcccc;  
    border-left-width: 6px;
}

.notificacion-card:hover {
    transform: translateX(5px);
}

.notif-icon {
    font-size: 26px;
    margin-right: 15px;
    min-width: 40px;
    text-align: center;
}

.notif-content {
    flex-grow: 1;
}

.notif-mensaje {
    margin: 0;
    color: #333;
    font-size: 14px;
    line-height: 1.4;
}
.notif-mensaje strong {
    font-weight: 700;
}
.notif-mensaje em {
    color: #555;
    font-style: normal;
    font-weight: 600;
}

.notif-fecha {
    font-size: 11px;
    color: #888;
    display: block;
    margin-top: 6px;
}

.btn-ver-notif {
    background: #6e6e6e;
    color: white;
    padding: 8px 14px;
    border-radius: 6px;
    text-decoration: none;
    font-size: 13px;
    font-weight: bold;
    transition: 0.2s;
    white-space: nowrap;
}

.btn-ver-notif:hover {
    background: #515151;
}

.is-alert .btn-ver-notif {
    background: #dc3545;
}
.is-alert .btn-ver-notif:hover {
    background: #b02a37;
}

.no-notif {
    text-align: center;
    padding: 40px;
    background: #f9f9f9;
    border-radius: 10px;
    color: #666;
}
</style>

<?php include __DIR__ . "/includes/footer.php"; ?>