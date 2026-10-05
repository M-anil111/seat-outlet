/* Privacy choices (US opt-out model). Loaded on every page when a Google Tag Manager id is configured.
   - The head script (inc/consent.php) has already decided whether analytics and advertising tags load: they load unless the
     browser sends Global Privacy Control or the visitor chose Decline. This file draws the privacy bar, stores the choice in the
     first-party cookie so_consent (accept | decline, one year) and reopens the bar from the footer link "Your privacy choices".
   - Accept (or leaving the bar alone) keeps tags on; Decline turns them off for next time and removes analytics cookies set by
     Google tags on this site. Global Privacy Control always wins, so no bar is shown and the footer link only explains it.
   - Google Maps (location search) and reCAPTCHA (form protection) load only when the visitor uses a location field or a form. */
(function () {
  var st = window.soConsent || { tags: false, gpc: false, choice: '', allowed: true };
  if (!st.tags) return;
  var bar = null, lastFocus = null;

  function setChoice(v) {
    var secure = location.protocol === 'https:' ? ';Secure' : '';
    document.cookie = 'so_consent=' + v + ';path=/;max-age=31536000;SameSite=Lax' + secure;
    st.choice = v;
  }
  function clearTagCookies() {
    var host = location.hostname, parts = host.split('.'), domains = ['', host];
    if (parts.length > 2) domains.push('.' + parts.slice(-2).join('.'));
    domains.push('.' + host);
    document.cookie.split('; ').forEach(function (c) {
      var n = c.split('=')[0];
      if (!/^(_ga|_gid|_gat|_gcl_|_fbp|_fbc|_uet|_clck|_clsk)/.test(n)) return;
      domains.forEach(function (d) { document.cookie = n + '=;path=/;max-age=0' + (d ? ';domain=' + d : ''); });
    });
  }
  function push(choice) { (window.dataLayer = window.dataLayer || []).push({ event: 'so_consent', consent_choice: choice }); }

  function close(restore) {
    if (!bar) return;
    bar.remove(); bar = null;
    if (restore && lastFocus && lastFocus.focus) { try { lastFocus.focus(); } catch (e) {} }
  }

  function decide(choice) {
    var was = st.allowed;
    setChoice(choice);
    var nowAllowed = !st.gpc && choice !== 'decline';
    st.allowed = nowAllowed;
    push(choice);
    if (nowAllowed && !was) {
      (function () { (window.dataLayer = window.dataLayer || []).push(arguments); })('consent', 'update', { ad_storage: 'granted', ad_user_data: 'granted', ad_personalization: 'granted', analytics_storage: 'granted' });
      if (window.soLoadGtm) window.soLoadGtm();
    }
    if (!nowAllowed) { clearTagCookies(); if (window.soGtmLoaded) { close(false); location.reload(); return; } }
    close(true);
  }

  function open(manage) {
    if (bar) { var f = bar.querySelector('button, input'); if (f) f.focus(); return; }
    lastFocus = document.activeElement;
    bar = document.createElement('section');
    bar.className = 'so-consent';
    bar.setAttribute('role', 'region');
    bar.setAttribute('aria-label', 'Privacy choices');
    var gpcOn = st.gpc;
    var body = gpcOn
      ? '<p class="so-consent__text" id="soConsentText">Your browser is sending a Global Privacy Control signal. We honor it: analytics and advertising cookies are off on this device.</p>' +
        '<div class="so-consent__actions"><button type="button" class="so-consent__btn so-consent__btn--primary" data-so-consent="close">OK</button></div>'
      : '<p class="so-consent__text" id="soConsentText">We use cookies for analytics and ads to improve Seat Outlet. You can accept, decline or manage them. See our <a href="/cookie-policy">cookie policy</a>.</p>' +
        '<div class="so-consent__actions">' +
        '<button type="button" class="so-consent__btn so-consent__btn--primary" data-so-consent="accept">Accept</button>' +
        '<button type="button" class="so-consent__btn so-consent__btn--primary" data-so-consent="decline">Decline</button>' +
        '<button type="button" class="so-consent__btn" data-so-consent="manage" aria-expanded="false" aria-controls="soConsentManage">Manage</button></div>' +
        '<div class="so-consent__manage" id="soConsentManage" hidden>' +
        '<p><strong>Essential</strong>: needed for the site to work (for example, remembering your location and this choice). Always on.</p>' +
        '<label class="so-consent__row"><input type="checkbox" id="soConsentAds"' + (st.allowed ? ' checked' : '') + '> <span><strong>Analytics and advertising</strong>: helps us see what works and show relevant ads.</span></label>' +
        '<button type="button" class="so-consent__btn so-consent__btn--primary" data-so-consent="save">Save choices</button></div>';
    bar.innerHTML = body;
    document.body.appendChild(bar);
    bar.addEventListener('click', function (e) {
      var b = e.target.closest('[data-so-consent]'); if (!b) return;
      var act = b.getAttribute('data-so-consent');
      if (act === 'accept') decide('accept');
      else if (act === 'decline') decide('decline');
      else if (act === 'close') close(true);
      else if (act === 'save') decide(bar.querySelector('#soConsentAds').checked ? 'accept' : 'decline');
      else if (act === 'manage') {
        var m = bar.querySelector('#soConsentManage'), on = m.hidden;
        m.hidden = !on; b.setAttribute('aria-expanded', on ? 'true' : 'false');
        if (on) { var c = m.querySelector('input'); if (c) c.focus(); }
      }
    });
    bar.addEventListener('keydown', function (e) { if (e.key === 'Escape') close(true); });
    if (manage) {
      var mb = bar.querySelector('[data-so-consent="manage"]'); if (mb) mb.click();
      else { var ok = bar.querySelector('button'); if (ok) ok.focus(); }
    }
  }

  document.addEventListener('click', function (e) {
    var l = e.target.closest('[data-so-privacy]');
    if (l) { e.preventDefault(); open(true); }
  });

  // First visit with no choice and no Global Privacy Control: show the bar (it never blocks the page and never steals focus).
  if (!st.gpc && !st.choice) {
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', function () { open(false); });
    else open(false);
  }
})();
