<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// 1. Archivos requeridos
require __DIR__ . "/includes/conexion.php";
require __DIR__ . "/includes/config_planes.php"; // ⚠️ Asegúrate de haber creado este archivo en el PASO 2

// 2. Validación de sesión
if (empty($_SESSION['user_id'])) {
    header("Location: login.php?redirect=publicaciones.php&msg=Debes iniciar sesión para publicar&type=error");
    exit;
}

$cat = $_GET['cat'] ?? '';
$uid = $_SESSION['user_id'];

// 3. Validación de categoría
$categorias_validas = [
    'actividad' => 'Actividades',
    'venta'     => 'Ventas',
    'trabajo'   => 'Trabajo',
    'servicio'  => 'Servicios',
    'alerta'    => 'Alertas Vecinales'
];

if (!array_key_exists($cat, $categorias_validas)) {
    die("Categoría inválida o no seleccionada. <a href='publicaciones.php'>Volver</a>");
}

$titulo_categoria = $categorias_validas[$cat];


// =========================================================================
// 🛑 LÓGICA DE RESTRICCIÓN DE PLANES 🛑
// =========================================================================

// A. Obtener el plan del usuario
$stmtPlan = $conn->prepare("SELECT plan_id FROM usuarios WHERE id = ?");
$stmtPlan->bind_param("i", $uid);
$stmtPlan->execute();
$userPlanId = $stmtPlan->get_result()->fetch_assoc()['plan_id'] ?? 0;
$reglas = obtenerLimitesPlan($userPlanId);

// B. Verificar límite
if ($cat !== 'alerta' && !$es_admin) {

    // CORRECCIÓN: Contar correctamente Ventas (estado) y Trabajos (estado_trabajo)
    $sqlCount = "SELECT COUNT(*) as total FROM publicaciones 
                 WHERE usuario_id = ? 
                 AND categoria NOT IN ('alerta', 'actividad') 
                 AND (
                    (categoria != 'trabajo' AND estado = 'disponible')
                    OR 
                    (categoria = 'trabajo' AND estado_trabajo = 'abierto')
                 )";
                 
    $stmtC = $conn->prepare($sqlCount);
    $stmtC->bind_param("i", $uid);
    $stmtC->execute();
    $activas = $stmtC->get_result()->fetch_assoc()['total'];

    // Si supera el límite, mostramos pantalla de bloqueo y cortamos ejecución
    if ($activas >= $reglas['limite_pubs']) {
        include __DIR__ . "/includes/header.php";
        ?>
        <div style="max-width:600px; margin:50px auto; text-align:center; padding:40px; background:#fff; border-radius:15px; box-shadow:0 10px 30px rgba(0,0,0,0.1); font-family: sans-serif;">
            <div style="font-size: 50px; margin-bottom: 20px;">✋</div>
            <h2 style="color:#d33; margin-top:0;">¡Límite alcanzado!</h2>
            
            <p style="font-size:18px; color:#555; line-height: 1.6;">
                Actualmente tienes <b><?= $activas ?></b> publicaciones activas.<br>
                El límite de tu plan <b><?= htmlspecialchars($reglas['nombre']) ?></b> es de <b><?= $reglas['limite_pubs'] ?></b>.
            </p>
            
            <hr style="border:0; border-top:1px solid #eee; margin: 20px 0;">

            <p style="color:#777; font-size: 14px; margin-bottom: 30px;">
                💡 <b>Consejo:</b> Para publicar algo nuevo, puedes marcar como "Vendido" una publicación anterior en tu perfil o mejorar tu plan para tener más espacio.
            </p>
            
            <div style="display:flex; gap:10px; justify-content:center; flex-wrap:wrap;">
                <a href="mis_publicaciones.php" class="btn" style="background:#666; color:white; padding:12px 25px; text-decoration:none; border-radius:50px; font-weight:bold;">Gestionar mis publicaciones</a>
                <a href="planes.php" class="btn" style="background:#2cd19b; color:white; padding:12px 25px; text-decoration:none; border-radius:50px; font-weight:bold;">Ver Planes y Precios</a>
            </div>
        </div>
        <?php
        include __DIR__ . "/includes/footer.php";
        exit; // 🛑 AQUÍ SE DETIENE EL SCRIPT
    }
}

// Si pasa el filtro, mostramos el formulario
include __DIR__ . "/includes/header.php"; 

// --- INICIO CÓDIGO NUEVO PARA MOSTRAR ALERTAS ---
if (!empty($_GET['msg'])) {
    $tipo = $_GET['type'] ?? 'info';
    // Colores: Rojo si es error, Verde si es éxito
    $colorFondo = ($tipo === 'error') ? '#ffecec' : '#d4edda';
    $colorTexto = ($tipo === 'error') ? '#721c24' : '#155724';
    $borde      = ($tipo === 'error') ? '#f5c6cb' : '#c3e6cb';
    
    echo "
    <div style='background: $colorFondo; color: $colorTexto; border: 1px solid $borde; padding: 15px; margin: 20px auto; max-width: 800px; border-radius: 5px; text-align: center;'>
        <strong>" . htmlspecialchars($_GET['msg']) . "</strong>
    </div>";
}

?>

<section class="form-section">
    
    <?php if ($cat === 'alerta'): ?>
      <div class="alerta-box" style="background:#fff5f5; padding:15px; border-radius:8px; border:1px solid #fecaca; margin-bottom:20px;">
        <h3 style="color:#c53030; margin-top:0; display:flex; align-items:center; gap:10px;">🚨 Reporte de Seguridad</h3>
        <p style="font-size:14px; color:#555; margin:0;">
           Describe qué está pasando. Tu ubicación actual se guardará automáticamente para avisar a los vecinos cercanos.
        </p>
      </div>
    <?php endif; ?>

  <h2 class="form-title">Crear Publicación de <span><?= $titulo_categoria ?></span></h2>

  <form action="publicar_guardar.php" method="POST" enctype="multipart/form-data" class="publicar-form">
      
      <input type="hidden" name="categoria" value="<?= htmlspecialchars($cat) ?>">

      <label>Título de la publicación:</label>
      <input type="text" name="titulo" required placeholder="Ej: Vendo bicicleta / Se busca gasfíter">

      <label>Descripción:</label>
      <textarea name="descripcion" rows="5" required placeholder="Detalla lo que ofreces o lo que sucede..."></textarea>

      <label>
          Número de contacto (WhatsApp)
          <?php if($cat === 'alerta'): ?>
              <span style="font-weight:normal; color:#666; font-size:0.9em;">(Opcional)</span>
          <?php endif; ?>
      </label>
      
      <input type="text" name="contacto" id="contacto" maxlength="9" 
             placeholder="<?= ($cat === 'alerta') ? 'Opcional' : 'Ej: 976922011' ?>" 
             class="form-control" 
             <?= ($cat !== 'alerta') ? 'required' : '' ?>> 

      <small style="color:#777;">Solo 9 dígitos, sin +56 ni espacios</small>

      <?php if ($cat === 'venta'): ?>
      <div id="campos-venta">
        <label>Precio:</label>
        <input type="number" name="precio" placeholder="Ej: 15000" required>

        <label>Estado del producto:</label>
        <select name="estado_producto" required>
            <option value="Nuevo">Nuevo</option>
            <option value="Semi nuevo">Semi nuevo</option>
            <option value="Usado">Usado</option>
        </select>

        <label>Condición / Detalle:</label>
        <select name="condicion" required>
            <option value="Excelente">Excelente</option>
            <option value="Bueno">Bueno</option>
            <option value="Con detalles">Con detalles</option>
            <option value="Malo">Malo</option>
        </select>

        <label>Entrega:</label>
        <input type="text" name="entrega" placeholder="Ej: Retiro en mi domicilio / Metro">

        <label>Método de pago:</label>
        <input type="text" name="metodo_pago" placeholder="Ej: Efectivo, Transferencia">
      </div>
      <?php endif; ?>

      <?php if ($cat === 'actividad'): ?>
      <div class="actividad-box">
        <h3 class="form-subsection-title">Detalles de la Actividad</h3>

        <label class="input-label">📍 Lugar / Punto de encuentro:</label>
        <input type="text" name="lugar" maxlength="100" class="input-field" placeholder="Ej: Multicancha Los Vilos" required>

        <label class="input-label">🗓 Día y Hora:</label>
        <input type="datetime-local" name="fecha_evento" class="input-field" required>

        <label class="input-label">👥 Cupos (Opcional):</label>
        <input type="number" name="cupos" maxlength="3" class="input-field" placeholder="Ej: 10">

        <label class="input-label">🏷 Tipo de actividad:</label>
        <select name="tipo_actividad" class="input-field" required>
            <option value="Deporte">Deporte</option>
            <option value="Juntada">Juntada</option>
            <option value="Carrete">Carrete</option>
            <option value="Paseo">Paseo</option>
            <option value="Taller">Taller</option>
            <option value="Otro">Otro</option>
        </select>
      </div>
      <?php endif; ?>

      <?php if ($cat === 'servicio'): ?>
      <div class="servicio-box">
        <h3 class="form-subsection-title">Detalles del servicio</h3>

        <label class="input-label">💲 Precio estimado (ej: desde 10.000):</label>
        <input type="number" name="precio" class="input-field" required>

        <label class="input-label">📍 Comuna / Zona:</label>
        <input type="text" name="ubicacion" class="input-field" placeholder="Ej: Quilicura / Renca / Colina" required>

        <label class="input-label">🕒 Disponibilidad (Opcional):</label>
        <input type="text" name="horario" class="input-field" placeholder="Ej: Lunes a Viernes 10:00 a 19:00">
      </div>
      <?php endif; ?>

      <?php if ($cat === 'trabajo'): ?>
      <div class="trabajo-box">
        <h3 class="form-subsection-title">Detalles del trabajo</h3>

        <label class="input-label">🏷 Tipo de trabajo:</label>
        <select name="tipo_trabajo" class="input-field" required>
            <option value="Tiempo completo">Tiempo completo</option>
            <option value="Part-time">Part-time</option>
            <option value="Freelance">Freelance</option>
            <option value="Temporal">Temporal</option>
            <option value="Práctica">Práctica</option>
        </select>

        <label class="input-label">🏡 Modalidad:</label>
        <select name="modalidad" class="input-field" required>
            <option value="Presencial">Presencial</option>
            <option value="Híbrido">Híbrido</option>
            <option value="Remoto">Remoto</option>
        </select>

        <label class="input-label">📍 Zona / Comuna(s):</label>
        <input type="text" name="zona_cobertura" class="input-field" placeholder="Ej: Quilicura, Renca, Colina" required>

        <label class="input-label">💰 Salario (opcional):</label>
        <input type="text" name="salario" class="input-field" placeholder="Ej: $500.000 líquidos / Por hora">

        <label class="input-label">🧰 ¿Materiales / Herramientas incluidos? (opcional):</label>
        <select name="materiales_incluidos" class="input-field">
            <option value="">No especificar</option>
            <option value="Sí">Sí</option>
            <option value="No">No</option>
        </select>

        <label class="input-label">📋 Requisitos (opcional):</label>
        <textarea name="requisitos" rows="3" class="input-field" placeholder="Ej: Experiencia mínima, estudios, etc."></textarea>

        <label class="input-label">🧑‍💼 Experiencia deseada (opcional):</label>
        <textarea name="experiencia" rows="3" class="input-field" placeholder="Ej: 1 año en el rubro, etc."></textarea>

        <label class="input-label">👥 Máximo de postulantes (opcional):</label>
        <input type="number" name="cupos_trabajo" class="input-field" placeholder="Ej: 20">
      </div>
      <?php endif; ?>

      <label>Imagen (opcional):</label>
      <div class="upload-box" id="drop-area">
          <p>📷 Arrastra una imagen aquí o haz click</p>
          <input type="file" name="imagen" id="file-input" accept="image/*" hidden>
      </div>
      <img id="preview" style="display:none; margin-top:10px; max-width:300px; border-radius:10px;">

      <input type="hidden" name="latitud" id="pub-lat" value="0.0">
      <input type="hidden" name="longitud" id="pub-lon" value="0.0">

      <button type="submit" class="btn-submit">Publicar</button>

  </form>

  <a href="publicaciones.php" class="btn-volver form-back">← Volver</a>

</section>

<script>
// Validación solo números en contacto
document.getElementById('contacto')?.addEventListener('input', function () {
    this.value = this.value.replace(/[^0-9]/g, '');
});

// Geolocalización
document.addEventListener("DOMContentLoaded", function() {
    const latInput = document.getElementById('pub-lat');
    const lonInput = document.getElementById('pub-lon');
    
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            function(position) {
                latInput.value = position.coords.latitude;
                lonInput.value = position.coords.longitude;
            },
            function(error) {
                // Fallback (Plaza Quilicura) si niega permisos
                latInput.value = "-33.3644"; 
                lonInput.value = "-70.7380";
            },
            { enableHighAccuracy: true, timeout: 5000, maximumAge: 0 }
        );
    }
});

// Drag & Drop Imagen
const dropArea = document.getElementById('drop-area');
const fileInput = document.getElementById('file-input');
const preview = document.getElementById('preview');

if(dropArea){
    dropArea.addEventListener('click', () => fileInput.click());

    fileInput.addEventListener('change', e => {
      const file = e.target.files[0];
      if (!file) return;
      const reader = new FileReader();
      reader.onload = e => {
        preview.src = e.target.result;
        preview.style.display = 'block';
      };
      reader.readAsDataURL(file);
    });

    dropArea.addEventListener('dragover', e => {
      e.preventDefault();
      dropArea.classList.add('dragover');
    });

    dropArea.addEventListener('dragleave', () => {
      dropArea.classList.remove('dragover');
    });

    dropArea.addEventListener('drop', e => {
      e.preventDefault();
      dropArea.classList.remove('dragover');
      fileInput.files = e.dataTransfer.files;
      fileInput.dispatchEvent(new Event('change'));
    });
}
</script>

<?php include __DIR__ . "/includes/footer.php"; ?>