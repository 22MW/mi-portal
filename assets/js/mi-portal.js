/* Mi portal · menú desplegable de móvil y cambio de pestaña sin recargar (si algo falla, se carga la página normal). */
(function () {
  'use strict';
  var NAV = '.mp-nav', CONTENIDO = '.woocommerce-MyAccount-content';
  var nav = document.querySelector(NAV);
  if (!nav) { return; }
  var cfg = window.miPortal || {};

  /* ---------- tablas: la celda sin data-title toma el título de su columna (lo leen las tarjetas de las pantallas estrechas) ---------- */
  function etiquetarTablas() {
    [].forEach.call(document.querySelectorAll(CONTENIDO + ' table'), function (t) {
      var ths = [].map.call(t.querySelectorAll('thead th'), function (th) { return th.textContent.trim(); });
      if (!ths.length) { return; }
      [].forEach.call(t.querySelectorAll('tbody tr'), function (tr) {
        [].forEach.call(tr.children, function (td, i) {
          if (td.tagName === 'TD' && !td.hasAttribute('data-title') && ths[i]) { td.setAttribute('data-title', ths[i]); }
        });
      });
    });
  }
  /* ---------- tablas: si una tabla no cabe en su tarjeta (o queda muy apretada), pasa a tarjetas (.mp-tarjetas) ---------- */
  var MP_ANCHO_TABLA = 900; // por debajo de este ancho útil (px) las tablas pasan a tarjetas
  function ajustarTablas() {
    [].forEach.call(document.querySelectorAll(CONTENIDO + ' table'), function (t) {
      if (t.querySelector('tfoot') || !t.querySelector('thead th')) { return; }
      var caja = t.closest(CONTENIDO), cs = getComputedStyle(caja);
      var ancho = caja.clientWidth - parseFloat(cs.paddingLeft) - parseFloat(cs.paddingRight);
      t.classList.remove('mp-tarjetas');
      // Misma regla para todas las tablas (Pedidos, Reservas…), solo según el ancho de la caja, para que se vean igual en cada pantalla.
      // Si una tabla desborda aunque haya sitio, pasa a tarjetas igualmente para no tener scroll horizontal.
      var desborda = t.offsetWidth > ancho + 1;
      t.classList.toggle('mp-tarjetas', ancho < MP_ANCHO_TABLA || desborda);
    });
  }
  var temporizador;
  addEventListener('resize', function () { clearTimeout(temporizador); temporizador = setTimeout(ajustarTablas, 120); });
  addEventListener('load', ajustarTablas);
  etiquetarTablas();
  ajustarTablas();

  /* ---------- menú desplegable ---------- */
  var toggle = nav.querySelector('.mp-movil__toggle'), panel = nav.querySelector('.mp-movil__panel');
  function abrir(on) {
    if (!toggle || !panel) { return; }
    toggle.setAttribute('aria-expanded', on ? 'true' : 'false');
    panel.hidden = !on;
  }
  if (toggle && panel) {
    toggle.addEventListener('click', function () { abrir(panel.hidden); });
    panel.addEventListener('click', function (e) { if (e.target.closest('a')) { abrir(false); } });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !panel.hidden) { abrir(false); toggle.focus(); } });
    document.addEventListener('click', function (e) { if (!panel.hidden && !nav.contains(e.target)) { abrir(false); } });
  }

  /* ---------- cambio de pestaña sin recargar ---------- */
  var ocupado = false;
  var limpia = function (u) { return u.split('?')[0].replace(/\/$/, ''); };
  var PROHIBIDOS = ['cancel_order', 'wc-ajax', 'add-to-cart', 'wc_order_attribution', 'pay_for_order', 'order_again', 'download_file', 'subscription_renewal', 'reactivate', 'do_action', 'customer-logout', '_wpnonce'];

  // Endpoints (slugs que llegan desde PHP en cfg.sinFetch) que se abren con recarga completa: ruta tras la base o parámetro de la URL.
  function sinFetch(url) {
    var lista = cfg.sinFetch || [];
    if (!lista.length) { return false; }
    var tramo = url.pathname.slice((cfg.base || '').length).replace(/^\/+/, '').split('/')[0];
    try { tramo = decodeURIComponent(tramo); } catch (e) {}
    for (var i = 0; i < lista.length; i++) { if (tramo === lista[i] || url.searchParams.has(lista[i])) { return true; } }
    return false;
  }

  function esSegura(a) {
    if (!a || !a.href || a.target === '_blank' || a.hasAttribute('download')) { return false; }
    var url;
    try { url = new URL(a.href); } catch (e) { return false; }
    if (url.host !== location.host || (cfg.base && url.pathname.indexOf(cfg.base) !== 0)) { return false; }
    if (['pdf', 'zip', 'docx', 'xlsx', 'png', 'jpg', 'jpeg'].indexOf(url.pathname.split('.').pop().toLowerCase()) > -1) { return false; }
    for (var i = 0; i < PROHIBIDOS.length; i++) { if (a.href.indexOf(PROHIBIDOS[i]) > -1) { return false; } }
    if (sinFetch(url)) { return false; }
    return !a.closest('form');
  }

  function cargando(on) {
    var c = document.querySelector(CONTENIDO);
    if (!c) { return; }
    c.classList.toggle('mp-cambiando', on);
    var previo = document.querySelector('.mp-cargando');
    if (!on) { if (previo) { previo.remove(); } return; }
    if (previo) { return; }
    var aviso = document.createElement('div');
    aviso.className = 'mp-cargando';
    aviso.setAttribute('role', 'status');
    aviso.innerHTML = '<span class="mp-puntos" aria-hidden="true"><i></i><i></i><i></i></span><span class="mp-sr">Cargando…</span>';
    document.body.appendChild(aviso);
  }

  function marcarActiva(url) {
    var destino = limpia(url);
    [].forEach.call(document.querySelectorAll('.mp-nav li[class*="woocommerce-MyAccount-navigation-link"]'), function (li) {
      var a = li.querySelector('a');
      if (!a) { return; }
      var es = limpia(a.href) === destino;
      li.classList.toggle('is-active', es);
      if (es && li.closest('.mp-movil')) {
        var t = nav.querySelector('.mp-movil__actual'); if (t) { t.textContent = a.textContent.trim(); }
      }
    });
  }

  function ejecutarScripts(el) {
    [].forEach.call(el.querySelectorAll('script'), function (viejo) {
      var nuevo = document.createElement('script');
      [].forEach.call(viejo.attributes, function (at) { nuevo.setAttribute(at.name, at.value); });
      nuevo.textContent = viejo.textContent;
      viejo.parentNode.replaceChild(nuevo, viejo);
    });
  }

  function cargar(url, empujar) {
    if (ocupado) { return; }
    ocupado = true; cargando(true);
    fetch(url, { credentials: 'same-origin' })
      .then(function (r) { return r.text(); })
      .then(function (html) {
        var doc = new DOMParser().parseFromString(html, 'text/html');
        var nuevo = doc.querySelector(CONTENIDO), actual = document.querySelector(CONTENIDO);
        if (!nuevo || !actual) { location.href = url; return; }
        actual.innerHTML = nuevo.innerHTML;
        ejecutarScripts(actual);
        etiquetarTablas();
        ajustarTablas();
        if (doc.title) { document.title = doc.title; }
        if (empujar) { history.pushState({ miPortal: url }, '', url); }
        marcarActiva(url);
        scrollTo(0, 0);
        cargando(false); ocupado = false;
      })
      .catch(function () { ocupado = false; location.href = url; });
  }

  function alPulsar(e) {
    if (e.defaultPrevented || e.button || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) { return; }
    var a = e.target.closest('a');
    if (!esSegura(a) || limpia(a.href) === limpia(location.href)) { return; }
    e.preventDefault();
    cargar(a.href, true);
  }

  if (window.fetch && window.DOMParser && history.pushState) {
    nav.addEventListener('click', alPulsar);
    var c = document.querySelector(CONTENIDO);
    if (c) { c.addEventListener('click', alPulsar); }
    history.replaceState({ miPortal: location.href }, '', location.href);
    addEventListener('popstate', function (e) {
      if (!e.state || !e.state.miPortal) { return; }
      var destino;
      try { destino = new URL(e.state.miPortal, location.href); } catch (err) { destino = null; }
      if (destino && sinFetch(destino)) { location.reload(); return; }
      cargar(e.state.miPortal, false);
    });
    marcarActiva(location.href);
  }
})();
