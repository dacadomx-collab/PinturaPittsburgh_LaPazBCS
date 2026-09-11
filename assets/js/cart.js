// assets/js/cart.js — PinturaPittsburgh_LaPazBCS
// Carrito mínimo de sesión (sessionStorage) — puente entre producto.html (PDP)
// y checkout.html. No persiste entre sesiones de navegador a propósito: el
// pedido se confirma o se abandona en la misma visita.
(function (global) {
    'use strict';

    var STORAGE_KEY = 'pp_cart';

    function leerCarrito() {
        try {
            var raw = sessionStorage.getItem(STORAGE_KEY);
            return raw ? JSON.parse(raw) : [];
        } catch (e) {
            return [];
        }
    }

    function guardarCarrito(items) {
        try {
            sessionStorage.setItem(STORAGE_KEY, JSON.stringify(items));
        } catch (e) {
            // sessionStorage no disponible — el carrito simplemente no persiste.
        }
    }

    /**
     * @param {{producto_presentacion_id:number, nombre:string, volumen:string, precio:number, cantidad:number}} item
     */
    function agregar(item) {
        var items = leerCarrito();
        var existente = items.find(function (i) {
            return i.producto_presentacion_id === item.producto_presentacion_id;
        });

        if (existente) {
            existente.cantidad += item.cantidad;
        } else {
            items.push(item);
        }

        guardarCarrito(items);
        return items;
    }

    function quitar(index) {
        var items = leerCarrito();
        items.splice(index, 1);
        guardarCarrito(items);
        return items;
    }

    function vaciar() {
        guardarCarrito([]);
    }

    function total(items) {
        return (items || leerCarrito()).reduce(function (acc, item) {
            return acc + item.precio * item.cantidad;
        }, 0);
    }

    global.PPCart = {
        getAll: leerCarrito,
        add: agregar,
        remove: quitar,
        clear: vaciar,
        total: total
    };
})(window);
