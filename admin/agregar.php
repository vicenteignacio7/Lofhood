<?php
// admin/agregar.php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../includes/conexion.php'; // define $conn (mysqli)

$error = '';
$newName = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitizar
    $nombre       = trim($_POST['nombre'] ?? '');
    $descripcion  = trim($_POST['descripcion'] ?? '');
    $precio       = isset($_POST['precio']) ? (float)$_POST['precio'] : 0.0;
    $categoria    = trim($_POST['categoria'] ?? '');
    $estado       = trim($_POST['estado'] ?? '');
    // Si viene vacío, guardar NULL
    $subcategoria = isset($_POST['subcategoria']) && $_POST['subcategoria'] !== '' ? trim($_POST['subcategoria']) : null;

    // Validación básica
    if ($nombre === '' || $descripcion === '' || $precio <= 0 || $categoria === '' || $estado === '') {
        $error = 'Completa todos los campos obligatorios.';
    }

    // Imagen obligatoria
    if (!$error) {
        if (!empty($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $tmpName  = $_FILES['imagen']['tmp_name'];
            $origName = $_FILES['imagen']['name'];
            $ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
            $allowed  = ['jpg', 'jpeg', 'png', 'gif'];

            if (!in_array($ext, $allowed, true)) {
                $error = 'Tipo de imagen no permitido. Usa JPG, PNG o GIF.';
            } else {
                $newName = uniqid('img_', true) . '.' . $ext;
                $destDir = __DIR__ . '/../assets/imagenes';
                if (!is_dir($destDir)) {
                    @mkdir($destDir, 0755, true);
                }
                $dest = $destDir . '/' . $newName;
                if (!move_uploaded_file($tmpName, $dest)) {
                    $error = 'Error al mover la imagen al directorio.';
                }
            }
        } else {
            $error = 'Debes subir una imagen para el producto.';
        }
    }

    // Insertar si todo OK
    if (!$error) {
        $stmt = $conn->prepare("
            INSERT INTO catalogo_productos
                (nombre, descripcion, precio, categoria, subcategoria, imagen, estado)
            VALUES
                (?, ?, ?, ?, ?, ?, ?)
        ");
        // $subcategoria puede ser NULL y bind_param lo envía como NULL si la variable es null
        $stmt->bind_param(
            'ssdssss',
            $nombre,
            $descripcion,
            $precio,
            $categoria,
            $subcategoria,
            $newName,
            $estado
        );

        if ($stmt->execute()) {
            $stmt->close();
            // IMPORTANTE: redirigir ANTES de imprimir nada
            header('Location: index_admin.php?added=1');
            exit;
        } else {
            $error = 'Error al agregar producto: ' . $stmt->error;
            $stmt->close();
        }
    }
}

// A partir de aquí ya podemos imprimir HTML
$cats = $conn->query("SELECT DISTINCT categoria FROM catalogo_productos ORDER BY categoria ASC");

include __DIR__ . '/includes/header_admin.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Agregar Producto</title>
  <style>
    body { 
      font-family: Arial, sans-serif; 
      padding: 20px; 
      background: url('../assets/imagenes/bg-admin.jpg') no-repeat center center fixed;
      background-size: cover;
    }
    .panel {
      background: rgba(255, 255, 255, 0.85);
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
      max-width: 600px;
      margin: 40px auto;
      padding: 20px 30px;
    }
    .panel h1 { margin-top: 0; }
    label { display:block; margin-top:10px; font-weight:bold; }
    input, textarea, select, button {
      width: 100%;
      padding: 8px;
      margin-top: 5px;
      box-sizing: border-box;
    }
    button {
      margin-top: 15px;
      background: #4caf50;
      color: #fff;
      border: none;
      cursor: pointer;
      border-radius: 4px;
    }
    button:hover { background: #43a047; }
    .error { color:red; }
    a.volver {
      display: inline-block;
      margin-top: 15px;
      color: #555;
      text-decoration: none;
    }
    a.volver:hover { text-decoration: underline; }
  </style>
  <script>
    function toggleSubcategoria() {
      const categoria = (document.querySelector('select[name="categoria"]').value || '').toLowerCase();
      const subcatDiv = document.getElementById('subcategoriaDiv');

      // Mostrar SOLO cuando la categoría sea exactamente "Snack/dulce"
      if (categoria === 'snack/dulce') {
        subcatDiv.style.display = 'block';
      } else {
        subcatDiv.style.display = 'none';
        const sel = document.querySelector('select[name="subcategoria"]');
        if (sel) sel.value = '';
      }
    }
    document.addEventListener('DOMContentLoaded', toggleSubcategoria);
  </script>
</head>
<body>
  <div class="panel">
    <h1>Agregar Nuevo Producto</h1>
    <?php if ($error): ?>
      <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
      <label>Nombre</label>
      <input type="text" name="nombre" required>

      <label>Descripción</label>
      <textarea name="descripcion" required></textarea>

      <label>Precio</label>
      <input type="number" step="0.01" name="precio" required>

      <label>Categoría</label>
      <select name="categoria" onchange="toggleSubcategoria()" required>
        <option value="">-- Selecciona --</option>
        <?php while ($c = $cats->fetch_assoc()): ?>
          <option value="<?= htmlspecialchars($c['categoria']) ?>">
            <?= ucfirst(str_replace('_', ' ', $c['categoria'])) ?>
          </option>
        <?php endwhile; ?>
      </select>

      <!-- Subcategoría: SOLO para Snack/dulce -->
      <div id="subcategoriaDiv" style="display:none;">
        <label>Subcategoría</label>
        <select name="subcategoria">
          <option value="">-- Ninguna --</option>
          <option value="dulce">dulce</option>
          <option value="snack">snack</option>
        </select>
      </div>

      <label>Estado</label>
      <select name="estado" required>
        <option value="disponible">Disponible</option>
        <option value="agotado">Agotado</option>
      </select>

      <label>Imagen</label>
      <input type="file" name="imagen" accept="image/*" required>

      <button type="submit">Agregar Producto</button>
    </form>

    <a href="index_admin.php" class="volver">← Volver al panel</a>
  </div>
</body>
</html>
