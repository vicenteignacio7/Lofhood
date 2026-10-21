<?php
session_start();

$productos = [
  "ramen_clasico" => ["nombre" => "ramen_clasico", "precio" => 4990],
  "ramen_picante" => ["nombre" => "ramen_picante", "precio" => 6990]
];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $productoId = $_POST["producto"] ?? "";
  if (isset($productos[$productoId])) {
    $_SESSION["carrito"][] = $productos[$productoId];
  }
}

header("Location: index.php");
exit;
