// assets/js/main.js
console.log('main.js cargado');

document.addEventListener('DOMContentLoaded', () => {
  let carrito = JSON.parse(localStorage.getItem('carrito') || '[]');
  const guardarCarrito = () => localStorage.setItem('carrito', JSON.stringify(carrito));

  const cartBtn      = document.querySelector('button.btn-icon.cart-btn');
  const cartModal    = document.getElementById('cart-modal');
  const closeCartBtn = cartModal.querySelector('.close-cart-btn');
  const cartList     = document.getElementById('cart-items');
  const cartTotalEl  = document.getElementById('cart-total');
  const cartCountEl  = document.getElementById('cart-count');
  const headerCount  = document.getElementById('cart-count-header');
  const clearBtn     = document.getElementById('clear-cart-btn');
  const checkoutBtn  = document.getElementById('checkout-btn');
  const envioRadios  = document.querySelectorAll('input[name="envio"]');
  const paketForm    = document.getElementById('paket-form');

  function renderizar() {
    cartList.innerHTML = '';
    let total = 0;
    let contador = 0;

    if (!carrito.length) {
      cartList.innerHTML = '<li class="empty">El carrito está vacío.</li>';
    } else {
      carrito.forEach((item, i) => {
        total += item.precio * item.cantidad;
        contador += item.cantidad;
        const li = document.createElement('li');
        li.className = 'cart-item';
        li.innerHTML = `
          <span>${item.nombre}</span>
          <div class="quantity-controls">
            <button class="btn-sub" data-index="${i}">−</button>
            <span class="qty">${item.cantidad}</span>
            <button class="btn-add" data-index="${i}">+</button>
          </div>
          <span>$${item.precio * item.cantidad}</span>
          <button class="remove" data-index="${i}">×</button>
        `;
        cartList.appendChild(li);
      });

      cartList.querySelectorAll('.remove').forEach(b => {
        b.onclick = () => {
          carrito.splice(+b.dataset.index, 1);
          guardarCarrito();
          renderizar();
        };
      });
      cartList.querySelectorAll('.btn-add').forEach(b => {
        b.onclick = () => {
          carrito[+b.dataset.index].cantidad++;
          guardarCarrito();
          renderizar();
        };
      });
      cartList.querySelectorAll('.btn-sub').forEach(b => {
        b.onclick = () => {
          const idx = +b.dataset.index;
          if (carrito[idx].cantidad > 1) carrito[idx].cantidad--;
          else carrito.splice(idx, 1);
          guardarCarrito();
          renderizar();
        };
      });
    }

    cartTotalEl.textContent = total.toString();
    cartCountEl.textContent = contador.toString();
    if (headerCount) headerCount.textContent = contador.toString();
  }

  cartBtn.onclick = () => { cartModal.style.display = 'flex'; renderizar(); };
  closeCartBtn.onclick = () => cartModal.style.display = 'none';
  cartModal.onclick = e => { if (e.target === cartModal) cartModal.style.display = 'none'; };

  envioRadios.forEach(radio => {
    radio.onchange = () => {
      if (paketForm) paketForm.style.display = radio.value === 'paket' ? 'block' : 'none';
    };
  });

  document.querySelectorAll('.add-to-cart').forEach(btn => {
    btn.onclick = () => {
      const textoOriginal = btn.innerText;
      const card   = btn.closest('.producto');
      const nombre = card.querySelector('h3').innerText.trim();
      const precio = parseInt(card.dataset.precio, 10);
      const ex     = carrito.find(i => i.nombre === nombre);
      if (ex) ex.cantidad++;
      else carrito.push({ nombre, precio, cantidad: 1 });
      guardarCarrito();
      renderizar();

      btn.innerText = "✔ Agregado";
      btn.disabled = true;
      btn.classList.add('agregado');
      setTimeout(() => {
        btn.innerText = textoOriginal;
        btn.disabled = false;
        btn.classList.remove('agregado');
      }, 2000);
    };
  });

  clearBtn.onclick = () => {
    carrito = [];
    guardarCarrito();
    renderizar();
  };

  let sending = false;
  checkoutBtn.onclick = async () => {
    if (sending) return;
    if (!carrito.length) return alert('El carrito está vacío');

    const sel = document.querySelector('input[name="envio"]:checked');
    if (!sel) return alert('Por favor, selecciona una opción de envío.');

    if (sel.value === 'paket') {
      const n = document.getElementById('paket-nombre').value.trim();
      const d = document.getElementById('paket-direccion').value.trim();
      const c = document.getElementById('paket-comuna').value.trim();
      const t = document.getElementById('paket-telefono').value.trim();
      if (!n || !d || !c || !t) {
        return alert('Completa todos los datos para envío por Paket.');
      }
    }

    sending = true;
    checkoutBtn.disabled = true;

    try {
      const resp = await fetch('guardar_pedido.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({
          items:     carrito,
          envio:     sel.value,
          nombre:    document.getElementById('paket-nombre')?.value.trim()    || '',
          direccion: document.getElementById('paket-direccion')?.value.trim()  || '',
          comuna:    document.getElementById('paket-comuna')?.value.trim()     || '',
          telefono:  document.getElementById('paket-telefono')?.value.trim()   || ''
        })
      });

      const text = await resp.text();
      let data;
      try { data = JSON.parse(text); } catch { throw new Error("Respuesta inválida:\n"+text); }
      if (!resp.ok || !data.success) throw new Error(data.error || 'Error al guardar pedido.');

      // Elección de método de pago
      Swal.fire({
        icon: 'success',
        title: `Pedido #${data.pedido_id} guardado`,
        html: `Total: $${data.total}<br><br><b>Elige cómo pagar:</b>`,
        showDenyButton: true,
        allowOutsideClick: false,
        confirmButtonText: '💳 Pagar con Webpay',
        denyButtonText: '🏦 Transferencia bancaria'
      }).then(async (res) => {
        if (res.isConfirmed) {
          // Webpay
          const form = document.createElement('form');
          form.method = 'POST';
          form.action = 'crear_pago.php';
          form.innerHTML = `
            <input type="hidden" name="buy_order" value="${data.pedido_id}">
            <input type="hidden" name="amount" value="${data.total.replace(/\./g,'')}">
          `;
          document.body.appendChild(form);
          form.submit();
        } else if (res.isDenied) {
          // Transferencia
          const datosHTML = `
            <div style="text-align:left">
              <p><b>Nombre:</b> Inversiones Rambra SpA</p>
              <p><b>RUT:</b> 77.902.378-8</p>
              <p><b>Banco:</b> Santander</p>
              <p><b>Tipo:</b> Cuenta corriente</p>
              <p><b>N° cuenta:</b> 94240000</p>
              <p><b>Correo comprobante:</b> Tokiramencl@gmail.com</p>
              <p class="muted">Incluye el <b>N° de pedido (${data.pedido_id}) y el número de teléfono</b> en el asunto.</p>
            </div>`;
          const r2 = await Swal.fire({
            icon: 'info',
            title: 'Datos de transferencia',
            html: datosHTML,
            showCancelButton: true,
            confirmButtonText: 'Confirmar pedido por transferencia',
            cancelButtonText: 'Cancelar',
            allowOutsideClick: false
          });
          if (r2.isConfirmed) {
            const up = await fetch('admin/actualizar_estado.php', {
              method: 'POST',
              credentials: 'include',
              headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
              body: new URLSearchParams({
                pedido_id: String(data.pedido_id),
                estado: 'pendiente-transferencia'
              })
            });

            const raw = await up.text();
            let ok = false;
            try { const j = JSON.parse(raw); ok = !!j.ok; } catch { ok = raw.trim().toUpperCase() === 'OK'; }

            if (!ok) {
              console.error('actualizar_estado resp:', up.status, raw);
              Swal.fire({icon:'error',title:'No se pudo marcar transferencia',text: raw || 'Intenta nuevamente.'});
              return;
            }

            Swal.fire({icon:'success',title:'Pedido registrado por transferencia',text:'Envíanos el comprobante.'});
          }
        }
      });

    } catch (err) {
      console.error(err);
      Swal.fire({ icon:'error', title:'Error', text: err.message || 'Ocurrió un problema.' });
    } finally {
      sending = false;
      checkoutBtn.disabled = false;
    }
  };

  renderizar();
});
