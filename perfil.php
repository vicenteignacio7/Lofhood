<?php
session_start();
require __DIR__ . "/includes/conexion.php";

// 1. Verificar sesión
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$id = $_SESSION['user_id'];
$msg = $_GET['msg'] ?? '';
$type = $_GET['type'] ?? '';

// --- LÓGICA DEL BOTÓN VOLVER ---
$from = $_GET['from'] ?? 'index'; 
$volver = "index.php"; 

if (strpos($from, 'mis') !== false) $volver = "mis_publicaciones.php";
elseif (strpos($from, 'cat') !== false) { 
    $p = $_GET; unset($p['from'], $p['id']); 
    $volver = "ver_publicaciones.php?" . http_build_query($p); 
}
elseif ($from === 'dashboard') $volver = "dashboard.php";
elseif (strpos($from, 'ver_publicacion') !== false) $volver = "javascript:history.back()"; 
// -------------------------------

// 2. Obtener datos actuales
$stmt = $conn->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// 3. VALIDACIÓN DE FOTO CORREGIDA
$foto_db = $user['imagen'];
$ruta_relativa = 'uploads/perfiles/' . $foto_db; // Ruta para el navegador (src)
$ruta_absoluta = __DIR__ . '/' . $ruta_relativa; // Ruta para que PHP verifique el archivo

// Si no hay foto en BD, o el archivo físico no existe, usamos el default
if (empty($foto_db) || !file_exists($ruta_absoluta)) {
    $ruta_final = 'assets/imagenes/usuario_default.png';
} else {
    $ruta_final = $ruta_relativa;
}
?>

<?php include __DIR__ . "/includes/header.php"; ?>

<style>
    .btn-back {
        position: fixed; top: 20px; left: 20px; z-index: 9999;
        background: #fff; color: #333; padding: 10px 20px;
        border-radius: 50px; box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        text-decoration: none; font-weight: bold; border: 1px solid #eee;
    }
    .perfil-container {
        max-width: 500px; margin: 60px auto; padding: 30px;
        background: #fff; border-radius: 15px; box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    }
    .perfil-header { text-align: center; margin-bottom: 30px; }
    
    .avatar-wrapper {
        position: relative; width: 120px; height: 120px; margin: 0 auto 15px;
    }
    .avatar-preview {
        width: 100%; height: 100%; border-radius: 50%;
        object-fit: cover; border: 4px solid #f0f0f0;
        cursor: pointer; transition: 0.3s;
    }
    .avatar-preview:hover { opacity: 0.8; border-color: #2cd19b; }
    
    .camara-icon {
        position: absolute; bottom: 5px; right: 5px;
        background: #2cd19b; color: #fff; border-radius: 50%;
        width: 30px; height: 30px; display: flex;
        align-items: center; justify-content: center; font-size: 14px;
        pointer-events: none; /* Para que el click pase a la imagen */
    }

    .form-group { margin-bottom: 20px; }
    .form-label { display: block; margin-bottom: 8px; font-weight: 600; color: #555; }
    .form-input {
        width: 100%; padding: 12px; border: 1px solid #ddd;
        border-radius: 8px; font-size: 16px; box-sizing: border-box;
    }
    .btn-guardar {
        width: 100%; background: #2cd19b; color: white; padding: 14px;
        border: none; border-radius: 8px; font-size: 16px; font-weight: bold;
        cursor: pointer; transition: 0.3s;
    }
    .btn-guardar:hover { background: #25b384; }
    .file-input { display: none; }
</style>

<a href="<?= $volver ?>" class="btn-back">← Volver</a>

<div class="perfil-container">
    <div class="perfil-header">
        <h2>Mi Perfil</h2>
        <p>Mantén tus datos actualizados para tus vecinos</p>
    </div>

    <?php if($msg): ?>
        <div style="padding:15px; margin-bottom:20px; border-radius:8px; text-align:center; 
             background: <?= $type == 'error' ? '#f8d7da' : '#d4edda' ?>; 
             color: <?= $type == 'error' ? '#721c24' : '#155724' ?>;">
            <?= htmlspecialchars($msg) ?>
        </div>
    <?php endif; ?>

    <form action="actualizar_perfil_logic.php" method="POST" enctype="multipart/form-data">
        
        <div class="avatar-wrapper">
            <label for="fotoInput" style="cursor:pointer;">
                <img src="<?= $ruta_final ?>?t=<?= time() ?>" class="avatar-preview" id="avatarPreview">
                <div class="camara-icon">📷</div>
            </label>
            <input type="file" name="imagen" id="fotoInput" class="file-input" accept="image/*">
        </div>
        <p style="text-align:center; font-size:13px; color:#999; margin-top:-10px; margin-bottom:25px;">Toca la foto para cambiarla</p>

        <div class="form-group">
            <label class="form-label">Nombre de Usuario (Apodo)</label>
            <input type="text" name="username" class="form-input" value="<?= htmlspecialchars($user['username']) ?>" required>
        </div>

        <div class="form-group">
            <label class="form-label">Correo Electrónico</label>
            <input type="email" name="email" class="form-input" value="<?= htmlspecialchars($user['email']) ?>" required>
        </div>

        <div class="form-group">
            <label class="form-label">Teléfono / WhatsApp</label>
            <input type="text" name="phone" class="form-input" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="+56 9 ...">
        </div>

        <button type="submit" class="btn-guardar">Guardar Cambios</button>
    </form>
</div>

<script>
// Previsualizar al instante
document.getElementById('fotoInput').addEventListener('change', function(e) {
    if (e.target.files && e.target.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('avatarPreview').src = e.target.result;
        }
        reader.readAsDataURL(e.target.files[0]);
    }
});
</script>

<?php include __DIR__ . "/includes/footer.php"; ?>