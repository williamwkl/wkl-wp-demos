// Saltline six-step booking flow over the availability rendered into data-avail.
// Live WordPress: "Hold my seats" posts the form and Events Manager creates the booking.
// Static copy (<html data-static-export>): the Done screen is shown in the page; nothing is stored or sent.
(function () {
  'use strict';
  var root = document.querySelector('[data-booking]');
  if (!root) return;
  var STATIC = document.documentElement.hasAttribute('data-static-export');
  var A = JSON.parse(root.dataset.avail);
  var $ = function (s, r) { return (r || root).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || root).querySelectorAll(s)); };
  var esc = function (s) { var d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; };
  var TOURS = {}; A.tours.forEach(function (t) { TOURS[t.key] = t; });
  var ORDER = ['tour', 'date', 'time', 'party', 'details', 'confirm', 'done'];
  var st = { step: 'tour', tour: null, date: null, dep: null, adults: 2, kids: 0 };
  var form = $('.book-form');

  var fmtDate = function (iso, opts) { var p = iso.split('-'); return new Date(+p[0], +p[1] - 1, +p[2]).toLocaleDateString('en-US', opts || { weekday: 'long', month: 'long', day: 'numeric' }); };
  var fmtTime = function (hm) { var h = +hm.slice(0, 2), m = hm.slice(3); return ((h % 12) || 12) + ':' + m + (h < 12 ? ' am' : ' pm'); };
  var meet = function (hm) { var h = +hm.slice(0, 2), m = +hm.slice(3) - 15; if (m < 0) { m += 60; h--; } return fmtTime(('0' + h).slice(-2) + ':' + ('0' + m).slice(-2)); };
  var deps = function (tour, date) { return A.departures.filter(function (d) { return d.tour === tour && (!date || d.date === date); }); };
  var paddlers = function () { return st.adults + st.kids; };
  var price = function () { return st.tour ? TOURS[st.tour].price : 0; };

  function show(step, focus) {
    st.step = step;
    $$('[data-s]').forEach(function (s) { s.hidden = s.dataset.s !== step; });
    var i = ORDER.indexOf(step);
    $$('.stepper li').forEach(function (li) {
      var j = ORDER.indexOf(li.dataset.step);
      li.classList.toggle('done', j < i); li.classList.toggle('on', j === i);
      if (j === i) li.setAttribute('aria-current', 'step'); else li.removeAttribute('aria-current');
    });
    summary();
    if (focus !== false) { var h = $('[data-s="' + step + '"] h2'); if (h) h.focus(); }
  }

  // ---- step 1: tour
  $$('.tour').forEach(function (b) {
    b.addEventListener('click', function () {
      st.tour = b.dataset.tour; st.date = null; st.dep = null;
      $$('.tour').forEach(function (x) { x.classList.toggle('on', x === b); x.setAttribute('aria-pressed', String(x === b)); });
      renderCal(); show('date', !b.dataset.auto); // no focus move on the automatic pre-selection at page load
      delete b.dataset.auto;
    });
  });

  // ---- step 2: calendar (one sample month)
  function renderCal() {
    $('[data-tourname]').textContent = TOURS[st.tour].name;
    var y = +A.month.slice(0, 4), m = +A.month.slice(5) - 1;
    $('[data-monthname]').textContent = new Date(y, m, 1).toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
    var first = (new Date(y, m, 1).getDay() + 6) % 7, days = new Date(y, m + 1, 0).getDate(), today = +A.today.slice(8);
    var html = '<tr>', col = 0;
    for (var i = 0; i < first; i++) { html += '<td></td>'; col++; }
    for (var d = 1; d <= days; d++) {
      var iso = A.month + '-' + ('0' + d).slice(-2), ds = deps(st.tour, iso), left = ds.reduce(function (n, x) { return Math.max(n, x.left); }, 0);
      var name = new Date(y, m, d).toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric' });
      var cell;
      if (d < today) cell = '<span class="day past" aria-label="' + name + ', past">' + d + '</span>';
      else if (!ds.length) cell = '<span class="day off" aria-label="' + name + ', no departure">' + d + '</span>';
      else if (!left) cell = '<span class="day full" aria-label="' + name + ', sold out">' + d + '<small>Full</small></span>';
      else cell = '<button type="button" class="day' + (left <= 3 ? ' few' : '') + (st.date === iso ? ' on' : '') + '" data-date="' + iso + '"' + (st.date === iso ? ' aria-current="date"' : '') + ' aria-label="' + name + ', ' + left + (left === 1 ? ' seat' : ' seats') + ' left"><span>' + d + '</span><span class="dot" aria-hidden="true"></span><small aria-hidden="true">' + left + ' left</small></button>';
      html += '<td>' + cell + '</td>';
      if (++col === 7 && d < days) { html += '</tr><tr>'; col = 0; }
    }
    while (col < 7) { html += '<td></td>'; col++; }
    $('[data-cal]').innerHTML = html + '</tr>';
    var next = $('[data-s="date"] [data-next]');
    next.setAttribute('aria-disabled', String(!st.date));
    next.textContent = st.date ? 'Continue with ' + fmtDate(st.date, { month: 'long', day: 'numeric' }) : 'Pick a date to continue';
  }
  $('[data-cal]').addEventListener('click', function (e) {
    var b = e.target.closest('button.day'); if (!b) return;
    st.date = b.dataset.date; st.dep = null; renderCal();
    var nb = $('[data-cal] [data-date="' + st.date + '"]'); if (nb) nb.focus();
  });
  $$('[data-month]').forEach(function (b) { b.addEventListener('click', function () { $('[data-month-note]').hidden = false; }); });

  // ---- step 3: departures
  function renderSlots() {
    $('[data-datename]').textContent = fmtDate(st.date);
    $('[data-slots]').innerHTML = deps(st.tour, st.date).map(function (d) {
      var full = !d.left, cls = 'slot' + (st.dep && st.dep.event === d.event ? ' on' : '') + (full ? ' full' : '');
      var pill = full ? '<span class="pill gone">Sold out</span>' : '<span class="pill' + (d.left <= 3 ? ' warn' : '') + '">' + d.left + (d.left === 1 ? ' seat' : ' seats') + ' left</span>';
      return '<div role="listitem"><button type="button" class="' + cls + '" data-event="' + d.event + '"' + (full ? ' disabled' : '') + ' aria-pressed="' + (st.dep && st.dep.event === d.event) + '"><span><b>' + fmtTime(d.time) + '</b><span class="cap">' + esc(d.note) + '</span></span>' + pill + '</button></div>';
    }).join('');
    var next = $('[data-s="time"] [data-next]');
    next.setAttribute('aria-disabled', String(!st.dep));
    next.textContent = st.dep ? 'Continue with ' + fmtTime(st.dep.time) : 'Pick a departure';
  }
  $('[data-slots]').addEventListener('click', function (e) {
    var b = e.target.closest('button.slot'); if (!b || b.disabled) return;
    st.dep = A.departures.filter(function (d) { return d.event === +b.dataset.event; })[0];
    st.adults = Math.min(st.adults, st.dep.left); st.kids = Math.min(st.kids, st.dep.left - st.adults, st.adults);
    renderSlots(); var nb = $('[data-slots] [data-event="' + st.dep.event + '"]'); if (nb) nb.focus();
  });

  // ---- step 4: party (capacity, one adult per kid)
  function renderParty() {
    var left = st.dep.left, n = paddlers();
    $('[data-price]').textContent = '$' + price();
    $('[data-adults]').textContent = st.adults; $('[data-kids]').textContent = st.kids;
    $('[data-ad="-1"]').disabled = st.adults <= 1; $('[data-kd="-1"]').disabled = st.kids <= 0;
    $('[data-ad="1"]').disabled = n >= left; $('[data-kd="1"]').disabled = n >= left || st.kids >= st.adults;
    $('[data-captext]').textContent = n + ' of ' + left + ' remaining seats on this departure';
    $('[data-capbar]').style.width = (n / left * 100) + '%';
    $('[data-capnote]').hidden = n < left;
    $('[data-s="party"] [data-next]').textContent = 'Continue, ' + n + (n === 1 ? ' paddler' : ' paddlers');
    summary();
  }
  $$('[data-ad],[data-kd]').forEach(function (b) {
    b.addEventListener('click', function () {
      if (b.dataset.ad) { st.adults += +b.dataset.ad; if (st.kids > st.adults) st.kids = st.adults; } else st.kids += +b.dataset.kd;
      renderParty();
    });
  });

  // ---- summary card
  function summary() {
    var t = st.tour && TOURS[st.tour], items = [];
    var row = function (ok, title, sub) { return '<li class="' + (ok ? 'ok' : 'todo') + '">' + (ok ? '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m5 12 5 5L20 7"/></svg>' : '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="8"/></svg>') + '<span><b>' + esc(title) + '</b>' + esc(sub || '') + '</span></li>'; };
    items.push(row(!!t, t ? t.name : 'Tour', t ? t.dur.toLowerCase() + ', ' + t.level.toLowerCase() + ', $' + t.price + ' per person' : 'Not chosen yet'));
    items.push(row(!!st.dep, st.dep ? fmtDate(st.date, { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' }) : (st.date ? fmtDate(st.date) : 'Date and time'), st.dep ? fmtTime(st.dep.time) + ' departure, meet ' + meet(st.dep.time) : (st.date ? 'Pick a departure' : 'Not chosen yet')));
    var reached = ORDER.indexOf(st.step) >= ORDER.indexOf('party');
    items.push(row(reached, reached ? paddlers() + (paddlers() === 1 ? ' paddler' : ' paddlers') : 'Paddlers', reached ? st.adults + (st.adults === 1 ? ' adult' : ' adults') + (st.kids ? ', ' + st.kids + (st.kids === 1 ? ' kid' : ' kids') : '') : 'Not chosen yet'));
    var name = ($('#f-name') || {}).value;
    items.push(row(ORDER.indexOf(st.step) >= ORDER.indexOf('confirm') && !!name, 'Details', ORDER.indexOf(st.step) >= ORDER.indexOf('confirm') && name ? 'Lead paddler ' + name : 'Name and contact'));
    $('[data-sum]').innerHTML = items.join('');
    $('[data-total]').textContent = '$' + (reached ? paddlers() * price() : 0);
  }

  // ---- step 6: review
  function renderReview() {
    var t = TOURS[st.tour];
    var rows = [['Tour', t.name], ['Departure', fmtDate(st.date, { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' }) + ', ' + fmtTime(st.dep.time)], ['Meet', A.location + ', ' + meet(st.dep.time)],
      ['Paddlers', st.adults + (st.adults === 1 ? ' adult' : ' adults') + (st.kids ? ', ' + st.kids + (st.kids === 1 ? ' kid (8 to 12)' : ' kids (8 to 12)') : '')],
      ['Lead paddler', $('#f-name').value + ', ' + $('#f-email').value], ['Total due at check-in', '$' + (paddlers() * price()).toFixed(2)]];
    $('[data-review]').innerHTML = rows.map(function (r) { return '<div><span>' + esc(r[0]) + '</span><span>' + esc(r[1]) + '</span></div>'; }).join('');
  }

  // ---- navigation
  $$('[data-next]').forEach(function (b) {
    b.addEventListener('click', function () {
      if (b.getAttribute('aria-disabled') === 'true') return;
      var cur = st.step;
      if (cur === 'date') { renderSlots(); show('time'); }
      else if (cur === 'time') { renderParty(); show('party'); }
      else if (cur === 'party') show('details');
      else if (cur === 'details') {
        var bad = $$('[data-s="details"] [required]').filter(function (i) { return !i.checkValidity(); });
        if (bad.length) { bad[0].reportValidity(); return; }
        renderReview(); show('confirm');
      }
    });
  });
  $$('[data-back]').forEach(function (b) { b.addEventListener('click', function () { show(ORDER[Math.max(0, ORDER.indexOf(st.step) - 1)]); }); });

  form.addEventListener('submit', function (e) {
    if (st.step !== 'confirm' || !st.dep) { e.preventDefault(); return; }
    var set = function (sel, v) { var i = $(sel); if (i) i.value = v; }; // hidden fields are stripped from the static copy
    set('[data-f-event]', st.dep.event); set('[data-f-adults]', st.adults); set('[data-f-kids]', st.kids);
    if (!STATIC) return; // live: Events Manager creates the booking
    e.preventDefault();
    done({ n: 'SL-' + A.month.slice(0, 4) + '-' + String(1000 + Math.floor(Math.random() * 9000)), name: $('#f-name').value.trim(), email: $('#f-email').value.trim(), tour: st.tour, date: st.date, time: st.dep.time, adults: st.adults, kids: st.kids }, true);
  });

  function done(d, demo) {
    var t = TOURS[d.tour], n = d.adults + d.kids, first = d.name.split(' ')[0], when = fmtDate(d.date, { weekday: 'short', month: 'long', day: 'numeric', year: 'numeric' }) + ', ' + fmtTime(d.time);
    var words = ['Zero', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight'][n] || n;
    $('[data-done-title]').textContent = 'See you on the water, ' + first + '.';
    $('[data-done-lead]').innerHTML = 'Booking <b>' + esc(d.n) + '</b>. ' + esc(words) + (n === 1 ? ' seat is' : ' seats are') + ' held on the ' + esc(fmtTime(d.time)) + ' ' + esc(t.name) + ', ' + esc(fmtDate(d.date, { weekday: 'long', month: 'long', day: 'numeric' })) + '. ' + (demo ? 'Demo: this booking only exists in this page; nothing was sent or saved, and no email goes out.' : 'The confirmation email below has been sent (in this Playground demo it goes to the mail log).');
    $('[data-email]').innerHTML = '<div class="meta"><span><b>From:</b> Saltline Kayak Tours &lt;hello@saltlinekayak.example.com&gt;</span><span><b>To:</b> ' + esc(d.email) + '</span><span><b>Subject:</b> You are booked: ' + esc(t.name) + ', ' + esc(when) + '</span></div>' +
      '<div class="body"><p class="brandrow">Saltline</p><h3>' + esc(words) + (n === 1 ? ' seat' : ' seats') + ' held.</h3><p>Hi ' + esc(first) + ', thanks for booking with us. Here is everything you need; keep this email, there is nothing to print.</p>' +
      '<div class="card"><div><span>Tour</span><b>' + esc(t.name) + '</b></div><div><span>When</span><b>' + esc(when) + '</b></div><div><span>Arrive by</span><b>' + esc(meet(d.time)) + '</b></div><div><span>Where</span><b>' + esc(A.location) + '</b></div><div><span>Paddlers</span><b>' + d.adults + (d.adults === 1 ? ' adult' : ' adults') + (d.kids ? ', ' + d.kids + (d.kids === 1 ? ' kid' : ' kids') : '') + '</b></div><div><span>Due at check-in</span><b>$' + (n * t.price).toFixed(2) + '</b></div><div><span>Booking</span><b>' + esc(d.n) + '</b></div></div>' +
      '<p><b>What to bring:</b> swimwear or quick-dry layers to wear under the wetsuit, a towel, sunglasses with a strap, water. We supply the kayak, paddle, spray skirt, wetsuit, dry bag and the guide.</p><p style="margin-top:10px"><b>Weather:</b> if the forecast turns we email you by 8 pm the night before with a new time or a full refund.</p></div>' +
      '<div class="foot">Saltline Kayak Tours, the boathouse at Marrow Cove public launch. (555) 010-0100. Free changes up to 24 hours before departure.</div>';
    st.tour = d.tour; st.date = d.date; st.dep = A.departures.filter(function (x) { return x.tour === d.tour && x.date === d.date && x.time === d.time; })[0] || st.dep; st.adults = d.adults; st.kids = d.kids;
    show('done');
    root.classList.add('is-done');
    $$('.stepper li').forEach(function (li) { li.classList.add('done'); li.classList.remove('on'); li.removeAttribute('aria-current'); });
  }
  $('[data-again]').addEventListener('click', function () { root.classList.remove('is-done'); st = { step: 'tour', tour: null, date: null, dep: null, adults: 2, kids: 0 }; $$('.tour').forEach(function (x) { x.classList.remove('on'); x.setAttribute('aria-pressed', 'false'); }); show('tour'); });

  // ---- start: confirmation (live redirect), pre-selected tour (?tour=slug), or step 1
  if (root.dataset.done) { done(JSON.parse(root.dataset.done), false); return; }
  var pre = new URLSearchParams(location.search).get('tour') || root.dataset.pre;
  var preKey = A.tours.filter(function (t) { return t.slug === pre || t.key === pre; })[0];
  if (preKey) { var b = $('.tour[data-tour="' + preKey.key + '"]'); if (b) { b.dataset.auto = '1'; b.click(); return; } }
  show('tour', false);
})();
