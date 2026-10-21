<?php
// admin/includes/header.php
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id'])) { header('Location: ../login.php'); exit; }
$isAdmin = (($_SESSION['role'] ?? '') === 'admin');

$current = basename($_SERVER['PHP_SELF'] ?? '');
function active($file){ global $current; return $current === $file ? ' is-active' : ''; }
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Panel Admin - LofHood</title>
  <link rel="stylesheet" href="../assets/css/style.css"/>
  <link rel="stylesheet" href="admin.css"/>
  <style>
    :root{
      --brand:#5b7436;     /* verde principal */
      --brand-2:#47602a;   /* verde más oscuro */
      --pill-bg:#5f7a3a;
      --text:#fff;
      --muted:rgba(255,255,255,.75);
      --shadow:0 8px 24px rgba(0,0,0,.12);
      --radius:14px;
      --maxw:1200px;
    }

    /* Contenedor superior */
    .admin-header-wrap{
      position:sticky; top:0; z-index:1000;
      backdrop-filter: saturate(1.2) blur(4px);
      background: linear-gradient(180deg, rgba(255,255,255,.0), rgba(255,255,255,.0));
      padding: 10px 14px;
    }

    /* Barra píldora */
    .admin-header{
      margin: 0 auto;
      max-width: var(--maxw);
      background: var(--pill-bg);
      color:var(--text);
      display:flex; align-items:center; gap:14px;
      padding:10px 12px;
      border-radius: var(--radius);
      box-shadow: var(--shadow);
    }

    .admin-header .logo img{ height:32px; display:block; }

    .admin-nav{
      display:flex; align-items:center; gap:4px;
      margin-left:8px; flex:1;
    }
    .admin-link{
      color:var(--text); text-decoration:none; font-weight:700;
      padding:8px 10px; border-radius:10px; transition:.15s ease;
      display:inline-flex; align-items:center; gap:8px;
      white-space:nowrap;
    }
    .admin-link:hover{ background:rgba(255,255,255,.12); }
    .admin-link.is-active{ background:rgba(0,0,0,.22); }

    .admin-actions{ margin-left:auto; display:flex; align-items:center; gap:8px; }
    .logout-btn{
      background: rgba(0,0,0,.18);
      border:0; color:#fff; padding:8px 10px; border-radius:10px;
      cursor:pointer; display:inline-flex; align-items:center; gap:8px;
      font-weight:700;
    }
    .logout-btn:hover{ background: rgba(0,0,0,.28); }
    .icon-image{ width:18px; height:18px; }

    /* Hamburguesa (móvil) */
    .burger{
      display:none; margin-left:6px;
      background: rgba(0,0,0,.18); color:#fff; border:0;
      padding:8px 10px; border-radius:10px; font-weight:700; cursor:pointer;
    }

    /* Responsive */
    @media (max-width: 1024px){
      .admin-header{ padding:8px 10px; }
      .admin-link{ padding:8px 8px; }
    }
    @media (max-width: 920px){
      .burger{ display:inline-flex; }
      .admin-nav{
        position:absolute; left:14px; right:14px; top:100%;
        background:var(--pill-bg);
        border-radius: var(--radius);
        padding:10px; margin-top:8px;
        box-shadow: var(--shadow);
        display:none; flex-direction:column; align-items:flex-start; gap:6px;
      }
      .admin-nav.show{ display:flex; }
      .admin-actions{ margin-left:0; }
    }
  </style>
</head>
<body>

<div class="admin-header-wrap">
  <div class="admin-header">
    <a class="logo" href="index_admin.php">
      <img src="../assets/imagenes/logo_verde.jpg" alt="LofHood">
    </a>

    <button class="burger" id="burgerBtn" aria-expanded="false" aria-controls="adminNav">Menú</button>

    <nav class="admin-nav" id="adminNav">
      <a class="admin-link<?= active('index_admin.php') ?>" href="index_admin.php">Inicio</a>
      <a class="admin-link<?= active('agregar.php') ?>" href="agregar.php">+ Agregar Producto</a>
      <?php if ($isAdmin): ?>
        <a class="admin-link<?= active('pedidos_puente.php') ?>" href="pedidos_puente.php">Pedidos Puente</a>
        <a class="admin-link<?= active('pedidos_quilicura.php') ?>" href="pedidos_quilicura.php">Pedidos Quilicura</a>
        <a class="admin-link<?= active('export_pedidos.php') ?>" href="export_pedidos.php">Exportar pedidos</a>
      <?php endif; ?>
    </nav>

    <div class="admin-actions">
      <button class="logout-btn" onclick="location.href='../logout.php';">
        <img src="../assets/imagenes/poder.png" alt="" class="icon-image">
        <span>Salir</span>
      </button>
    </div>
  </div>
</div>

<script>
  // Toggle menú móvil
  const burger = document.getElementById('burgerBtn');
  const nav = document.getElementById('adminNav');
  burger?.addEventListener('click', () => {
    const open = nav.classList.toggle('show');
    burger.setAttribute('aria-expanded', open ? 'true' : 'false');
  });
</script>
