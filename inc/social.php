<?php // Include-only file: answer 404 if it is requested directly over the web.
if (PHP_SAPI !== 'cli' && isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) { http_response_code(404); exit; }

/**
 * Seat Outlet's public social profiles: one list for the footer, the reviews page and the Organization schema (sameAs).
 * Public profile addresses only. Never put logins or passwords here.
 * 'icon' is a Bootstrap Icons class where the site's icon font has the glyph; the rest are shown as text pills.
 */
const SO_SOCIAL = [
    ['name' => 'Facebook',  'url' => 'https://www.facebook.com/seatoutlet.usa',          'icon' => 'bi-facebook'],
    ['name' => 'YouTube',   'url' => 'https://www.youtube.com/@SeatOutlet',               'icon' => 'bi-youtube'],
    ['name' => 'Instagram', 'url' => 'https://www.instagram.com/seatoutlet/',             'icon' => 'bi-instagram'],
    ['name' => 'X',         'url' => 'https://x.com/SeatOutlet',                          'icon' => ''],
    ['name' => 'Threads',   'url' => 'https://www.threads.com/@seatoutlet',               'icon' => ''],
    ['name' => 'Pinterest', 'url' => 'https://www.pinterest.com/seatoutlet/',             'icon' => ''],
    ['name' => 'LinkedIn',  'url' => 'https://www.linkedin.com/company/seat-outlet',      'icon' => ''],
    ['name' => 'Reddit',    'url' => 'https://www.reddit.com/user/SeatOutlet/',           'icon' => ''],
    ['name' => 'Medium',    'url' => 'https://medium.com/@seatoutlet',                    'icon' => ''],
    ['name' => 'Tumblr',    'url' => 'https://www.tumblr.com/blog/seatoutlet',            'icon' => ''],
];

/** Profile addresses for schema.org sameAs (plus the Linktree page). */
function soSocialSameAs(): array {
    $urls = array_column(SO_SOCIAL, 'url');
    $urls[] = 'https://linktr.ee/seatoutlet';
    return $urls;
}

/** The profiles without an icon in the font, as small text links. */
function soSocialPillsHtml(): string {
    $out = '';
    foreach (SO_SOCIAL as $s) {
        if ($s['icon'] !== '') continue;
        $out .= '<a class="so-social-pill" href="' . htmlspecialchars($s['url'], ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener me" aria-label="Seat Outlet on ' . htmlspecialchars($s['name'], ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($s['name'], ENT_QUOTES, 'UTF-8') . '</a>';
    }
    return $out;
}
