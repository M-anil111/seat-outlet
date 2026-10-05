<?php
require_once 'functions.php';

// Real top cities, from getTopVenues() (already in production use - see
// cron/home-venues.php) rather than the fully hardcoded, unlinked ~100-city
// list this page used to have (every entry was href="#" and there was no
// real city ID behind any of them to link to safely). See getTopCities()
// in functions.php.
$topCities = getTopCities(60);

// Cities grouped by state, biggest states (most events on sale) first, with the event counts the API reports.
$soStates = ['AL'=>'Alabama','AK'=>'Alaska','AZ'=>'Arizona','AR'=>'Arkansas','CA'=>'California','CO'=>'Colorado','CT'=>'Connecticut','DE'=>'Delaware','DC'=>'District of Columbia','FL'=>'Florida','GA'=>'Georgia','HI'=>'Hawaii','ID'=>'Idaho','IL'=>'Illinois','IN'=>'Indiana','IA'=>'Iowa','KS'=>'Kansas','KY'=>'Kentucky','LA'=>'Louisiana','ME'=>'Maine','MD'=>'Maryland','MA'=>'Massachusetts','MI'=>'Michigan','MN'=>'Minnesota','MS'=>'Mississippi','MO'=>'Missouri','MT'=>'Montana','NE'=>'Nebraska','NV'=>'Nevada','NH'=>'New Hampshire','NJ'=>'New Jersey','NM'=>'New Mexico','NY'=>'New York','NC'=>'North Carolina','ND'=>'North Dakota','OH'=>'Ohio','OK'=>'Oklahoma','OR'=>'Oregon','PA'=>'Pennsylvania','RI'=>'Rhode Island','SC'=>'South Carolina','SD'=>'South Dakota','TN'=>'Tennessee','TX'=>'Texas','UT'=>'Utah','VT'=>'Vermont','VA'=>'Virginia','WA'=>'Washington','WV'=>'West Virginia','WI'=>'Wisconsin','WY'=>'Wyoming'];
$byState = [];
foreach ($topCities as $c) {
    $st = (string) ($c['state'] ?? '');
    $byState[$st]['cities'][] = $c;
    $byState[$st]['events'] = ($byState[$st]['events'] ?? 0) + (int) ($c['eventCount'] ?? 0);
}
uasort($byState, function ($a, $b) { return $b['events'] <=> $a['events']; });

$cityLinks = ['Concerts' => 'concerts-city', 'Sports' => 'sports-city', 'Theater' => 'theater-city', 'Festivals' => 'festivals-city'];
$h = function ($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); };

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

    <section class="events-section pt-4">
        <div class="container">
            <?php if (empty($byState)) { ?>
                <p>No cities available right now.</p>
            <?php } else { ?>
                <div class="so-cities__near" id="soCitiesNear" hidden>
                    <h2 class="section-heading">Near you: <span data-so-near-state></span></h2>
                    <div class="so-cities__grid" data-so-near-grid></div>
                </div>
                <nav class="so-cities__jump" aria-label="Jump to a state">
                    <?php foreach ($byState as $abbr => $info) { ?>
                        <a href="#state-<?php echo $h(strtolower($abbr)); ?>"><?php echo $h($soStates[$abbr] ?? $abbr); ?></a>
                    <?php } ?>
                </nav>
                <?php foreach ($byState as $abbr => $info) { ?>
                    <div class="so-cities__state" id="state-<?php echo $h(strtolower($abbr)); ?>" data-state="<?php echo $h($abbr); ?>" data-state-name="<?php echo $h($soStates[$abbr] ?? $abbr); ?>">
                        <h2 class="section-heading"><?php echo $h($soStates[$abbr] ?? $abbr); ?> <small><?php echo number_format($info['events']); ?> events in top cities</small></h2>
                        <div class="so-cities__grid">
                            <?php foreach ($info['cities'] as $city) {
                                $citySlug = soSlug('city', $city['label'], $city['id']); ?>
                                <div class="so-citycard">
                                    <a class="so-citycard__main" href="/event-city/<?php echo $h($citySlug); ?>">
                                        <strong><?php echo $h($city['label']); ?></strong>
                                        <span><?php echo number_format((int) $city['eventCount']); ?> events</span>
                                    </a>
                                    <div class="so-citycard__more">
                                        <?php foreach ($cityLinks as $label => $prefix) { ?>
                                            <a href="/<?php echo $h($prefix); ?>/<?php echo $h($citySlug); ?>"><?php echo $h($label); ?></a>
                                        <?php } ?>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                <?php } ?>
            <?php } ?>
        </div>
    </section>
</main>
<script>
// "Near you": the visitor's saved place (cookie so_label, for example "Bee Cave, TX") picks the state to show first.
(function () {
    var m = /(?:^|; )so_label=([^;]*)/.exec(document.cookie);
    if (!m) return;
    var label = ''; try { label = decodeURIComponent(m[1]); } catch (e) { return; }
    var st = /,\s*([A-Z]{2})\s*$/.exec(label);
    if (!st) return;
    var src = document.querySelector('.so-cities__state[data-state="' + st[1] + '"]');
    var box = document.getElementById('soCitiesNear');
    if (!src || !box) return;
    box.querySelector('[data-so-near-state]').textContent = src.getAttribute('data-state-name');
    box.querySelector('[data-so-near-grid]').innerHTML = src.querySelector('.so-cities__grid').innerHTML;
    box.hidden = false;
})();
</script>

<?php soSeoCopy('city-events'); ?>
<?php include 'footer.php'; ?>
