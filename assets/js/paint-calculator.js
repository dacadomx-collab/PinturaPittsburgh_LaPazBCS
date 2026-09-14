// assets/js/paint-calculator.js — PinturaPittsburgh_LaPazBCS
// Calculadora de Pintura Costera: m² de muro -> litros/galones/cubetas
// requeridos, con corrección por textura y exposición ambiental.
//
// ⚠️ Los factores de rendimiento (PPPaintCalculator.RENDIMIENTO_BASE_M2_POR_GALON
// y los multiplicadores de textura/exposición) son valores de referencia
// configurables, NO una cifra certificada en laboratorio para un producto
// específico. Antes de comprometer una cotización, verificar el rendimiento
// real de la ficha técnica del producto elegido (productos.ficha_tecnica_pdf_url).
(function (global) {
    'use strict';

    var LITROS_POR_GALON = 3.78;
    var LITROS_POR_CUBETA = 19;

    // Rendimiento de referencia sobre una superficie lisa, en condiciones
    // estándar, una sola mano. Ajustar aquí si el equipo técnico define un
    // valor distinto — nunca hardcodear un número distinto en otro archivo.
    var RENDIMIENTO_BASE_M2_POR_GALON = 32;

    var FACTOR_TEXTURA = {
        liso: 1,
        enjarre_fino: 0.85,
        estuco_rustico: 0.65
    };

    var FACTOR_EXPOSICION = {
        interior: 1,
        exterior_protegido: 0.9,
        exterior_expuesto: 0.75 // brisa marina directa — mayor absorción/repintado
    };

    function calcular(areaM2, textura, exposicion) {
        var factorTextura = FACTOR_TEXTURA[textura] || 1;
        var factorExposicion = FACTOR_EXPOSICION[exposicion] || 1;
        var rendimientoEfectivo = RENDIMIENTO_BASE_M2_POR_GALON * factorTextura * factorExposicion;

        var galones = areaM2 / rendimientoEfectivo;
        var litros = galones * LITROS_POR_GALON;
        var cubetas = litros / LITROS_POR_CUBETA;

        return {
            litros: Math.ceil(litros * 10) / 10,
            galones: Math.ceil(galones * 10) / 10,
            cubetas: Math.ceil(cubetas * 10) / 10,
            rendimientoEfectivo: Math.round(rendimientoEfectivo)
        };
    }

    function init() {
        var form = document.getElementById('calculadora-pintura');
        if (!form) {
            return;
        }

        var areaInput = document.getElementById('calc-area');
        var previousArea = '';
        // Teclado decimal y filtro también para pegado, dictado y arrastrar texto.
        areaInput.addEventListener('beforeinput', function (event) {
            if (event.data && /[^0-9.,]/.test(event.data)) event.preventDefault();
        });
        areaInput.addEventListener('input', function () {
            if (/^[0-9]*([.,][0-9]*)?$/.test(areaInput.value)) previousArea = areaInput.value;
            else areaInput.value = previousArea;
        });

        var resultBox = document.getElementById('calculadora-resultado');
        var resultValue = document.getElementById('calculadora-resultado-valor');
        var resultNote = document.getElementById('calculadora-resultado-nota');

        form.addEventListener('submit', function (event) {
            event.preventDefault();

            var area = Number(areaInput.value.replace(',', '.'));
            var textura = document.getElementById('calc-textura').value;
            var exposicion = document.getElementById('calc-exposicion').value;

            if (!Number.isFinite(area) || area <= 0) {
                resultBox.hidden = false;
                resultBox.className = 'calculator-result';
                resultValue.textContent = 'Ingresa un área válida en m².';
                resultNote.textContent = '';
                return;
            }

            var r = calcular(area, textura, exposicion);

            resultBox.hidden = false;
            resultValue.textContent = r.galones + ' galones (' + r.litros + ' L) ≈ ' + r.cubetas + ' cubetas de 19 L';
            resultNote.textContent = 'Estimado con rendimiento de referencia de ' + r.rendimientoEfectivo + ' m²/galón según textura y exposición. Confirma el rendimiento real en la ficha técnica del producto antes de comprar.';
        });
    }

    global.PPPaintCalculator = { calcular: calcular, init: init };
})(window);
