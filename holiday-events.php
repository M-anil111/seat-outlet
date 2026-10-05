<?php
// /holiday-events: the US and Canadian holidays with their next dates and a page for each of the biggest cities (inc/holidays.php).
require_once 'functions.php';
$pageMetaTitle       = soTitleUpTo(70, 'Buy Tickets to Holiday Events in the US and Canada', 'Holiday Events in the US and Canada');
$pageTitleMax        = 70;
$pageFocusKeyword    = 'Holiday Events Near Me';
$pageMetaDescription = 'Buy tickets to holiday events near you: Christmas shows, New Year\'s Eve, July 4th, Thanksgiving weekend, Canada Day and more, in cities across the US and Canada.';
$pageCanonicalUrl    = HOME_URL . '/holiday-events';
$h = function ($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); };
$cities = [];
foreach (getTopCities(150) as $c) { $cities[$c['country'] ?? 'US'][] = $c; }
include 'header.php';
?>
<section class="so-sitemap py-5">
    <div class="container">
        <h2 class="so-sitemap__title">Holiday events in the US and Canada</h2>
        <p class="so-sitemap__lead">Pick a holiday, then your city. Each page lists the events on sale in that city for the holiday's dates, and the dates are worked out every year, Easter and the long weekends included.</p>
        <?php foreach (['US' => 'United States', 'CA' => 'Canada'] as $cc => $ccName) { $list = array_slice($cities[$cc] ?? [], 0, 12); ?>
        <h3 class="mt-4"><?php echo $h($ccName); ?></h3>
        <div class="so-sitemap__grid">
            <?php foreach (soHolidayHubRows($cc) as $key => [$label, $dates]) { ?>
            <nav class="so-sitemap__col" aria-labelledby="hol-<?php echo $h($cc . '-' . $key); ?>">
                <h4 id="hol-<?php echo $h($cc . '-' . $key); ?>" class="fs-6 fw-bold"><?php echo $h($label); ?></h4>
                <p class="so-sitemap__group"><?php echo $h($dates); ?></p>
                <ul>
                    <?php foreach ($list as $c) { ?><li><a href="/<?php echo $h($key); ?>-in-<?php echo $h(soSlug('city', $c['label'], $c['id'])); ?>"><?php echo $h($label); ?> in <?php echo $h($c['name']); ?></a></li><?php } ?>
                </ul>
                <?php if ($key === 'christmas-shows-near-me') { ?><p class="mb-0"><a href="/christmas-shows-near-me">Christmas shows near me</a></p><?php } ?>
            </nav>
            <?php } ?>
        </div>
        <?php } ?>
    </div>
</section>
<?php include 'footer.php'; ?>
