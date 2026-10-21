<?php
// admin/export_pedidos.php
declare(strict_types=1);
session_start();

// ===== Debug opcional =====
if (isset($_GET['debug'])) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}

// Solo ADMIN
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../includes/conexion.php';
$mysqli = $conn;
if ($mysqli->connect_errno) {
    die('Error al conectar: ' . $mysqli->connect_error);
}

/* ===== Cargar autoloaders ===== */
// 1) Autoloader principal (Transbank y resto del sistema)
$mainAutoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($mainAutoload)) {
    require_once $mainAutoload;
}

// 2) Autoloader aislado de PhpSpreadsheet
$pssAutoload = __DIR__ . '/../vendor_extra/phpspreadsheet/vendor/autoload.php';
if (file_exists($pssAutoload)) {
    require_once $pssAutoload;
} else {
    if (isset($_GET['debug'])) {
        die("No se encontró PhpSpreadsheet en: {$pssAutoload}");
    }
    http_response_code(500);
    exit;
}

/* ===== IMPORTS (deben estar FUERA de funciones/ifs) ===== */
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

/* ===== Helpers UI ===== */
setlocale(LC_TIME, 'es_ES.UTF-8', 'es_ES', 'Spanish_Spain', 'es');

function monthOptions(int $selectedMonth): string {
    $out = '';
    for ($m = 1; $m <= 12; $m++) {
        $sel = ($m === $selectedMonth) ? 'selected' : '';
        $name = ucfirst(strftime('%B', mktime(0,0,0,$m,1)));
        $out .= "<option value=\"$m\" $sel>$name</option>";
    }
    return $out;
}
function yearOptions(int $selectedYear): string {
    $current = (int)date('Y');
    $out = '';
    for ($y = $current - 4; $y <= $current + 1; $y++) {
        $sel = ($y === $selectedYear) ? 'selected' : '';
        $out .= "<option value=\"$y\" $sel>$y</option>";
    }
    return $out;
}

/* ===== Parámetros ===== */
$group  = $_GET['group']  ?? 'all';
$month  = isset($_GET['month']) ? max(1, min(12, (int)$_GET['month'])) : (int)date('n');
$year   = isset($_GET['year'])  ? (int)$_GET['year'] : (int)date('Y');
$action = $_GET['action'] ?? '';

$startDate = sprintf('%04d-%02d-01 00:00:00', $year, $month);
$endDate   = date('Y-m-t 23:59:59', strtotime($startDate));

$map = [
    'q_domi'   => ['domicilio-quilicura'],
    'q_retiro' => ['retiro-quilicura'],
    'p_domi'   => ['domicilio-puente'],
    'p_retiro' => ['retiro-puente'],
    'paket'    => ['paket'],
    'q_all'    => ['domicilio-quilicura','retiro-quilicura','paket'],
    'p_all'    => ['domicilio-puente','retiro-puente','paket'],
    'all'      => ['domicilio-quilicura','retiro-quilicura','domicilio-puente','retiro-puente','paket'],
];
$envios = $map[$group] ?? $map['all'];

/* ===== Descargar XLSX ===== */
if ($action === 'download') {
    try {
        $placeholders = implode(',', array_fill(0, count($envios), '?'));

        $sql = "
            SELECT 
                pr.id,
                pr.nombre_envio AS cliente,
                pr.comuna_envio AS comuna,
                pr.direccion_envio AS direccion,
                pr.telefono_envio AS telefono,
                pr.envio,
                pr.fecha,
                pr.estado,
                pr.total,
                p.authorization_code,
                p.amount AS pagado_clp,
                p.created_at AS pagado_en,
                det.detalle AS detalle
            FROM pedidos pr
            INNER JOIN (
                SELECT p1.*
                FROM pagos p1
                INNER JOIN (
                    SELECT order_id, MAX(created_at) AS last_created
                    FROM pagos
                    GROUP BY order_id
                ) u ON u.order_id = p1.order_id AND p1.created_at = u.last_created
                LEFT JOIN pagos px
                   ON px.order_id = p1.order_id
                  AND px.created_at = p1.created_at
                  AND px.id > p1.id
                WHERE px.id IS NULL
                  AND p1.status = 'AUTHORIZED'
            ) p ON p.order_id = pr.id
            LEFT JOIN (
                SELECT 
                    dp.pedido_id,
                    GROUP_CONCAT(
                        CONCAT(
                            dp.cantidad, 'x ',
                            dp.nombre_producto,
                            ' ($', dp.precio, ' c/u = $', dp.subtotal, ')'
                        )
                        ORDER BY dp.id SEPARATOR ' | '
                    ) AS detalle
                FROM detalle_pedido dp
                GROUP BY dp.pedido_id
            ) det ON det.pedido_id = pr.id
            WHERE pr.envio IN ($placeholders)
              AND pr.fecha BETWEEN ? AND ?
            ORDER BY pr.fecha DESC
        ";

        $stmt = $mysqli->prepare($sql);
        if (!$stmt) {
            throw new Exception("Error al preparar SQL: " . $mysqli->error);
        }

        $types  = str_repeat('s', count($envios)) . 'ss';
        $params = array_merge($envios, [$startDate, $endDate]);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        // Crear hoja
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Pedidos Pagados');

        // Encabezados
        $headers = [
            'ID Pedido','Cliente','Comuna','Dirección','Teléfono','Tipo Envío',
            'Fecha Pedido','Estado Pedido','Total Pedido (CLP)','Monto Pagado (CLP)',
            'Código Autorización','Fecha Pago','Detalle'
        ];
        $sheet->fromArray($headers, null, 'A1');

        // Estilos encabezado
        $sheet->getStyle('A1:M1')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E2E8F0']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CBD5E1']
                ]
            ]
        ]);

        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:M1');

        // Rellenar datos
        $row = 1;
        while ($r = $result->fetch_assoc()) {
            $row++;
            $sheet->fromArray([
                (int)$r['id'], $r['cliente'], $r['comuna'], $r['direccion'],
                $r['telefono'], $r['envio'], $r['fecha'], $r['estado'],
                (float)$r['total'], (float)$r['pagado_clp'], $r['authorization_code'],
                $r['pagado_en'], $r['detalle'] ?? ''
            ], null, "A{$row}");
        }
        $lastDataRow = max(2, $row);

        // Formatos numéricos
        $sheet->getStyle("I2:I{$lastDataRow}")
              ->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle("J2:J{$lastDataRow}")
              ->getNumberFormat()->setFormatCode('#,##0');

        // Autoancho y ajuste detalle
        foreach (range('A', 'L') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $sheet->getColumnDimension('M')->setWidth(80);
        $sheet->getStyle("M2:M{$lastDataRow}")->getAlignment()->setWrapText(true);

        // Resumen
        $summaryStart = $lastDataRow + 2;
        $sheet->setCellValue("A{$summaryStart}", 'RESUMEN MES')->getStyle("A{$summaryStart}")->getFont()->setBold(true);
        $sheet->setCellValue("A".($summaryStart+1), 'Pedidos (cantidad)');
        $sheet->setCellValue("M".($summaryStart+1), "=COUNTA(A2:A{$lastDataRow})");
        $sheet->setCellValue("A".($summaryStart+2), 'Total Pedido (CLP)');
        $sheet->setCellValue("I".($summaryStart+2), "=SUM(I2:I{$lastDataRow})");
        $sheet->setCellValue("A".($summaryStart+3), 'Monto Pagado (CLP)');
        $sheet->setCellValue("J".($summaryStart+3), "=SUM(J2:J{$lastDataRow})");
        $sheet->setCellValue("A".($summaryStart+4), 'Promedio Ticket Pagado');
        $sheet->setCellValue("J".($summaryStart+4), "=IFERROR(AVERAGE(J2:J{$lastDataRow}),0)");

        $sheet->getStyle("I".($summaryStart+2).":J".($summaryStart+4))
              ->getNumberFormat()->setFormatCode('#,##0');

        // Nombre del archivo
        $labels = [
            'q_domi' => 'quilicura_domicilio','q_retiro' => 'quilicura_retiro',
            'p_domi' => 'puente_domicilio','p_retiro' => 'puente_retiro',
            'paket' => 'paket','q_all' => 'quilicura_todo',
            'p_all' => 'puente_todo','all' => 'todos',
        ];
        $label = $labels[$group] ?? 'todos';
        $filename = sprintf('pedidos_%s_%04d_%02d.xlsx', $label, $year, $month);

        // Descarga
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="'.$filename.'"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;

    } catch (Throwable $e) {
        if (isset($_GET['debug'])) {
            echo "<pre>ERROR: ".$e->getMessage()."\n".$e->getTraceAsString()."</pre>";
        } else {
            http_response_code(500);
        }
        exit;
    }
}

/* ===== UI (solo cuando NO se descarga) ===== */
include __DIR__ . '/includes/header_admin.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Exportar pedidos</title>
  <link rel="stylesheet" href="admin.css">
</head>
<body>
  <main class="container">
    <h1>Exportar pedidos a Excel (.xlsx)</h1>
    <form method="GET">
      <input type="hidden" name="action" value="download">
      Mes: <select name="month"><?= monthOptions((int)date('n')) ?></select>
      Año: <select name="year"><?= yearOptions((int)date('Y')) ?></select>
      Grupo:
      <select name="group">
        <option value="q_domi">Quilicura – Domicilio</option>
        <option value="q_retiro">Quilicura – Retiro</option>
        <option value="p_domi">Puente Alto – Domicilio</option>
        <option value="p_retiro">Puente Alto – Retiro</option>
        <option value="paket">Paket</option>
        <option value="q_all">Quilicura – Todo</option>
        <option value="p_all">Puente Alto – Todo</option>
        <option value="all">Todo (global)</option>
      </select>
      <button type="submit">Descargar XLSX</button>
      <a href="?debug=1" style="margin-left:10px;">Probar en modo debug</a>
    </form>
  </main>
</body>
</html>
