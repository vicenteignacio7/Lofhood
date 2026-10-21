<?php
// transbank_config.php
declare(strict_types=1);

// 1) Composer Autoload
require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;
use Transbank\Webpay\Options;

// 2) Cargar .env
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// 3) Credenciales y entorno
$commerceCode = $_ENV['COMMERCE_CODE']   ?? '';
$apiKey       = $_ENV['API_KEY_SECRET']  ?? '';
$envName      = strtoupper($_ENV['ENVIRONMENT'] ?? 'INTEGRACION');

if ($commerceCode === '' || $apiKey === '') {
    throw new RuntimeException('Faltan COMMERCE_CODE o API_KEY_SECRET en .env');
}

// 4) Mapear entorno
$integrationType = ($envName === 'PRODUCCION' || $envName === 'PRODUCTION')
    ? Options::ENVIRONMENT_PRODUCTION
    : Options::ENVIRONMENT_INTEGRATION;

// 5) Crear Options (orden correcto: apiKey, commerceCode, integrationType, timeout)
return new Options(
    $apiKey,
    $commerceCode,
    $integrationType,
    Options::DEFAULT_TIMEOUT
);
