// Fernwood Makers directory: client-side search, filters, sort and pagination over server-rendered cards.
// Works on the static export (no server): it reads the generated JSON index and only shows/hides/reorders
// cards that are already in the HTML, so the page stays crawlable and usable without JavaScript.
(function () {
  'use strict';
  var BAND_MIN = { 1: 0, 2: 25, 3: 75, 4: 200 };

  function parseQuery(q) {
    // "under $50" / "below 30" narrows price bands; the rest are search words.
    var out = { words: [], maxPrice: null };
    var m = q.match(/\b(?:under|below|less than)\s*\$?\s*(\d+)/i);
    if (m) { out.maxPrice = parseInt(m[1], 10); q = q.replace(m[0], ' '); }
    out.words = q.toLowerCase().split(/[\s,]+/).filter(function (w) { return w.length > 1; });
    return out;
  }

  function initDirectory(root) {
    var form = root.querySelector('.filters');
    var grid = root.querySelector('.grid');
    var cards = Array.prototype.slice.call(grid.querySelectorAll('.card'));
    var byId = {}; cards.forEach(function (c) { byId[c.dataset.id] = c; });
    var perPage = parseInt(root.dataset.perPage, 10) || 12;
    var syncUrl = root.dataset.syncUrl === '1';
    var countEl = root.querySelector('.count');
    var sheetCount = root.querySelector('.js-count');
    var empty = root.querySelector('.empty'), pager = root.querySelector('.pager');
    var chipsBox = root.querySelector('.active-chips');
    var search = root.querySelector('.dir-search input[name=q]');
    var sortSel = root.querySelector('select[name=sort]');
    var index = cards.map(function (c, i) { // fallback index from the DOM until the JSON loads
      return { id: +c.dataset.id, craft: c.dataset.craft, town: c.dataset.town, price: +c.dataset.price, open: c.dataset.open === '1', title: c.querySelector('.card-title').textContent, text: c.textContent, order: i, date: 0 };
    });
    var page = 1;
    root.classList.add('js');
    cards.forEach(function (c) { c.classList.remove('pg-hide'); });

    fetch(root.dataset.index).then(function (r) { return r.ok ? r.json() : null; }).then(function (data) {
      if (!data || !data.makers) return;
      var order = {}; index.forEach(function (x) { order[x.id] = x.order; });
      index = data.makers.filter(function (m) { return byId[m.id]; }).map(function (m) { m.order = order[m.id]; return m; });
      apply(false);
    }).catch(function () {});

    function state() {
      var fd = new FormData(form);
      var lockC = (root.dataset.lockCategory || '').split(',').filter(Boolean), lockT = (root.dataset.lockTown || '').split(',').filter(Boolean);
      var st = { category: lockC.length ? lockC : fd.getAll('category'), town: lockT.length ? lockT : fd.getAll('town'), price: fd.getAll('price').map(Number), open: fd.get('open') === '1', q: search ? search.value.trim() : '', sort: sortSel ? sortSel.value : 'recommended' };
      return st;
    }

    function matches(m, st, pq) {
      if (st.category.length && st.category.indexOf(m.craft) < 0) return false;
      if (st.town.length && st.town.indexOf(m.town) < 0) return false;
      if (st.price.length && st.price.indexOf(m.price) < 0) return false;
      if (st.open && !m.open) return false;
      if (pq.maxPrice !== null && BAND_MIN[m.price] >= pq.maxPrice) return false;
      var hay = (m.text + ' ' + m.title).toLowerCase();
      for (var i = 0; i < pq.words.length; i++) {
        var w = pq.words[i];
        if (hay.indexOf(w) < 0 && hay.indexOf(w.replace(/s$/, '')) < 0) return false;
      }
      return true;
    }

    function label(name, value) {
      var input = form.querySelector('input[name="' + name + '"][value="' + value + '"]');
      return input ? input.parentNode.querySelector('span').textContent : value;
    }

    function renderChips(st) {
      if (!chipsBox) return;
      chipsBox.innerHTML = '';
      var add = function (name, value, text) {
        var b = document.createElement('button');
        b.type = 'button'; b.className = 'chip active';
        b.innerHTML = '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path d="M5 12l4 4L19 6"/></svg>';
        b.appendChild(document.createTextNode(text));
        b.setAttribute('aria-label', 'Remove filter: ' + text);
        b.addEventListener('click', function () {
          if (name === 'q') { search.value = ''; }
          else { var i = form.querySelector('input[name="' + name + '"][value="' + value + '"]'); if (i) i.checked = false; }
          page = 1; apply(true);
        });
        chipsBox.appendChild(b);
      };
      if (!root.dataset.lockCategory) st.category.forEach(function (v) { add('category', v, label('category', v)); });
      if (!root.dataset.lockTown) st.town.forEach(function (v) { add('town', v, label('town', v)); });
      st.price.forEach(function (v) { add('price', v, label('price', v)); });
      if (st.open) add('open', '1', 'Open studio');
      if (st.q) add('q', '', '"' + st.q + '"');
    }

    function renderPager(pages) {
      if (!pager) return;
      pager.hidden = pages < 2;
      pager.innerHTML = '';
      if (pages < 2) return;
      var mk = function (text, p, opts) {
        var b = document.createElement('button');
        b.type = 'button'; b.className = 'page-btn' + (opts && opts.current ? ' current' : '');
        b.textContent = text;
        if (opts && opts.label) b.setAttribute('aria-label', opts.label);
        if (opts && opts.current) b.setAttribute('aria-current', 'page');
        b.disabled = !!(opts && opts.disabled);
        b.addEventListener('click', function () { page = p; apply(true); root.scrollIntoView({ behavior: 'smooth', block: 'start' }); });
        pager.appendChild(b);
      };
      mk('Previous', page - 1, { disabled: page === 1, label: 'Previous page' });
      for (var p = 1; p <= pages; p++) mk(String(p), p, { current: p === page, label: 'Page ' + p });
      mk('Next', page + 1, { disabled: page === pages, label: 'Next page' });
    }

    function syncLocation(st) {
      if (!syncUrl || !window.history.replaceState) return;
      var params = new URLSearchParams();
      if (st.q) params.set('q', st.q);
      if (st.category.length) params.set('category', st.category.join(','));
      if (st.town.length) params.set('town', st.town.join(','));
      if (st.price.length) params.set('price', st.price.join(','));
      if (st.open) params.set('open', '1');
      if (st.sort !== 'recommended') params.set('sort', st.sort);
      if (page > 1) params.set('page', page);
      var qs = params.toString();
      history.replaceState(null, '', location.pathname + (qs ? '?' + qs : '') + location.hash);
    }

    function apply(userAction) {
      var st = state(), pq = parseQuery(st.q);
      var list = index.filter(function (m) { return matches(m, st, pq); });
      list.sort(function (a, b) {
        if (st.sort === 'az') return a.title.localeCompare(b.title);
        if (st.sort === 'newest') return (b.date - a.date) || (b.order - a.order);
        return a.order - b.order;
      });
      var pages = Math.max(1, Math.ceil(list.length / perPage));
      if (page > pages) page = pages;
      var visible = {}; list.slice((page - 1) * perPage, page * perPage).forEach(function (m) { visible[m.id] = true; });
      // Reorder only when the order actually changed: re-inserting rendered cards repaints them (and would reset LCP).
      var current = Array.prototype.map.call(grid.children, function (c) { return +c.dataset.id; }).filter(function (id) { return list.some(function (m) { return m.id === id; }); });
      var wanted = list.map(function (m) { return m.id; });
      if (current.join() !== wanted.join()) list.forEach(function (m) { grid.appendChild(byId[m.id]); });
      cards.forEach(function (c) { c.hidden = !visible[c.dataset.id]; });
      var from = (page - 1) * perPage + 1, to = Math.min(page * perPage, list.length);
      if (!list.length) countEl.innerHTML = 'No makers match these filters <span class="mute">(0 of ' + index.filter(function (m) { return (!root.dataset.lockCategory || m.craft === root.dataset.lockCategory) && (!root.dataset.lockTown || m.town === root.dataset.lockTown); }).length + ')</span>';
      else if (list.length > perPage) countEl.innerHTML = 'Showing <strong>' + from + ' to ' + to + '</strong> of <strong>' + list.length + '</strong> makers';
      else countEl.innerHTML = '<strong>' + list.length + '</strong> ' + (list.length === 1 ? 'maker' : 'makers');
      if (sheetCount) sheetCount.textContent = list.length;
      empty.hidden = list.length > 0;
      renderPager(pages);
      renderChips(st);
      if (userAction) syncLocation(st);
    }

    // Initial state from the URL (?category=ceramics&town=coldwater&price=2&open=1&q=mugs&sort=az&page=2)
    var params = new URLSearchParams(location.search);
    ['category', 'town', 'price'].forEach(function (name) {
      (params.get(name) || '').split(',').filter(Boolean).forEach(function (v) {
        var i = form.querySelector('input[name="' + name + '"][value="' + v + '"]'); if (i) i.checked = true;
      });
    });
    if (params.get('open') === '1') { var o = form.querySelector('input[name=open]'); if (o) o.checked = true; }
    if (search && params.get('q')) search.value = params.get('q');
    if (sortSel && params.get('sort')) sortSel.value = params.get('sort');
    page = parseInt(params.get('page'), 10) || 1;

    // Locked context filter (category/town page): announced as disabled, cannot be unticked.
    form.addEventListener('click', function (e) { if (e.target.matches('input[aria-disabled="true"]')) e.preventDefault(); });
    form.addEventListener('change', function () { page = 1; apply(true); });
    form.addEventListener('submit', function (e) { e.preventDefault(); page = 1; apply(true); closeSheet(); });
    if (search) {
      search.form.addEventListener('submit', function (e) { e.preventDefault(); page = 1; apply(true); });
      var t; search.addEventListener('input', function () { clearTimeout(t); t = setTimeout(function () { page = 1; apply(true); }, 180); });
    }

    // Mobile bottom sheet
    // Mobile bottom sheet: a modal dialog while open (role, aria-modal, label, focus trap, scroll lock, Escape).
    var openBtn = root.querySelector('.filters-open');
    var title = form.querySelector('.sheet-title');
    var outside = [];
    function focusables() { return Array.prototype.filter.call(form.querySelectorAll('a[href],button:not([disabled]),input:not([disabled]),select,textarea'), function (el) { return el.offsetParent !== null; }); }
    function openSheet() {
      form.classList.add('is-open');
      form.setAttribute('role', 'dialog'); form.setAttribute('aria-modal', 'true'); if (title) form.setAttribute('aria-labelledby', title.id);
      document.documentElement.classList.add('sheet-open');
      openBtn.setAttribute('aria-expanded', 'true');
      outside = [];
      for (var node = form; node && node !== document.body; node = node.parentElement) {
        Array.prototype.forEach.call(node.parentElement.children, function (sib) { if (sib !== node && !sib.hasAttribute('inert')) outside.push(sib); });
      }
      // Every sibling along the sheet's ancestor chain becomes inert (header, footer, results, search).
      outside.forEach(function (el) { el.setAttribute('inert', ''); });
      var f = form.querySelector('.sheet-close'); f && f.focus();
    }
    function closeSheet() {
      if (!form.classList.contains('is-open')) return;
      form.classList.remove('is-open');
      form.removeAttribute('role'); form.removeAttribute('aria-modal'); form.removeAttribute('aria-labelledby');
      document.documentElement.classList.remove('sheet-open');
      outside.forEach(function (el) { el.removeAttribute('inert'); }); outside = [];
      openBtn.setAttribute('aria-expanded', 'false'); openBtn.focus();
    }
    form.addEventListener('keydown', function (e) {
      if (e.key !== 'Tab' || !form.classList.contains('is-open')) return;
      var f = focusables(); if (!f.length) return;
      var first = f[0], last = f[f.length - 1];
      if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
      else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
    });
    document.addEventListener('focusin', function (e) { if (form.classList.contains('is-open') && !form.contains(e.target)) { var f = focusables(); f.length && f[0].focus(); } });
    if (openBtn) openBtn.addEventListener('click', openSheet);
    root.querySelectorAll('.sheet-close, .sheet-done').forEach(function (b) { b.addEventListener('click', closeSheet); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeSheet(); });

    apply(false);
  }

  document.querySelectorAll('.directory').forEach(initDirectory);

  // Header mobile menu
  var btn = document.querySelector('.menu-toggle'), menu = document.getElementById('mobile-menu');
  if (btn && menu) {
    var set = function (open) { btn.setAttribute('aria-expanded', String(open)); menu.hidden = !open; document.documentElement.classList.toggle('menu-open', open); };
    btn.addEventListener('click', function () { set(btn.getAttribute('aria-expanded') !== 'true'); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !menu.hidden) { set(false); btn.focus(); } });
  }

  // Demo forms: validate in the browser, show the success state, send nothing.
  document.addEventListener('submit', function (e) {
    var f = e.target.closest('.demo-form');
    if (!f) return;
    e.preventDefault();
    if (!f.checkValidity()) { f.reportValidity(); return; }
    var box = document.createElement('div');
    box.className = 'form-success'; box.setAttribute('role', 'status'); box.setAttribute('tabindex', '-1');
    box.innerHTML = '<strong>' + (f.dataset.success || 'Thanks, your message is ready to go.') + '</strong><br><span>Demo form: this is a portfolio sample, so nothing was sent.</span>';
    f.replaceWith(box); box.focus();
  });
})();
