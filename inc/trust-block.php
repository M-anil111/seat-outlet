<?php
/**
 * soBuyerGuaranteeSection(): the "Buyer Guarantee" section shown on artist, event, venue, category and hub pages.
 *
 * Only facts the site can stand behind: the TicketNetwork 100% guarantee as published on /worry-free-guarantee,
 * the refund terms in bold, and a live inventory line computed from the same API rows the page lists
 * (_metadata.ticketCount), never an invented "only 3 left".
 *
 * $o = [
 *   'subject' => 'Daniel Sloss',     // "Daniel Sloss Buyer Guarantee"; '' gives "Buyer Guarantee"
 *   'events'  => [...],              // optional API event rows for the live inventory line
 *   'id'      => 'guarantee',        // anchor id
 * ]
 */
require_once __DIR__ . '/guarantee.php';   // SO_TN_POLICY_URL and the guarantee wording live there

function soInventoryLine(array $events): string {
    $tickets = 0; $dates = 0;
    foreach ($events as $ev) {
        $n = (int) ($ev['_metadata']['ticketCount'] ?? 0);
        if ($n > 0) { $tickets += $n; $dates++; }
    }
    if ($tickets <= 0) return '';
    $t = number_format($tickets) . ' ' . ($tickets === 1 ? 'ticket' : 'tickets');
    return count($events) === 1 ? "$t listed for this event right now" : "$t listed across $dates " . ($dates === 1 ? 'date' : 'dates') . " on this page right now";
}

function soBuyerGuaranteeSection(array $o = []): void {
    $h = fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
    $subject = trim((string) ($o['subject'] ?? ''));
    $inventory = soInventoryLine($o['events'] ?? []);
    ?>
    <section class="so-guarantee" id="<?php echo $h($o['id'] ?? 'guarantee'); ?>" aria-labelledby="<?php echo $h(($o['id'] ?? 'guarantee') . '-title'); ?>">
        <div class="so-guarantee__head">
            <img src="/images/moneyback-p3.png" alt="100% buyer guarantee badge for secure checkout" width="67" height="70" loading="lazy" decoding="async" class="so-guarantee__badge">
            <div>
                <h2 class="so-guarantee__title" id="<?php echo $h(($o['id'] ?? 'guarantee') . '-title'); ?>"><?php echo $h(($subject !== '' ? $subject . ' ' : '') . 'Buyer Guarantee'); ?></h2>
                <p class="so-guarantee__lead">Every order is fulfilled through the TicketNetwork marketplace and covered by its 100% guarantee.</p>
            </div>
        </div>
        <ul class="so-guarantee__list">
            <li><strong>Authentic tickets.</strong> Your tickets will be valid for entry.</li>
            <li><strong>On-time delivery.</strong> Tickets ship in time for at least one delivery attempt before the event.</li>
            <li><strong>What you ordered.</strong> You get the seats you bought, or better.</li>
            <li><strong>Secure checkout.</strong> Payment is handled on an encrypted checkout page.</li>
        </ul>
        <p class="so-guarantee__refund"><strong>Refund policy: if the event is canceled, you get a full refund (delivery fees excluded). If it is rescheduled, your tickets stay valid for the new date. Tickets are not refundable for a change of plans.</strong></p>
        <?php if ($inventory !== '') { ?>
            <p class="so-guarantee__live"><span class="so-guarantee__dot" aria-hidden="true"></span><?php echo $h($inventory); ?>. <?php echo count($o['events'] ?? []) === 1 ? 'The seat map above shows every listing and price.' : 'Open a date to see every listing on the seat map.'; ?></p>
        <?php } ?>
        <p class="so-guarantee__links">
            <a href="/worry-free-guarantee">How the 100% guarantee works</a>
            <a href="/ticket-buyer-protection">Buyer protection terms</a>
            <a href="/how-to-buy-tickets-online">How to buy tickets online</a>
            <a href="<?php echo $h(SO_TN_POLICY_URL); ?>" rel="noopener" target="_blank">TicketNetwork policies<span class="visually-hidden"> (opens in a new tab)</span></a>
        </p>
    </section>
    <?php
}

/** A short FAQ (rendered as <details class="so-faq">, which soInjectFaqSchema() turns into FAQPage schema). $faqs = [[q, a], ...] */
function soMiniFaq(string $title, array $faqs): void {
    if (!$faqs) return;
    $h = fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
    ?>
    <div class="tab-section content-section-detail so-minifaq">
        <h2 class="so-heading fw-bold fs-4 mb-3 text-black"><?php echo $h($title); ?></h2>
        <?php foreach ($faqs as [$q, $a]) { ?><details class="so-faq"><summary><?php echo $h($q); ?></summary><p><?php echo $h($a); ?></p></details><?php } ?>
    </div>
    <?php
}
