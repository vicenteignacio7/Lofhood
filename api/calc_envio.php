<?php
// admin/api_calc_envio.php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/conexion.php';
require_once __DIR__ . '/../includes/envio_config.php';
require_once __DIR__ . '/../includes/geo_helpers.php';

$direccion = trim($_POST['direccion'] ?? '');
$envio     = trim($_POST['envio'] ?? '');     // 'domicilio-puente' | 'domicilio-quilicura'
$comuna    = trim($_POST['comuna'] ?? '');    // opcional, la fijamos por envío si viene vacía

if ($direccion === '' || $envio === '') {
    echo json_encode(['ok'=>false,'error'=>'Faltan datos.']); exit;
}

// Normalización de abreviaturas (igual que antes)
function normalize_street($s) {
    $s = preg_replace('/\s+/', ' ', trim($s));
    $map = [
        '/\bAv[.]?\b/i'   => 'Avenida',
        '/\bPje[.]?\b/i'  => 'Pasaje',
        '/\bSta[.]?\b/i'  => 'Santa',
        '/\bSto[.]?\b/i'  => 'Santo',
        '/\bGral[.]?\b/i' => 'General',
        '/\bPdte[.]?\b/i' => 'Presidente',
        '/\bCl[.]?\b/i'   => 'Calle',
    ];
    foreach ($map as $re=>$rep) $s = preg_replace($re, $rep, $s);
    return $s;
}

$direccionN = normalize_street($direccion);

// ¿Qué local/comuna usamos?
if ($envio === 'domicilio-quilicura') {
    $localLat = Q_LOCAL_LAT; $localLon = Q_LOCAL_LON;
    $maxKm = Q_MAX_KM; $base = Q_BASE_ENVIO; $umbral = Q_UMBRAL_EXTRA_KM; $extraKm = Q_EXTRA_POR_KM;
    if ($comuna === '') $comuna = 'Quilicura';
} else {
    // por defecto Puente
    $localLat = PA_LOCAL_LAT; $localLon = PA_LOCAL_LON;
    $maxKm = PA_MAX_KM; $base = PA_BASE_ENVIO; $umbral = PA_UMBRAL_EXTRA_KM; $extraKm = PA_EXTRA_POR_KM;
    if ($comuna === '') $comuna = 'Puente Alto';
}

// Candidatos si no hay número
$cands = [$direccionN];
if (!preg_match('/\d/', $direccionN)) {
    $cands[] = $direccionN.' 100';
    $cands[] = $direccionN.' 1';
}

$geo = null;
foreach ($cands as $cand) {
    $geo = geocode_with_cache($conn, $cand, $comuna);
    if ($geo) break;
}

if (!$geo) {
    echo json_encode([
        'ok'=>true,'allowed'=>false,'km'=>null,'costo'=>0,
        'msg'=>'No pudimos ubicar la dirección. Ingresa calle y número.',
        'detalle'=>'Geocoding falló'
    ]); exit;
}

$lat = (float)$geo['lat']; $lon = (float)$geo['lon'];
if (!function_exists('haversine_km')) {
    function haversine_km($a,$b,$c,$d){ return haversine_distance($a,$b,$c,$d); }
}
$km = round(haversine_km($localLat, $localLon, $lat, $lon), 2);

if ($km > $maxKm) {
    echo json_encode([
        'ok'=>true,'allowed'=>false,'km'=>$km,'costo'=>0,
        'msg'=>'Dirección fuera del radio de despacho.',
        'detalle'=>"Distancia {$km}km (máx {$maxKm}km)"
    ]); exit;
}

$costo = (int)$base;
if ($km > $umbral) {
    $extra = ceil(max(0, $km - $umbral));
    $costo += $extra * (int)$extraKm;
}

echo json_encode([
    'ok'=>true,'allowed'=>true,'km'=>$km,'costo'=>$costo,
    'msg'=>'Dirección validada','detalle'=>"Distancia: {$km} km"
]);
