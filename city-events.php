<?php
require_once 'functions.php';

// Real top cities, from getTopVenues() (already in production use - see
// cron/home-venues.php) rather than the fully hardcoded, unlinked ~100-city
// list this page used to have (every entry was href="#" and there was no
// real city ID behind any of them to link to safely). See getTopCities()
// in functions.php.
$topCities = getTopCities(60);

$citySections = [
    'Events'    => 'event-city',
    'Concerts'  => 'concerts-city',
    'Theater'   => 'theater-city',
    'Sports'    => 'sports-city',
    'Festival'  => 'festivals-city',
];

include 'header.php';
?>

<main class="cities">
    <section class="hero-section">
        <div class="container">
            <div class="row justify-content-center text-center">
                <div class="col-lg-9 hero-inner">
                    <h1 class="hero-title">City Events</h1>
                    <p class="hero-subtitle">Pick your city to browse city events, from concerts and sports to theater and festivals.</p>
                </div>
            </div>
        </div>
    </section>

    <?php foreach ($citySections as $sectionLabel => $urlPrefix) { ?>
        <section class="events-section pt-5">
            <div class="container">
                <h2 class="section-heading"><?php echo htmlspecialchars($sectionLabel, ENT_QUOTES, 'UTF-8'); ?></h2>

                <div class="events-grid">
                    <?php if (!empty($topCities)) { ?>
                        <?php foreach ($topCities as $city) {
                            $citySlug = createSlug($city['label'], $city['id']);
                        ?>
                            <a href="/<?php echo htmlspecialchars($urlPrefix, ENT_QUOTES, 'UTF-8'); ?>/<?php echo htmlspecialchars($citySlug, ENT_QUOTES, 'UTF-8'); ?>">
                                <?php echo htmlspecialchars($sectionLabel, ENT_QUOTES, 'UTF-8'); ?> in <?php echo htmlspecialchars($city['label'], ENT_QUOTES, 'UTF-8'); ?>
                            </a>
                        <?php } ?>
                    <?php } else { ?>
                        <p>No cities available right now.</p>
                    <?php } ?>
                </div>
            </div>
        </section>
    <?php } ?>
</main>

<?php soSeoCopy('city-events'); ?>
<?php include 'footer.php'; ?>
