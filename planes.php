<?php
require __DIR__ . '/includes/init.php';
include __DIR__ . '/includes/header.php';
?>

<style>
    .planes-container {
        max-width: 1000px;
        margin: 40px auto;
        padding: 0 20px;
        text-align: center;
    }
    .planes-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 30px;
        margin-top: 40px;
    }
    .plan-card {
        background: #fff;
        border-radius: 15px;
        padding: 30px 20px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        border: 1px solid #eee;
        transition: 0.3s;
        position: relative;
        overflow: hidden;
    }
    .plan-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 40px rgba(0,0,0,0.1);
    }
    .plan-card.destacado {
        border: 2px solid #2cd19b;
        transform: scale(1.05);
        z-index: 2;
    }
    .etiqueta-popular {
        background: #2cd19b;
        color: white;
        position: absolute;
        top: 20px;
        right: -30px;
        transform: rotate(45deg);
        padding: 5px 40px;
        font-size: 12px;
        font-weight: bold;
    }
    .plan-titulo { font-size: 24px; margin-bottom: 10px; color: #333; }
    .plan-precio { font-size: 36px; font-weight: 800; color: #2cd19b; margin-bottom: 20px; }
    .plan-precio span { font-size: 16px; color: #999; font-weight: normal; }
    .plan-features { list-style: none; padding: 0; margin-bottom: 30px; text-align: left; }
    .plan-features li { margin-bottom: 12px; color: #555; display: flex; align-items: center; gap: 10px; }
    .check { color: #2cd19b; font-weight: bold; }
    .cross { color: #ccc; }
    
    .btn-plan {
        display: block;
        width: 100%;
        padding: 12px;
        border-radius: 50px;
        text-decoration: none;
        font-weight: bold;
        transition: 0.3s;
    }
    .btn-gratis { background: #eee; color: #333; }
    .btn-premium { background: #2cd19b; color: white; }
    .btn-premium:hover { background: #25b384; }
</style>

<div class="planes-container">
    <h2>Mejora tu experiencia en LofHood</h2>
    <p>Elige el plan perfecto para vender más y destacar en tu comunidad.</p>

    <div class="planes-grid">
        
        <div class="plan-card">
            <h3 class="plan-titulo">Vecino</h3>
            <div class="plan-precio">$0 <span>/ siempre</span></div>
            <ul class="plan-features">
                <li><span class="check">✔</span> 3 Publicaciones Activas</li>
                <li><span class="check">✔</span> Alertas de Seguridad Ilimitadas</li>
                <li><span class="check">✔</span> Comentar y Calificar</li>
                <li><span class="cross">✖</span> Ofertas de Trabajo</li>
                <li><span class="cross">✖</span> Publicaciones Destacadas</li>
            </ul>
            <a href="#" class="btn-plan btn-gratis">Tu plan actual</a>
        </div>

        <div class="plan-card destacado">
            <div class="etiqueta-popular">POPULAR</div>
            <h3 class="plan-titulo">Emprendedor</h3>
            <div class="plan-precio">$3.990 <span>/ mes</span></div>
            <ul class="plan-features">
                <li><span class="check">✔</span> <b>15</b> Publicaciones Activas</li>
                <li><span class="check">✔</span> Alertas de Seguridad Ilimitadas</li>
                <li><span class="check">✔</span> 2 Ofertas de Trabajo Activas</li>
                <li><span class="check">✔</span> <b>1 Publicación Destacada</b></li>
                <li><span class="check">✔</span> Insignia "Emprendedor"</li>
            </ul>
            <a href="https://wa.me/569XXXXXXXX?text=Hola, quiero activar el Plan Emprendedor en LofHood" target="_blank" class="btn-plan btn-premium">Activar Plan</a>
        </div>

        <div class="plan-card">
            <h3 class="plan-titulo">Pyme / Negocio</h3>
            <div class="plan-precio">$6.990 <span>/ mes</span></div>
            <ul class="plan-features">
                <li><span class="check">✔</span> <b>Publicaciones Ilimitadas</b></li>
                <li><span class="check">✔</span> Alertas de Seguridad Ilimitadas</li>
                <li><span class="check">✔</span> Ofertas de Trabajo Ilimitadas</li>
                <li><span class="check">✔</span> <b>5 Destacados Rotativos</b></li>
                <li><span class="check">✔</span> Link a tu Web en Perfil</li>
            </ul>
            <a href="https://wa.me/569XXXXXXXX?text=Hola, quiero activar el Plan Pyme en LofHood" target="_blank" class="btn-plan btn-premium">Activar Plan</a>
        </div>

    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
