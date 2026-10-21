<?php
session_start();
require __DIR__ . "/includes/conexion.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    
    $id = $_SESSION['user_id'];
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);

    // Validaciones básicas
    if (empty($username) || empty($email)) {
        header("Location: perfil.php?msg=Nombre y correo son obligatorios&type=error");
        exit;
    }

    // 1. Manejo de la Imagen
    $imgSql = "";
    $params = [$username, $email, $phone];
    $types = "sss"; // 3 strings

    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === 0) {
        $ext = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
        $permitidos = ['jpg', 'jpeg', 'png', 'webp'];

        if (in_array($ext, $permitidos)) {
            // Nombre único para evitar caché
            $new_name = "perfil_" . $id . "_" . time() . "." . $ext;
            
            // --- AQUÍ ESTÁ LA MAGIA PARA QUE CARGUE ---
            // Aseguramos que la carpeta exista
            $carpeta_destino = __DIR__ . '/uploads/perfiles/';
            if (!is_dir($carpeta_destino)) {
                mkdir($carpeta_destino, 0777, true);
            }
            // ------------------------------------------

            if (move_uploaded_file($_FILES['imagen']['tmp_name'], $carpeta_destino . $new_name)) {
                $imgSql = ", imagen = ?";
                $params[] = $new_name; // Añadir foto a parametros
                $types .= "s";         // Añadir tipo string
            }
        }
    }

    // Añadir el ID al final para el WHERE
    $params[] = $id; 
    $types .= "i"; // El ID es entero

    // 2. Actualizar BD
    $sql = "UPDATE usuarios SET username = ?, email = ?, phone = ? $imgSql WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        header("Location: perfil.php?msg=Datos actualizados correctamente&type=success");
    } else {
        header("Location: perfil.php?msg=Error al guardar&type=error");
    }

} else {
    header("Location: index.php");
}
?>