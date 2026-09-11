// assets/js/postal-coverage.js — PinturaPittsburgh_LaPazBCS
//
// Valida la cobertura postal contra api/validar_cp.php (Contrato 4). Ya NO es
// un espejo en memoria — consulta la tabla real `codigos_postales_cobertura`.
//
// Fail-safe: si el formato es inválido o el servidor no responde, se trata
// como NO cubierto — nunca se habilita una entrega a domicilio por defecto.
(function (global) {
    'use strict';

    /**
     * @param {string} codigoPostal
     * @returns {Promise<{cubierto: boolean, zona_colonia?: string, modalidad_logistica?: string, ventana_entrega?: string, error?: string}>}
     */
    function checkCoverage(codigoPostal) {
        var cp = String(codigoPostal).trim();

        if (!/^\d{5}$/.test(cp)) {
            return Promise.resolve({ cubierto: false, error: 'formato_invalido' });
        }

        return fetch('api/validar_cp.php?cp=' + encodeURIComponent(cp))
            .then(function (res) {
                return res.json().then(function (body) {
                    return { res: res, body: body };
                });
            })
            .then(function (r) {
                if (!r.res.ok || r.body.status !== 'success') {
                    throw new Error(r.body.message || 'No fue posible validar la cobertura.');
                }
                return r.body.data;
            })
            .catch(function () {
                // Fail-safe: servidor no disponible -> se bloquea, nunca se abre.
                return { cubierto: false, error: 'servicio_no_disponible' };
            });
    }

    global.PPPostalCoverage = { check: checkCoverage };
})(window);
