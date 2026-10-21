<?php
// admin/editar.php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../includes/conexion.php'; // define $conn (mysqli)

$error = '';
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    die('ID de producto inválido.');
}

// capturar return_url (GET o POST) para devolver a la misma vista
$returnParam = $_GET['return_url'] ?? $_POST['return_url'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre       = trim($_POST['nombre'] ?? '');
    $descripcion  = trim($_POST['descripcion'] ?? '');
    $precio       = isset($_POST['precio']) ? (float)$_POST['precio'] : 0.0;
    $categoria    = trim($_POST['categoria'] ?? '');
    $estado       = trim($_POST['estado'] ?? '');
    $subcategoria = isset($_POST['subcategoria']) && $_POST['subcategoria'] !== '' ? trim($_POST['subcategoria']) : null;
    $imagenDB     = $_POST['current_image'] ?? '';

    // CAMBIO 1: si la categoría NO es "Snack/dulce", forzar NULL
    if (mb_strtolower($categoria) !== 'snack/dulce') {
        $subcategoria = null;
    }

    if ($nombre === '' || $descripcion === '' || $precio <= 0 || $categoria === '' || $estado === '') {
        $error = 'Completa todos los campos obligatorios.';
    }

    if (!$error && !empty($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $tmpName  = $_FILES['imagen']['tmp_name'];
        $origName = $_FILES['imagen']['name'];
        $ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        $allowed  = ['jpg','jpeg','png','gif'];

        if (!in_array($ext, $allowed, true)) {
            $error = 'Tipo de imagen no permitido. Usa JPG, PNG o GIF.';
        } else {
            $newName = uniqid('img_', true) . '.' . $ext;
            $destDir = __DIR__ . '/../assets/imagenes';
            if (!is_dir($destDir)) {
                @mkdir($destDir, 0755, true);
            }
            $dest = $destDir . '/' . $newName;

            if (move_uploaded_file($tmpName, $dest)) {
                if ($imagenDB && is_file($destDir . '/' . $imagenDB)) {
                    @unlink($destDir . '/' . $imagenDB);
                }
                $imagenDB = $newName;
            } else {
                $error = 'Error al mover la imagen al directorio.';
            }
        }
    }

    if (!$error) {
        $stmt = $conn->prepare("
            UPDATE catalogo_productos
               SET nombre = ?,
                   descripcion = ?,
                   precio = ?,
                   categoria = ?,
                   subcategoria = ?,
                   imagen = ?,
                   estado = ?
             WHERE id = ?
        ");
        $stmt->bind_param(
            'ssdssssi',
            $nombre,
            $descripcion,
            $precio,
            $categoria,
            $subcategoria,
            $imagenDB,
            $estado,
            $id
        );

        if ($stmt->execute()) {
            $stmt->close();
            // Redirige a la misma vista si viene return_url
            $dest = $returnParam ? urldecode($returnParam) : 'index_admin.php?updated=1';
            // evitar open redirect
            if (strpos($dest, '://') !== false) { $dest = 'index_admin.php?updated=1'; }
            header('Location: ' . $dest);
            exit;
        } else {
            $error = 'Error al actualizar: ' . $stmt->error;
            $stmt->close();
        }
    }
}

// Cargar datos
$res = $conn->prepare("SELECT * FROM catalogo_productos WHERE id = ? LIMIT 1");
$res->bind_param('i', $id);
$res->execute();
$productoRes = $res->get_result();
if (!$productoRes || !$productoRes->num_rows) {
    http_response_code(404);
    die('Producto no encontrado.');
}
$producto = $productoRes->fetch_assoc();
$res->close();

$cats = $conn->query("SELECT DISTINCT categoria FROM catalogo_productos ORDER BY categoria ASC");

// ya podemos imprimir
include __DIR__ . '/includes/header_admin.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Editar Producto</title>
  <style>
    body { font-family: Arial, sans-serif; padding: 20px; background: url('../assets/imagenes/bg-admin.jpg') no-repeat center center fixed; background-size: cover; }
    .panel { background: rgba(255, 255, 255, 0.85); border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); max-width: 600px; margin: 40px auto; padding: 20px 30px; }
    .panel h1 { margin-top: 0; }
    label { display:block; margin-top:10px; font-weight:bold; }
    input, textarea, select, button { width: 100%; padding: 8px; margin-top: 5px; box-sizing: border-box; }
    button { margin-top: 15px; background: #2196f3; color: #fff; border: none; cursor: pointer; border-radius: 4px; }
    button:hover { background: #1976d2; }
    .error { color:red; }
    a.volver { display: inline-block; margin-top: 15px; color: #555; text-decoration: none; }
    a.volver:hover { text-decoration: underline; }
    .current-img { margin-top: 5px; margin-bottom: 10px; max-width: 100px; }
  </style>
  <script>
    function toggleSubcategoria() {
      const categoria = (document.querySelector('select[name="categoria"]').value || '').toLowerCase();
      const subcatDiv = document.getElementById('subcategoria-container');
      // CAMBIO 2: visible solo cuando la categoría es "snack/dulce"
      subcatDiv.style.display = (categoria === 'snack/dulce') ? 'block' : 'none';
    }
    document.addEventListener('DOMContentLoaded', () => {
      toggleSubcategoria();
      document.querySelector('select[name="categoria"]').addEventListener('change', toggleSubcategoria);
    });
  </script>
</head>
<body>
  <div class="panel">
    <h1>Editar Producto</h1>
    <?php if ($error): ?>
      <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="return_url" value="<?= htmlspecialchars($returnParam) ?>">
      <label>Nombre</label>
      <input type="text" name="nombre" value="<?= htmlspecialchars($producto['nombre']) ?>" required>

      <label>Descripción</label>
      <textarea name="descripcion" required><?= htmlspecialchars($producto['descripcion']) ?></textarea>

      <label>Precio</label>
      <input type="number" step="0.01" name="precio" value="<?= htmlspecialchars((string)$producto['precio']) ?>" required>

      <label>Categoría</label>
      <select name="categoria" required>
        <?php while ($c = $cats->fetch_assoc()): ?>
          <option value="<?= htmlspecialchars($c['categoria']) ?>" <?= $producto['categoria'] === $c['categoria'] ? 'selected' : '' ?>>
            <?= ucfirst(str_replace('_',' ', $c['categoria'])) ?>
          </option>
        <?php endwhile; ?>
      </select>

      <div id="subcategoria-container" style="display:none;">
        <label>Subcategoría</label>
        <select name="subcategoria">
          <option value="">-- Selecciona --</option>
          <!-- CAMBIO 3: opciones actualizadas -->
          <option value="dulce" <?= ($producto['subcategoria'] === 'dulce') ? 'selected' : '' ?>>dulce</option>
          <option value="snack" <?= ($producto['subcategoria'] === 'snack') ? 'selected' : '' ?>>snack</option>
        </select>
      </div>

      <label>Estado</label>
      <select name="estado" required>
        <option value="disponible" <?= $producto['estado']==='disponible' ? 'selected' : '' ?>>Disponible</option>
        <option value="agotado"    <?= $producto['estado']==='agotado'    ? 'selected' : '' ?>>Agotado</option>
      </select>

      <label>Imagen Actual</label><br>
      <img src="../assets/imagenes/<?= htmlspecialchars($producto['imagen']) ?>" class="current-img" alt=""><br>

      <label>Reemplazar Imagen</label>
      <input type="file" name="imagen" accept="image/*">
      <input type="hidden" name="current_image" value="<?= htmlspecialchars($producto['imagen']) ?>">

      <button type="submit">Guardar Cambios</button>
    </form>

    <?php
      $backUrl = $returnParam ? urldecode($returnParam) : 'index_admin.php';
      if (strpos($backUrl, '://') !== false) { $backUrl = 'index_admin.php'; }
    ?>
    <a href="<?= htmlspecialchars($backUrl) ?>" class="volver">← Volver</a>
  </div>
</body>
</html>
