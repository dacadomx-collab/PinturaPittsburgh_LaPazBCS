// Propuesta/propuesta.js — Cotización ACADEP para Famza
// Botón de impresión + firma digital dibujable (canvas, mouse y táctil).
// Sin dependencias externas — mismo criterio del resto del sitio.
document.addEventListener('DOMContentLoaded', function () {

    // ── Botón flotante de impresión ─────────────────────────────────────────
    var printBtn = document.getElementById('proposal-print-btn');
    if (printBtn) {
        printBtn.addEventListener('click', function () {
            window.print();
        });
    }

    // ── Fecha de hoy precargada (el cliente la puede cambiar si firma otro día) ──
    var fechaInput = document.getElementById('firma-fecha');
    if (fechaInput && !fechaInput.value) {
        var hoy = new Date();
        var yyyy = hoy.getFullYear();
        var mm = String(hoy.getMonth() + 1).padStart(2, '0');
        var dd = String(hoy.getDate()).padStart(2, '0');
        fechaInput.value = yyyy + '-' + mm + '-' + dd;
    }

    // ── Firma digital (canvas) ───────────────────────────────────────────────
    var canvas = document.getElementById('firma-canvas');
    if (!canvas || !canvas.getContext) {
        return;
    }
    var ctx = canvas.getContext('2d');
    ctx.lineWidth = 2.2;
    ctx.lineCap = 'round';
    ctx.strokeStyle = '#0f172a';

    var dibujando = false;
    var ultimoPunto = null;
    var firmaDibujada = false;

    function posicionRelativa(evento) {
        var rect = canvas.getBoundingClientRect();
        var escalaX = canvas.width / rect.width;
        var escalaY = canvas.height / rect.height;
        var punto = (evento.touches && evento.touches[0]) ? evento.touches[0] : evento;
        return {
            x: (punto.clientX - rect.left) * escalaX,
            y: (punto.clientY - rect.top) * escalaY
        };
    }

    function iniciarTrazo(evento) {
        evento.preventDefault();
        dibujando = true;
        ultimoPunto = posicionRelativa(evento);
    }

    function trazar(evento) {
        if (!dibujando) {
            return;
        }
        evento.preventDefault();
        var punto = posicionRelativa(evento);
        ctx.beginPath();
        ctx.moveTo(ultimoPunto.x, ultimoPunto.y);
        ctx.lineTo(punto.x, punto.y);
        ctx.stroke();
        ultimoPunto = punto;
        firmaDibujada = true;
    }

    function terminarTrazo() {
        dibujando = false;
        ultimoPunto = null;
    }

    // Mouse
    canvas.addEventListener('mousedown', iniciarTrazo);
    canvas.addEventListener('mousemove', trazar);
    canvas.addEventListener('mouseup', terminarTrazo);
    canvas.addEventListener('mouseleave', terminarTrazo);

    // Táctil (dedo o lápiz óptico)
    canvas.addEventListener('touchstart', iniciarTrazo, { passive: false });
    canvas.addEventListener('touchmove', trazar, { passive: false });
    canvas.addEventListener('touchend', terminarTrazo);
    canvas.addEventListener('touchcancel', terminarTrazo);

    var limpiarBtn = document.getElementById('firma-limpiar-btn');
    if (limpiarBtn) {
        limpiarBtn.addEventListener('click', function () {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            firmaDibujada = false;
        });
    }

    // ── Autorizar y guardar: guarda el registro en la base de datos y avisa
    //    a ACADEP por correo. Es la única acción que deja constancia real —
    //    imprimir (arriba) solo genera la copia del cliente. ─────────────────
    var autorizarBtn = document.getElementById('autorizar-guardar-btn');
    var estadoEl = document.getElementById('autorizar-estado');
    var nombreInput = document.getElementById('firma-nombre');
    var checkDesarrollo = document.querySelector('input[name="autoriza_desarrollo"]');
    var checkIguala = document.querySelector('input[name="autoriza_iguala"]');

    function mostrarEstado(texto, esError) {
        if (!estadoEl) {
            return;
        }
        estadoEl.textContent = texto;
        estadoEl.hidden = false;
        estadoEl.classList.toggle('proposal-nota--error', esError);
        estadoEl.classList.toggle('proposal-nota--ok', !esError);
    }

    if (autorizarBtn) {
        autorizarBtn.addEventListener('click', function () {
            var nombre = nombreInput ? nombreInput.value.trim() : '';
            var fecha = fechaInput ? fechaInput.value : '';
            var autorizaDesarrollo = !!(checkDesarrollo && checkDesarrollo.checked);
            var autorizaIguala = !!(checkIguala && checkIguala.checked);

            if (!nombre) {
                mostrarEstado('Escribe el nombre de quien autoriza antes de continuar.', true);
                return;
            }
            if (!autorizaDesarrollo && !autorizaIguala) {
                mostrarEstado('Marca al menos uno de los dos servicios que autorizas.', true);
                return;
            }
            if (!firmaDibujada) {
                mostrarEstado('Dibuja tu firma en el recuadro antes de continuar.', true);
                return;
            }

            autorizarBtn.disabled = true;
            mostrarEstado('Guardando tu autorización…', false);

            fetch('../api/propuesta_autorizar.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    nombre_autoriza: nombre,
                    fecha_autorizacion: fecha,
                    autoriza_desarrollo: autorizaDesarrollo,
                    autoriza_iguala: autorizaIguala,
                    firma_png: canvas.toDataURL('image/png')
                })
            })
                .then(function (respuesta) {
                    return respuesta.json().then(function (cuerpo) {
                        return { ok: respuesta.ok, cuerpo: cuerpo };
                    });
                })
                .then(function (resultado) {
                    if (!resultado.ok) {
                        throw new Error(resultado.cuerpo && resultado.cuerpo.message ? resultado.cuerpo.message : 'No se pudo guardar.');
                    }
                    mostrarEstado('✅ Tu autorización quedó registrada. ¡Gracias!', false);
                    autorizarBtn.textContent = '✅ Autorización guardada';
                })
                .catch(function (error) {
                    autorizarBtn.disabled = false;
                    mostrarEstado(error.message || 'No se pudo guardar tu autorización. Intenta de nuevo.', true);
                });
        });
    }
});
