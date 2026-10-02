<?php
// Include-only file: answer 404 if it is requested directly over the web.
if (PHP_SAPI !== 'cli' && isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) { http_response_code(404); exit; }

/**
 * Small banner shown on the page a visitor lands on after an old event link was redirected (header.php adds ?so_notice=event-past or
 * ?so_notice=event-gone). Only the two fixed messages below can be shown, whatever the query says. The parameter is removed from the
 * address bar once the page has loaded so a shared or bookmarked URL stays clean.
 */
function soEventNoticeHtml(): string {
    $key = (string) ($_GET['so_notice'] ?? '');
    $msgs = [
        'event-past' => 'That event has already happened. Here are the dates that are still on sale.',
        'event-gone' => 'That event is no longer listed. Here are similar tickets that are still on sale.',
    ];
    if (!isset($msgs[$key])) return '';
    return '<div class="so-notice" role="status"><div class="container so-notice__in"><span>' . htmlspecialchars($msgs[$key], ENT_QUOTES, 'UTF-8')
        . '</span><button type="button" class="so-notice__x" aria-label="Dismiss this message">&times;</button></div></div>'
        . '<script>(function(){var n=document.currentScript.previousElementSibling,b=n&&n.querySelector(".so-notice__x");'
        . 'if(b)b.addEventListener("click",function(){n.remove();});'
        . 'try{var u=new URL(location.href);u.searchParams.delete("so_notice");history.replaceState(null,"",u.pathname+u.search+u.hash);}catch(e){}})();</script>';
}
