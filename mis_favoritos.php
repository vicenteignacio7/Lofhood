<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require __DIR__ . "/includes/conexion.php";

if (empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$usuario_id = $_SESSION['user_id'];

// Obtener SOLO los favoritos
$sql = "SELECT p.*, u.username 
        FROM favoritos f
        INNER JOIN publicaciones p ON f.publicacion_id = p.id
        INNER JOIN usuarios u ON p.usuario_id = u.id
        WHERE f.usuario_id = ?
        ORDER BY f.fecha DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();

include __DIR__ . "/includes/header.php";
?>

<section class="publicaciones-lista">
    <h2 class="categories-title" style="margin-top:60px; text-align:center;">❤️ Mis Favoritos</h2>
    <p class="categories-subtitle" style="text-align:center;">Lo que más te ha gustado</p>

    <div class="grid-publicaciones" id="grid-favs">
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="pub-card" id="card-<?= $row['id'] ?>">
                    <a class="pub-link" href="ver_publicacion.php?id=<?= $row['id'] ?>&cat=<?= $row['categoria'] ?>&from=fav">
                        <div class="pub-img-wrapper" style="position:relative;">
    <?php if (!empty($row['imagen'])): ?>
        <img src="uploads/<?= htmlspecialchars($row['imagen']) ?>" class="pub-img">
    <?php else: ?>
        <div class="no-image-box">
            <span class="no-image-icon">✖</span>
            <span class="no-image-text">Sin Foto</span>
        </div>
    <?php endif; ?>

    <button class="btn-fav active" data-id="<?= $row['id'] ?>">
        <i class="fa-solid fa-heart"></i>
    </button>
</div>
                        
                        <?php if ($row['categoria'] === 'actividad' && $row['estado'] === 'terminado'): ?>
                            <span class="badge-terminado">TERMINADO</span>
                        <?php elseif ($row['categoria'] === 'trabajo' && ($row['estado_trabajo'] ?? '') === 'cerrado'): ?>
                             <span class="badge-vendido">CERRADO</span>
                        <?php elseif ($row['categoria'] !== 'actividad' && $row['categoria'] !== 'trabajo' && $row['estado'] === 'vendido'): ?>
                             <span class="badge-vendido">VENDIDO</span>
                        <?php endif; ?>

                        <div class="pub-info">
            <h3 class="pub-titulo"><?= htmlspecialchars($row['titulo']) ?></h3>

            <?php if (!empty($row['precio'])): ?>
                <p style="color:#2cd19b; font-weight:bold; margin: 3px 0;">
                    $<?= number_format($row['precio'], 0, ',', '.') ?>
                </p>
            <?php elseif (!empty($row['salario'])): ?>
                <p style="color:#2b6cb0; font-weight:bold; font-size:13px; margin: 3px 0;">
                    💰 <?= htmlspecialchars($row['salario']) ?>
                </p>
            <?php endif; ?>
            <span class="pub-user">Por <?= htmlspecialchars($row['username']) ?></span>
        </div>
                    </a>
                </div>

            <?php endwhile; ?>
        <?php else: ?>
            <p class="no-pubs" id="no-favs-msg" style="text-align:center; width:100%;">Aún no tienes favoritos guardados.</p>
        <?php endif; ?>
    </div>
    
    <p class="no-pubs" id="empty-msg" style="text-align:center; width:100%; display:none;">Ya no tienes favoritos guardados.</p>

</section>

<script>
document.addEventListener("click", function(e) {
    // Detectar clic en el botón de corazón
    const btn = e.target.closest(".btn-fav");
    if (!btn) return;

    e.preventDefault(); // No ir al enlace de la tarjeta
    e.stopPropagation();

    const id = btn.dataset.id;
    const card = document.getElementById(`card-${id}`);

    // Llamada AJAX a toggle_favorito.php
    fetch("toggle_favorito.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `id=${id}`
    })
    .then(res => res.json())
    .then(data => {
        if(data.status === 'removed') {
            // Animación de eliminación
            if(card) {
                card.style.transition = "all 0.3s ease";
                card.style.opacity = "0";
                card.style.transform = "scale(0.9)";
                
                setTimeout(() => {
                    card.remove();
                    // Verificar si nos quedamos sin favoritos para mostrar mensaje
                    const remaining = document.querySelectorAll(".pub-card");
                    if(remaining.length === 0) {
                        const msg = document.getElementById("empty-msg");
                        if(msg) msg.style.display = "block";
                    }
                }, 300);
            }
        } else if (data.status === 'error') {
            alert(data.msg || "Error al actualizar favorito");
        }
    })
    .catch(err => console.error(err));
});
</script>

<?php include __DIR__ . "/includes/footer.php"; ?>