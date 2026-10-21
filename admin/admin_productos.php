<?php
session_start();
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['trabajador_puente', 'trabajador_quilicura'])) {
    header('Location: ../login.php');
    exit;
}

include __DIR__ . "/../includes/conexion.php";

$result = $conn->query("
    SELECT id, nombre, categoria, precio, estado 
      FROM catalogo_productos 
     ORDER BY categoria, nombre
");
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Administrar Productos</title>
  <link rel="stylesheet" href="../assets/css/admin_productos.css">
</head>
<body>
  <h2>Administrar Estado de Productos</h2>

  <?php
    $volverUrl = "#";
    if ($_SESSION['role'] === 'trabajador_quilicura') {
        $volverUrl = "pedidos_quilicura.php";
    } elseif ($_SESSION['role'] === 'trabajador_puente') {
        $volverUrl = "pedidos_puente.php";
    }
  ?>
<div class="navbar">
  <div>
    <a href="<?= $volverUrl ?>">← Volver a Pedidos</a>
  </div>
  <a href="../logout.php">Cerrar sesión</a>
</div>

  <table border="1" cellpadding="10">
    <tr>
      <th>Producto</th>
      <th>Categoría</th>
      <th>Precio</th>
      <th>Estado</th>
    </tr>
    <?php while ($p = $result->fetch_assoc()): ?>
      <tr>
        <td><?= htmlspecialchars($p['nombre']) ?></td>
        <td><?= htmlspecialchars($p['categoria']) ?></td>
        <td>$<?= number_format($p['precio'], 0, ',', '.') ?></td>
        <td>
          <select class="estado-producto" data-id="<?= $p['id'] ?>">
            <option value="disponible" <?= $p['estado'] === 'disponible' ? 'selected' : '' ?>>Disponible</option>
            <option value="agotado"     <?= $p['estado'] === 'agotado'     ? 'selected' : '' ?>>Agotado</option>
          </select>
        </td>
      </tr>
    <?php endwhile; ?>
  </table>

  <script>
  document.querySelectorAll('.estado-producto').forEach(select => {
    select.addEventListener('change', () => {
      const id = select.dataset.id;
      const estado = select.value;

      fetch('actualizar_estado_producto.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `id=${id}&estado=${estado}`
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          alert("✅ Producto actualizado.");
        } else {
          alert("❌ Error al actualizar.");
        }
      })
      .catch(err => {
        alert("⚠️ Error de red.");
        console.error(err);
      });
    });
  });
  </script>
</body>
</html>
