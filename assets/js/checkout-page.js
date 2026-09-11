// assets/js/checkout-page.js — PinturaPittsburgh_LaPazBCS
// Checkout: renderiza el carrito (assets/js/cart.js), revalida cobertura
// postal en vivo (api/validar_cp.php) y envía el pedido a
// api/pedido_crear.php (Contrato 5). La validación real y definitiva ocurre
// SIEMPRE en el servidor — esta pantalla solo da feedback inmediato.
(function (global, document) {
    'use strict';

    function formatMoney(value) {
        return '$' + Number(value).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function renderCarrito() {
        var items = global.PPCart.getAll();
        var lista = document.getElementById('checkout-items');
        var totalEl = document.getElementById('checkout-total');
        var submitBtn = document.getElementById('checkout-submit-btn');
        var vacioMsg = document.getElementById('checkout-vacio');

        lista.innerHTML = '';

        if (items.length === 0) {
            vacioMsg.hidden = false;
            totalEl.textContent = formatMoney(0);
            submitBtn.disabled = true;
            return;
        }

        vacioMsg.hidden = true;
        submitBtn.disabled = false;

        items.forEach(function (item, index) {
            var li = document.createElement('li');
            li.className = 'checkout-item';

            var texto = document.createElement('span');
            texto.textContent = item.cantidad + ' × ' + item.nombre + ' (' + item.volumen + ') — ' + formatMoney(item.precio * item.cantidad);
            li.appendChild(texto);

            var quitarBtn = document.createElement('button');
            quitarBtn.type = 'button';
            quitarBtn.className = 'checkout-item__quitar';
            quitarBtn.textContent = 'Quitar';
            quitarBtn.addEventListener('click', function () {
                global.PPCart.remove(index);
                renderCarrito();
            });
            li.appendChild(quitarBtn);

            lista.appendChild(li);
        });

        totalEl.textContent = formatMoney(global.PPCart.total(items));
    }

    var cpCubierto = null; // null = sin validar, true/false = resultado del servidor

    function actualizarVisibilidadDomicilio() {
        var modalidad = document.querySelector('input[name="modalidad"]:checked').value;
        var grupoDomicilio = document.getElementById('checkout-domicilio-grupo');
        grupoDomicilio.hidden = modalidad !== 'entrega_domicilio';

        if (modalidad === 'recoleccion_tienda') {
            cpCubierto = true; // no aplica validación postal
        } else {
            cpCubierto = null;
        }
    }

    function validarCpEnVivo() {
        var input = document.getElementById('checkout-cp');
        var resultado = document.getElementById('checkout-cp-resultado');

        window.PPPostalCoverage.check(input.value).then(function (r) {
            resultado.hidden = false;

            if (r.error === 'formato_invalido') {
                resultado.className = 'postal-bar__result postal-bar__result--blocked';
                resultado.textContent = 'Ingresa un código postal válido de 5 dígitos.';
                cpCubierto = false;
                return;
            }

            if (r.error === 'servicio_no_disponible') {
                resultado.className = 'postal-bar__result postal-bar__result--blocked';
                resultado.textContent = 'No pudimos verificar la cobertura. Intenta de nuevo.';
                cpCubierto = false;
                return;
            }

            if (!r.cubierto) {
                resultado.className = 'postal-bar__result postal-bar__result--blocked';
                resultado.textContent = 'Ese código postal está fuera de La Paz, B.C.S. — solo disponible recolección en tienda.';
                cpCubierto = false;
                return;
            }

            resultado.className = 'postal-bar__result postal-bar__result--ok';
            resultado.textContent = 'Cobertura confirmada — ' + r.zona_colonia + '.';
            cpCubierto = true;
        });
    }

    function manejarEnvio(event) {
        event.preventDefault();

        var modalidad = document.querySelector('input[name="modalidad"]:checked').value;
        var statusEl = document.getElementById('checkout-status');

        if (modalidad === 'entrega_domicilio' && cpCubierto !== true) {
            statusEl.hidden = false;
            statusEl.className = 'postal-bar__result postal-bar__result--blocked';
            statusEl.textContent = 'Valida un código postal cubierto antes de continuar, o elige recolección en tienda.';
            return;
        }

        var items = global.PPCart.getAll().map(function (item) {
            return { producto_presentacion_id: item.producto_presentacion_id, cantidad: item.cantidad };
        });

        var body = {
            cliente_nombre: document.getElementById('checkout-nombre').value,
            cliente_telefono: document.getElementById('checkout-telefono').value,
            cliente_email: document.getElementById('checkout-email').value,
            modalidad: modalidad,
            items: items
        };

        if (modalidad === 'entrega_domicilio') {
            body.cp_entrega = document.getElementById('checkout-cp').value;
            body.direccion_entrega = document.getElementById('checkout-direccion').value;
        }

        statusEl.hidden = false;
        statusEl.className = 'postal-bar__result';
        statusEl.textContent = 'Procesando tu pedido...';

        fetch('api/pedido_crear.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        })
            .then(function (res) { return res.json().then(function (b) { return { res: res, body: b }; }); })
            .then(function (r) {
                if (!r.res.ok || r.body.status !== 'success') {
                    statusEl.className = 'postal-bar__result postal-bar__result--blocked';
                    statusEl.textContent = r.body.message || 'No fue posible procesar tu pedido.';
                    return;
                }

                global.PPCart.clear();
                document.getElementById('checkout-form').hidden = true;
                statusEl.className = 'postal-bar__result postal-bar__result--ok';
                statusEl.textContent = '¡Pedido #' + r.body.data.id + ' confirmado! Total: ' + formatMoney(r.body.data.total) + '. Nos pondremos en contacto contigo para coordinar la entrega.';
                renderCarrito();
            })
            .catch(function () {
                statusEl.className = 'postal-bar__result postal-bar__result--blocked';
                statusEl.textContent = 'No fue posible conectar con el servidor.';
            });
    }

    function init() {
        var form = document.getElementById('checkout-form');
        if (!form) {
            return;
        }

        renderCarrito();
        actualizarVisibilidadDomicilio();

        document.querySelectorAll('input[name="modalidad"]').forEach(function (radio) {
            radio.addEventListener('change', actualizarVisibilidadDomicilio);
        });

        document.getElementById('checkout-validar-cp-btn').addEventListener('click', validarCpEnVivo);
        form.addEventListener('submit', manejarEnvio);
    }

    document.addEventListener('DOMContentLoaded', init);
})(window, document);
