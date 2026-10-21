<?php 
if (session_status() === PHP_SESSION_NONE) session_start();
require __DIR__ . "/includes/conexion.php";

$usuario_actual_id = $_SESSION['user_id'] ?? null;
$usuario_actual_role = $_SESSION['role'] ?? null;

if ($usuario_actual_id && !$usuario_actual_role) {
    // Si la sesión existe pero el rol no está, lo cargamos desde la DB
    $stmtRole = $conn->prepare("SELECT role FROM usuarios WHERE id = ?");
    $stmtRole->bind_param("i", $usuario_actual_id);
    $stmtRole->execute();
    $usuario_actual_role = $stmtRole->get_result()->fetch_assoc()['role'] ?? null;
    $_SESSION['role'] = $usuario_actual_role;
    $stmtRole->close();
}

// 1. Capturar parámetros
$cat = $_GET['cat'] ?? '';
$busqueda = trim($_GET['q'] ?? ''); // Capturamos lo que escriben en el buscador

$categorias_validas = [
    'actividad' => 'Actividades',
    'venta'     => 'Ventas',
    'trabajo'   => 'Trabajo',
    'servicio'  => 'Servicios',
    'alerta'    => 'Alertas Vecinales'
];

// 🟢 SOLUCIÓN: Si no hay categoría, redirigir al Inicio
if (!array_key_exists($cat, $categorias_validas)) { 
    header("Location: index.php");
    exit;
}
$titulo_categoria = $categorias_validas[$cat];
$uid = $_SESSION['user_id'] ?? 0;

// --- PAGINACIÓN: Configuración ---
$limite = 10; // Publicaciones por página
$pagina = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($pagina < 1) $pagina = 1;
$offset = ($pagina - 1) * $limite;

// 1. Contar total de publicaciones (para saber si hay "Siguiente")
$sqlCount = "SELECT COUNT(*) as total FROM publicaciones p WHERE p.categoria = ?";
if (!empty($busqueda)) {
    $sqlCount .= " AND (p.titulo LIKE ? OR p.descripcion LIKE ?)";
}
$stmtCount = $conn->prepare($sqlCount);

if (!empty($busqueda)) {
    $term = "%" . $busqueda . "%";
    $stmtCount->bind_param("sss", $cat, $term, $term);
} else {
    $stmtCount->bind_param("s", $cat);
}
$stmtCount->execute();
$total_pubs = $stmtCount->get_result()->fetch_assoc()['total'];
$total_paginas = ceil($total_pubs / $limite);
$stmtCount->close();

// 2. Consulta real con LIMIT y OFFSET
// 2. Consulta real con LIMIT y OFFSET
$sql = "SELECT 
          p.*, 
          u.username,
          p.precio, p.salario, -- ✅ Agregamos precio y salario aquí
          (SELECT COUNT(*) FROM favoritos f WHERE f.publicacion_id = p.id AND f.usuario_id = $uid) as es_favorito
        FROM publicaciones p
        INNER JOIN usuarios u ON p.usuario_id = u.id
        WHERE p.categoria = ?";

if (!empty($busqueda)) {
    $sql .= " AND (p.titulo LIKE ? OR p.descripcion LIKE ?)";
}

$sql .= " ORDER BY p.fecha DESC LIMIT ? OFFSET ?"; // ✅ Agregamos el límite

$stmt = $conn->prepare($sql);

if (!empty($busqueda)) {
    $stmt->bind_param("sssii", $cat, $term, $term, $limite, $offset);
} else {
    $stmt->bind_param("sii", $cat, $limite, $offset);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<?php include __DIR__ . "/includes/header.php"; ?>

<style>
/* Estilos del Buscador */
.search-container {
    max-width: 600px;
    margin: 20px auto 10px;
    padding: 0 15px;
    position: relative;
}
.search-form {
    display: flex;
    gap: 8px;
}
.search-input {
    flex: 1;
    padding: 12px 20px;
    border: 1px solid #ddd;
    border-radius: 50px;
    font-size: 16px;
    outline: none;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    transition: 0.3s;
}
.search-input:focus {
    border-color: #2cd19b;
    box-shadow: 0 4px 12px rgba(44, 209, 155, 0.2);
}
.search-btn {
    background: #2cd19b;
    color: white;
    border: none;
    width: 46px;
    height: 46px;
    border-radius: 50%;
    cursor: pointer;
    font-size: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: 0.3s;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}
.search-btn:hover { background: #25b384; transform: scale(1.05); }

/* Botón limpiar búsqueda */
.btn-clear-search {
    display: inline-block;
    margin-top: 10px;
    color: #d33;
    font-size: 14px;
    text-decoration: none;
    font-weight: 500;
}
.btn-clear-search:hover { text-decoration: underline; }

/* Botón volver flotante */
.btn-back {
    position: fixed; 
    top: 20px;       
    left: 20px;      
    z-index: 9999;   
    background-color: #fff; 
    color: #333;
    padding: 10px 20px;
    border-radius: 50px;    
    box-shadow: 0 4px 10px rgba(0,0,0,0.2); 
    text-decoration: none;
    font-weight: bold;
    transition: all 0.3s ease;
}
.btn-back:hover {
    background-color: #f0f0f0;
    transform: scale(1.05); 
}
</style>

<section class="publicaciones-lista">
  <a href="publicaciones.php" class="btn-back">← Volver</a>

  <div class="search-container">
      <form action="ver_publicaciones.php" method="GET" class="search-form">
          <input type="hidden" name="cat" value="<?= htmlspecialchars($cat) ?>">
          
          <input type="text" name="q" class="search-input" 
                 placeholder="Buscar en <?= htmlspecialchars($titulo_categoria) ?>..." 
                 value="<?= htmlspecialchars($busqueda) ?>">
          
          <button type="submit" class="search-btn">🔍</button>
      </form>
  </div>

  <?php if (!empty($busqueda)): ?>
      <h2 class="categories-title">Resultados para: "<?= htmlspecialchars($busqueda) ?>"</h2>
      <p class="categories-subtitle">
          En la categoría <?= htmlspecialchars($titulo_categoria) ?> 
          (<a href="ver_publicaciones.php?cat=<?= $cat ?>" class="btn-clear-search">Borrar filtro ✖</a>)
      </p>
  <?php else: ?>
      <h2 class="categories-title">Publicaciones de <?= htmlspecialchars($titulo_categoria) ?></h2>
      <p class="categories-subtitle">Explora lo que la comunidad está compartiendo</p>
  <?php endif; ?>

  <div class="grid-publicaciones">
  <?php if ($result->num_rows > 0): while($row = $result->fetch_assoc()): ?>
    
    <div class="pub-card">
      <a class="pub-link" 
         href="ver_publicacion.php?id=<?= $row['id'] ?>&cat=<?= urlencode($cat) ?>&from=cat&<?= http_build_query($_GET) ?>">
       <div class="pub-img-wrapper">
    <?php if(isset($_SESSION['user_id'])): ?>
        <button class="btn-fav <?= $row['es_favorito'] ? 'active' : '' ?>" data-id="<?= $row['id'] ?>">
            <i class="<?= $row['es_favorito'] ? 'fa-solid' : 'fa-regular' ?> fa-heart"></i>
        </button>
    <?php endif; ?>

    <?php if (!empty($row['imagen'])): ?>
        <img src="uploads/<?= htmlspecialchars($row['imagen']) ?>" class="pub-img">
    <?php else: ?>
        <div class="no-image-box">
            <span class="no-image-icon">✖</span>
            <span class="no-image-text">Sin Foto</span>
        </div>
    <?php endif; ?>
</div>

          <?php if ($row['categoria'] === 'actividad' && $row['estado'] === 'terminado'): ?>
              <span class="badge-terminado">TERMINADO</span>

          <?php elseif ($row['categoria'] === 'trabajo' && isset($row['estado_trabajo']) && $row['estado_trabajo'] === 'cerrado'): ?>
              <span class="badge-vendido">CERRADO</span> 

          <?php elseif ($row['categoria'] !== 'actividad' && $row['categoria'] !== 'trabajo' && $row['estado'] === 'vendido'): ?>
              <span class="badge-vendido">VENDIDO</span>
          <?php endif; ?>

       <div class="pub-info">
          <h3 class="pub-titulo"><?= htmlspecialchars($row['titulo']) ?></h3>

          <?php if (!empty($row['precio'])): ?>
              <p style="color:#2cd19b; font-weight:bold; font-size:15px; margin: 5px 0;">
                  $<?= number_format($row['precio'], 0, ',', '.') ?>
              </p>
          <?php elseif (!empty($row['salario'])): ?>
              <p style="color:#2b6cb0; font-weight:bold; font-size:14px; margin: 5px 0;">
                  💰 <?= htmlspecialchars($row['salario']) ?>
              </p>
          <?php endif; ?>
          <span class="pub-user">Publicado por <b><?= htmlspecialchars($row['username']) ?></b></span>
          <span class="pub-fecha"><?= date("d/m/Y H:i", strtotime($row['fecha'])) ?></span>
        </div>

      </a>

      <?php 
      // 🛑 NUEVA LÓGICA DE PERMISOS: Mostrar acciones si es el dueño O es el Admin
      $es_dueno_o_admin = (
          ($usuario_actual_id && $usuario_actual_id == $row['usuario_id']) || 
          ($usuario_actual_role === 'admin')
      );
      ?>
<?php if($es_dueno_o_admin): ?>

      <div class="acciones-fijas">
        
        <a href="editar_publicacion.php?id=<?= $row['id'] ?>&cat=<?= urlencode($cat) ?>&from=cat&<?= http_build_query($_GET) ?>" 
           class="btn-item edit">✏ Editar</a>
<?php if ($cat === 'actividad'): ?>
            <?php if ($row['estado'] === 'disponible'): ?>
                <button class="btn-item terminado btn-toggle-estado"
                        data-id="<?= $row['id'] ?>" 
                        data-cat="<?= $cat ?>" 
                        data-from="cat" 
                        data-estado="terminado">
                  Marcar como Terminado
                </button>
            <?php else: ?>
                <button class="btn-item disponible btn-toggle-estado"
                        data-id="<?= $row['id'] ?>" 
                        data-cat="<?= $cat ?>" 
                        data-from="cat" 
                        data-estado="disponible">
                  Marcar como Disponible
                </button>
            <?php endif; ?>

        <?php elseif ($cat === 'trabajo'): ?>
            <?php $estado_trabajo = $row['estado_trabajo'] ?? 'abierto'; ?>
            <?php if ($estado_trabajo === 'abierto'): ?>
                <button class="btn-item vendido btn-toggle-estado"
                        data-id="<?= $row['id'] ?>" 
                        data-cat="<?= $cat ?>" 
                        data-from="cat" 
                        data-estado="cerrado">
                  Marcar como Cerrado
                </button>
            <?php else: ?>
                <button class="btn-item disponible btn-toggle-estado"
                        data-id="<?= $row['id'] ?>" 
                        data-cat="<?= $cat ?>" 
                        data-from="cat" 
                        data-estado="abierto">
                  Marcar como Abierto
                </button>
            <?php endif; ?>

        <?php else: ?>
            <?php if ($row['estado'] === 'disponible'): ?>
                <button class="btn-item vendido btn-toggle-estado"
                        data-id="<?= $row['id'] ?>" 
                        data-cat="<?= $cat ?>" 
                        data-from="cat" 
                        data-estado="vendido">
                  Marcar como Vendido
                </button>
            <?php else: ?>
                <button class="btn-item disponible btn-toggle-estado"
                        data-id="<?= $row['id'] ?>" 
                        data-cat="<?= $cat ?>" 
                        data-from="cat" 
                        data-estado="disponible">
                  Marcar como Disponible
                </button>
            <?php endif; ?>
        <?php endif; ?>

        <button class="btn-item delete btn-eliminar"
                data-id="<?= $row['id'] ?>" 
                data-cat="<?= $cat ?>" 
                data-from="cat">
          🗑 Eliminar
        </button>
      </div>
      <?php endif; ?>

    </div>

  <?php endwhile; else: ?>
        <?php if (!empty($busqueda)): ?>
            <p class="no-pubs">No encontramos resultados para "<b><?= htmlspecialchars($busqueda) ?></b>".</p>
            <a href="ver_publicaciones.php?cat=<?= $cat ?>" style="display:block; text-align:center; margin-top:10px; color:#2cd19b;">Ver todo</a>
        <?php else: ?>
            <p class="no-pubs">No hay publicaciones aún en esta categoría.</p>
        <?php endif; ?>
    <?php endif; ?>
    
    </div> <?php if ($total_paginas > 1): ?>
    <div class="paginacion-container" style="text-align:center; margin: 30px 0;">
        <?php if ($pagina > 1): ?>
            <a href="ver_publicaciones.php?cat=<?= $cat ?>&q=<?= urlencode($busqueda) ?>&page=<?= $pagina - 1 ?>" class="btn-paginacion">← Anterior</a>
        <?php endif; ?>
        
        <span class="pagina-info" style="margin: 0 15px; font-weight:bold; color:#555;">
            Página <?= $pagina ?> de <?= $total_paginas ?>
        </span>

        <?php if ($pagina < $total_paginas): ?>
            <a href="ver_publicaciones.php?cat=<?= $cat ?>&q=<?= urlencode($busqueda) ?>&page=<?= $pagina + 1 ?>" class="btn-paginacion">Siguiente →</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

</section>
<style>

    
    /* Estilos Modal */
    .alerta-overlay { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0, 0, 0, 0.6); backdrop-filter: blur(4px); z-index: 99999999; display: none; justify-content: center; align-items: center; opacity: 0; transition: opacity 0.3s ease; }
    .alerta-card { background: #fff; width: 90%; max-width: 400px; padding: 30px; border-radius: 20px; text-align: center; box-shadow: 0 20px 50px rgba(0,0,0,0.3); transform: translateY(20px) scale(0.95); transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); position: relative; overflow: hidden; }
    .alerta-card::before { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 6px; background: linear-gradient(90deg, #ff6b6b, #ee5253); }
    .icon-circle { width: 70px; height: 70px; background: #fff0f0; border-radius: 50%; display: inline-flex; justify-content: center; align-items: center; margin-bottom: 15px; }
    .icon-emoji { font-size: 35px; }
    .alerta-title { font-size: 20px; font-weight: 700; color: #2d3436; margin-bottom: 10px; display: block; }
    .alerta-msg { font-size: 15px; color: #636e72; line-height: 1.5; margin-bottom: 25px; display: block; }
    .btn-entendido { background: #2d3436; color: #fff; border: none; padding: 12px 30px; border-radius: 50px; font-size: 14px; font-weight: 600; cursor: pointer; transition: transform 0.2s, background 0.2s; outline: none; }
    .btn-entendido:hover { background: #000; transform: translateY(-2px); }
    .alerta-overlay.mostrar { display: flex; opacity: 1; }
    .alerta-overlay.mostrar .alerta-card { transform: translateY(0) scale(1); }
</style>

<div id="modalAlerta" class="alerta-overlay">
    <div class="alerta-card">
        <div class="icon-circle"><span class="icon-emoji">✋</span></div>
        <span class="alerta-title">¡Atención!</span>
        <span id="textoAlerta" class="alerta-msg">...</span>
        <button class="btn-entendido" onclick="cerrarAlerta()">Entendido</button>
    </div>
</div>

<script>
// 1. Control manual de Scroll
if (history.scrollRestoration) { history.scrollRestoration = 'manual'; }

document.addEventListener("DOMContentLoaded", function() {
    
    // 2. Restaurar posición
    const scrollPos = sessionStorage.getItem("scrollPos");
    if (scrollPos) {
        window.scrollTo(0, parseInt(scrollPos));
        sessionStorage.removeItem("scrollPos");
    }

   
});

// 4. Cerrar Alerta y LIMPIAR URL
function cerrarAlerta() {
    const modal = document.getElementById('modalAlerta');
    modal.classList.remove('mostrar');
    setTimeout(() => { modal.style.display = 'none'; }, 300);

    // Limpiamos URL aquí
    const url = new URL(window.location);
    url.searchParams.delete('msg');
    url.searchParams.delete('type');
    window.history.replaceState({}, '', url);
}

document.getElementById('modalAlerta').addEventListener('click', function(e) {
    if (e.target === this) cerrarAlerta();
});

// 5. Botones Acción
document.querySelectorAll('.btn-toggle-estado').forEach(btn => {
    btn.addEventListener('click', function (e) {
        e.stopPropagation();
        sessionStorage.setItem("scrollPos", window.scrollY); 
        const { id, estado, cat, from } = this.dataset;
        const currentQuery = window.location.search.substring(1); 
        window.location.href = `cambiar_estado.php?id=${id}&estado=${estado}&cat=${encodeURIComponent(cat)}&from=${encodeURIComponent(from)}&query=${encodeURIComponent(currentQuery)}`;
    });
});

document.querySelectorAll(".btn-eliminar").forEach(btn => {
    btn.addEventListener("click", function (e) {
        e.stopPropagation();
        const { id, cat, from } = this.dataset;
        if(typeof Swal !== 'undefined'){
            Swal.fire({
                title: "¿Eliminar publicación?",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#d33",
                confirmButtonText: "Sí, eliminar"
            }).then(result => {
                if (result.isConfirmed) {
                    sessionStorage.setItem("scrollPos", window.scrollY);
                    const currentQuery = window.location.search.substring(1);
                    window.location.href = `eliminar_publicacion.php?id=${id}&cat=${encodeURIComponent(cat)}&from=${encodeURIComponent(from)}&query=${encodeURIComponent(currentQuery)}`;
                }
            });
        } else {
            if(confirm("¿Eliminar?")) {
                sessionStorage.setItem("scrollPos", window.scrollY);
                const currentQuery = window.location.search.substring(1);
                window.location.href = `eliminar_publicacion.php?id=${id}&cat=${encodeURIComponent(cat)}&from=${encodeURIComponent(from)}&query=${encodeURIComponent(currentQuery)}`;
            }
        }
    });
});

// 6. Favoritos
document.addEventListener("click", function(e) {
    const btn = e.target.closest(".btn-fav");
    if (!btn) return;
    e.preventDefault(); e.stopPropagation();
    const id = btn.dataset.id;
    const icon = btn.querySelector("i");
    const isActive = btn.classList.contains("active");
    btn.classList.toggle("active");
    icon.classList.replace(isActive ? "fa-solid" : "fa-regular", isActive ? "fa-regular" : "fa-solid");
    fetch("toggle_favorito.php", {
        method: "POST", headers: { "Content-Type": "application/x-www-form-urlencoded" }, body: `id=${id}`
    }).then(res => res.json()).then(data => { if(data.status === 'error') btn.classList.toggle("active"); }).catch(err => console.error(err));
});
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> <?php
// 1. Verificamos si hay mensaje en la URL (GET) que es lo que mostró tu log
if (isset($_GET['msg']) && !empty($_GET['msg'])) {
    $mensaje = htmlspecialchars($_GET['msg']); // Limpiamos el texto
    $tipo = isset($_GET['type']) ? htmlspecialchars($_GET['type']) : 'success'; // 'error' o 'success'

    // Ajustamos el icono para SweetAlert
    $icon = ($tipo == 'error') ? 'error' : 'success'; 

    echo "<script>
        Swal.fire({
            icon: '$icon',
            title: 'Atención',
            text: '$mensaje',
            confirmButtonColor: '#3085d6',
            confirmButtonText: 'Entendido'
        }).then((result) => {
            // Opcional: Limpiar la URL para que si recargas no salga el mensaje de nuevo
            if (result.isConfirmed || result.isDismissed) {
                window.history.replaceState(null, null, window.location.pathname);
            }
        });
    </script>";
}
?>
<style>
/* 🔴 CSS FINAL: GRID CON ELIMINAR CENTRADO 🔴 */

/* 1. Contenedor Grid */
.acciones-fijas {
    display: grid !important;
    grid-template-columns: 1fr 1fr !important; /* Dos columnas iguales */
    gap: 10px !important; 
    padding: 15px !important;
    width: 100% !important;
    box-sizing: border-box !important;
    background: #f8f9fa !important;
    border-top: 1px solid #ddd !important;
}

/* 2. Botones de arriba (Editar y Estado) */
.btn-item {
    width: 100% !important;
    height: 100% !important;
    display: flex !important;
    justify-content: center !important;
    align-items: center !important;
    text-align: center !important;
    padding: 10px 5px !important;
    font-size: 13px !important; 
    font-weight: 700 !important;
    border-radius: 8px !important;
    border: none !important;
    cursor: pointer !important;
    text-decoration: none !important;
    line-height: 1.2 !important;
    min-height: 40px !important;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1) !important; /* Sombra suave para que resalten */
}

/* 3. Botón Eliminar (CENTRADO y un poco más angosto) */
.btn-item.delete {
    grid-column: 1 / -1 !important; /* Ocupa toda la fila de abajo */
    
    /* TRUCO PARA CENTRARLO VISUALMENTE */
    width: 80% !important; /* No ocupa todo el ancho, solo el 80% */
    margin: 10px auto 0 auto !important; /* 'auto' a los lados lo centra perfectamente */
    justify-self: center !important; /* Alineación Grid al centro */
    
    background-color: #dc3545 !important;
    color: white !important;
    border-radius: 50px !important; /* Bordes más redondos para diferenciarlo */
}

/* Colores */
.btn-item.edit { background-color: #007bff !important; color: white !important; }
.btn-item.disponible, .btn-item.abierto { background-color: #28a745 !important; color: white !important; }
.btn-item.vendido, .btn-item.terminado, .btn-item.cerrado { background-color: #6c757d !important; color: white !important; }

</style>
<?php include __DIR__ . "/includes/footer.php"; ?>