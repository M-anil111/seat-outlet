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
           'button' => 'Sign me up', 'interest_type' => '', 'interest_id' => 0, 'interest_name' => '', 'id' => '', 'names' => true, 'class' => '', 'alert_kind' => '', 'baseline_price' => 0,
           'layout' => ''];
    $h = function ($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); };
    // layout 'split' (home page): bell icon, title and text on the left, the form on the right with visible field labels
    // and an arrow on the button. Every other form keeps the compact stacked layout.
    $split = $o['layout'] === 'split';
    $field = function ($input, $label, $extra = '') use ($split, $h) {
        return $split ? '<label class="so-nl__field' . $extra . '"><span class="so-nl__label">' . $h($label) . '</span>' . $input . '</label>' : $input;
    };
    $bell = '<span class="so-nl__icon" aria-hidden="true"><svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" focusable="false"><path d="M18 9.5a6 6 0 0 0-12 0c0 5.5-2.5 7.5-2.5 7.5h17S18 15 18 9.5"/><path d="M10.3 20a1.9 1.9 0 0 0 3.4 0M12 3.5V2.6M2.6 7.4a9 9 0 0 1 1.6-3.2M21.4 7.4a9 9 0 0 0-1.6-3.2"/></svg></span>';
    $intro = '<p class="so-nl__title">' . $h($o['title']) . '</p>' . ($o['text'] !== '' ? '<p class="so-nl__sub">' . $h($o['text']) . '</p>' : '');
    return '<div role="group" aria-label="Email alerts" class="so-nl ' . ($split ? 'so-nl--split ' : '') . $h($o['class']) . '"' . ($o['id'] !== '' ? ' id="' . $h($o['id']) . '"' : '') . '>'
        . ($split ? '<div class="so-nl__intro">' . $bell . $intro . '</div>' : $intro)
        . '<form class="so-nl__form so-lead-form" method="post" action="/ajax/subscribe.php" data-source="' . $h($o['source']) . '" data-interest-type="' . $h($o['interest_type']) . '" data-interest-id="' . (int) $o['interest_id'] . '" data-interest-name="' . $h($o['interest_name']) . '"' . ($o['alert_kind'] === 'price' && $o['baseline_price'] > 0 ? ' data-alert-kind="price" data-baseline-price="' . $h(round((float) $o['baseline_price'], 2)) . '"' : '') . '>'
        . '<input type="text" name="website" value="" class="so-nl__hp" autocomplete="off" tabindex="-1" aria-hidden="true">'
        . '<div class="so-nl__fields' . ($o['names'] ? '' : ' so-nl__fields--email') . '">'
        . ($o['names'] ? $field('<input name="fname" type="text" placeholder="First name"' . ($split ? '' : ' aria-label="First name"') . ' maxlength="70" autocomplete="given-name">', 'First name')
                       . $field('<input name="lname" type="text" placeholder="Last name"' . ($split ? '' : ' aria-label="Last name"') . ' maxlength="70" autocomplete="family-name">', 'Last name') : '')
        . $field('<input name="email" type="email" pattern="[^@\s]+@[^@\s]+\.[A-Za-z]{2,}" title="Enter a full email address, for example name@example.com" placeholder="Email"' . ($split ? '' : ' aria-label="Email"') . ' maxlength="120" autocomplete="email" required>', 'Email', ' so-nl__field--email')
        . '<button type="submit">' . $h($o['button'])
        . ($split ? '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M4 12h15M13 6l6 6-6 6"/></svg>' : '')
        . '</button></div>'
        . '<p class="so-nl__legal">By signing up you agree to get emails from Seat Outlet. Unsubscribe any time. <a href="/privacy-policy">Privacy policy</a></p>'
        . '<p class="so-nl__msg" role="status" aria-live="polite" hidden></p></form></div>';
}
