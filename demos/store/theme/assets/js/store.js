// Lantern Hill storefront.
// Live WordPress: forms post to WooCommerce as usual (real cart and checkout).
// Static copy (<html data-static-export>, set by scripts/export.mjs): a simulated cart in sessionStorage.
// Nothing typed at checkout is sent anywhere; the order only exists in this tab.
(function () {
  'use strict';
  var STATIC = document.documentElement.hasAttribute('data-static-export');
  var RATE = 0.12, FREE_OVER = 40, SHIP = 6, COUPON = 'FIRSTBAG', COUPON_RATE = 0.10;
  var KEY = 'lh-cart', ORDER = 'lh-last-order';
  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };
  var money = function (n) { return '$' + (Math.round(n * 100) / 100).toFixed(2); };
  var esc = function (s) { var d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; };

  // ---------- simulated cart ----------
  function load() { try { return JSON.parse(sessionStorage.getItem(KEY)) || { lines: [], coupon: false }; } catch (e) { return { lines: [], coupon: false }; } }
  function save(c) { try { sessionStorage.setItem(KEY, JSON.stringify(c)); } catch (e) {} badge(); }
  function count(c) { return c.lines.reduce(function (n, l) { return n + (l.bundle ? 1 : l.qty); }, 0); }
  function totals(c) {
    var sub = c.lines.reduce(function (s, l) { return s + l.price * l.qty; }, 0);
    var disc = c.coupon ? sub * COUPON_RATE : 0;
    var ship = sub - disc >= FREE_OVER || sub === 0 ? 0 : SHIP;
    return { sub: sub, disc: disc, ship: ship, total: sub - disc + ship };
  }
  function badge() {
    if (!STATIC) return;
    var n = count(load());
    $$('[data-cart-count]').forEach(function (b) { b.textContent = n; b.hidden = !n; });
    $$('[data-cart-label]').forEach(function (b) { b.textContent = 'Cart, ' + n + (n === 1 ? ' item' : ' items'); });
  }
  function add(line) {
    var c = load();
    var same = !line.bundle && c.lines.filter(function (l) { return l.id === line.id && l.variant === line.variant; })[0];
    if (same) same.qty += line.qty; else c.lines.push(line);
    save(c);
    toast(line.name);
  }
  function relUrl(path) { // demo pages live next to each other under the demo root
    var base = $('link[rel="home"]') || $('a.brand');
    return new URL(path, base ? base.href : location.href).href;
  }
  function toast(name) {
    var t = $('.toast') || document.body.appendChild(Object.assign(document.createElement('div'), { className: 'toast' }));
    t.setAttribute('role', 'status');
    t.innerHTML = 'Added ' + esc(name) + '. <a href="' + esc(relUrl('demo-cart/')) + '">View cart</a>';
    clearTimeout(toast.t); toast.t = setTimeout(function () { t.remove(); }, 4000);
  }

  // ---------- header menu ----------
  var burger = $('.burger'), menu = $('#menu');
  if (burger && menu) {
    var setMenu = function (open) { burger.setAttribute('aria-expanded', String(open)); menu.hidden = !open; document.documentElement.classList.toggle('menu-open', open); };
    burger.addEventListener('click', function () { setMenu(burger.getAttribute('aria-expanded') !== 'true'); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !menu.hidden) { setMenu(false); burger.focus(); } });
  }

  // ---------- product page options ----------
  var pdp = $('.pdp-form');
  if (pdp) {
    var price = $('[data-pdp-price]');
    var sync = function () {
      var r = $('[data-role=size]:checked', pdp);
      if (!r) return;
      var vi = $('[data-role=variation]', pdp); if (vi) vi.value = r.value; // hidden fields are absent on the static copy
      var as = $('[data-role=attr-size]', pdp); if (as) as.value = r.dataset.size;
      if (price) price.textContent = money(+r.dataset.price);
    };
    pdp.addEventListener('change', sync); sync();
    $$('[data-step]', pdp).forEach(function (b) {
      b.addEventListener('click', function () { var q = $('#qty', pdp); q.value = Math.min(9, Math.max(1, (+q.value || 1) + +b.dataset.step)); });
    });
  }

  // ---------- add to cart (static only; live forms submit to WooCommerce) ----------
  document.addEventListener('submit', function (e) {
    var f = e.target;
    if (!STATIC || !f.matches || !f.matches('.add-form')) return;
    e.preventDefault();
    var it = JSON.parse(f.dataset.item);
    var size = $('[data-role=size]:checked', f), grind = $('[data-role=grind]:checked', f), qty = $('#qty', f);
    add({ id: it.id, name: it.name, img: it.img, qty: Math.max(1, +(qty && qty.value) || 1),
      price: size ? +size.dataset.price : +it.price,
      variant: size ? size.dataset.size + ', ' + (grind ? grind.parentNode.textContent.trim().toLowerCase() : 'whole bean') : it.variant });
  }, true);

  // ---------- shop chips + sort (in-page filter; chips are real links without JS) ----------
  var shop = $('[data-shop]');
  if (shop) {
    var grid = $('.shop-grid', shop), cards = $$('.card', grid), countEl = $('.shop-count', shop), cur = ($('.chip.on', shop) || {}).dataset;
    var apply = function (cat) {
      var n = 0;
      cards.forEach(function (c) { var show = cat === 'all' || c.dataset.cat === cat; c.hidden = !show; if (show) n++; });
      countEl.textContent = 'Showing ' + n + (n === 1 ? ' product' : ' products');
    };
    $$('.chip', shop).forEach(function (chip) {
      chip.setAttribute('role', 'button'); chip.setAttribute('aria-pressed', chip.classList.contains('on') ? 'true' : 'false');
      chip.addEventListener('click', function (e) {
        e.preventDefault();
        $$('.chip', shop).forEach(function (c) { c.classList.toggle('on', c === chip); c.setAttribute('aria-pressed', c === chip ? 'true' : 'false'); c.removeAttribute('aria-current'); });
        apply(chip.dataset.cat);
      });
    });
    var sort = $('#sort', shop);
    if (sort) sort.addEventListener('change', function () {
      var v = sort.value;
      cards.slice().sort(function (a, b) {
        if (v === 'low') return a.dataset.price - b.dataset.price;
        if (v === 'high') return b.dataset.price - a.dataset.price;
        return a.dataset.order - b.dataset.order;
      }).forEach(function (c) { grid.appendChild(c); });
    });
  }

  // ---------- bundle builder ----------
  var bundle = $('[data-bundle]');
  if (bundle) {
    var boxes = $$('[data-role=pick]', bundle), MAX = 3;
    var st = $('[data-b-status]', bundle), bar = $('[data-b-bar]', bundle), slots = $('[data-b-slots]', bundle), cta = $('[data-b-cta]', bundle);
    var labels = ['1. Pick a coffee, or anything', '2. Pick another', '3. And one more'];
    var picked = [];
    var render = function () {
      picked = picked.filter(function (b) { return b.checked; });
      boxes.forEach(function (b) { if (b.checked && picked.indexOf(b) < 0) picked.push(b); });
      var full = picked.length >= MAX;
      boxes.forEach(function (b) { b.disabled = full && !b.checked; });
      var items = picked.reduce(function (s, b) { return s + +b.dataset.price; }, 0), disc = full ? items * RATE : 0;
      st.textContent = picked.length + ' of 3 picked' + (full ? ', bundle discount applied' : '');
      bar.style.width = (picked.length / MAX * 100) + '%';
      slots.innerHTML = '';
      for (var i = 0; i < MAX; i++) {
        var li = document.createElement('li'), b = picked[i];
        li.className = 'slot' + (b ? ' filled' : '');
        if (b) {
          li.innerHTML = (b.dataset.img ? '<img src="' + esc(b.dataset.img) + '" alt="" width="36" height="36">' : '') + '<span>' + esc(b.dataset.name) + '</span>';
          var rm = document.createElement('button'); rm.type = 'button'; rm.className = 'rm'; rm.textContent = '×';
          rm.setAttribute('aria-label', 'Remove ' + b.dataset.name);
          rm.addEventListener('click', (function (box) { return function () { box.checked = false; render(); box.focus(); }; })(b));
          li.appendChild(rm);
        } else li.textContent = labels[i];
        slots.appendChild(li);
      }
      $('[data-b-items]', bundle).textContent = money(items);
      $('[data-b-disc]', bundle).textContent = disc ? '−' + money(disc) : money(0);
      $('[data-b-total]', bundle).textContent = money(items - disc);
      cta.setAttribute('aria-disabled', String(!full));
      cta.textContent = full ? 'Add bundle to cart, ' + money(items - disc) : 'Pick 3 items to continue';
    };
    bundle.addEventListener('change', render);
    $$('[data-bcat]', bundle).forEach(function (chip) {
      chip.addEventListener('click', function () {
        $$('[data-bcat]', bundle).forEach(function (c) { var on = c === chip; c.classList.toggle('on', on); c.setAttribute('aria-pressed', String(on)); });
        $$('.pick-item', bundle).forEach(function (l) { l.hidden = chip.dataset.bcat !== 'all' && l.dataset.cat !== chip.dataset.bcat; });
      });
    });
    bundle.addEventListener('submit', function (e) {
      if (picked.length !== MAX) { e.preventDefault(); st.textContent = 'Pick 3 items to continue (' + picked.length + ' of 3 picked)'; return; }
      if (!STATIC) return; // live: WooCommerce adds the three items and the 12 percent fee
      e.preventDefault();
      var items = picked.reduce(function (s, b) { return s + +b.dataset.price; }, 0);
      add({ id: 'bundle-' + Date.now(), bundle: true, name: '3-item bundle', qty: 1, img: picked[0].dataset.img,
        price: Math.round(items * (1 - RATE) * 100) / 100,
        variant: picked.map(function (b) { return b.dataset.name; }).join(', ') });
      boxes.forEach(function (b) { b.checked = false; }); render();
    });
    render();
  }

  // ---------- simulated cart / checkout / confirmation pages ----------
  function lineHtml(l, i, controls) {
    return '<div class="line">' + (l.img ? '<img src="' + esc(l.img) + '" alt="" width="72" height="72">' : '<span class="thumb-ph"></span>') +
      '<div><div class="t">' + esc(l.name) + (l.qty > 1 ? ' × ' + l.qty : '') + '</div><div class="s">' + esc(l.variant) + (l.bundle ? '<br><span class="save">Bundle discount applied</span>' : '') + '</div></div>' +
      (controls ? '<div class="controls"><span class="price">' + money(l.price * l.qty) + '</span><button class="rm" type="button" data-rm="' + i + '">Remove<span class="sr"> ' + esc(l.name) + '</span></button></div>' : '<span class="price">' + money(l.price * l.qty) + '</span>') + '</div>';
  }
  function totalsHtml(t, c) {
    return '<div><span>Subtotal</span><span>' + money(t.sub) + '</span></div>' +
      (c.coupon ? '<div><span>Coupon ' + COUPON + ' (10 percent)</span><span class="save">−' + money(t.disc) + '</span></div>' : '') +
      '<div><span>Shipping</span><span' + (t.ship ? '>' + money(t.ship) : ' class="save">Free') + '</span></div>' +
      '<div class="big"><span>Total</span><span>' + money(t.total) + '</span></div>';
  }
  function renderCart(root, controls) {
    var c = load(), t = totals(c), lines = $('[data-cart-lines]', root), tot = $('[data-cart-totals]', root);
    if (lines) lines.innerHTML = c.lines.length ? c.lines.map(function (l, i) { return lineHtml(l, i, controls); }).join('') : '<p class="empty">Your cart is empty. <a class="inline-44" href="' + esc(relUrl('shop/')) + '">Browse the coffee</a></p>';
    if (tot) tot.innerHTML = totalsHtml(t, c);
    var go = $('[data-cart-checkout]', root); if (go) go.setAttribute('aria-disabled', String(!c.lines.length));
    var place = $('[data-place-order]', root); if (place) place.textContent = 'Place test order, ' + money(t.total);
  }
  var simCart = $('[data-sim-cart]');
  if (simCart) {
    renderCart(simCart, true);
    simCart.addEventListener('click', function (e) {
      var b = e.target.closest('[data-rm]'); if (!b) return;
      var c = load(); c.lines.splice(+b.dataset.rm, 1); save(c); renderCart(simCart, true);
      var first = $('[data-rm]', simCart) || $('#coupon'); if (first) first.focus();
    });
    $('[data-checkout-cta]');
    $('[data-cart-checkout]', simCart).addEventListener('click', function (e) { if (!load().lines.length) e.preventDefault(); });
    $('[data-coupon]', simCart).addEventListener('submit', function (e) {
      e.preventDefault();
      var v = $('#coupon').value.trim().toUpperCase(), msg = $('[data-coupon-msg]'), c = load();
      if (v === COUPON) { c.coupon = true; save(c); msg.textContent = 'FIRSTBAG applied: 10 percent off.'; }
      else msg.textContent = v ? 'That code is not valid in the demo. Try FIRSTBAG.' : 'Enter a code.';
      renderCart(simCart, true);
    });
  }
  var simCo = $('[data-sim-checkout]');
  if (simCo) {
    renderCart(simCo, false);
    var form = $('[data-checkout-form]', simCo);
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      if (!load().lines.length) { alert('Your demo cart is empty.'); return; }
      $$('.field', form).forEach(function (f) { f.classList.remove('err'); var m = $('.msg', f); if (m) m.remove(); });
      var bad = $$('input[required], select[required]', form).filter(function (i) { return !i.checkValidity(); });
      if (bad.length) {
        bad.forEach(function (i) { var f = i.closest('.field'); f.classList.add('err'); i.setAttribute('aria-invalid', 'true'); var m = document.createElement('p'); m.className = 'msg'; m.id = i.id + '-msg'; m.textContent = i.validationMessage || 'Please fill in this field.'; f.appendChild(m); i.setAttribute('aria-describedby', m.id); });
        bad[0].focus(); return;
      }
      var c = load(), t = totals(c);
      // Kept in this tab only (sessionStorage) so the confirmation page can greet the shopper; never sent anywhere.
      sessionStorage.setItem(ORDER, JSON.stringify({ n: 'LH-' + (10000 + Math.floor(Math.random() * 90000)), first: $('#co-first').value.trim(), city: $('#co-city').value.trim(), lines: c.lines, totals: t, coupon: c.coupon }));
      sessionStorage.removeItem(KEY);
      location.href = relUrl('demo-order/');
    });
  }
  var simConfirm = $('[data-sim-confirm]');
  if (simConfirm) {
    var o = null; try { o = JSON.parse(sessionStorage.getItem(ORDER)); } catch (e) {}
    if (o) {
      $('[data-confirm-title]').textContent = 'Thanks' + (o.first ? ', ' + o.first : '') + '. Your coffee roasts Thursday.';
      $('[data-confirm-lead]').innerHTML = 'Test order <b>' + esc(o.n) + '</b>. Nothing was charged and no email is sent: this demo order only exists in this browser tab.';
      var box = $('[data-confirm-order]');
      box.innerHTML = o.lines.map(function (l) { return lineHtml(l, 0, false); }).join('') + '<div class="totals" style="margin-top:12px">' + totalsHtml(o.totals, { coupon: o.coupon }) + '<div><span>Paid with</span><span>Test order, no payment taken</span></div></div>';
      box.hidden = false;
    }
  }
  badge();
})();
