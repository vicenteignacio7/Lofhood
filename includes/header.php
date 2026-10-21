<?php
if (session_status() === PHP_SESSION_NONE) session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>LofHood</title>

  <link rel="icon" type="image/png" href="assets/imagenes/favicon.png">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">


</head>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
  console.log("PATH:", window.location.pathname);

// ============================================================
// 🚀 SISTEMA GLOBAL DE SCROLL — SOLUCIÓN DEFINITIVA REAL
// ============================================================
document.addEventListener("DOMContentLoaded", () => {

    const path = window.location.pathname;

    // ================================================
    // 1) ver_publicacion.php → CONTROL TOTAL
    // ================================================
    if (path.includes("ver_publicacion.php")) {

        // Si venimos desde VOLVER → no subir arriba
        const coming = sessionStorage.getItem("comingFromDetail");

        if (coming === "1") {
            const saved = sessionStorage.getItem("listScrollPos");
            if (saved) window.scrollTo(0, Number(saved));
        } else {
            // Entrada desde listado o desde otra parte
            window.scrollTo(0, 0);
        }

        // limpiar flag
        sessionStorage.removeItem("comingFromDetail");
        return;
    }



    // ================================================
    // 2) index.php → SIEMPRE ARRIBA
    // ================================================
   if (
    path.endsWith("/index.php") ||  // index real
    path === "/" ||                 // raíz
    path === "/lofhood/index.php"   // en caso de localhost carpeta
) {
    sessionStorage.removeItem("listScrollPos");
    sessionStorage.removeItem("comingFromDetail");
    window.scrollTo(0, 0);
    return;
}




    // ================================================
    // 3) Restaurar scroll en PUBLICACIONES y MIS PUBLICACIONES
    // ================================================
    const isListado =
        path.includes("publicaciones.php") ||
        path.includes("mis_publicaciones.php") ||
        path.includes("ver_publicaciones.php");

    if (isListado) {
        const saved = sessionStorage.getItem("listScrollPos");
        if (saved) window.scrollTo(0, Number(saved));
    }



    // ================================================
    // 4) CLICK GLOBAL
    // ================================================
    document.addEventListener("click", function (e) {

        const el = e.target.closest("a, button");
        if (!el) return;


        // 🔥 ACCIONES QUE NUNCA GUARDAN SCROLL
        const block = [
            "btn-comentar",
            "btn-responder",
            "btn-editar-coment",
            "btn-eliminar-coment",
            "btn-unirse",
            "btn-cancelar",
            "btn-reportar",
            "btn-enviar-reporte",
            "btn-toggle-estado"
        ];

        if (block.some(c => el.classList.contains(c))) {
            return; // NO guardar scroll
        }


        // 🔥 Header → limpiar todo
        if (el.closest("header")) {
            sessionStorage.removeItem("listScrollPos");
            sessionStorage.removeItem("comingFromDetail");
            return;
        }


        // 👉 ENTRAR A ver_publicacion.php → guardar scroll del listado
        if (el.tagName === "A" && el.href && el.href.includes("ver_publicacion.php")) {
            sessionStorage.setItem("listScrollPos", window.scrollY);
            return;
        }


        // 👉 BOTONES VOLVER
        const volver = ["btn-back", "btn-volver", "form-back", "volver"];
        if (volver.some(c => el.classList.contains(c))) {
            sessionStorage.setItem("comingFromDetail", "1");
            return;
        }


        // 👉 En cualquier otra navegación externa → limpiar
        sessionStorage.removeItem("comingFromDetail");
    });

});
</script>
<body>

<header class="header">

  <div class="header-left">
    <a class="logo" href="index.php">
      <img src="assets/imagenes/logo_verde.png" alt="LofHood logo">
    </a>
  </div>

  <button class="menu-toggle" aria-label="Abrir menú" type="button">
    <span></span><span></span><span></span>
  </button>

<nav class="nav-links" aria-label="Navegación principal">
    <a href="index.php">Inicio</a>
    <a href="publicaciones.php">Publicaciones</a>
<a href="ver_publicaciones.php?cat=alerta">Alertas</a>
    <?php if (!empty($_SESSION['user_id'])): ?>
      
      <?php if(($_SESSION['role'] ?? '') === 'admin'): ?>
        <a href="mis_publicaciones.php">Todas las publicaciones</a>
      <?php else: ?>
        <a href="mis_publicaciones.php">Mis publicaciones</a>
      <?php endif; ?>
      
    <?php endif; ?>

    <a href="mis_notificaciones.php">Notificaciones</a>
    <a href="mis_favoritos.php">Favoritos</a>
    <a href="terminos.php">Terminos y condiciones</a>
    <?php if(($_SESSION['role'] ?? '') === 'admin'): ?>
        <a href="dashboard.php" style="color:#ffeb3b;">Dashboard</a>
  <?php endif; ?>
  </nav>

  <div class="header-right">
    <?php if (empty($_SESSION['user_id'] ?? null)): ?>
      <a class="btn-header bordered" href="login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>">
        <img src="assets/imagenes/acceso.png" class="icon-header" alt=""> Login
      </a>
      <a class="btn-header bordered" href="register.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>">
        <img src="assets/imagenes/registro.png" class="icon-header" alt=""> Registrarse
      </a>
    <?php else: ?>
      <span class="welcome">¡Hola, <?= htmlspecialchars($_SESSION['username']) ?>!</span>
      <?php 
// Detectamos el nombre del archivo actual para saber de dónde venimos (ej: 'index', 'mis_publicaciones')
$pagina_actual = basename($_SERVER['PHP_SELF'], '.php'); 

// Si hay parámetros en la URL (como ?cat=actividad), los guardamos también para no perder filtros
$query_actual = $_SERVER['QUERY_STRING'] ? '&' . $_SERVER['QUERY_STRING'] : '';
?>

<a href="perfil.php?from=<?= $pagina_actual ?><?= $query_actual ?>" 
   class="btn-perfil" 
   onclick="sessionStorage.setItem('scrollPos', window.scrollY)">
   👤 Mi Perfil
</a>
      <a class="btn-header bordered" href="logout.php">
        <img src="assets/imagenes/poder.png" class="icon-header" alt=""> Salir
      </a>
      
    <?php endif; ?>
  </div>

</header>

<!-- ✅ ALERTA BONITA -->


<!-- ✅ Menú móvil -->
<script>
document.addEventListener("DOMContentLoaded", () => {
  const toggle = document.querySelector(".menu-toggle");
  const nav = document.querySelector(".nav-links");

  toggle.addEventListener("click", () => {
    nav.classList.toggle("show");
    toggle.classList.toggle("active");
  });

  document.querySelectorAll(".nav-links a").forEach(link => {
    link.addEventListener("click", () => {
      nav.classList.remove("show");
      toggle.classList.remove("active");
    });
  });
});
</script>
