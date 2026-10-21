<?php 
if (session_status() === PHP_SESSION_NONE) session_start();
require __DIR__ . "/includes/conexion.php";

if (!isset($_GET['id'])) die("ID no válido");

$id = intval($_GET['id']);
$usuario_id = $_SESSION['user_id'] ?? 0;

$sql = "SELECT * FROM publicaciones WHERE id = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$pub = $stmt->get_result()->fetch_assoc();
if (!$pub) die("Publicación no encontrada");

// Seguridad
if ($pub['usuario_id'] != $usuario_id && ($_SESSION['role'] ?? '') !== 'admin') {
    die("No tienes permiso para editar esta publicación.");
}

$cat = $_GET['cat'] ?? $pub['categoria'];
$from = $_GET['from'] ?? '';
$query = $_GET;
unset($query['id'], $query['from']);

include __DIR__ . "/includes/header.php";
?>

<?php if (!empty($_SESSION['error_wsp'])): ?>
<script>
Swal.fire({
    icon: 'error',
    title: 'Número inválido',
    html: 'El número debe tener exactamente <b>9 dígitos</b><br>Ejemplo: <b>976922011</b>',
    confirmButtonText: 'Entendido',
    backdrop: true
});
</script>
<?php unset($_SESSION['error_wsp']); endif; ?>


<section class="form-section">
  <h2 class="form-title">Editar Publicación</h2>

  <form id="form-publicacion" action="editar_publicacion_guardar.php" method="POST" enctype="multipart/form-data" class="publicar-form">
      <input type="hidden" name="from"  value="<?= $_GET['from'] ?? 'index' ?>">
      <input type="hidden" name="query" value="<?= http_build_query($_GET) ?>">
      <input type="hidden" name="id" value="<?= $pub['id'] ?>">
      
      <label>Categoría:</label>
      <p class="cat-edit-fixed"><?= strtoupper(htmlspecialchars($pub['categoria'])) ?></p>
      <input type="hidden" name="categoria" value="<?= $pub['categoria'] ?>">

      <input type="hidden" name="eliminar_imagen" id="eliminar_imagen" value="0">

      <label>Título:</label>
      <input type="text" name="titulo" id="titulo" maxlength="80" 
             value="<?= htmlspecialchars($pub['titulo']) ?>" required>

      <label>Descripción:</label>
      <textarea name="descripcion" id="descripcion" rows="5" maxlength="1000" required><?= htmlspecialchars($pub['descripcion']) ?></textarea>

      <?php if ($cat === 'venta'): ?>
      <div class="venta-box">
          <h3 class="form-subsection-title">Detalles de la Venta</h3>

          <label>💲 Precio:</label>
          <input type="text" inputmode="numeric" name="precio" class="solo-numeros" maxlength="10" value="<?= htmlspecialchars($pub['precio']) ?>" required>

          <label>📌 Estado del producto:</label>
          <select name="estado_producto" required>
              <option value="Nuevo" <?= $pub['estado_producto']=='Nuevo'?'selected':'' ?>>Nuevo</option>
              <option value="Semi nuevo" <?= $pub['estado_producto']=='Semi nuevo'?'selected':'' ?>>Semi nuevo</option>
              <option value="Usado" <?= $pub['estado_producto']=='Usado'?'selected':'' ?>>Usado</option>
          </select>

          <label>🔍 Condición / Detalles:</label>
          <select name="condicion" required>
            <option value="Excelente" <?= $pub['condicion']=='Excelente'?'selected':'' ?>>Excelente</option>
            <option value="Bueno" <?= $pub['condicion']=='Bueno'?'selected':'' ?>>Bueno</option>
            <option value="Con detalles" <?= $pub['condicion']=='Con detalles'?'selected':'' ?>>Con detalles</option>
            <option value="Malo" <?= $pub['condicion']=='Malo'?'selected':'' ?>>Malo</option>
        </select>

          <label>🚚 Entrega:</label>
          <input type="text" name="entrega" maxlength="100" value="<?= htmlspecialchars($pub['entrega']) ?>" placeholder="Ej: Retiro en Quilicura / A coordinar">

          <label>💳 Método de pago:</label>
          <input type="text" name="metodo_pago" maxlength="100" value="<?= htmlspecialchars($pub['metodo_pago']) ?>" placeholder="Ej: Efectivo / Transferencia">
      </div>
      <?php endif; ?>

      <?php if ($cat === 'actividad'): ?>
      <div class="actividad-box">
          <h3 class="form-subsection-title">Detalles de la Actividad</h3>

          <label>📍 Lugar:</label>
          <input type="text" name="lugar" maxlength="100" value="<?= htmlspecialchars($pub['lugar']) ?>" required>

          <label>🗓 Día y Hora:</label>
          <input type="datetime-local" name="fecha_evento" value="<?= date('Y-m-d\TH:i', strtotime($pub['fecha_evento'])) ?>" required>

          <label>👥 Cupos:</label>
          <input type="text" inputmode="numeric" name="cupos" class="solo-numeros" maxlength="4" value="<?= htmlspecialchars($pub['cupos']) ?>">

          <label>🏷 Tipo de actividad:</label>
          <select name="tipo_actividad" required>
              <option value="Deporte"   <?= $pub['tipo_actividad']=='Deporte'?'selected':'' ?>>Deporte</option>
              <option value="Juntada"   <?= $pub['tipo_actividad']=='Juntada'?'selected':'' ?>>Juntada</option>
              <option value="Carrete"   <?= $pub['tipo_actividad']=='Carrete'?'selected':'' ?>>Carrete</option>
              <option value="Paseo"     <?= $pub['tipo_actividad']=='Paseo'?'selected':'' ?>>Paseo</option>
              <option value="Taller"    <?= $pub['tipo_actividad']=='Taller'?'selected':'' ?>>Taller</option>
              <option value="Otro"      <?= $pub['tipo_actividad']=='Otro'?'selected':'' ?>>Otro</option>
          </select>
      </div>
      <?php endif; ?>

      <?php if ($cat === 'servicio'): ?>
      <div class="servicio-box">
        <h3 class="form-subsection-title">Detalles del servicio</h3>

        <label>💲 Precio (ej: desde 10.000):</label>
        <input type="text" inputmode="numeric" name="precio" class="solo-numeros" maxlength="10" value="<?= htmlspecialchars($pub['precio']) ?>" required>

        <label>📍 Comuna / Zona:</label>
        <input type="text" name="ubicacion" maxlength="100" value="<?= htmlspecialchars($pub['ubicacion']) ?>" required>

        <label>🕒 Disponibilidad (Opcional):</label>
        <input type="text" name="horario" maxlength="100" value="<?= htmlspecialchars($pub['horario']) ?>">
      </div>
      <?php endif; ?>

      <?php if ($cat === 'trabajo'): ?>
      <div class="trabajo-box">
        <h3 class="form-subsection-title">Detalles del trabajo</h3>

        <label>🏷 Tipo de trabajo:</label>
        <select name="tipo_trabajo" required>
            <option value="Tiempo completo" <?= $pub['tipo_trabajo']=='Tiempo completo'?'selected':'' ?>>Tiempo completo</option>
            <option value="Part-time" <?= $pub['tipo_trabajo']=='Part-time'?'selected':'' ?>>Part-time</option>
            <option value="Freelance" <?= $pub['tipo_trabajo']=='Freelance'?'selected':'' ?>>Freelance</option>
            <option value="Temporal" <?= $pub['tipo_trabajo']=='Temporal'?'selected':'' ?>>Temporal</option>
            <option value="Práctica" <?= $pub['tipo_trabajo']=='Práctica'?'selected':'' ?>>Práctica</option>
        </select>

        <label>🏡 Modalidad:</label>
        <select name="modalidad" required>
            <option value="Presencial" <?= $pub['modalidad']=='Presencial'?'selected':'' ?>>Presencial</option>
            <option value="Híbrido" <?= $pub['modalidad']=='Híbrido'?'selected':'' ?>>Híbrido</option>
            <option value="Remoto" <?= $pub['modalidad']=='Remoto'?'selected':'' ?>>Remoto</option>
        </select>

        <label>📍 Zona / Comuna(s):</label>
        <input type="text" name="zona_cobertura" maxlength="100" value="<?= htmlspecialchars($pub['zona_cobertura']) ?>" required>

        <label>💰 Salario (opcional):</label>
        <input type="text" name="salario" maxlength="50" value="<?= htmlspecialchars($pub['salario']) ?>">

        <label>🧰 ¿Materiales / Herramientas incluidos?:</label>
        <select name="materiales_incluidos">
            <option value="">No especificar</option>
            <option value="Sí" <?= $pub['materiales_incluidos']=='Sí'?'selected':'' ?>>Sí</option>
            <option value="No" <?= $pub['materiales_incluidos']=='No'?'selected':'' ?>>No</option>
        </select>

        <label>📋 Requisitos (opcional):</label>
        <textarea name="requisitos" rows="3" maxlength="500"><?= htmlspecialchars($pub['requisitos']) ?></textarea>

        <label>🧑‍💼 Experiencia deseada (opcional):</label>
        <textarea name="experiencia" rows="3" maxlength="500"><?= htmlspecialchars($pub['experiencia']) ?></textarea>

        <label>👥 Máximo de postulantes (opcional):</label>
        <input type="text" inputmode="numeric" name="cupos_trabajo" class="solo-numeros" maxlength="3" value="<?= htmlspecialchars($pub['cupos_trabajo']) ?>">

      </div>
      <?php endif; ?>

      <?php
          // Recuperar contacto guardado
          $stmtTel = $conn->prepare("SELECT phone FROM usuarios WHERE id = ?");
          $stmtTel->bind_param("i", $pub['usuario_id']);
          $stmtTel->execute();
          $stmtTel->store_result();
          $stmtTel->bind_result($telefonoBD);
          $stmtTel->fetch();
          $stmtTel->close();

          $contactoActual = $pub['contacto'] ?: $telefonoBD; 
      ?>
      <div class="form-group">
        <label>Número de contacto (WhatsApp del anuncio)</label>
        <input type="text" inputmode="numeric" name="contacto" id="contacto" 
               value="<?= htmlspecialchars($contactoActual) ?>" 
               class="input-text solo-numeros" placeholder="Ej: 976922011" maxlength="9" required>
        <small style="color:#777;">Solo 9 dígitos, sin +56</small>
      </div>

      <label>Imagen actual:</label>
      <div class="upload-preview">
        <img id="preview" 
             src="<?= !empty($pub['imagen']) ? "uploads/".$pub['imagen'] : "assets/imagenes/no-image.png" ?>" 
             class="preview-img">
      </div>

      <?php if (!empty($pub['imagen'])): ?>
      <button type="button" id="remove-img-btn" class="btn-remove-img">Quitar imagen</button>
      <?php endif; ?>

      <label>Nueva imagen (Opcional):</label>
      <div class="upload-box" id="drop-area">
          <p>📷 Arrastra una imagen aquí o haz click</p>
          <input type="file" name="imagen" id="file-input" accept="image/*" hidden>
      </div>

      <button type="submit" class="btn-submit">Guardar Cambios</button>
  </form>

  <a href="javascript:history.back()" class="btn-volver form-back">← Cancelar</a>

</section>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Seleccionamos todos los inputs que tengan la clase 'solo-numeros'
    const inputsNumericos = document.querySelectorAll('.solo-numeros');

    inputsNumericos.forEach(function(input) {
        input.addEventListener('input', function() {
            // Reemplaza cualquier cosa que NO sea un número (0-9) por vacío
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    });
});
</script>
<script>
document.addEventListener("DOMContentLoaded", () => {

  // Variables
  const fileInput = document.getElementById("file-input");
  const preview = document.getElementById("preview");
  const dropArea = document.getElementById("drop-area");
  const removeBtn = document.getElementById("remove-img-btn");

  if (dropArea && fileInput && preview) {
    
    // 1. Clic para abrir explorador
    dropArea.addEventListener("click", () => fileInput.click());

    // 2. Cambio en el input (Selección manual)
    fileInput.addEventListener("change", () => {
      const file = fileInput.files[0];
      if (file) preview.src = URL.createObjectURL(file);
    });

    // --- NUEVO: EVENTOS PARA ARRASTRAR Y SOLTAR ---

    // 3. Al arrastrar encima (Visual)
    dropArea.addEventListener('dragover', (e) => {
        e.preventDefault(); 
        dropArea.classList.add('dragover'); 
    });

    // 4. Al salir del área (Visual)
    dropArea.addEventListener('dragleave', () => {
        dropArea.classList.remove('dragover');
    });

    // 5. Al soltar la imagen (Lógica)
    dropArea.addEventListener('drop', (e) => {
        e.preventDefault();
        dropArea.classList.remove('dragover');

        // Asignar los archivos soltados al input
        if (e.dataTransfer.files.length > 0) {
            fileInput.files = e.dataTransfer.files;
            // Actualizar vista previa
            fileInput.dispatchEvent(new Event('change'));
        }
    });
  }

  // Lógica del botón "Quitar imagen"
  if (removeBtn && preview) {
    removeBtn.addEventListener("click", () => {
      document.getElementById("eliminar_imagen").value = "1";
      preview.src = "assets/imagenes/no-image.png";
      removeBtn.style.display = 'none'; // Ocultar botón al borrar
      fileInput.value = ""; // Limpiar input
    });
  }

});

// Validación extra al enviar el formulario
document.getElementById("form-publicacion").addEventListener("submit", function(e) {
    let tel = document.getElementById("contacto").value.trim();

    // Eliminar caracteres no numéricos por seguridad
    tel = tel.replace(/[^0-9]/g, ""); 
    document.getElementById("contacto").value = tel;

    if (tel.length !== 9) {
        e.preventDefault();
        Swal.fire({
            icon: 'error',
            title: 'Número inválido',
            html: 'El número debe tener exactamente <b>9 dígitos</b>',
            confirmButtonText: 'Entendido'
        });
        document.getElementById("contacto").focus();
        return false;
    }
});
</script>

<script>
const imageInput = document.getElementById("file-input");
if(imageInput){
  imageInput.addEventListener("change", function(){
    const file = this.files[0];
    if(!file) return;

    const validTypes = ["image/jpeg", "image/png", "image/webp", "image/gif"];
    if(!validTypes.includes(file.type)){
      alert("⚠ Solo se permiten imágenes JPG, PNG, WEBP o GIF.");
      this.value = "";
      return;
    }
    
    // 3MB Limite
    if(file.size > 3 * 1024 * 1024){
      alert("⚠ La imagen no debe superar los 3 MB.");
      this.value = "";
      return;
    }
  });
}
</script>