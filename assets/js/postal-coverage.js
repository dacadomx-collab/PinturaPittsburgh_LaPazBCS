// assets/js/postal-coverage.js — PinturaPittsburgh_LaPazBCS
//
// Espejo de solo lectura de `codigos_postales_cobertura` (database/001_schema_inicial.sql).
// TEMPORAL: valida en el cliente mientras no exista api/validar_cp.php. El
// backend SIEMPRE debe revalidar en el servidor antes de aceptar un pedido
// (ver knowledge/03_CONTRATOS_API_Y_RUTAS.md Contrato 4) — este archivo nunca
// es la fuente de verdad final, solo feedback inmediato en el checkout.
//
// Si el seed de la tabla cambia, actualizar estos mismos rangos aquí.
(function (global) {
    'use strict';

    var RANGOS = [
        { min: 23000, max: 23000, zona: 'Zona Central, Love, Puerta de Hierro', modalidad: 'despacho_local', ventana: 'Menos de 2 a 4 Horas' },
        { min: 23010, max: 23019, zona: 'Colina del Sol, Ciudad del Cielo, Palmira, Pedregal', modalidad: 'ruta_programada', ventana: 'Mismo Día' },
        { min: 23020, max: 23026, zona: 'El Esterito, Ladrillera, Guerrero, Antonio Navarro Rubio', modalidad: 'ruta_programada', ventana: 'Mismo Día' },
        { min: 23040, max: 23050, zona: 'Los Olivos, Bella Vista, Roma, Tecnológico, Indeco', modalidad: 'despacho_inmediato', ventana: 'Menos de 2 Horas' },
        { min: 23070, max: 23075, zona: 'Balandra, Las Garzas, Privadas, Agustín Arriola', modalidad: 'despacho_inmediato', ventana: 'Menos de 2 Horas' },
        { min: 23080, max: 23085, zona: '8 de Octubre, Altamira Residencial, Camino Real', modalidad: 'ruta_programada', ventana: 'Mismo Día' },
        { min: 23090, max: 23098, zona: 'Miramar, Arcos del Sol, Atardeceres, Bahía de la Paz', modalidad: 'ruta_periferica', ventana: 'Mismo Día / Hasta 24 Horas' }
    ];

    var COBERTURA_MIN = 23000;
    var COBERTURA_MAX = 23098;

    /**
     * @param {string} codigoPostal
     * @returns {{cubierto: boolean, zona_colonia?: string, modalidad_logistica?: string, ventana_entrega?: string}}
     */
    function checkCoverage(codigoPostal) {
        var cp = parseInt(String(codigoPostal).trim(), 10);

        if (!/^\d{5}$/.test(String(codigoPostal).trim()) || isNaN(cp)) {
            return { cubierto: false, error: 'formato_invalido' };
        }

        if (cp < COBERTURA_MIN || cp > COBERTURA_MAX) {
            return { cubierto: false };
        }

        for (var i = 0; i < RANGOS.length; i++) {
            var r = RANGOS[i];
            if (cp >= r.min && cp <= r.max) {
                return {
                    cubierto: true,
                    zona_colonia: r.zona,
                    modalidad_logistica: r.modalidad,
                    ventana_entrega: r.ventana
                };
            }
        }

        // Dentro del rango urbano 23000-23098 pero sin colonia confirmada en
        // la fuente (ver database/001_schema_inicial.sql — filas "pendiente
        // de verificar"). Se cubre con ventana genérica hasta cargar SEPOMEX.
        return {
            cubierto: true,
            zona_colonia: 'Zona Urbana La Paz (colonia por confirmar)',
            modalidad_logistica: 'ruta_programada',
            ventana_entrega: 'Mismo Día'
        };
    }

    global.PPPostalCoverage = { check: checkCoverage };
})(window);
