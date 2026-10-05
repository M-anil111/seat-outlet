/* "Add to home screen": a small, polite bottom card on phones.
   - Android / Chrome: uses the browser's own install prompt (beforeinstallprompt), one tap.
   - iPhone / iPad Safari: no install API exists, so the card shows the two taps (Share, then Add to Home Screen).
   Rules: phones only, never inside the installed app, not on event pages (the buy button owns that screen), after the visitor
   has seen at least two pages and a few seconds, and once dismissed it stays away for 30 days. */
(function () {
  var KEY_PV = 'so_pv', KEY_OFF = 'so_install_off';
  function get(k) { try { return localStorage.getItem(k); } catch (e) { return null; } }
  function set(k, v) { try { localStorage.setItem(k, v); } catch (e) {} }

  var standalone = (window.matchMedia && matchMedia('(display-mode: standalone)').matches) || window.navigator.standalone === true;
  if (standalone) return;
  var isPhone = window.matchMedia && matchMedia('(max-width: 820px) and (pointer: coarse)').matches;
  if (!isPhone) return;
  if (/^\/(event|checkout)\b/.test(location.pathname)) return;

  var off = parseInt(get(KEY_OFF) || '0', 10);
  if (off && Date.now() - off < 30 * 864e5) return;

  var pv = (parseInt(get(KEY_PV) || '0', 10) || 0) + 1;
  set(KEY_PV, String(pv));

  var ua = navigator.userAgent;
  var isIOS = /iphone|ipad|ipod/i.test(ua) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
  var isIOSSafari = isIOS && /safari/i.test(ua) && !/crios|fxios|edgios|opios/i.test(ua);
  var deferred = null;

  window.addEventListener('beforeinstallprompt', function (e) { e.preventDefault(); deferred = e; });
  window.addEventListener('appinstalled', function () { set(KEY_OFF, String(Date.now() + 365 * 864e5)); track('installed'); hide(); });

  function track(step) { (window.dataLayer = window.dataLayer || []).push({ event: 'so_install_prompt', step: step }); }

  var card = null;
  function hide() { if (card) { card.classList.remove('is-open'); setTimeout(function () { if (card) { card.remove(); card = null; } }, 250); } }

  function show() {
    if (card || pv < 2) return;
    if (!deferred && !isIOSSafari) return;                                   // nothing to offer on this browser
    if (document.querySelector('.so-nudge, .so-consent, .modal.show, .offcanvas.show')) { return setTimeout(show, 6000); }
    card = document.createElement('div');
    card.className = 'so-install';
    card.setAttribute('role', 'dialog');
    card.setAttribute('aria-label', 'Add Seat Outlet to your home screen');
    var steps = isIOSSafari && !deferred
      ? '<span class="so-install__how">Tap <svg width="16" height="20" viewBox="0 0 16 20" aria-hidden="true"><path d="M8 1v12M4 5l4-4 4 4M2 9H1v9h14V9h-1" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg> <b>Share</b>, then <b>Add to Home Screen</b>.</span>'
      : '<span>Open tickets in one tap, right from your phone.</span>';
    card.innerHTML =
      '<img src="/images/app/icon-192.png" alt="" width="48" height="48">' +
      '<div class="so-install__txt"><strong>Add Seat Outlet to your home screen</strong>' + steps + '</div>' +
      (deferred ? '<button type="button" class="so-install__go">Add</button>' : '') +
      '<button type="button" class="so-install__x" aria-label="Not now">&times;</button>';
    document.body.appendChild(card);
    requestAnimationFrame(function () { card.classList.add('is-open'); });
    track('shown');

    card.querySelector('.so-install__x').addEventListener('click', function () { set(KEY_OFF, String(Date.now())); track('dismissed'); hide(); });
    var go = card.querySelector('.so-install__go');
    if (go) go.addEventListener('click', function () {
      deferred.prompt();
      deferred.userChoice.then(function (c) { track(c && c.outcome === 'accepted' ? 'accepted' : 'declined'); if (!c || c.outcome !== 'accepted') set(KEY_OFF, String(Date.now())); deferred = null; hide(); });
    });
  }

  setTimeout(show, 7000);
})();
