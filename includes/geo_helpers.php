<?php
// includes/geo_helpers.php
// Funciones para geocodificar y calcular distancias

/**
 * Geocodifica una dirección usando Nominatim (OpenStreetMap) con cache en MySQL
 */
function geocode_with_cache(mysqli $db, string $address, string $comuna = ''): ?array {
    // 1) Buscar en cache
    if ($st = $db->prepare("SELECT lat, lon FROM geocode_cache WHERE address=? LIMIT 1")) {
        $st->bind_param('s', $address);
        $st->execute();
        if ($row = $st->get_result()->fetch_assoc()) {
            return ['lat' => (float)$row['lat'], 'lon' => (float)$row['lon']];
        }
    }

    // 2) Normalizar dirección
    $addr = trim(preg_replace('/\s+/', ' ', $address));

    // Si se pasa la comuna, la agregamos
    if ($comuna) {
        if (stripos($addr, $comuna) === false) {
            $addr .= ', ' . $comuna;
        }
    }
    // Siempre agregar Chile
    if (stripos($addr, 'Chile') === false) {
        $addr .= ', Chile';
    }

    // --- Intento A: consulta estructurada ---
    $paramsA = [
        'format'           => 'json',
        'q'                => $addr,
        'countrycodes'     => 'cl',
        'limit'            => 1,
        'accept-language'  => 'es',
        'addressdetails'   => 0
    ];
    $url1 = "https://nominatim.openstreetmap.org/search?" . http_build_query($paramsA);

    // --- Intento B: acotado a la comuna ---
    $viewboxes = [
        'Puente Alto' => '-70.625,-33.520,-70.510,-33.650',
        'Quilicura'   => '-70.781,-33.318,-70.718,-33.404'
    ];
    $paramsB = [
        'format'           => 'json',
        'q'                => $addr,
        'countrycodes'     => 'cl',
        'limit'            => 1,
        'accept-language'  => 'es',
        'bounded'          => 1,
        'addressdetails'   => 0
    ];
    if (isset($viewboxes[$comuna])) {
        $paramsB['viewbox'] = $viewboxes[$comuna];
    }
    $url2 = "https://nominatim.openstreetmap.org/search?" . http_build_query($paramsB);

    $ua = 'tokiramen.cl/checkout (contacto@tokiramen.cl)';

    $do_fetch = function (string $url) use ($ua) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERAGENT      => $ua,
            CURLOPT_TIMEOUT        => 8,
        ]);
        $raw = curl_exec($ch);
        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($http === 429) return ['rate_limited' => true];
        $data = json_decode($raw, true);
        return (is_array($data) && !empty($data[0]['lat']) && !empty($data[0]['lon']))
            ? ['lat' => (float)$data[0]['lat'], 'lon' => (float)$data[0]['lon']]
            : null;
    };

    // Intento A
    $res = $do_fetch($url1);
    if (isset($res['rate_limited'])) {
        usleep(1100000); // Esperar 1.1s
        $res = $do_fetch($url1);
    }
    if (is_array($res) && isset($res['lat'])) {
        save_cache($db, $address, $res['lat'], $res['lon']);
        return $res;
    }

    // Intento B
    $res = $do_fetch($url2);
    if (isset($res['rate_limited'])) {
        usleep(1100000);
        $res = $do_fetch($url2);
    }
    if (is_array($res) && isset($res['lat'])) {
        save_cache($db, $address, $res['lat'], $res['lon']);
        return $res;
    }

    return null;
}

/**
 * Guarda en cache
 */
function save_cache(mysqli $db, string $address, float $lat, float $lon): void {
    if ($ins = $db->prepare("INSERT INTO geocode_cache(address, lat, lon) VALUES (?, ?, ?) 
        ON DUPLICATE KEY UPDATE lat=VALUES(lat), lon=VALUES(lon)")) {
        $ins->bind_param('sdd', $address, $lat, $lon);
        $ins->execute();
    }
}

/**
 * Calcula distancia en km entre dos coordenadas (Haversine)
 */
function haversine_distance(float $lat1, float $lon1, float $lat2, float $lon2): float {
    $earth_radius = 6371; // km
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat/2) * sin($dLat/2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon/2) * sin($dLon/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    return $earth_radius * $c;
}
