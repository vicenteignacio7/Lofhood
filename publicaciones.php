<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require __DIR__ . '/includes/conexion.php'; 
require __DIR__ . '/includes/config_planes.php';

// Variables por defecto (por si no está logueado)
$bloqueo_plan = false;
$bloqueo_actividad = false;
$msj_plan = "";
$msj_actividad = "";

if (isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];
    
    // 👑 1. DETECTAR ADMIN (Nuevo)
    $es_admin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');

    // 2. OBTENER LÍMITE DEL PLAN (Para Ventas, Servicios, Trabajo)
    $stmtPlan = $conn->prepare("SELECT plan_id FROM usuarios WHERE id = ?");
    $stmtPlan->bind_param("i", $uid);
    $stmtPlan->execute();
    $userPlanId = $stmtPlan->get_result()->fetch_assoc()['plan_id'] ?? 0;
    $reglas = obtenerLimitesPlan($userPlanId);

   // 3. CONTADOR A: USO DEL PLAN (Excluye Alerta y Actividad)
    // CORRECCIÓN VISUAL
    $sqlPlan = "SELECT COUNT(*) as total FROM publicaciones 
                WHERE usuario_id = ? 
                AND categoria NOT IN ('alerta', 'actividad') 
                AND (
                    (categoria != 'trabajo' AND estado = 'disponible')
                    OR 
                    (categoria = 'trabajo' AND estado_trabajo = 'abierto')
                )";
    $stmtP = $conn->prepare($sqlPlan);
    $stmtP->bind_param("i", $uid);
    $stmtP->execute();
    $uso_plan = $stmtP->get_result()->fetch_assoc()['total'];

    // 4. CONTADOR B: USO DE ACTIVIDAD (Solo Actividad)
    $sqlAct = "SELECT COUNT(*) as total FROM publicaciones 
               WHERE usuario_id = ? 
               AND categoria = 'actividad' 
               AND estado NOT IN ('vendido', 'cerrado', 'terminado')";
    $stmtA = $conn->prepare($sqlAct);
    $stmtA->bind_param("i", $uid);
    $stmtA->execute();
    $uso_actividad = $stmtA->get_result()->fetch_assoc()['total'];

    // 5. DEFINIR BLOQUEOS (Aquí estaba el problema)
    
    // Regla para Ventas/Servicios/Trabajo
    // CORRECCIÓN: Si superó el límite Y NO ES ADMIN
    if ($uso_plan >= $reglas['limite_pubs'] && !$es_admin) {
        $bloqueo_plan = true;
        $msj_plan = "✋ ¡Límite del Plan Alcanzado!\\n\\nTu plan permite " . $reglas['limite_pubs'] . " publicaciones de Venta/Servicio/Trabajo y ya las ocupaste.\\n\\nDebes cerrar publicaciones antiguas para crear nuevas de este tipo.";
    }

    // Regla para Actividades (Fijo: Máximo 1)
    // CORRECCIÓN: Si superó el límite Y NO ES ADMIN
    if ($uso_actividad >= 1 && !$es_admin) {
        $bloqueo_actividad = true;
        $msj_actividad = "✋ ¡Límite de Actividades!\\n\\nSolo puedes tener 1 actividad activa a la vez.\\n\\nDebes esperar a que termine tu evento actual o eliminarlo para crear otro.";
    }
}

include __DIR__ . '/includes/header.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Publicaciones</title>
  <link rel="icon" type="image/png" href="assets/imagenes/favicon.png">
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    .subcategories { display: none; margin-top: 12px; padding-top: 8px; border-top: 1px solid rgba(255,255,255,0.2); }
    .btn-view.toggle-sub { cursor: pointer; }
    .category-content h3 { margin-bottom: 6px; }
    .category-content p { margin-bottom: 12px; }
    
    /* Estilo para botón bloqueado */
    .btn-bloqueado {
        background-color: #999 !important;
        cursor: not-allowed;
        opacity: 0.7;
    }

    @media (max-width: 768px){
      .category-card .btn-view { width: 100%; text-align: center; }
      .subcategories .btn-view { display: block; margin-top: 8px; }
    }
  </style>
</head>
<body>

<section class="categories" id="categorias" style="padding-top:110px;">
  
  <h2 class="categories-title">Publicaciones</h2>
  <p class="categories-subtitle">Elige el tipo de publicación y qué deseas hacer</p>

  <div class="categories-grid">

  <div class="category-card">
    <img src="assets/imagenes/actividades.png" alt="Actividades" class="category-image">
    <div class="category-content">
      <h3>ACTIVIDADES</h3>
      <p>Publica o revisa actividades de tu barrio</p>
      <a href="ver_publicaciones.php?cat=actividad" class="btn-view btn-pub">Ver publicaciones</a>
      
      <a href="<?= isset($_SESSION['user_id']) ? "crear_publicacion.php?cat=actividad" : "publicaciones.php?require_login=" . urlencode("crear_publicacion.php?cat=actividad") ?>" 
         class="btn-view btn-pub <?= ($bloqueo_actividad) ? 'btn-bloqueado' : '' ?>"
         onclick="<?= ($bloqueo_actividad) ? "mostrarError(event, '$msj_actividad')" : '' ?>">
         Crear actividad
      </a>
    </div>
  </div>

  <div class="category-card">
    <img src="assets/imagenes/ventas.jpeg" alt="Ventas" class="category-image">
    <div class="category-content">
      <h3>VENTAS</h3>
      <p>Compra y vende artículos fácilmente</p>
      <a href="ver_publicaciones.php?cat=venta" class="btn-view btn-pub">Ver publicaciones</a>
      
      <a href="<?= isset($_SESSION['user_id']) ? "crear_publicacion.php?cat=venta" : "publicaciones.php?require_login=" . urlencode("crear_publicacion.php?cat=venta") ?>" 
         class="btn-view btn-pub <?= ($bloqueo_plan) ? 'btn-bloqueado' : '' ?>"
         onclick="<?= ($bloqueo_plan) ? "mostrarError(event, '$msj_plan')" : '' ?>">
         Crear publicación
      </a>
    </div>
  </div>

  <div class="category-card">
    <img src="assets/imagenes/servicios.png" alt="Servicios" class="category-image">
    <div class="category-content">
      <h3>SERVICIOS</h3>
      <p>Ofrece o solicita servicios dentro de tu comunidad</p>
      <a href="ver_publicaciones.php?cat=servicio" class="btn-view btn-pub">Ver publicaciones</a>
      
      <a href="<?= isset($_SESSION['user_id']) ? "crear_publicacion.php?cat=servicio" : "publicaciones.php?require_login=" . urlencode("crear_publicacion.php?cat=servicio") ?>" 
         class="btn-view btn-pub <?= ($bloqueo_plan) ? 'btn-bloqueado' : '' ?>"
         onclick="<?= ($bloqueo_plan) ? "mostrarError(event, '$msj_plan')" : '' ?>">
         Crear publicación
      </a>
    </div>
  </div>

  <div class="category-card">
    <img src="assets/imagenes/trabajo.jpeg" alt="Trabajo" class="category-image">
    <div class="category-content">
      <h3>TRABAJO</h3>
      <p>Ofrece o busca empleos y servicios</p>
      <a href="ver_publicaciones.php?cat=trabajo" class="btn-view btn-pub">Ver publicaciones</a>
      
      <a href="<?= isset($_SESSION['user_id']) ? "crear_publicacion.php?cat=trabajo" : "publicaciones.php?require_login=" . urlencode("crear_publicacion.php?cat=trabajo") ?>" 
         class="btn-view btn-pub <?= ($bloqueo_plan) ? 'btn-bloqueado' : '' ?>"
         onclick="<?= ($bloqueo_plan) ? "mostrarError(event, '$msj_plan')" : '' ?>">
         Crear publicación
      </a>
    </div>
  </div>

</div>

  <div style="text-align:center; margin-top:20px;">
      <a href="crear_publicacion.php?cat=alerta" class="btn-sos">🚨 Publicar Alerta</a>
  </div>
</section>

<script>
// Función única para mostrar errores
function mostrarError(e, mensaje) {
    e.preventDefault(); 
    // Como tenemos el modal personalizado abajo, usaremos ese:
    document.getElementById('modalMensaje').innerText = mensaje;
    const overlay = document.getElementById('miModalCustom');
    overlay.style.display = 'flex';
    setTimeout(() => { overlay.classList.add('active'); }, 10);
}

// Toggle de submenús
document.addEventListener('DOMContentLoaded', function(){
  const toggles = document.querySelectorAll('.toggle-sub');
  toggles.forEach(t => {
    t.addEventListener('click', function(e){
      e.preventDefault();
      const targetId = this.getAttribute('data-target');
      const targetEl = document.getElementById(targetId);
      document.querySelectorAll('.subcategories').forEach(sc => {
        if (sc !== targetEl) sc.style.display = 'none';
      });
      targetEl.style.display = (targetEl.style.display === 'block') ? 'none' : 'block';
    });
  });
});
</script>

</body>
<style>
/* Fondo oscuro */
.modal-overlay {
    position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0, 0, 0, 0.6); backdrop-filter: blur(3px);
    display: none; justify-content: center; align-items: center;
    z-index: 9999; opacity: 0; transition: opacity 0.3s ease;
}

/* Caja Blanca */
.modal-box {
    background: white; padding: 30px; border-radius: 15px;
    width: 90%; max-width: 420px; text-align: center;
    box-shadow: 0 10px 25px rgba(0,0,0,0.2);
    transform: scale(0.8); transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

/* Animaciones */
.modal-overlay.active { display: flex; opacity: 1; }
.modal-overlay.active .modal-box { transform: scale(1); }

/* Textos e Iconos */
.modal-icon { font-size: 50px; margin-bottom: 15px; display: block; }
.modal-title { font-size: 22px; font-weight: bold; color: #333; margin-bottom: 10px; display: block; }
.modal-text { font-size: 15px; color: #666; line-height: 1.5; margin-bottom: 25px; display: block; }

/* Contenedor de Botones */
.modal-actions {
    display: flex;
    gap: 10px;
    justify-content: center;
    flex-wrap: wrap; /* Para que se ajusten en móviles pequeños */
}

/* Estilo Base de Botones */
.modal-btn {
    border: none; padding: 12px 20px; border-radius: 50px;
    font-size: 14px; font-weight: bold; cursor: pointer;
    text-decoration: none; display: inline-block;
    transition: transform 0.2s, box-shadow 0.2s;
}
.modal-btn:hover { transform: translateY(-2px); }

/* Botón 1: Gestionar (El importante) */
.btn-gestionar {
    background: #2cd19b; /* Verde menta o tu color corporativo */
    color: white;
    box-shadow: 0 4px 10px rgba(44, 209, 155, 0.3);
    flex: 1; /* Ocupa espacio disponible */
    min-width: 140px;
}

/* Botón 2: Cerrar (Secundario) */
.btn-cerrar {
    background: #f1f2f6;
    color: #555;
    min-width: 100px;
}
.btn-cerrar:hover { background: #e1e2e6; }

</style>

<div id="miModalCustom" class="modal-overlay">
    <div class="modal-box">
        <span class="modal-icon">✋</span>
        <span class="modal-title">¡Límite alcanzado!</span>
        
        <span id="modalMensaje" class="modal-text">Texto del error...</span>
        
        <div class="modal-actions">
            <a href="mis_publicaciones.php" class="modal-btn btn-gestionar">Gestionar mis publicaciones</a>
            
            <button class="modal-btn btn-cerrar" onclick="cerrarModal()">Entendido</button>
        </div>
    </div>
</div>

<script>
// Función para cerrar
function cerrarModal() {
    const overlay = document.getElementById('miModalCustom');
    overlay.classList.remove('active');
    setTimeout(() => { overlay.style.display = 'none'; }, 300);
}

// Cerrar al hacer click fuera
document.getElementById('miModalCustom').addEventListener('click', function(e) {
    if (e.target === this) cerrarModal();
});
</script>
</html>
<?php include __DIR__ . '/includes/footer.php'; ?>