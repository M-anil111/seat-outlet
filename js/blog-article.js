/* Blog article: newsletter sign-up boxes (.so-nl). Same checks as the homepage form: reCAPTCHA v3, duplicate check, then the normal post. */
(function () {
  var forms = document.querySelectorAll('.so-nl__form');
  if (!forms.length || typeof RECAPTCHA_SITE_KEY === 'undefined') return;
  var loaded = false;

  function load(cb) {
    if (loaded) return cb();
    var s = document.createElement('script');
    s.src = 'https://www.google.com/recaptcha/api.js?render=' + RECAPTCHA_SITE_KEY;
    s.async = true;
    s.onload = function () { loaded = true; cb(); };
    document.body.appendChild(s);
  }

  forms.forEach(function (form) {
    var err = form.querySelector('.so-nl__err');
    var btn = form.querySelector('button');
    function fail(msg) { btn.disabled = false; err.textContent = msg; err.hidden = false; }

    form.addEventListener('submit', function (e) {
      e.preventDefault();
      err.hidden = true;
      btn.disabled = true;
      load(function () {
        grecaptcha.ready(function () {
          grecaptcha.execute(RECAPTCHA_SITE_KEY, { action: 'newsletter' }).then(function (token) {
            form.querySelector('[name=token]').value = token;
            fetch('/ajax/check-email.php', { method: 'POST', body: new FormData(form) })
              .then(function (r) { return r.json(); })
              .then(function (d) {
                if (d.status === 'success') { form.submit(); }
                else if (d.status === 'duplicate') { fail('This email is already subscribed.'); }
                else if (d.status === 'recaptcha') { fail('reCAPTCHA failed. Please try again.'); }
                else { fail('Please check your email address and try again.'); }
              })
              .catch(function () { fail('Something went wrong. Please try again.'); });
          });
        });
      });
    });
  });
})();
