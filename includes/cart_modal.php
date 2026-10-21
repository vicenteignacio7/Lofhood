<?php 
if (session_status() === PHP_SESSION_NONE) session_start();
$isGuest = empty($_SESSION['user_id'] ?? null);
?>
<style>
  #cart-modal{position:fixed;inset:0;display:none;align-items:flex-start;justify-content:flex-end;background:rgba(0,0,0,.55);z-index:9998}
  #cart-modal .panel{width:420px;max-width:94vw;height:100%;background:#fff;box-shadow:-12px 0 24px rgba(0,0,0,.15);display:flex;flex-direction:column}
  #cart-modal header{display:flex;align-items:center;justify-content:space-between;padding:14px 16px;border-bottom:1px solid #eee}
  #cart-modal .body{padding:14px 16px;overflow:auto;flex:1}
  #cart-modal .footer{padding:14px 16px;border-top:1px solid #eee;display:flex;gap:.5rem}
  .cart-list{list-style:none;margin:0;padding:0}
  .cart-list li{display:flex;justify-content:space-between;gap:10px;padding:8px 0;border-bottom:1px dashed #eee}
  .row{display:flex;justify-content:space-between;margin-top:10px}
  .muted{color:#666}
  .btn{border:0;border-radius:10px;padding:.65rem .95rem;font-weight:700;cursor:pointer}
  .btn-primary{background:#5b7436;color:#fff}
  .btn-ghost{background:#eef2e7}
  .field{width:100%;padding:10px;border:1px solid #ccc;border-radius:8px}
  .box{margin-top:14px;padding:12px;border:1px solid #e5e5e5;border-radius:10px;background:#fafafa}
  .hide{display:none}
</style>

<div id="cart-modal" aria-hidden="true">
  <div class="panel" role="dialog" aria-labelledby="cart-title">
    <header>
      <h2 id="cart-title" style="margin:0;font-size:18px">Tu carrito (<span id="cart-count">0</span>)</h2>
      <button type="button" class="btn btn-ghost close-cart-btn">×</button>
    </header>

    <div class="body">
      <ul id="cart-items" class="cart-list">
        <li class="empty">El carrito está vacío.</li>
      </ul>

      <!-- Subtotal que pinta tu JS -->
      <div class="row"><span class="muted">Total:</span><span>$<span id="cart-total">0</span></span></div>
      <!-- Envío y Gran total (solo UI) -->
      <div class="row"><span class="muted">Envío:</span><span>$<span id="cart-shipping">0</span></span></div>
      <div class="row" style="font-weight:700"><span>Total con envío:</span><span>$<span id="cart-grand">0</span></span></div>

      <div class="box">
        <h4 style="margin:0 0 .5rem">Opciones de entrega o envío:</h4>

        <label style="display:flex;gap:8px;margin:.25rem 0;">
          <input type="radio" name="envio" value="retiro-quilicura"> 📍 Retiro en tienda - Quilicura
        </label>

        <label style="display:flex;gap:8px;margin:.25rem 0;">
          <input type="radio" name="envio" value="domicilio-quilicura"> 🚚 Envío a domicilio - Quilicura
        </label>

        <!-- Sector para Quilicura domicilio -->
        <div id="quilicura-sector-box" class="hide" style="margin:.6rem 0 0">
          <label class="muted" style="display:block;margin:.25rem 0;">Sector de Quilicura</label>
          <select id="qlc-sector" class="field">
            <option value="Quilicura" data-tarifa="2500">Quilicura — $2.500</option>
            <option value="Valle Grande" data-tarifa="4000">Valle Grande — $4.000</option>
            <option value="Valle Lo Campino" data-tarifa="3000">Valle Lo Campino — $3.000</option>
          </select>
        </div>

        <label style="display:flex;gap:8px;margin:.25rem 0;">
          <input type="radio" name="envio" value="retiro-puente"> 📍 Retiro en tienda - Puente Alto
        </label>

        <label style="display:flex;gap:8px;margin:.25rem 0;">
          <input type="radio" name="envio" value="paket"> 🚚 Envío a todo santiago Paket $3.000
        </label>
      </div>

      <!-- Datos del comprador (solo invitados) -->
      <div id="guest-box" class="box" style="<?php echo $isGuest ? '' : 'display:none'; ?>">
        <h4 style="margin:0 0 .5rem">Datos del comprador</h4>
        <input id="paket-nombre" class="field" type="text" placeholder="Nombre completo" autocomplete="name">
        <input id="paket-telefono" class="field" type="tel" placeholder="Teléfono de contacto" style="margin-top:.5rem" autocomplete="tel">
        <p class="muted" style="margin:.5rem 0 0;font-size:.9rem">Obligatorio si compras sin iniciar sesión.</p>
      </div>

      <!-- Datos para el envío: usa SIEMPRE los mismos IDs que lee tu main.js -->
      <div id="envio-box" class="box hide">
        <h4 style="margin:0 0 .5rem">Datos para el envío</h4>
        <input id="paket-direccion" class="field" type="text" placeholder="Dirección completa">
        <!-- "comuna" será: texto libre para Paket, o el sector elegido para Quilicura -->
        <input id="paket-comuna" class="field" type="text" placeholder="Comuna / Sector" style="margin-top:.5rem">
      </div>
    </div>

    <div class="footer">
      <button id="checkout-btn" class="btn btn-primary" type="button" style="flex:1">Finalizar Pedido</button>
      <button id="clear-cart-btn" class="btn btn-ghost" type="button" style="flex:1">Vaciar Carrito</button>
    </div>
  </div>
</div>

<script>
(function(){
  // Solo UI adicional. Tu main.js permanece igual.
  const radios   = document.querySelectorAll('input[name="envio"]');
  const sectorBx = document.getElementById('quilicura-sector-box');
  const sector   = document.getElementById('qlc-sector');

  const envioBx  = document.getElementById('envio-box');        // contiene #paket-direccion y #paket-comuna
  const comunaEl = document.getElementById('paket-comuna');

  const shipEl   = document.getElementById('cart-shipping');
  const grandEl  = document.getElementById('cart-grand');
  const subEl    = document.getElementById('cart-total');

  const TARIFA_PAKET = 3000;

  function subtotalNum(){
    return parseInt(String(subEl.textContent||'0').replace(/[^\d]/g,''),10) || 0;
  }
  function tarifaActual(){
    const v = document.querySelector('input[name="envio"]:checked')?.value || '';
    if (v === 'domicilio-quilicura') {
      const t = parseInt(sector.options[sector.selectedIndex].dataset.tarifa,10) || 0;
      return t;
    }
    if (v === 'paket') return TARIFA_PAKET;
    return 0;
  }
  function recalc(){
    const ship = tarifaActual();
    shipEl.textContent  = ship.toLocaleString('es-CL');
    grandEl.textContent = (subtotalNum() + ship).toLocaleString('es-CL');
  }

  function applyVisibility(){
    const v = document.querySelector('input[name="envio"]:checked')?.value || '';
    if (v === 'domicilio-quilicura') {
      sectorBx.classList.remove('hide');
      envioBx.classList.remove('hide');
      // comuna = sector elegido (texto no editable)
      comunaEl.value = sector.value;
      comunaEl.readOnly = true;
    } else if (v === 'paket') {
      sectorBx.classList.add('hide');
      envioBx.classList.remove('hide');
      // comuna libre para Paket
      comunaEl.value = '';
      comunaEl.readOnly = false;
    } else { // retiros
      sectorBx.classList.add('hide');
      envioBx.classList.add('hide');
      // limpiar para evitar confusiones
      // comunaEl.value = '';
    }
    recalc();
  }

  radios.forEach(r=> r.addEventListener('change', applyVisibility));
  sector?.addEventListener('change', ()=>{
    comunaEl.value = sector.value;
    recalc();
  });

  // Recalcular cuando tu JS cambie el subtotal
  const obs = new MutationObserver(recalc);
  obs.observe(subEl, {childList:true, subtree:true, characterData:true});

  // Primer estado
  applyVisibility();
})();
</script>
