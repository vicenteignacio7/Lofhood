<?php 
// categorias.php

include __DIR__ . "/includes/header.php";
include __DIR__ . "/includes/conexion.php";

// 1) Definir cuántos productos por página
$porPagina = 20;

// 2) Calcular total de productos
$resCount = $conn->query("SELECT COUNT(*) AS total FROM catalogo_productos WHERE estado = 'disponible'");
$totalRows = $resCount->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $porPagina);

// 3) Obtener la página actual desde ?page=, validar rangos
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
if ($page > $totalPages) $page = $totalPages;

// 4) Calcular el OFFSET
$offset = ($page - 1) * $porPagina;

// 5) Traer sólo los 10 productos de esta página
$sql = "SELECT * 
        FROM catalogo_productos 
        WHERE estado = 'disponible'
        ORDER BY categoria, nombre 
        LIMIT $porPagina OFFSET $offset";

$result = $conn->query($sql);
?>

<section class="productos" id="todos">
  <h2>Todos los Productos (Página <?= $page ?> de <?= $totalPages ?>)</h2>

  <div class="grid-productos">
    <?php if ($result && $result->num_rows > 0): ?>
      <?php while($p = $result->fetch_assoc()): ?>
        <div class="producto" data-precio="<?= $p['precio'] ?>">
          <img 
            src="assets/imagenes/<?= htmlspecialchars($p['imagen']) ?>" 
            alt="<?= htmlspecialchars($p['nombre']) ?>" 
          />
          <h3 class="product-title"><?= htmlspecialchars($p['nombre']) ?></h3>
          <p><?= htmlspecialchars($p['descripcion']) ?></p>
          <p><strong>$<?= number_format($p['precio'], 0, ',', '.') ?></strong></p>
          <button type="button" class="add-to-cart">Agregar al Carrito</button>
        </div>
      <?php endwhile; ?>
    <?php else: ?>
      <p>No hay productos disponibles.</p>
    <?php endif; ?>
  </div>

  <!-- 6) Paginación -->
  <?php if ($totalPages > 1): ?>
  <nav class="pagination" style="text-align:center; margin:2em 0;">
    <?php if ($page > 1): ?>
      <a href="?page=<?= $page-1 ?>" class="btn">&laquo; Anterior</a>
    <?php endif; ?>

    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
      <?php if ($p === $page): ?>
        <span class="btn current"><?= $p ?></span>
      <?php else: ?>
        <a href="?page=<?= $p ?>" class="btn"><?= $p ?></a>
      <?php endif; ?>
    <?php endfor; ?>

    <?php if ($page < $totalPages): ?>
      <a href="?page=<?= $page+1 ?>" class="btn">Siguiente &raquo;</a>
    <?php endif; ?>
  </nav>
  <?php endif; ?>
</section>

<!-- Contacto (opcional) -->
<section class="contacto" id="contacto">
  <h2>Contáctanos</h2>
  <form method="POST" action="procesar_contacto.php">
    <input type="text" name="nombre" placeholder="Nombre" required>
    <input type="email" name="correo" placeholder="Correo" required>
    <textarea name="mensaje" placeholder="Mensaje" required></textarea>
    <button type="submit">Enviar</button>
  </form>
</section>

<script src="assets/js/main.js"></script>
<?php include __DIR__ . "/includes/footer.php"; ?>
