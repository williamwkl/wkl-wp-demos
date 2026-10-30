// Harbor & Pine: mobile menu disclosure, sticky bar that hides while a form is in view,
// and the demo form handler (validates in the browser, shows the success state, sends nothing).
(function () {
  var btn = document.querySelector('.menu-toggle'), menu = document.getElementById('mobile-menu');
  if (btn && menu) {
    var setOpen = function (open) {
      btn.setAttribute('aria-expanded', String(open));
      menu.hidden = !open;
      document.documentElement.classList.toggle('menu-open', open);
    };
    btn.addEventListener('click', function () { setOpen(btn.getAttribute('aria-expanded') !== 'true'); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !menu.hidden) { setOpen(false); btn.focus(); } });
    menu.addEventListener('click', function (e) { if (e.target.closest('a')) setOpen(false); });
  }
  var bar = document.querySelector('.sticky-bar'), forms = document.querySelectorAll('.frm-fluent-form');
  if (bar && forms.length && 'IntersectionObserver' in window) {
    var visible = new Set();
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) { en.isIntersecting ? visible.add(en.target) : visible.delete(en.target); });
      var hide = visible.size > 0;
      bar.classList.toggle('is-hidden', hide);
      // Off-screen links must not take focus or be announced.
      if (hide) { bar.setAttribute('inert', ''); bar.setAttribute('aria-hidden', 'true'); }
      else { bar.removeAttribute('inert'); bar.removeAttribute('aria-hidden'); }
    });
    forms.forEach(function (f) { io.observe(f); });
  }
  document.addEventListener('submit', function (e) {
    var form = e.target.closest('.frm-fluent-form');
    if (!form) return;
    e.preventDefault(); e.stopImmediatePropagation();
    form.querySelectorAll('.ff-el-is-required').forEach(function (lab) {
      var group = lab.closest('.ff-el-group');
      group && group.querySelectorAll('input:not([type=hidden]),select,textarea').forEach(function (el) { el.required = true; });
    });
    form.querySelectorAll('.ff-el-tc input[type=checkbox], [data-name="consent"] input[type=checkbox]').forEach(function (el) { el.required = true; });
    if (!form.checkValidity()) { form.reportValidity(); return; }
    var box = document.createElement('div');
    box.className = 'form-success'; box.setAttribute('role', 'status'); box.setAttribute('tabindex', '-1');
    box.innerHTML = '<strong>Thank you. Someone from our office will call you within one business day.</strong><br><span>Demo form: this is a portfolio sample, so nothing was sent.</span>';
    form.replaceWith(box); box.focus();
  }, true);
})();
