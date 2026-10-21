<?php
// TIENDA/finalizarpedido.php

session_start();
if (empty($_SESSION['user_id'])) {
  $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
  header('Location: login.php');
  exit;
}

require __DIR__ . '/includes/conexion.php';

$carrito = $_SESSION['carrito'] ?? [];
if (!$carrito) {
  echo "<p>Carrito vacío.</p><p><a href='index.php'>Volver</a></p>";
  exit;
}

// Calculate total & insert pedido + detalle (same as guardar_pedido.php)
$total = array_reduce($carrito, fn($sum,$i)=>$sum+$i['precio']*$i['cantidad'],0);

// ... you can either duplicate guardar_pedido.php logic here, 
// or simply redirect into guardar_pedido.php (fetch + then show crear_pago)

header('Location: crear_pago.php');
exit;
