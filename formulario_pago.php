<?php
// formulario_pago.php
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Formulario de Pago</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      padding: 40px;
      background-color: #f9f9f9;
      color: #333;
    }
    form {
      background-color: #fff;
      padding: 20px;
      max-width: 400px;
      margin: auto;
      border-radius: 10px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    input[type="text"],
    input[type="number"] {
      width: 100%;
      padding: 10px;
      margin: 10px 0;
      border: 1px solid #ccc;
      border-radius: 5px;
    }
    button {
      padding: 10px 15px;
      background-color: #6f6f6fff;
      border: none;
      color: white;
      font-size: 16px;
      border-radius: 5px;
      cursor: pointer;
    }
    button:hover {
      background-color: #353535ff;
    }
  </style>
</head>
<body>
  <h2 style="text-align:center;">Formulario de Pago de Prueba</h2>
  <form action="crear_pago.php" method="POST">
    <label for="buy_order">Orden de Compra:</label>
    <input type="text" name="buy_order" id="buy_order" placeholder="ORD123456" required>

    <label for="amount">Monto:</label>
    <input type="number" name="amount" id="amount" placeholder="1000" required>

    <button type="submit">Pagar con Webpay</button>
  </form>
</body>
</html>
