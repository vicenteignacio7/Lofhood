<?php
// admin/pedidos_transferencia.php
declare(strict_types=1);
session_start();

// conexión (ajusta la ruta correcta a tu proyecto)
require_once __DIR__ . '/../includes/conexion.php'; // <- evita 500 por include inválido

mysqli_report(MYSQLI_REPORT_OFF); // no arrojar warnings como fatales

// Consulta segura
$sql = "
  SELECT id, fecha, total, nombre_envio AS cliente, telefono_envio, envio
  FROM pedidos
  WHERE estado = 'pendiente-transferencia'
  ORDER BY fecha DESC
";
$res   = $conn->query($sql);
$error = $res ? '' : ($conn->error ?: 'Error de consulta');
$rows  = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Transferencias pendientes</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="p-4">
  <h2>Transferencias pendientes</h2>

  <?php if ($error): ?>
    <div class="alert alert-danger">Error: <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <table class="table table-striped">
    <thead>
      <tr>
        <th>#</th><th>Fecha</th><th>Total</th><th>Cliente</th><th>Contacto</th><th>Envío</th><th>Acción</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$rows): ?>
        <tr><td colspan="7" class="text-muted">Sin transferencias pendientes.</td></tr>
      <?php else: foreach ($rows as $row): ?>
        <tr>
          <td><?= (int)$row['id'] ?></td>
          <td><?= htmlspecialchars($row['fecha']) ?></td>
          <td>$<?= number_format((float)$row['total'], 0, ',', '.') ?></td>
          <td><?= htmlspecialchars($row['cliente']) ?></td>
          <td><?= htmlspecialchars($row['telefono_envio']) ?></td>
          <td><?= htmlspecialchars($row['envio']) ?></td>
          <td class="d-flex gap-2">
            <a href="actualizar_estado.php?id=<?= (int)$row['id'] ?>&estado=pagado"
               class="btn btn-success btn-sm btn-mark">Marcar pagado</a>
            <a href="actualizar_estado.php?id=<?= (int)$row['id'] ?>&estado=cancelado"
               class="btn btn-danger btn-sm btn-cancel">Cancelar</a>
          </td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>

  <script>
  // AJAX sin recargar
  document.querySelectorAll('.btn-mark, .btn-cancel').forEach(a => {
    a.addEventListener('click', async (e) => {
      e.preventDefault();
      try {
        const res  = await fetch(a.href, { method:'POST',
          headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'} });
        const data = await res.json();
        if (!res.ok || !data.ok) throw new Error(data.error || 'No se pudo actualizar');
        a.closest('tr')?.remove();
      } catch (err) {
        alert('Error: ' + (err.message || 'No se pudo actualizar'));
      }
    });
  });
  </script>
</body>
</html>
