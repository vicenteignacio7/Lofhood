<?php 
include("includes/header.php");
include("includes/conexion.php");

// ----- Parámetros -----
$categoria    = isset($_GET['cat'])    ? trim($_GET['cat'])    : '';
$subcategoria = isset($_GET['subcat']) ? trim($_GET['subcat']) : '';

if ($categoria === '') {
    echo "<section class='productos'><h2>No se especificó una categoría</h2></section>";
    include("includes/footer.php");
    exit;
}

// ----- Título -----
$tituloCat = strtoupper(str_replace('_', ' ', $categoria));
$tituloSub = $subcategoria !== '' ? ' — ' . strtoupper(str_replace('_', ' ', $subcategoria)) : '';

// ----- Consulta preparada -----
if ($subcategoria !== '') {
    $sql = "
        SELECT id, nombre, descripcion, precio, imagen
        FROM catalogo_productos
        WHERE categoria = ? AND subcategoria = ? AND estado = 'disponible'
        ORDER BY nombre COLLATE utf8mb4_spanish_ci ASC
    ";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        // Fallback si el COLLATE no existe en tu MySQL
        $sql = "
            SELECT id, nombre, descripcion, precio, imagen
            FROM catalogo_productos
            WHERE categoria = ? AND subcategoria = ? AND estado = 'disponible'
            ORDER BY nombre ASC
        ";
        $stmt = $conn->prepare($sql);
    }
    $stmt->bind_param('ss', $categoria, $subcategoria);
} else {
    $sql = "
        SELECT id, nombre, descripcion, precio, imagen
        FROM catalogo_productos
        WHERE categoria = ? AND estado = 'disponible'
        ORDER BY nombre COLLATE utf8mb4_spanish_ci ASC
    ";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        $sql = "
            SELECT id, nombre, descripcion, precio, imagen
            FROM catalogo_productos
            WHERE categoria = ? AND estado = 'disponible'
            ORDER BY nombre ASC
        ";
        $stmt = $conn->prepare($sql);
    }
    $stmt->bind_param('s', $categoria);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<section class="productos" id="<?php echo htmlspecialchars($categoria); ?>">
  <h2><?php echo htmlspecialchars($tituloCat . $tituloSub); ?></h2>

  <div class="grid-productos">
    <?php if ($result && $result->num_rows > 0): ?>
      <?php while($p = $result->fetch_assoc()): ?>
        <div class="producto" data-precio="<?php echo (float)$p['precio']; ?>">
          <img src="assets/imagenes/<?php echo htmlspecialchars($p['imagen']); ?>" alt="<?php echo htmlspecialchars($p['nombre']); ?>">
          <h3 class="product-title"><?php echo htmlspecialchars($p['nombre']); ?></h3>
          <p><?php echo htmlspecialchars($p['descripcion']); ?></p>
          <p><strong>$<?php echo number_format((float)$p['precio'], 0, ',', '.'); ?></strong></p>
          <button type="button" class="add-to-cart">Agregar al Carrito</button>
        </div>
      <?php endwhile; ?>
    <?php else: ?>
      <p>No hay productos en esta <?php echo $subcategoria ? 'subcategoría' : 'categoría'; ?>.</p>
    <?php endif; ?>
  </div>
</section>

<script src="./assets/js/main.js"></script>
<?php include("includes/footer.php"); ?>
