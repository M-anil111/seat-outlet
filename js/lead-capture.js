/* Email capture: binds every form.so-lead-form (see soLeadForm in inc/leads.php). reCAPTCHA v3 is loaded on first use. */
(function () {
  var forms = document.querySelectorAll('form.so-lead-form');
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
      grecaptcha.ready(function () { grecaptcha.execute(RECAPTCHA_SITE_KEY, { action: 'lead' }).then(cb, function () { cb(''); }); });
    });
  }
  forms.forEach(function (form) {
    var msg = form.querySelector('.so-nl__msg'), btn = form.querySelector('button[type=submit]');
    function say(t, ok) { msg.textContent = t; msg.hidden = false; msg.classList.toggle('is-ok', !!ok); }
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      msg.hidden = true; btn.disabled = true;
      token(function (t) {
        var fd = new FormData(form);
        fd.append('token', t);
        fd.append('source', form.dataset.source || 'site');
        fd.append('interest_type', form.dataset.interestType || '');
        fd.append('interest_id', form.dataset.interestId || '0');
        fd.append('interest_name', form.dataset.interestName || '');
        fd.append('page', location.pathname);
        if (form.dataset.alertKind) { fd.append('alert_kind', form.dataset.alertKind); fd.append('baseline_price', form.dataset.baselinePrice || ''); }
        fetch('/ajax/subscribe.php', { method: 'POST', body: fd, headers: { 'Accept': 'application/json' } })
          .then(function (r) { return r.json(); })
          .then(function (d) {
            btn.disabled = false;
            if (d.status === 'success' || d.status === 'duplicate') {
              say(d.message || (d.status === 'duplicate' ? 'You are already on the list. Thank you!' : 'You are in. Watch your inbox.'), true);
              form.querySelectorAll('input').forEach(function (i) { i.disabled = true; }); btn.disabled = true;
              (window.dataLayer = window.dataLayer || []).push({ event: 'generate_lead', lead_source: form.dataset.source || 'site', lead_interest: form.dataset.interestType || '' });
            } else if (d.status === 'invalid') say(d.message || 'Please check your email address and try again.');
            else if (d.status === 'rate') say(d.message || 'Too many tries. Please wait a few minutes.');
            else say(d.message || 'Something went wrong. Please try again.');
          })
          .catch(function () { btn.disabled = false; say('Something went wrong. Please try again.'); });
      });
    });
  });
})();
