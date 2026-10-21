<?php // includes/footer.php ?>

<footer>
  <p>&copy; 2025 LofHood. Todos los derechos reservados.</p>
  
</footer>

<script src="assets/js/main.js" defer></script>
<script>
// Ocultar alertas automáticamente
setTimeout(() => {
  let alert = document.querySelector('.alert-box');
  if(alert){
    alert.style.transition = "opacity 0.6s";
    alert.style.opacity = "0";
    setTimeout(()=> alert.remove(), 600);
  }
}, 3000);
</script>

<?php if (!empty($_GET['require_login'])): ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    Swal.fire({
        title: '¡Necesitas una cuenta!',
        text: 'Para crear una publicación debes iniciar sesión o registrarte.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#2cd19b', // Tu color verde corporativo
        cancelButtonColor: '#d33',
        confirmButtonText: 'Iniciar Sesión',
        cancelButtonText: 'Seguir mirando',
        backdrop: `rgba(0,0,0,0.6)` // Fondo oscuro para resaltar
    }).then((result) => {
        if (result.isConfirmed) {
            // Redirigir al login enviando a dónde quería ir el usuario
            window.location.href = 'login.php?redirect=' + encodeURIComponent('<?= $_GET['require_login'] ?>');
        } else {
            // Si cancela, limpiamos la URL para que no salga la alerta de nuevo al recargar
            const url = new URL(window.location);
            url.searchParams.delete('require_login');
            window.history.replaceState({}, '', url);
        }
    });
});
</script>
<?php endif; ?>
</html>