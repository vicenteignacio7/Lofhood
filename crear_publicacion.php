<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (empty($_SESSION['user_id'])) {
    header("Location: login.php?redirect=publicaciones.php&msg=Debes iniciar sesión para publicar&type=error");
    exit;
}

require __DIR__ . "/includes/conexion.php";
$cat = $_GET['cat'] ?? '';

$categorias_validas = [
    'actividad' => 'Actividades',
    'venta'     => 'Ventas',
    'trabajo'   => 'Trabajo',
    'servicio'  => 'Servicios',
    'alerta'    => 'Alertas Vecinales'
];

if (!array_key_exists($cat, $categorias_validas)) {
    die("Categoría inválida");
}

$titulo_categoria = $categorias_validas[$cat];

include __DIR__ . "/includes/header.php";
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

<section class="form-section">
<?php if ($cat === 'alerta'): ?>
      <div class="alerta-box" style="background:#fff5f5; padding:15px; border-radius:8px; border:1px solid #fecaca; margin-bottom:15px;">
        <h3 style="color:#c53030; margin-top:0;">🚨 Reporte de Seguridad</h3>
        <p style="font-size:14px; color:#555;">
           Describe qué está pasando. Tu ubicación actual se guardará automáticamente para avisar a los vecinos cercanos.
        </p>
        </div>
      <?php endif; ?>
  <h2 class="form-title">Crear Publicación de <span><?= $titulo_categoria ?></span></h2>

  <form action="publicar_guardar.php" method="POST" enctype="multipart/form-data" class="publicar-form">
      <input type="hidden" name="categoria" value="<?= htmlspecialchars($cat) ?>">

      <label>Título de la publicación:</label>
      <input type="text" name="titulo" maxlength="80" placeholder="Máx 80 caracteres" required>

      <label>Descripción:</label>
      <textarea name="descripcion" rows="5" maxlength="1000" placeholder="Máx 1000 caracteres" required></textarea>

      <label>
          Número de contacto (WhatsApp del anuncio)
          <?php if($cat === 'alerta'): ?>
              <span style="font-weight:normal; color:#666; font-size:0.9em;">(Opcional)</span>
          <?php endif; ?>
      </label>
      
      <input type="text" inputmode="numeric" name="contacto" id="contacto" maxlength="9" 
             placeholder="<?= ($cat === 'alerta') ? 'Opcional' : 'Ej: 976922011' ?>" 
             class="form-control solo-numeros" 
             <?= ($cat !== 'alerta') ? 'required' : '' ?>> 

      <small style="color:#777;">Solo 9 dígitos, sin +56 ni espacios</small>

      <div id="campos-venta" <?= ($cat === 'venta') ? '' : 'style="display:none;"' ?>>

        <label>Precio:</label>
        <input type="text" inputmode="numeric" name="precio" class="solo-numeros" maxlength="10" placeholder="Ej: 15000" <?= ($cat === 'venta') ? 'required' : '' ?>>

        <label>Estado del producto:</label>
        <select name="estado_producto" <?= ($cat === 'venta') ? 'required' : '' ?>>
            <option value="Nuevo">Nuevo</option>
            <option value="Semi nuevo">Semi nuevo</option>
            <option value="Usado">Usado</option>
        </select>

        <label>Condición / Detalle:</label>
        <select name="condicion" <?= ($cat === 'venta') ? 'required' : '' ?>>
            <option value="Excelente">Excelente</option>
            <option value="Bueno">Bueno</option>
            <option value="Con detalles">Con detalles</option>
            <option value="Malo">Malo</option>
        </select>

        <label>Entrega:</label>
        <input type="text" name="entrega" maxlength="100" placeholder="Ej: Retiro en Quilicura / A coordinar">

        <label>Método de pago:</label>
        <input type="text" name="metodo_pago" maxlength="100" placeholder="Ej: Efectivo, Transferencia">

      </div>

      <?php if ($cat === 'actividad'): ?>
      <div class="actividad-box">

        <h3 class="form-subsection-title">Detalles de la Actividad</h3>

        <label class="input-label">📍 Lugar / Punto de encuentro:</label>
        <input type="text" name="lugar" maxlength="100" class="input-field" placeholder="Ej: Multicancha Los Vilos" required>

        <label class="input-label">🗓 Día y Hora:</label>
        <input type="datetime-local" name="fecha_evento" class="input-field" required>

        <label class="input-label">👥 Cupos (Opcional):</label>
        <input type="text" inputmode="numeric" name="cupos" maxlength="4" class="input-field solo-numeros" placeholder="Ej: 10">

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

        <label class="input-label">💲 Precio (ej: desde 10.000):</label>
        <input type="text" inputmode="numeric" name="precio" class="input-field solo-numeros" maxlength="10" required>

        <label class="input-label">📍 Comuna / Zona:</label>
        <input type="text" name="ubicacion" maxlength="100" class="input-field" placeholder="Ej: Quilicura / Renca / Colina" required>

        <label class="input-label">🕒 Disponibilidad (Opcional):</label>
        <input type="text" name="horario" maxlength="100" class="input-field" placeholder="Ej: Lunes a Viernes 10:00 a 19:00">

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
        <input type="text" name="zona_cobertura" maxlength="100" class="input-field" placeholder="Ej: Quilicura, Renca, Colina" required>

        <label class="input-label">💰 Salario (opcional):</label>
        <input type="text" name="salario" maxlength="50" class="input-field" placeholder="Ej: $500.000 líquidos / Por hora">

        <label class="input-label">🧰 ¿Materiales / Herramientas incluidos? (opcional):</label>
        <select name="materiales_incluidos" class="input-field">
            <option value="">No especificar</option>
            <option value="Sí">Sí</option>
            <option value="No">No</option>
        </select>

        <label class="input-label">📋 Requisitos (opcional):</label>
        <textarea name="requisitos" rows="3" maxlength="500" class="input-field" placeholder="Ej: Experiencia mínima, estudios, etc."></textarea>

        <label class="input-label">🧑‍💼 Experiencia deseada (opcional):</label>
        <textarea name="experiencia" rows="3" maxlength="500" class="input-field" placeholder="Ej: 1 año en el rubro, etc."></textarea>

        <label class="input-label">👥 Máximo de postulantes (opcional):</label>
        <input type="text" inputmode="numeric" name="cupos_trabajo" maxlength="3" class="input-field solo-numeros" placeholder="Ej: 20">

      </div>
      <?php endif; ?>

      <label>Imagen (opcional):</label>
      <div class="upload-box" id="drop-area">
          <p>📷 Arrastra una imagen aquí o haz click</p>
          <input type="file" name="imagen" id="file-input" accept="image/*" hidden>
      </div>

      <img id="preview" style="display:none; margin-top:10px; max-width:300px; border-radius:10px;">

      <label style="margin-top: 20px; display:block;">📍 Ubicación de la publicación:</label>
      
      <div id="mapa-publicacion" style="height: 300px; width: 100%; border-radius: 8px; margin-bottom: 10px; border: 1px solid #ccc;"></div>

      <div style="display: flex; gap: 10px; margin-bottom: 20px;">
          <button type="button" id="btn-usar-gps" style="background-color: #2b6cb0; color: white; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer; font-size: 0.9em;">
              🎯 Usar mi ubicación actual
          </button>
          <small style="align-self: center; color: #666;">O arrastra el pin en el mapa</small>
      </div>

      <input type="hidden" name="latitud" id="pub-lat" value="-33.3644">
      <input type="hidden" name="longitud" id="pub-lon" value="-70.7380">
      
      <button type="submit" class="btn-submit">Publicar</button>

  </form>

  <a href="publicaciones.php" class="btn-volver form-back">← Volver</a>

</section>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

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
document.addEventListener("DOMContentLoaded", function() {

    // --- CONFIGURACIÓN INICIAL ---
    const defaultLat = -33.3644; // Plaza Quilicura
    const defaultLon = -70.7380;
    const radioMetros = 5000;    // 5 KM

    // Referencias
    const latInput = document.getElementById('pub-lat');
    const lonInput = document.getElementById('pub-lon');
    const btnGps = document.getElementById('btn-usar-gps');

    // Validación inicial
    if (!latInput.value || latInput.value == "0.0") {
        latInput.value = defaultLat;
        lonInput.value = defaultLon;
    }

    // 1. INICIALIZAR EL MAPA
    var map = L.map('mapa-publicacion').setView([latInput.value, lonInput.value], 13);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap'
    }).addTo(map);

    // 2. CREAR EL PIN
    var marker = L.marker([latInput.value, lonInput.value], {
        draggable: true
    }).addTo(map);

    // 3. CREAR EL CÍRCULO
    var circle = L.circle([latInput.value, lonInput.value], {
        color: '#2b6cb0',
        fillColor: '#3182ce',
        fillOpacity: 0.1,
        radius: radioMetros
    }).addTo(map);

    // --- FUNCIÓN PARA ACTUALIZAR INPUTS ---
    function updateInputs(lat, lng) {
        // Reducir decimales para evitar errores
        const latFixed = parseFloat(lat).toFixed(8);
        const lngFixed = parseFloat(lng).toFixed(8);

        latInput.value = latFixed;
        lonInput.value = lngFixed;

        // Visual
        circle.setLatLng([lat, lng]);
        
        console.log("📍 COORDENADAS GUARDADAS EN FORMULARIO:", latFixed, lngFixed);
    }

    // --- EVENTOS ---

    marker.on('dragend', function(e) {
        var pos = marker.getLatLng();
        updateInputs(pos.lat, pos.lng);
        map.panTo(pos);
    });

    marker.on('drag', function(e) {
        circle.setLatLng(e.latlng);
    });

    map.on('click', function(e) {
        marker.setLatLng(e.latlng);
        updateInputs(e.latlng.lat, e.latlng.lng);
    });

    // --- BOTÓN GPS ---
    if (btnGps) {
        btnGps.addEventListener('click', function() {
            const textoOriginal = btnGps.innerHTML;
            btnGps.innerHTML = "⌛ Buscando...";
            btnGps.disabled = true;

            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        const userLat = position.coords.latitude;
                        const userLon = position.coords.longitude;

                        marker.setLatLng([userLat, userLon]);
                        map.setView([userLat, userLon], 13);
                        updateInputs(userLat, userLon);

                        var group = new L.featureGroup([marker, circle]);
                        map.fitBounds(group.getBounds());

                        btnGps.innerHTML = "✅ Ubicación Actualizada";
                        setTimeout(() => { 
                            btnGps.innerHTML = textoOriginal; 
                            btnGps.disabled = false;
                        }, 2000);
                    },
                    function(error) {
                        console.error("Error GPS:", error);
                        alert("No pudimos obtener tu ubicación. Verifica permisos.");
                        btnGps.innerHTML = "❌ Error";
                        setTimeout(() => { 
                            btnGps.innerHTML = textoOriginal; 
                            btnGps.disabled = false;
                        }, 2000);
                    },
                    { enableHighAccuracy: true }
                );
            } else {
                alert("Navegador no soporta GPS.");
            }
        });
    }
});
</script>

<script>
const dropArea = document.getElementById('drop-area');
const fileInput = document.getElementById('file-input');
const preview = document.getElementById('preview');

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
</script>

<?php include __DIR__ . "/includes/footer.php"; ?>