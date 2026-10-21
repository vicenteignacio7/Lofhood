<?php
declare(strict_types=1);

/**
 * 1) Configuración cookie de sesión
 */
$isHttps = (
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
);
// Verificamos si la sesión NO está iniciada antes de configurar parametros
if (session_status() === PHP_SESSION_NONE) {
    // Usamos la sintaxis clásica compatible con todo y segura contra strict_types
    session_set_cookie_params(
        0,                // lifetime
        '/; samesite=Lax',// path (truco para samesite compatible)
        '',               // domain
        (bool) $isHttps,  // forzamos a booleano por seguridad
        true              // httponly
    );
    
    session_start();
}
/**
 * 2) Composer (si existe) + carga .env (con fallback manual compatible PHP 7)
 */
$PROJECT_ROOT = dirname(__DIR__); // /home/USER/public_html

// 2.a) Autoload si existe
$autoload = $PROJECT_ROOT . '/vendor/autoload.php';
if (is_file($autoload)) {
    require $autoload;
}

// 2.b) Intentar con vlucas/phpdotenv si está disponible
$envLoaded = false;
try {
    if (class_exists('Dotenv\\Dotenv')) {
        // Sobrescribe variables previas del hosting
        Dotenv\Dotenv::createImmutable($PROJECT_ROOT)->overload();
        $envLoaded = true;
    }
} catch (\Throwable $e) {
    // seguimos con fallback
}

// 2.c) Fallback: cargador .env manual (sin Composer) compatible PHP 7
if (!$envLoaded) {
    $tryFiles = array(
        $PROJECT_ROOT . '/.env',
        dirname($PROJECT_ROOT) . '/.env',
    );
    foreach ($tryFiles as $envFile) {
        if (is_file($envFile) && is_readable($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || $line[0] === '#') continue;
                if (strpos($line, '=') === false) continue;

                list($name, $value) = explode('=', $line, 2);
                $name  = trim($name);
                $value = trim($value);

                // quitar comillas envolventes si existen
                $len = strlen($value);
                if ($len >= 2) {
                    $first = $value[0];
                    $last  = $value[$len - 1];
                    if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                        $value = substr($value, 1, $len - 2);
                    }
                }

                $_ENV[$name] = $value;
                // también exportar a getenv()
                @putenv($name . '=' . $value);
            }
            $envLoaded = true;
            break;
        }
    }
    if (!$envLoaded) {
        error_log('WARNING: .env no encontrado o no legible en ' . $PROJECT_ROOT);
    }
}

/**
 * 3) Conexión a la base de datos
 */
require __DIR__ . '/conexion.php';
