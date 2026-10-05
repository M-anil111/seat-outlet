<?php
/**
 * soPageHero(): the one page header used by artist, venue, city, search, category and hub pages. It is the event page's
 * white card (.so-evhero) without the buy column, so every page type opens the same way on desktop and on phones:
 * breadcrumb, then a card with a picture (or initials tile), a small blue eyebrow, the title, one meta line, a stats
 * line and an optional button.
 *
 * $o = [
 *   'crumbs'  => [['label' => 'Home', 'url' => '/'], ...],   // trail above the card; the last item is the current page
 *   'image'   => ['url' => ..., 'alt' => ...] | null,        // a real picture, else an initials tile from 'name'
 *   'credit'  => image array for renderImageCredit()          // optional
 *   'name'    => 'Daniel Sloss',                              // for the initials tile and its colour
 *   'icon'    => 'bi-search',                                 // optional icon instead of initials when there is no picture
 *   'eyebrow' => 'Comedy', 'eyebrowUrl' => '/comedy-show-tickets',
 *   'title'   => 'Daniel Sloss Tickets', 'tag' => 'h1',
 *   'meta'    => trusted HTML (address, links)                // optional
 *   'stats'   => ['6 upcoming events', 'Tickets from <strong>$1</strong>'],   // trusted HTML pieces, joined with dots
 *   'lead'    => 'One sentence about the page',               // optional plain text
 *   'cta'     => ['See dates', '#dates'],                      // optional
 *   'note'    => true,                                         // resale disclosure under the stats
 * ]
 */
function soPageHero(array $o): void {
    $h = fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
    $name = (string) ($o['name'] ?? $o['title'] ?? '');
    $img = $o['image'] ?? null;
    $tag = ($o['tag'] ?? 'h1') === 'h2' ? 'h2' : 'h1';
    $crumbs = $o['crumbs'] ?? [];
    $stats = array_values(array_filter($o['stats'] ?? [], 'strlen'));
    ?>
<section class="so-evhero so-phero">
  <div class="container">
    <?php if ($crumbs) { $last = count($crumbs) - 1; ?>
    <nav class="so-crumbs" aria-label="Breadcrumb"><ol>
      <?php foreach ($crumbs as $i => $c) { ?>
        <?php if ($i === $last || empty($c['url'])) { ?><li aria-current="page"><?php echo $h($c['label']); ?></li>
        <?php } else { ?><li><a href="<?php echo $h($c['url']); ?>"><?php echo $h($c['label']); ?></a></li><?php } ?>
      <?php } ?>
    </ol></nav>
    <?php } ?>
    <div class="so-evhero__card">
      <div class="so-evhero__media" style="--so-hue:<?php echo (int) (hexdec(substr(md5($name), 0, 4)) % 360); ?>">
        <?php if ($img && !empty($img['url'])) { ?>
          <img src="<?php echo $h($img['url']); ?>" alt="<?php echo $h($img['alt'] ?? $name); ?>" width="480" height="480" fetchpriority="high" decoding="async">
        <?php } elseif (!empty($o['icon'])) { ?>
          <i class="bi <?php echo $h($o['icon']); ?> so-phero__icon" aria-hidden="true"></i>
        <?php } else { ?>
          <span class="so-evhero__initials" aria-hidden="true"><?php echo $h(soInitials($name)); ?></span>
        <?php } ?>
      </div>
      <div class="so-evhero__main">
        <?php if (!empty($o['eyebrow'])) { ?>
          <p class="so-evhero__when"><?php echo !empty($o['eyebrowUrl']) ? '<a href="' . $h($o['eyebrowUrl']) . '">' . $h($o['eyebrow']) . '</a>' : $h($o['eyebrow']); ?></p>
        <?php } ?>
        <<?php echo $tag; ?> class="so-evhero__title"><?php echo $h($o['title'] ?? ''); ?></<?php echo $tag; ?>>
        <?php if (!empty($o['meta'])) { ?>
          <p class="so-evhero__venue"><i class="bi bi-geo-alt" aria-hidden="true"></i><span><?php echo $o['meta']; ?></span></p>
        <?php } ?>
        <?php if (!empty($o['lead'])) { ?>
          <p class="so-phero__lead"><?php echo $h($o['lead']); ?></p>
        <?php } ?>
        <?php if ($stats) { ?>
          <p class="so-phero__stats"><?php echo implode('<span class="so-phero__dot" aria-hidden="true">&middot;</span>', $stats); ?></p>
        <?php } ?>
        <?php if (($o['note'] ?? true) || !empty($o['cta'])) { ?>
          <div class="so-phero__foot">
            <?php if (!empty($o['cta'])) { ?><a class="so-phero__cta" href="<?php echo $h($o['cta'][1]); ?>"><?php echo $h($o['cta'][0]); ?></a><?php } ?>
            <?php if ($o['note'] ?? true) { ?><p class="so-phero__note">Resale marketplace. Prices are set by sellers and may be above or below face value.</p><?php } ?>
          </div>
        <?php } ?>
        <?php if (!empty($o['credit']) && function_exists('renderImageCredit')) { renderImageCredit($o['credit'], 'img-credit so-phero__credit'); } ?>
      </div>
    </div>
  </div>
</section>
    <?php
}
