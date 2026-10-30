// Demo-only form handler: validates in the browser and shows an inline success state.
// Nothing is sent anywhere. Remove this file to let Contact Form 7 submit normally.
document.addEventListener('submit', function (e) {
  var form = e.target.closest('.wpcf7-form');
  if (!form) return;
  e.preventDefault();
  e.stopImmediatePropagation();
  form.querySelectorAll('[aria-required="true"], .wpcf7-acceptance input').forEach(function (el) { el.required = true; });
  if (!form.checkValidity()) { form.reportValidity(); return; }
  var box = document.createElement('div');
  box.className = 'form-success';
  box.setAttribute('role', 'status');
  box.setAttribute('tabindex', '-1');
  box.innerHTML = '<strong>Thanks, your request is in.</strong><br>This is a demo form, so nothing was sent. On a live site the team would reply within one business day.';
  form.replaceWith(box);
  box.focus();
}, true);
