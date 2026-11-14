(function () {
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register((document.documentElement.dataset.base || '/').replace(/\/?$/, '/') + 'sw.js').catch(function () {});
    }
    var app = window.matchMedia('(display-mode: standalone)').matches || navigator.standalone;
    if (!app) return;
    var propio = function (u) {
        try { return new URL(u, location.href).origin === location.origin; } catch (e) { return false; }
    };
    document.addEventListener('click', function (e) {
        var a = e.target.closest && e.target.closest('a[target="_blank"]');
        if (a && propio(a.href) && !a.hasAttribute('download')) a.removeAttribute('target');
    }, true);
    var abrir = window.open;
    window.open = function (u, t, f) {
        if (u && propio(u) && (!t || t === '_blank')) { location.href = u; return window; }
        return abrir.call(window, u, t, f);
    };
})();
