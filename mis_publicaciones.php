<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require __DIR__ . "/includes/conexion.php";

if (empty($_SESSION['user_id'])) {
    header("Location: login.php?redirect=mis_publicaciones.php&msg=Debes iniciar sesión&type=error");
    exit;
}

$usuario_id = $_SESSION['user_id'];

$categorias = [
    'actividad' => 'Actividades',
    'venta'     => 'Ventas',
    'trabajo'   => 'Trabajo',
    'servicio'  => 'Servicios',
    'alerta'    => 'Alertas Vecinales'
];

// Obtener filtros marcados
$filtroCats = $_GET['cat'] ?? array_keys($categorias);
if (!is_array($filtroCats)) $filtroCats = [$filtroCats];

include __DIR__ . "/includes/header.php";
?>

<section class="publicaciones-lista" style="margin-top:20px;">

<?php
// Si ya tienes $_SESSION['role'], úsalo directamente
$role = $_SESSION['role'] ?? null;

// Si no está en sesión, consulta la BD (opcional)
if (!$role) {
    $stmtRole = $conn->prepare("SELECT role FROM usuarios WHERE id = ?");
    $stmtRole->bind_param("i", $usuario_id);
    $stmtRole->execute();
    $role = $stmtRole->get_result()->fetch_assoc()['role'];
}

// Define el título
$tituloPagina = ($role === 'admin') ? "Todas las publicaciones" : "Mis publicaciones";
?>
<h2 class="categories-title"><?= htmlspecialchars($tituloPagina) ?></h2>

    <p class="categories-subtitle">Filtra las publicaciones según categoría</p>

    <form method="GET" class="filter-box">
        <?php foreach ($categorias as $key => $label): ?>
            <label class="filter-pill">
                <input type="checkbox" name="cat[]" value="<?= $key ?>" <?= in_array($key, $filtroCats) ? 'checked' : '' ?>> <?= $label ?>
            </label>
        <?php endforeach; ?>

        <button type="submit" class="btn-filtrar">Filtrar</button>
        <a href="mis_publicaciones.php" class="btn-reset">Mostrar Todo</a>
    </form>

<?php
foreach ($categorias as $catKey => $catName):
    if (!in_array($catKey, $filtroCats)) continue;

    if ($_SESSION['role'] === 'admin') {
    // ADMIN VE TODAS LAS PUBLICACIONES DE TODA LA GENTE
    $sql = "SELECT p.*, u.username
            FROM publicaciones p
            INNER JOIN usuarios u ON p.usuario_id = u.id
            WHERE p.categoria = ?
            ORDER BY p.fecha DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $catKey);

} else {
    // USUARIO NORMAL VE SOLO SUS PUBLICACIONES
    $sql = "SELECT p.*, u.username
            FROM publicaciones p
            INNER JOIN usuarios u ON p.usuario_id = u.id
            WHERE p.usuario_id = ? AND p.categoria = ?
            ORDER BY p.fecha DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $usuario_id, $catKey);
}

    $stmt->execute();
    $result = $stmt->get_result();
?>
    <div class="separador-categoria"></div>
    <h3 class="mis-cat-title"><?= $catName ?></h3>

    <div class="grid-publicaciones">
    <?php if ($result->num_rows > 0): ?>
        <?php while($row = $result->fetch_assoc()): ?>

            <div class="pub-card">

                <a class="pub-link"
                    href="ver_publicacion.php?id=<?= $row['id'] ?>&cat=<?= $row['categoria'] ?>&from=mis&<?= http_build_query($_GET) ?>">

                 <div class="pub-img-wrapper">
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
                // 🛑 LÓGICA DE PERMISOS: Si es dueño O Admin, se muestran las acciones
                $puede_ejecutar_acciones = ($row['usuario_id'] == $usuario_id) || ($role === 'admin');
                ?>

                <?php if ($puede_ejecutar_acciones): ?>

               <div class="pub-actions">
                    <a href="editar_publicacion.php?id=<?= $row['id'] ?>&cat=<?= urlencode($row['categoria']) ?>&from=mis&<?= http_build_query($_GET) ?>" 
                        class="btn-accion edit">
                        ✏ Editar
                    </a>

                    <?php if($row['categoria'] === 'actividad'): ?>
                        <?php if($row['estado'] == 'disponible'): ?>
                            <button class="btn-accion btn-toggle-estado terminado"
                                data-id="<?= $row['id'] ?>" data-cat="actividad" data-from="mis" data-query="<?= http_build_query($_GET) ?>"
                                data-estado="terminado">
                                Marcar Terminado
                            </button>
                        <?php else: ?>
                            <button class="btn-accion btn-toggle-estado disponible"
                                data-id="<?= $row['id'] ?>" data-cat="actividad" data-from="mis" data-query="<?= http_build_query($_GET) ?>"
                                data-estado="disponible">
                                Actividad Disponible
                            </button>
                        <?php endif; ?>

                    <?php elseif($row['categoria'] === 'trabajo'): ?>
                        <?php 
                            // Aseguramos un valor por defecto si es null
                            $estado_trabajo = $row['estado_trabajo'] ?? 'abierto'; 
                        ?>
                        <?php if($estado_trabajo == 'abierto'): ?>
                            <button class="btn-accion btn-toggle-estado vendido"
                                data-id="<?= $row['id'] ?>" data-cat="trabajo" data-from="mis" data-query="<?= http_build_query($_GET) ?>"
                                data-estado="cerrado">
                                Cerrar Postulaciones
                            </button>
                        <?php else: ?>
                            <button class="btn-accion btn-toggle-estado disponible"
                                data-id="<?= $row['id'] ?>" data-cat="trabajo" data-from="mis" data-query="<?= http_build_query($_GET) ?>"
                                data-estado="abierto">
                                Abrir Postulaciones
                            </button>
                        <?php endif; ?>

                    <?php else: ?>
                        <?php if($row['estado'] == 'disponible'): ?>
                            <button class="btn-accion btn-toggle-estado vendido"
                                data-id="<?= $row['id'] ?>" data-cat="<?= $row['categoria'] ?>" data-from="mis" data-query="<?= http_build_query($_GET) ?>"
                                data-estado="vendido">
                                Marcar Vendido
                            </button>
                        <?php else: ?>
                            <button class="btn-accion btn-toggle-estado disponible"
                                data-id="<?= $row['id'] ?>" data-cat="<?= $row['categoria'] ?>" data-from="mis" data-query="<?= http_build_query($_GET) ?>"
                                data-estado="disponible">
                                Disponible otra vez
                            </button>
                        <?php endif; ?>

                    <?php endif; ?>

                    <button class="btn-accion delete btn-eliminar"
                        data-id="<?= $row['id'] ?>" data-cat="<?= $row['categoria'] ?>" data-from="mis" data-query="<?= http_build_query($_GET) ?>">
                        🗑 Eliminar
                    </button>

                </div>
                <?php endif; // Cierre de $puede_ejecutar_acciones ?>
                
            </div>

        <?php endwhile; ?>
    <?php else: ?>
        <p class="no-pubs">No tienes publicaciones en esta categoría.</p>
    <?php endif; ?>
    </div>

<?php endforeach; ?>

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
// 1. Control manual del scroll
if (history.scrollRestoration) { history.scrollRestoration = 'manual'; }

document.addEventListener("DOMContentLoaded", function() {
    
    // 2. Restaurar posición
    const scrollPos = sessionStorage.getItem("scrollPos");
    if (scrollPos) {
        window.scrollTo(0, parseInt(scrollPos));
        sessionStorage.removeItem("scrollPos");
    }

});

// 4. Cerrar Alerta y LIMPIAR URL AHORA
function cerrarAlerta() {
    const modal = document.getElementById('modalAlerta');
    modal.classList.remove('mostrar');
    setTimeout(() => { modal.style.display = 'none'; }, 300);

    // Limpiamos la URL solo al cerrar para evitar problemas de caché
    const url = new URL(window.location);
    url.searchParams.delete('msg');
    url.searchParams.delete('type');
    window.history.replaceState({}, '', url);
}

document.getElementById('modalAlerta').addEventListener('click', function(e) {
    if (e.target === this) cerrarAlerta();
});

// 5. Botones (Guardar Scroll)
document.querySelectorAll('.btn-toggle-estado').forEach(btn => {
    btn.addEventListener('click', function (e) {
        e.stopPropagation();
        sessionStorage.setItem("scrollPos", window.scrollY); 
        const { id, estado, cat, from, query } = this.dataset;
        window.location.href = `cambiar_estado.php?id=${id}&estado=${estado}&cat=${encodeURIComponent(cat)}&from=${encodeURIComponent(from)}&query=${encodeURIComponent(query)}`;
    });
});

document.querySelectorAll(".btn-eliminar").forEach(btn => {
    btn.addEventListener("click", function (e) {
        e.stopPropagation();
        const { id, cat, from, query } = this.dataset;
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
                    window.location.href = `eliminar_publicacion.php?id=${id}&cat=${encodeURIComponent(cat)}&from=${encodeURIComponent(from)}&query=${encodeURIComponent(query)}`;
                }
            });
        } else {
            if(confirm("¿Eliminar?")) {
                sessionStorage.setItem("scrollPos", window.scrollY);
                window.location.href = `eliminar_publicacion.php?id=${id}&cat=${encodeURIComponent(cat)}&from=${encodeURIComponent(from)}&query=${encodeURIComponent(query)}`;
            }
        }
    });
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
<?php include __DIR__ . "/includes/footer.php"; ?>