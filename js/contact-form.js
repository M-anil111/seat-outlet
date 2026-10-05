/* Contact form (see inc/contact.php): checks the fields, gets a reCAPTCHA v3 token and posts the form with fetch.
   Without JavaScript the form still posts normally, but the server cannot verify the visitor then and asks them to email us. */
(function () {
  var forms = document.querySelectorAll('form.so-cf__form');
  if (!forms.length) return;
  var loaded = false;
  function loadRecaptcha(cb) {
    if (typeof RECAPTCHA_SITE_KEY === 'undefined') return cb(null);
    if (loaded) return cb();
    var s = document.createElement('script');
    s.src = 'https://www.google.com/recaptcha/api.js?render=' + RECAPTCHA_SITE_KEY;
    s.async = true; s.onload = function () { loaded = true; cb(); }; s.onerror = function () { cb(null); };
    document.body.appendChild(s);
  }
  function token(cb) {
    loadRecaptcha(function (e) {
      if (e === null || typeof grecaptcha === 'undefined') return cb('');
      grecaptcha.ready(function () { grecaptcha.execute(RECAPTCHA_SITE_KEY, { action: 'contact' }).then(cb, function () { cb(''); }); });
    });
  }
  forms.forEach(function (form) {
    var p = form.dataset.prefix || 'cf';
    var alertBox = form.querySelector('.so-cf__alert');
    var btn = form.querySelector('button[type=submit]');
    var label = btn.textContent;
    var messages = {
      firstName: 'Enter your first name.', lastName: 'Enter your last name.',
      email: 'Enter a valid email address, like name@example.com.', subject: 'Choose a topic.',
      message: 'Tell us a little more (at least 10 characters).'
    };
    function fieldError(name, text) {
      var input = form.elements[name], box = document.getElementById(p + '-' + name + '-err');
      if (!input || !box) return;
      box.textContent = text || ''; box.hidden = !text;
      if (text) { box.setAttribute('role', 'alert'); input.setAttribute('aria-invalid', 'true'); } else { input.removeAttribute('aria-invalid'); }
      var g = input.closest('.so-cf__field'); if (g) g.classList.toggle('has-error', !!text);
    }
    function clearErrors() { Object.keys(messages).concat(['phone', 'orderId']).forEach(function (n) { fieldError(n, ''); }); }
    function say(text) { alertBox.textContent = text; alertBox.hidden = false; alertBox.focus(); }
    function check() {
      clearErrors();
      var first = null;
      Object.keys(messages).forEach(function (n) {
        var input = form.elements[n];
        if (input && !input.validity.valid) { fieldError(n, messages[n]); first = first || input; }
      });
      if (first) { say('Please fix the highlighted fields and try again.'); first.focus(); return false; }
      alertBox.hidden = true;
      return true;
    }
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      if (!check()) return;
      btn.disabled = true; btn.textContent = 'Sending...';
      token(function (t) {
        form.elements.recaptcha_token.value = t;
        fetch(form.getAttribute('action') || location.pathname, { method: 'POST', body: new FormData(form), headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
          .then(function (r) { return r.json().catch(function () { return { status: 'error' }; }); })
          .then(function (d) {
            if (d.status === 'success') {
              var wrap = document.getElementById(p + '-wrap');
              wrap.innerHTML = '<div class="so-cf so-cf--done"><div class="so-cf__ok" role="status" tabindex="-1"><h2>Message sent</h2><p></p></div></div>';
              wrap.querySelector('p').textContent = d.message || 'Thank you. Your message has been sent.';
              wrap.querySelector('.so-cf__ok').focus();
              (window.dataLayer = window.dataLayer || []).push({ event: 'contact_form_submit', form_page: location.pathname });
              return;
            }
            btn.disabled = false; btn.textContent = label;
            clearErrors();
            if (d.errors) Object.keys(d.errors).forEach(function (n) { fieldError(n, d.errors[n]); });
            say(d.message || 'Something went wrong. Please try again, or email us.');
          })
          .catch(function () { btn.disabled = false; btn.textContent = label; say('We could not send your message. Check your connection and try again, or email us.'); });
      });
    });
  });
})();
