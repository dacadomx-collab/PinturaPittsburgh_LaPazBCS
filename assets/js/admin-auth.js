// assets/js/admin-auth.js — PinturaPittsburgh_LaPazBCS
// Autenticación del backoffice: Bearer JWT (no cookies), Access+Refresh.
// Uso exclusivo de /admin/*.html — nunca cargar en el sitio público.
(function (global) {
    'use strict';

    var STORAGE_KEY = 'pp_admin_session';

    function getDeviceId() {
        var id = localStorage.getItem('pp_device_id');
        if (!id) {
            id = 'dev-' + Math.random().toString(36).slice(2) + Date.now().toString(36);
            localStorage.setItem('pp_device_id', id);
        }
        return id;
    }

    function getSession() {
        try {
            return JSON.parse(sessionStorage.getItem(STORAGE_KEY) || 'null');
        } catch (e) {
            return null;
        }
    }

    function setSession(session) {
        sessionStorage.setItem(STORAGE_KEY, JSON.stringify(session));
    }

    function clearSession() {
        sessionStorage.removeItem(STORAGE_KEY);
    }

    function login(email, password) {
        return fetch('../api/auth_login.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email: email, password: password, device_id: getDeviceId() })
        })
            .then(function (res) { return res.json().then(function (body) { return { res: res, body: body }; }); })
            .then(function (r) {
                if (!r.res.ok || r.body.status !== 'success') {
                    throw new Error(r.body.message || 'No fue posible iniciar sesión.');
                }
                setSession(r.body.data);
                return r.body.data;
            });
    }

    function logout() {
        clearSession();
        window.location.href = 'login.html';
    }

    function refresh() {
        var session = getSession();
        if (!session || !session.refresh_token) {
            return Promise.reject(new Error('Sin sesión activa.'));
        }

        return fetch('../api/auth_refresh.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ refresh_token: session.refresh_token, device_id: getDeviceId() })
        })
            .then(function (res) { return res.json().then(function (body) { return { res: res, body: body }; }); })
            .then(function (r) {
                if (!r.res.ok || r.body.status !== 'success') {
                    throw new Error('Sesión expirada.');
                }
                var merged = Object.assign({}, session, r.body.data);
                setSession(merged);
                return merged;
            });
    }

    /**
     * fetch autenticado: agrega Authorization: Bearer <access_token>. Si el
     * backend responde 401, intenta refrescar UNA vez y reintenta; si vuelve
     * a fallar, cierra sesión y redirige a login.html.
     */
    function authFetch(url, options) {
        options = options || {};
        var session = getSession();
        if (!session) {
            window.location.href = 'login.html';
            return Promise.reject(new Error('Sin sesión activa.'));
        }

        function doFetch(token) {
            var headers = Object.assign({}, options.headers || {}, {
                Authorization: 'Bearer ' + token,
                'Content-Type': 'application/json'
            });
            return fetch(url, Object.assign({}, options, { headers: headers }));
        }

        return doFetch(session.access_token).then(function (res) {
            if (res.status !== 401) {
                return res;
            }
            return refresh().then(function (nueva) { return doFetch(nueva.access_token); });
        }).catch(function (err) {
            clearSession();
            window.location.href = 'login.html';
            throw err;
        });
    }

    function requireSession() {
        if (!getSession()) {
            window.location.href = 'login.html';
        }
    }

    global.PPAdminAuth = {
        login: login,
        logout: logout,
        authFetch: authFetch,
        requireSession: requireSession,
        getSession: getSession
    };
})(window);
