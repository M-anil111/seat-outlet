<?php
// Include-only file: answer 404 if it is requested directly over the web.
if (PHP_SAPI !== 'cli' && isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) { http_response_code(404); exit; }
/**
 * The guarantee wording, in one place. /worry-free-guarantee is the source of truth: every other page that talks about
 * the guarantee (buyer protection, FAQ, contact, blog, checkout copy) calls these helpers instead of retyping it, so the
 * promise cannot drift apart again. The cover is TicketNetwork's, not a separate Seat Outlet promise: change the text
 * here only after TicketNetwork's own policy changes.
 */

define('SO_TN_POLICY_URL', 'https://www.ticketnetwork.com/policies');

/** The four things the guarantee covers (plain text). */
function soGuaranteePoints(): array {
    return [
        'Your tickets will be authentic and valid for entry',
        'Your tickets will be shipped in time for at least one delivery attempt before the event',
        'You receive the tickets you ordered, or better',
        'A full refund (delivery fees excluded) if the event is canceled',
    ];
}

/** One sentence that says who stands behind the order. */
function soGuaranteeLead(): string {
    return 'Seat Outlet orders are fulfilled through the TicketNetwork marketplace and covered by its 100% guarantee, in plain language:';
}

/** What the guarantee does not do. */
function soGuaranteeLimits(): string {
    return 'Resale ticket prices may be above or below face value. The guarantee covers your order, not the price. Tickets are not refundable for a change of plans. If an event is rescheduled, tickets typically stay valid for the new date.';
}

/** The list as markup. $attrs is added to the <ul>. */
function soGuaranteeList(string $attrs = ''): string {
    $out = '<ul' . ($attrs !== '' ? ' ' . $attrs : '') . '>';
    foreach (soGuaranteePoints() as $p) { $out .= '<li>' . htmlspecialchars($p, ENT_QUOTES, 'UTF-8') . '</li>'; }
    return $out . '</ul>';
}

/** "Read the full terms" line with links. */
function soGuaranteeLinks(bool $withProtectionPage = true): string {
    return 'Read the full terms in <a href="' . SO_TN_POLICY_URL . '" target="_blank" rel="noopener">TicketNetwork\'s policies</a>'
        . ($withProtectionPage ? ' and our <a href="/ticket-buyer-protection">ticket buyer protection</a> page' : '') . '.';
}

/** The whole block as a paragraph + list + limits, for pages that just need "the guarantee". */
function soGuaranteeBlock(string $listAttrs = ''): string {
    return '<p>' . htmlspecialchars(soGuaranteeLead(), ENT_QUOTES, 'UTF-8') . '</p>' . soGuaranteeList($listAttrs)
        . '<p>' . htmlspecialchars(soGuaranteeLimits(), ENT_QUOTES, 'UTF-8') . ' ' . soGuaranteeLinks(false)
        . ' See the <a href="/worry-free-guarantee">full guarantee page</a>.</p>';
}

/** A single sentence for short mentions (FAQ answers, contact page). */
function soGuaranteeSentence(): string {
    return 'Orders are covered by the TicketNetwork 100% guarantee: authentic and valid tickets, shipped in time for at least one delivery attempt before the event, the tickets you ordered or better, and a refund (delivery fees excluded) if the event is canceled.';
}
