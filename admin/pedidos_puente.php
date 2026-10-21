<?php
// admin/pedidos_puente.php
declare(strict_types=1);
session_start();

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin','trabajador_puente'], true)) {
    header('Location: ../login.php');
    exit;
}
$isAdmin = ($_SESSION['role'] === 'admin');

require_once __DIR__ . '/../includes/conexion.php';
$mysqli = $conn;
if ($mysqli->connect_errno) die('Error al conectar: ' . $mysqli->connect_error);

$sql = "
  SELECT pr.id,
         pr.nombre_envio AS cliente,
         pr.comuna_envio AS comuna,
         pr.direccion_envio AS direccion,
         pr.telefono_envio,
         pr.envio,
         pr.fecha,
         pr.estado,
         pr.total
  FROM pedidos pr
  WHERE pr.envio IN ('domicilio-puente','retiro-puente','paket')
    AND pr.pago_confirmado = 1
  ORDER BY pr.fecha DESC
";
$res = $mysqli->query($sql);
if (!$res) die('Error en consulta: ' . $mysqli->error);
$pedidos = $res->fetch_all(MYSQLI_ASSOC);

function obtenerDetallePedido(mysqli $db, int $pedido_id): array {
    $stmt = $db->prepare("SELECT nombre_producto, precio, cantidad, subtotal FROM detalle_pedido WHERE pedido_id=?");
    $stmt->bind_param('i', $pedido_id);
    $stmt->execute();
    $r = $stmt->get_result();
    $out = $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();
    return $out;
}

$grupos = [
    'domicilio-puente' => [],
    'retiro-puente'    => [],
    'paket'            => []
];
foreach ($pedidos as $p) $grupos[$p['envio']][] = $p;
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Pedidos - Puente Alto</title>
  <link rel="stylesheet" href="../assets/css/pedidos_trabajador.css">
</head>
<body>

<div class="navbar">
  <div>
    <a href="admin_productos.php">🛠️ Administrar Productos</a>
    <a href="pedidos_transferencia.php">🏦 Transferencias pendientes</a>
  </div>
  <a href="../logout.php">Cerrar sesión</a>
</div>

<h1>Panel de Pedidos - Puente Alto</h1>

<?php
$estados = ['pendiente','preparando','entregado'];
foreach ($grupos as $tipo => $pedidosTipo):
  if (!count($pedidosTipo)) continue;
?>
  <h3><?= ucfirst(str_replace('-', ' ', $tipo)) ?></h3>
  <?php foreach ($estados as $estadoActual):
    $filtrados = array_filter($pedidosTipo, fn($p) => $p['estado'] === $estadoActual);
    if (!count($filtrados)) continue;
  ?>
  <div class="estado-card">
    <h4><?= ucfirst($estadoActual) ?></h4>
    <table>
      <thead>
        <tr>
          <th>ID</th><th>Cliente</th><th>Dirección</th><th>Teléfono</th>
          <th>Envío</th><th>Fecha</th><th>Estado</th><th>Detalle</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($filtrados as $pedido):
        $detalle = obtenerDetallePedido($mysqli, (int)$pedido['id']);
        $total = 0;
      ?>
        <tr>
          <td data-label="ID"><?= $pedido['id'] ?></td>
          <td data-label="Cliente"><?= htmlspecialchars($pedido['cliente']) ?></td>
          <td data-label="Dirección"><?= htmlspecialchars($pedido['direccion']) ?></td>
          <td data-label="Teléfono"><?= htmlspecialchars($pedido['telefono_envio']) ?></td>
          <td data-label="Envío"><?= htmlspecialchars($pedido['envio']) ?></td>
          <td data-label="Fecha"><?= htmlspecialchars($pedido['fecha']) ?></td>
          <td data-label="Estado">
            <select data-id="<?= $pedido['id'] ?>" class="estado-select">
              <option value="pendiente"  <?= $pedido['estado']==='pendiente'?'selected':'' ?>>Pendiente</option>
              <option value="preparando" <?= $pedido['estado']==='preparando'?'selected':'' ?>>Preparando</option>
              <option value="entregado"  <?= $pedido['estado']==='entregado'?'selected':'' ?>>Entregado</option>
            </select>
          </td>
          <td data-label="Detalle">
            <?php if ($detalle): ?>
              <ul>
                <?php foreach ($detalle as $prod): $total += (float)$prod['subtotal']; ?>
                  <li><?= htmlspecialchars($prod['nombre_producto']) ?> (<?= (int)$prod['cantidad'] ?> × $<?= number_format((float)$prod['precio'],0,',','.') ?>) = $<?= number_format((float)$prod['subtotal'],0,',','.') ?></li>
                <?php endforeach; ?>
              </ul>
              <strong>Total: $<?= number_format($total,0,',','.') ?></strong>
            <?php else: ?><em>Sin productos</em><?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endforeach; ?>
<?php endforeach; ?>

<script>
document.querySelectorAll('.estado-select').forEach(select=>{
  select.addEventListener('change',()=>{
    const id=select.dataset.id,estado=select.value;
    fetch('actualizar_estado.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:`id=${encodeURIComponent(id)}&estado=${encodeURIComponent(estado)}`})
    .then(r=>r.json())
    .then(d=>{if(d.ok)location.reload();else alert('❌ Error al actualizar.');})
    .catch(()=>alert('⚠️ Error de conexión.'));
  });
});
</script>

</body>
</html>
