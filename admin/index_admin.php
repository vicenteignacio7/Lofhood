<?php 
require __DIR__ . '/../includes/init.php';

// 1) Solo usuarios logueados (hago el check ANTES de imprimir header_admin)
if (empty($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

include __DIR__ . '/includes/header_admin.php';

// === Filtros ===

// A) Filtro avanzado (tipo/valor) => por defecto 'nombre'
$filtro_tipo  = $_GET['tipo']  ?? 'nombre';
$filtro_valor = $_GET['valor'] ?? '';

// B) Construcción de cláusulas
$whereParts = [];
if ($filtro_valor !== '') {
    $valor = $conn->real_escape_string($filtro_valor);
    switch ($filtro_tipo) {
        case 'nombre':
            $whereParts[] = "nombre LIKE '%{$valor}%'";
            break;
        case 'categoria':
            $whereParts[] = "categoria LIKE '%{$valor}%'";
            break;
        case 'precio':
            // Acepta 2000 o 2.000
            $valor_num = (float) str_replace('.', '', $valor);
            $whereParts[] = "precio = {$valor_num}";
            break;
        default:
            // nada
            break;
    }
}
$where = $whereParts ? implode(' AND ', $whereParts) : '1=1';

// Query final
$sql = "SELECT * FROM catalogo_productos WHERE {$where} ORDER BY categoria, nombre";
$result = $conn->query($sql);

// Para el select de categorías
$cats_list = $conn->query("SELECT DISTINCT categoria FROM catalogo_productos ORDER BY categoria ASC");

// Para armar return_url en los enlaces
$currentUrl    = $_SERVER['REQUEST_URI'];
$encodedReturn = urlencode($currentUrl);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Panel Admin</title>
  <link rel="stylesheet" href="admin.css">
</head>
<body>

  <h1>Panel de Administración</h1>

  <!-- === FILTRO AVANZADO (por defecto: Nombre) === -->
  <form method="GET" style="margin:12px 0;">
    <label for="tipo">Buscar por:</label>
    <select name="tipo" id="tipo">
      <option value="nombre"   <?= $filtro_tipo=='nombre'   ? 'selected' : '' ?>>Nombre</option>
      <option value="categoria"<?= $filtro_tipo=='categoria'? 'selected' : '' ?>>Categoría</option>
      <option value="precio"   <?= $filtro_tipo=='precio'   ? 'selected' : '' ?>>Precio</option>
    </select>

    <!-- input libre para nombre / precio -->
    <input 
      type="text" 
      name="valor" 
      id="valor_text"
      placeholder="Escribe el valor a buscar" 
      value="<?= htmlspecialchars($filtro_valor) ?>"
      style="padding:5px; margin-left:10px;"
    >

    <!-- select para categorías -->
    <select name="valor" id="valor_categoria" style="display:none; padding:5px; margin-left:10px;">
      <option value="">-- Selecciona categoría --</option>
      <?php while($c = $cats_list->fetch_assoc()): ?>
        <option 
          value="<?= htmlspecialchars($c['categoria']) ?>" 
          <?= $filtro_tipo=='categoria' && $filtro_valor==$c['categoria'] ? 'selected' : '' ?>
        >
          <?= ucfirst(str_replace('_',' ',$c['categoria'])) ?>
        </option>
      <?php endwhile; ?>
    </select>

    <button type="submit" style="padding:6px 12px; margin-left:5px;">Buscar</button>

    <?php if($filtro_valor !== ''): ?>
      <a href="index_admin.php" style="
          display:inline-block;
          padding:6px 12px;
          margin-left:10px;
          background:#f44336;
          color:#fff;
          border-radius:4px;
          text-decoration:none;
        ">Mostrar todos</a>
    <?php endif; ?>
  </form>

  <script>
  document.addEventListener('DOMContentLoaded', function(){
    const tipo   = document.getElementById('tipo');
    const textIn = document.getElementById('valor_text');
    const catIn  = document.getElementById('valor_categoria');

    function toggleInputs(){
      if (tipo.value === 'categoria') {
        catIn.style.display  = 'inline-block';
        catIn.disabled       = false;
        textIn.style.display = 'none';
        textIn.disabled      = true;
      } else {
        textIn.style.display = 'inline-block';
        textIn.disabled      = false;
        catIn.style.display  = 'none';
        catIn.disabled       = true;
      }
    }
    tipo.addEventListener('change', toggleInputs);
    toggleInputs(); // por defecto queda en 'nombre'
  });
  </script>

  <!-- === TABLA DE PRODUCTOS === -->
  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>Nombre</th>
        <th>Precio</th>
        <th>Categoría</th>
        <th>Imagen</th>
        <th>Acciones</th>
      </tr>
    </thead>
    <tbody>
    <?php while($p = $result->fetch_assoc()): ?>
      <tr>
        <td><?= $p['id'] ?></td>
        <td><?= htmlspecialchars($p['nombre']) ?></td>
        <td>$<?= number_format($p['precio'],0,',','.') ?></td>
        <td><?= htmlspecialchars($p['categoria']) ?></td>
        <td><img src="../assets/imagenes/<?= htmlspecialchars($p['imagen']) ?>" width="50" alt=""></td>
        <td>
          <a href="editar.php?id=<?= $p['id'] ?>&return_url=<?= $encodedReturn ?>" class="btn btn-edit">Editar</a>
          <a href="eliminar.php?id=<?= $p['id'] ?>&return_url=<?= $encodedReturn ?>" class="btn btn-delete"
             onclick="return confirm('¿Seguro que quieres eliminar este producto?');">
            Eliminar
          </a>
        </td>
      </tr>
    <?php endwhile; ?>
    </tbody>
  </table>

</body>
</html>
