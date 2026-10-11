<?php // Include-only file: answer 404 if it is requested directly over the web.
if (PHP_SAPI !== 'cli' && isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) { http_response_code(404); exit; }

/** Shared look of the account pages: one narrow card. Plain Bootstrap classes plus a few rules printed with the page. */
function soAcctStyle(): string {
    return '<style>.so-acct{padding:clamp(28px,6vw,64px) 0}.so-acct__card{max-width:560px;margin:0 auto;background:#fff;border:1px solid #e3e7ef;border-radius:20px;padding:clamp(20px,4vw,36px);box-shadow:0 10px 30px rgba(16,24,40,.06)}'
        . '.so-acct h1{font-size:clamp(26px,5vw,34px);font-weight:800;letter-spacing:-.02em;margin:0 0 8px;color:#0b1120}.so-acct__lead{color:#4b5565;margin:0 0 20px}'
        . '.so-acct label{display:block;font-weight:700;margin:0 0 6px}.so-acct input[type=email]{width:100%;min-height:52px;padding:10px 16px;border:1.5px solid #c9d1de;border-radius:12px;font-size:17px}'
        . '.so-acct input[type=email]:focus{outline:3px solid #cfe0ff;border-color:#2556e0}.so-acct__btn{display:inline-flex;align-items:center;justify-content:center;width:100%;min-height:52px;margin-top:14px;border:0;border-radius:12px;background:#2556e0;color:#fff;font-weight:700;font-size:17px;cursor:pointer}'
        . '.so-acct__btn:hover{background:#1d46c0}.so-acct__btn[disabled]{opacity:.6;cursor:wait}.so-acct__btn--ghost{background:#fff;color:#1c2333;border:1.5px solid #c9d1de;width:auto;padding:0 18px;margin-top:0}'
        . '.so-acct__btn--danger{background:#b42318;width:auto;padding:0 18px}.so-acct__msg{margin:14px 0 0;padding:12px 14px;border-radius:12px;background:#fff4e5;color:#7a4b00}.so-acct__msg.is-ok{background:#e8f6ee;color:#14532d}'
        . '.so-acct__list{list-style:none;margin:0;padding:0}.so-acct__list li{display:flex;gap:12px;align-items:center;justify-content:space-between;padding:12px 0;border-top:1px solid #eef1f6}.so-acct__list li:first-child{border-top:0}'
        . '.so-acct__list a{font-weight:700;color:#0b1120;text-decoration:none}.so-acct__list a:hover{text-decoration:underline}.so-acct__meta{display:block;color:#5b6573;font-size:14px;font-weight:400}'
        . '.so-acct__past{opacity:.6}.so-acct__h2{font-size:20px;font-weight:800;margin:28px 0 8px;color:#0b1120}.so-acct__note{color:#5b6573;font-size:14px;margin:16px 0 0}'
        . '.so-acct__trust{margin:18px 0 0;padding:0;list-style:none;color:#374151;font-size:15px}.so-acct__trust li{padding:4px 0 4px 26px;position:relative}.so-acct__trust li::before{content:"";position:absolute;left:4px;top:11px;width:10px;height:6px;border-left:2.5px solid #2556e0;border-bottom:2.5px solid #2556e0;transform:rotate(-45deg)}'
        . '.so-acct__hp{position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden}</style>';
}

/**
 * Sets what header.php reads (it reads plain globals, so the page must include it at top level, not from a function):
 *   $pageMetaTitle = ...; $pageMetaDescription = ...; $pageNoCache = true; $pageRobots = 'noindex, nofollow';
 * This returns the two values, and sends the no-store header.
 * @return array{0:string,1:string} title, description
 */
function soAcctMeta(string $title, string $desc): array {
    header('Cache-Control: no-store');
    header('X-Robots-Tag: noindex, nofollow');   // robots.txt does not block these pages, so crawlers can read this
    return [$title . ' | Seat Outlet', $desc];
}

/** The body of /login and /register. */
function soAcctSignInBody(string $mode): void {
    $next = soAcctSafeNext($_GET['next'] ?? '');
    $create = $mode === 'register';
    $h1 = $create ? 'Create your Seat Outlet account' : 'Sign in to Seat Outlet';
    ?>
<section class="so-acct">
  <div class="container">
    <div class="so-acct__card">
      <h1><?php echo soAcctH($h1); ?></h1>
      <p class="so-acct__lead">Enter your email and we will send you a link. It signs you in, or creates your account if you are new. No password to remember.</p>
      <form id="soAcctForm" method="post" action="/ajax/account-link.php" novalidate>
        <label for="soAcctEmail">Email address</label>
        <input id="soAcctEmail" name="email" type="email" inputmode="email" autocomplete="email" required maxlength="254" placeholder="you@example.com">
        <input type="hidden" name="next" value="<?php echo soAcctH($next); ?>">
        <div class="so-acct__hp" aria-hidden="true"><label>Leave this empty<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
        <button type="submit" class="so-acct__btn" id="soAcctBtn">Email me a sign-in link</button>
        <p class="so-acct__msg" id="soAcctMsg" role="status" aria-live="polite" hidden></p>
      </form>
      <ul class="so-acct__trust">
        <li>Save events and see them on any device</li>
        <li>See the price and new-date alerts you set up</li>
        <li>Orders are placed with our ticket partner at checkout. We never store payment details.</li>
      </ul>
      <p class="so-acct__note">By continuing you agree to our <a href="/terms-and-conditions">terms</a> and <a href="/privacy-policy">privacy policy</a>. This site is protected by reCAPTCHA and the Google <a href="https://policies.google.com/privacy" rel="noopener" target="_blank">Privacy Policy</a> and <a href="https://policies.google.com/terms" rel="noopener" target="_blank">Terms of Service</a> apply.</p>
    </div>
  </div>
</section>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var form = document.getElementById('soAcctForm'), btn = document.getElementById('soAcctBtn'), msg = document.getElementById('soAcctMsg');
  if (!form) return;
  var loaded = false;
  function say(t, ok) { msg.textContent = t; msg.hidden = false; msg.classList.toggle('is-ok', !!ok); }
  function recaptcha(cb) {
    if (typeof RECAPTCHA_SITE_KEY === 'undefined') return cb('');
    function run() { grecaptcha.ready(function () { grecaptcha.execute(RECAPTCHA_SITE_KEY, { action: 'account' }).then(cb, function () { cb(''); }); }); }
    if (loaded) return run();
    var s = document.createElement('script');
    s.src = 'https://www.google.com/recaptcha/api.js?render=' + RECAPTCHA_SITE_KEY; s.async = true;
    s.onload = function () { loaded = true; run(); }; s.onerror = function () { cb(''); };
    document.body.appendChild(s);
  }
  form.addEventListener('submit', function (e) {
    e.preventDefault();
    var email = form.elements.email.value.trim();
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { say('Enter a valid email address.', false); form.elements.email.focus(); return; }
    msg.hidden = true; btn.disabled = true; btn.textContent = 'Sending...';
    recaptcha(function (t) {
      var fd = new FormData(form); fd.append('token', t);
      fetch('/ajax/account-link.php', { method: 'POST', body: fd, headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (d) {
          btn.disabled = false; btn.textContent = 'Email me a sign-in link';
          say(d.message || 'Something went wrong. Please try again.', d.status === 'sent');
          if (d.status === 'sent') { form.elements.email.value = ''; }
        })
        .catch(function () { btn.disabled = false; btn.textContent = 'Email me a sign-in link'; say('We could not reach the server. Check your connection and try again.', false); });
    });
  });
});
</script>
<?php
}

/** The body of the page the emailed link opens: a confirm button, or a short "expired" message. */
function soAcctVerifyBody(?array $ok, string $why): void {
    ?>
<section class="so-acct">
  <div class="container">
    <div class="so-acct__card">
    <?php if ($ok !== null): ?>
      <h1>Confirm sign in</h1>
      <p class="so-acct__lead">You are signing in as <strong><?php echo soAcctH(soAcctMask($ok['email'])); ?></strong>.</p>
      <form method="post" action="/account-verify">
        <button type="submit" class="so-acct__btn">Sign in</button>
      </form>
      <p class="so-acct__note">Not you? Close this page. Nothing happens until you press the button.</p>
    <?php else: ?>
      <h1><?php echo $why === 'forbidden' ? 'We could not confirm that request' : ($why === 'nocookie' ? 'Open the link from your email' : 'This link has expired'); ?></h1>
      <p class="so-acct__lead"><?php
        if ($why === 'forbidden') echo 'Open the link from your email again and press the button on this site.';
        elseif ($why === 'nocookie') echo 'This page continues from the link in your sign-in email, and needs cookies turned on in this browser. Open the link from the email, or ask for a new one.';
        else echo 'Sign-in links work once and last 15 minutes. Ask for a new one and it will arrive in a moment.'; ?></p>
      <a class="so-acct__btn" style="text-decoration:none" href="/login">Get a new link</a>
    <?php endif; ?>
    </div>
  </div>
</section>
<?php
}
