<?php
// Include-only file: answer 404 if it is requested directly over the web.
if (PHP_SAPI !== 'cli' && isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) { http_response_code(404); exit; }
/**
 * Email capture, one component for the whole site.
 *
 *   echo soLeadForm(['source' => 'event-empty', 'title' => '...', 'interest_type' => 'performer', 'interest_id' => 123, 'interest_name' => 'Adele']);
 *
 * The markup carries data attributes; js/lead-capture.js (loaded on every page by footer.php) binds every form.so-lead-form,
 * gets a reCAPTCHA v3 token and POSTs to /ajax/subscribe.php, which answers JSON {status: success|duplicate|invalid|recaptcha|rate|error, message?}.
 * source = where the form sits ('home', 'blog', 'blog-end', 'event-empty', 'artist-follow', 'city-empty', 'promo', 'contact' ...).
 * interest_* = what the visitor wants alerts for (performer | event | city | category), stored with the lead so alerts can be targeted.
 */
function soLeadForm(array $o = []) {
    $o += ['source' => 'site', 'title' => 'Get ticket alerts and new guides in your inbox', 'text' => 'Tour announcements, on-sale news and plain-English ticket advice. No spam.',
           'button' => 'Sign me up', 'interest_type' => '', 'interest_id' => 0, 'interest_name' => '', 'id' => '', 'names' => true, 'class' => ''];
    $h = function ($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); };
    return '<aside class="so-nl ' . $h($o['class']) . '"' . ($o['id'] !== '' ? ' id="' . $h($o['id']) . '"' : '') . '>'
        . '<p class="so-nl__title">' . $h($o['title']) . '</p>' . ($o['text'] !== '' ? '<p class="so-nl__sub">' . $h($o['text']) . '</p>' : '')
        . '<form class="so-nl__form so-lead-form" method="post" action="/ajax/subscribe.php" data-source="' . $h($o['source']) . '" data-interest-type="' . $h($o['interest_type']) . '" data-interest-id="' . (int) $o['interest_id'] . '" data-interest-name="' . $h($o['interest_name']) . '">'
        . '<input type="text" name="website" value="" class="so-nl__hp" autocomplete="off" tabindex="-1" aria-hidden="true">'
        . '<div class="so-nl__fields' . ($o['names'] ? '' : ' so-nl__fields--email') . '">'
        . ($o['names'] ? '<input name="fname" type="text" placeholder="First name" aria-label="First name" maxlength="70" autocomplete="given-name">'
                       . '<input name="lname" type="text" placeholder="Last name" aria-label="Last name" maxlength="70" autocomplete="family-name">' : '')
        . '<input name="email" type="email" placeholder="Email" aria-label="Email" maxlength="120" autocomplete="email" required>'
        . '<button type="submit">' . $h($o['button']) . '</button></div>'
        . '<p class="so-nl__legal">By signing up you agree to get emails from Seat Outlet. Unsubscribe any time. <a href="/privacy-policy">Privacy policy</a></p>'
        . '<p class="so-nl__msg" role="status" aria-live="polite" hidden></p></form></aside>';
}
