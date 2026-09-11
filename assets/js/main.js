// assets/js/main.js — PinturaPittsburgh_LaPazBCS
// Orquestador de la página: status check, barra de validación postal y
// arranque de los módulos de catálogo/calculadora (cada uno en su propio
// archivo bajo assets/js/, cargados antes que este script en index.html).

function initStatusCheck() {
    const statusEl = document.getElementById('status-check');
    if (!statusEl) {
        return;
    }

    fetch('api/status_check.php')
        .then((response) => response.json())
        .then((result) => {
            statusEl.textContent = result.message;
        })
        .catch(() => {
            statusEl.textContent = 'No se pudo contactar al servidor.';
        });
}

function initPostalBar() {
    const form = document.getElementById('postal-bar-form');
    if (!form || !window.PPPostalCoverage) {
        return;
    }

    const input = document.getElementById('postal-bar-input');
    const resultEl = document.getElementById('postal-bar-result');

    form.addEventListener('submit', (event) => {
        event.preventDefault();

        const resultado = window.PPPostalCoverage.check(input.value);
        resultEl.hidden = false;

        if (resultado.error === 'formato_invalido') {
            resultEl.className = 'postal-bar__result postal-bar__result--blocked';
            resultEl.textContent = 'Ingresa un código postal válido de 5 dígitos.';
            return;
        }

        if (!resultado.cubierto) {
            resultEl.className = 'postal-bar__result postal-bar__result--blocked';
            resultEl.textContent = 'La entrega a domicilio solo aplica dentro del municipio de La Paz, B.C.S. Puedes recoger tu pedido en tienda (Blvd. Agustín Olachea e Indeco) o llamarnos para cotizaciones de mayoreo.';
            return;
        }

        resultEl.className = 'postal-bar__result postal-bar__result--ok';
        resultEl.textContent = 'Cobertura confirmada — ' + resultado.zona_colonia + '. Ventana de entrega: ' + resultado.ventana_entrega + '.';
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initStatusCheck();
    initPostalBar();

    if (window.PPPaintCalculator) {
        window.PPPaintCalculator.init();
    }
    if (window.PPCatalogRender) {
        window.PPCatalogRender.init();
    }
});
