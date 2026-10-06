<?php
require_once 'functions.php';
$pageMetaTitle       = 'Our Network – Seat Outlet Partners | Seat Outlet';
$pageMetaDescription = 'Meet the businesses in the Seat Outlet network - ticketing partners and independent companies across marketing, real estate tech, CRM, and booking software.';
$pageCanonicalUrl    = HOME_URL . '/our-network';

$partners = [
    [
        'name' => 'Grab Tickets Now',
        'tagline' => 'Online Ticket Marketplace for Live Events',
        'url' => '/grab-tickets-now',
        'external' => false,
    ],
    [
        'name' => 'Ticket Deals',
        'tagline' => 'Discount Event Ticket Marketplace',
        'url' => '/ticket-deals',
        'external' => false,
    ],
    [
        'name' => 'Hunt Tickets',
        'tagline' => 'Event Ticket Search Marketplace',
        'url' => '/hunt-tickets',
        'external' => false,
    ],
    [
        'name' => 'Ticket Scanner',
        'tagline' => 'Travel Booking for Flights, Hotels & Cars',
        'url' => '/ticket-scanner',
        'external' => false,
    ],
    [
        'name' => 'IT Sprinkles',
        'tagline' => 'Custom Cakes in Austin',
        'url' => '/it-sprinkles',
        'external' => false,
    ],
    [
        'name' => 'Mindshare Consulting',
        'tagline' => 'Marketing Agency in Austin',
        'url' => '/mindshare-consulting',
        'external' => false,
    ],
    [
        'name' => 'Austin Sign Masters',
        'tagline' => 'Austin Texas Sign Companies',
        'url' => '/austin-sign-masters',
        'external' => false,
    ],
    [
        'name' => 'Viralpep',
        'tagline' => 'Social Media Management Tool',
        'url' => '/viralpep',
        'external' => false,
    ],
    [
        'name' => 'Dotbooker',
        'tagline' => 'Smart Booking & Appointment Software',
        'url' => '/dotbooker',
        'external' => false,
    ],
    [
        'name' => 'WingCMS',
        'tagline' => 'End to End Real Estate Tech',
        'url' => '/wingcms',
        'external' => false,
    ],
    [
        'name' => 'Signs N More Inc',
        'tagline' => 'Innovative Marketing & Branding',
        'url' => '/signs-n-more',
        'external' => false,
    ],
    [
        'name' => 'Salespeep',
        'tagline' => 'CRM for Sales, Marketing & Service',
        'url' => '/salespeep',
        'external' => false,
    ],
];

$pageRobots = 'noindex, follow';   // thin partner page: not worth a crawl, kept for visitors
include 'header.php';
?>

<section class="hero-section">
    <div class="container">
        <div class="row justify-content-center text-center">
            <div class="col-lg-9 hero-inner">
                <h1 class="hero-title">Our Network</h1>
                <p class="hero-subtitle">The businesses and partners connected to Seat Outlet</p>
            </div>
        </div>
    </div>
</section>

<section class="section-padding">
    <div class="container">
        <div class="row g-4">
            <?php foreach ($partners as $partner) { ?>
                <div class="col-md-6 col-lg-4">
                    <a href="<?php echo htmlspecialchars($partner['url'], ENT_QUOTES, 'UTF-8'); ?>" class="card h-100 text-decoration-none text-reset p-3">
                        <h3 class="h5 mb-1"><?php echo htmlspecialchars($partner['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                        <p class="text-muted mb-0"><?php echo htmlspecialchars($partner['tagline'], ENT_QUOTES, 'UTF-8'); ?></p>
                    </a>
                </div>
            <?php } ?>
        </div>
    </div>
</section>

<?php include 'footer.php'; ?>
