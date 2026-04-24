<?php include 'functions.php'; 
    if(!empty($_POST['startInputHeader']) && !empty($_POST['endInputHeader'])) {
        $dateTitle = $_POST['startInputHeader'] . ' to ' . $_POST['endInputHeader'];
    }else{
        $dateTitle = '';
    }    
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Seat Outlet</title>
    <meta name="robots" content="noindex nofollow">
    <link rel="icon" type="image/png" href="<?php echo HOME_URL; ?>/assets/images/favicon-new.png">

    <!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick-theme.css">    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo HOME_URL; ?>/css/style.css?v=<?php echo filemtime(__DIR__ . '/css/style.css'); ?>">
    <link rel="stylesheet" href="<?php echo HOME_URL; ?>/css/skeleton.css?v=<?php echo filemtime(__DIR__ . '/css/skeleton.css'); ?>"> -->

    <!-- Google Font (keep normal or preload) -->
<link rel="preload" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
<noscript>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</noscript>

<!-- CRITICAL CSS (keep blocking) -->
<link rel="stylesheet" href="<?php echo HOME_URL; ?>/css/style-mehul.css?v=<?php echo filemtime(__DIR__ . '/css/style-mehul.css'); ?>">

<!-- NON-CRITICAL CSS (async load) -->
<link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
<link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
<link rel="preload" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
<link rel="preload" href="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
<link rel="preload" href="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick-theme.css" as="style" onload="this.onload=null;this.rel='stylesheet'">

<!-- YOUR SECONDARY CSS -->
<link rel="preload" href="<?php echo HOME_URL; ?>/css/skeleton.css?v=<?php echo filemtime(__DIR__ . '/css/skeleton.css'); ?>" as="style" onload="this.onload=null;this.rel='stylesheet'">

<!-- NOSCRIPT FALLBACK -->
<noscript>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick-theme.css">
</noscript>

    <script>
        let mapsPromise = null;
        function loadGoogleMapsApi() {
            if (mapsPromise) return mapsPromise;

            mapsPromise = new Promise((resolve, reject) => {
                if (window.google && window.google.maps) {
                resolve(window.google);
                return;
                }

                window.__seatOutletMapsInit = function () {
                    resolve(window.google);
                };

                const script = document.createElement('script');
                script.src = 'https://maps.googleapis.com/maps/api/js?key=<?php echo GAPI_KEY; ?>&libraries=places&loading=async&callback=__seatOutletMapsInit';
                script.async = true;
                script.defer = true;
                script.onerror = reject;
                document.head.appendChild(script);
            });

            return mapsPromise;
        }
    </script>
    <?php include 'seo.php'; ?>    
</head>

<body>
    <div class="header-top-section">
        <!-- Top Utility Bar -->
        <div class="keyword-topbar">
            <div class="text-white text-center">
                <p>Buy Concert Tickets</p>
            </div>
        </div>
        <div class="tm-topbar">
            <div class="container-fluid">
                <div class="top-ticker">
                    <div class="ticker-track">
                        <div class="ticker-content">
                            <span class="ticker-item">
                                <span class="ticker-icon"></span>
                                Trusted marketplace for buying and selling live event tickets. Prices may vary from face value.
                            </span>

                            <span class="ticker-item">
                                <span class="ticker-icon"></span>
                                A trusted marketplace for live event tickets, connecting buyers and sellers worldwide.
                            </span>
                            <span class="ticker-item">
                                <span class="ticker-icon"></span>
                                Your reliable destination to buy and sell tickets for live events.
                            </span>
                            <span class="ticker-item">
                                <span class="ticker-icon"></span>
                                A secure platform for fans to buy and sell live event tickets.
                            </span>
                            <span class="ticker-item">
                                <span class="ticker-icon"></span>
                                The trusted hub for buying and selling tickets to concerts, sports, and live events.
                            </span>
                            <span class="ticker-item">
                                <span class="ticker-icon"></span>
                                A dependable marketplace for discovering and trading live event tickets.
                            </span>
                            <span class="ticker-item">
                                <span class="ticker-icon"></span>
                                Your Reliable Source for Live Event Tickets.
                            </span>
                            <span class="ticker-item">
                                <span class="ticker-icon"></span>
                                Trusted marketplace for buying and selling live event tickets. Prices may vary from face value.
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- MAIN BLUE HEADER -->
        <header class="tm-header">
            <div class="container-fluid p-0 px-md-4 px-lg-5 pb-md-4 pb-0">
                <div class="d-flex align-items-center justify-content-between py-md-4 py-3 px-md-0 px-2">
                    <!-- LEFT -->
                    <div class="d-flex align-items-center gap-4">
                        <!-- Logo -->
                        <a href="/" class="tm-logo" style="width:256px; height:auto;"><img src="<?php echo HOME_URL; ?>/assets/seatoutlet-logo.svg" alt="Seat Outlet" width="256" height="38"></a>
                    </div>
                    <!-- RIGHT -->
                    <div class="d-flex align-items-center gap-3">
                        <nav class="tm-nav-wrapper d-none d-sm-none d-md-none d-lg-block d-xl-block d-xxl-block">
                            <ul class="tm-nav" id="mainMenu">
                                <li class="menu-item"><a href="#">Concerts</a></li>
                                <li class="menu-item"><a href="#">Sports</a></li>
                                <li class="menu-item"><a href="#">Theater</a></li>
                                <li class="menu-item"><a href="#">Festivals</a></li>
                                <li class="menu-item"><a href="#">Cities</a></li>
                            </ul>
                        </nav>
                        <div class="tm-top-links d-flex d-sm-flex d-md-flex align-items-center">
                            <a href="#" class="tm-account">
                                <i class="bi bi-person fs-3"></i>
                                <span class="d-none d-sm-none d-md-none d-lg-block d-xl-block d-xxl-block so-signin">Sign In/Register</span>
                            </a>
                            <button class="btn mobile-menu-btn d-sm-block d-md-block d-lg-none d-xl-none d-xxl-none p-0" data-bs-toggle="offcanvas" data-bs-target="#mobileMenu">
                                <i class="bi bi-list fs-3 text-white"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <form method="post" action="<?php echo HOME_URL; ?>/search.php" class="search-bar-form">
                    <div class="search-bar-container d-flex flex-md-row p-md-1">
                        <div class="city-location search-item d-flex align-items-center gap-md-2 gap-1 px-3 py-2 flex-fill header-location-close locationInputFieldWrapper">
                            <svg class="icon" style="color: rgb(50 85 223);" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            <input type="text" placeholder="City or Zip Code" class="w-100" autocomplete="off" class="locationInputField" id="locationInputHeader" name="locationInputHeader" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="location-suggestions" aria-activedescendant="" aria-label="Search by city or zip code" value="<?php echo !empty($_POST['locationInputHeader']) ? htmlspecialchars($_POST['locationInputHeader']) : ''; ?>" />
                            <button type="button" id="locationHeaderReset" class="location-close<?php echo !empty($_POST['locationInputHeader']) ? '' : ' d-none'; ?>">												
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-x-octagon" viewBox="0 0 16 16">
                                    <path d="M4.54.146A.5.5 0 0 1 4.893 0h6.214a.5.5 0 0 1 .353.146l4.394 4.394a.5.5 0 0 1 .146.353v6.214a.5.5 0 0 1-.146.353l-4.394 4.394a.5.5 0 0 1-.353.146H4.893a.5.5 0 0 1-.353-.146L.146 11.46A.5.5 0 0 1 0 11.107V4.893a.5.5 0 0 1 .146-.353zM5.1 1 1 5.1v5.8L5.1 15h5.8l4.1-4.1V5.1L10.9 1z"/>
                                    <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708"/>
                                </svg>
                            </button>
                            <div id="locationResultsHeader" class="tn-dropdown-menu dropdown"></div>                           
                        </div>
                        <div class="date-range search-item d-flex align-items-center so-date-picker-wrapper gap-md-2 gap-1 px-md-3 px-2 py-2 flex-fill border-start" style="border-color: rgba(0,0,0,0.1);">
                            <svg class="icon" style="color: rgb(50 85 223);" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <input type="text" id="customDatePicker" placeholder="Select Date Range" autocomplete="off" readonly role="combobox" aria-haspopup="dialog" aria-expanded="false" aria-controls="date-picker-dialog" aria-label="Select date range" value="<?php echo !empty($dateTitle) ? htmlspecialchars($dateTitle) : ''; ?>"> 
                            <div class="filter-arrow"><i id="dateArrowHeader" class="bi bi-chevron-down"></i></div>
                        </div>
                        <div class="performer-city-venue search-item d-flex align-items-center gap-md-2 gap-1 px-3 py-2 flex-fill border-start header-venus-close" style="border-color: rgba(0,0,0,0.1);">
                            <svg class="icon" style="color: rgb(50 85 223);" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            <input type="text" placeholder="Performer, City or Venue" class="w-100" autocomplete="off" id="keywordHeader" name="keywordHeader" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="search-suggestions" aria-activedescendant="" aria-label="Search for performers, cities or venues" value="<?php echo !empty($_POST['keywordHeader']) ? htmlspecialchars($_POST['keywordHeader']) : ''; ?>" />
                            <button type="button" id="keywordHeaderReset" class="d-none location-close">												
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-x-octagon" viewBox="0 0 16 16">
                                    <path d="M4.54.146A.5.5 0 0 1 4.893 0h6.214a.5.5 0 0 1 .353.146l4.394 4.394a.5.5 0 0 1 .146.353v6.214a.5.5 0 0 1-.146.353l-4.394 4.394a.5.5 0 0 1-.353.146H4.893a.5.5 0 0 1-.353-.146L.146 11.46A.5.5 0 0 1 0 11.107V4.893a.5.5 0 0 1 .146-.353zM5.1 1 1 5.1v5.8L5.1 15h5.8l4.1-4.1V5.1L10.9 1z"/>
                                    <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708"/>
                                </svg>
                            </button>
                            <div id="search-loader" class="search-loader" style="display:none;">
                                <svg width="16" height="16" viewBox="0 0 50 50">
                                    <circle 
                                    cx="25" cy="25" r="20" 
                                    fill="none" 
                                    stroke="#666" 
                                    stroke-width="4"
                                    stroke-linecap="round"
                                    stroke-dasharray="90,150"
                                    stroke-dashoffset="0">
                                    <animateTransform
                                        attributeName="transform"
                                        type="rotate"
                                        repeatCount="indefinite"
                                        dur="1s"
                                        values="0 25 25;360 25 25"/>
                                    </circle>
                                </svg>
                            </div>
                            <div id="keywordResultsHeader" class="tn-dropdown-menu dropdown"></div>
                            <button class="btn btn-stub-primary px-4 py-2 small fw-semibold rounded-pill d-md-none d-block">
                                <svg viewBox="0 0 23 24" width="1.5em" height="1.5em" aria-hidden="true" focusable="false" class="BaseSvg-sc-yh8lnd-0 MagnifyingGlassIcon___StyledBaseSvg-sc-1pooy9n-0 hNajXU"><path d="M3.78 4.78 1.62 10l2.16 5.22L9 17.38l5.22-2.16L16.38 10l-2.16-5.22L9 2.62zM9 1l6.36 2.64L18 10l-2.33 5.61 6.11 6.11-1.06 1.06-6.1-6.1L9 19l-6.36-2.64L0 10l2.64-6.36z"></path></svg>
                            </button>
                        </div>
                        <input type="hidden" id="latHeader" name="latHeader" value="<?php echo !empty($_POST['latHeader']) ? $_POST['latHeader'] : ''; ?>">
                        <input type="hidden" id="lngHeader" name="lngHeader" value="<?php echo !empty($_POST['lngHeader']) ? $_POST['lngHeader'] : ''; ?>">
                        <input type="hidden" id="startInputHeader" name="startInputHeader" value="<?php echo !empty($_POST['startInputHeader']) ? $_POST['startInputHeader'] : ''; ?>">
                        <input type="hidden" id="endInputHeader" name="endInputHeader" value="<?php echo !empty($_POST['endInputHeader']) ? $_POST['endInputHeader'] : ''; ?>">
                        <button class="btn btn-stub-primary px-4 py-2 small fw-semibold rounded-pill d-md-block d-none">Search</button>
                    </div>
                </form>
            </div>
        </header>
    </div>
    <!-- Mobile Header -->


    <div class="offcanvas offcanvas-start header-menu-mobile-logo" tabindex="-1" id="mobileMenu">
        <div class="offcanvas-header">
            <a href="<?php echo HOME_URL; ?>" class="tm-logo" style="width:200px; height:auto;"><img src="<?php echo HOME_URL; ?>/assets/blue-logo.webp" alt="Seat Outlet" width="200" height="40"></a>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>
        
        <div class="offcanvas-body header-menu-mobile">
            <nav class="mobile-nav">
                <ul class="mobile-main-menu">
                    <li class="has-submenu">

                        <!-- ✅ ADD class + data-target -->
                        <a href="#" class="open-submenu" data-target="submenu-concerts">
                            Concerts <span class="arrow">
                                <svg xmlns="http://www.w3.org/2000/svg" version="1.1" xmlns:xlink="http://www.w3.org/1999/xlink" width="16" height="16" x="0" y="0" viewBox="0 0 492.004 492.004" style="enable-background:new 0 0 512 512" xml:space="preserve" class=""><g><path d="M382.678 226.804 163.73 7.86C158.666 2.792 151.906 0 144.698 0s-13.968 2.792-19.032 7.86l-16.124 16.12c-10.492 10.504-10.492 27.576 0 38.064L293.398 245.9l-184.06 184.06c-5.064 5.068-7.86 11.824-7.86 19.028 0 7.212 2.796 13.968 7.86 19.04l16.124 16.116c5.068 5.068 11.824 7.86 19.032 7.86s13.968-2.792 19.032-7.86L382.678 265c5.076-5.084 7.864-11.872 7.848-19.088.016-7.244-2.772-14.028-7.848-19.108z" fill="#000000" opacity="1" data-original="#000000" class=""></path></g></svg>
                            </span>
                        </a>
                        
                        <!-- Submenu Panel -->
                        <div class="submenu-panel" id="submenu-concerts">
                            <div class="submenu-header">
                                <span class="back-btn">
                                    <svg xmlns="http://www.w3.org/2000/svg" version="1.1" xmlns:xlink="http://www.w3.org/1999/xlink" width="16" height="16" x="0" y="0" viewBox="0 0 492.004 492.004" style="enable-background:new 0 0 512 512" xml:space="preserve" class=""><g transform="matrix(-1,1.2246467991473532e-16,-1.2246467991473532e-16,-1,497.00405883789074,492.0039672851562)"><path d="M382.678 226.804 163.73 7.86C158.666 2.792 151.906 0 144.698 0s-13.968 2.792-19.032 7.86l-16.124 16.12c-10.492 10.504-10.492 27.576 0 38.064L293.398 245.9l-184.06 184.06c-5.064 5.068-7.86 11.824-7.86 19.028 0 7.212 2.796 13.968 7.86 19.04l16.124 16.116c5.068 5.068 11.824 7.86 19.032 7.86s13.968-2.792 19.032-7.86L382.678 265c5.076-5.084 7.864-11.872 7.848-19.088.016-7.244-2.772-14.028-7.848-19.108z" fill="#ffffff" opacity="1" data-original="#000000" class=""></path></g></svg>
                                </span>
                                <span>Concerts</span>
                                <span class="close-btn" data-bs-dismiss="offcanvas">
                                    <svg xmlns="http://www.w3.org/2000/svg" version="1.1" xmlns:xlink="http://www.w3.org/1999/xlink" width="16" height="16" x="0" y="0" viewBox="0 0 365.717 365" style="enable-background:new 0 0 512 512" xml:space="preserve" class=""><g><g fill="#f44336"><path d="M356.34 296.348 69.727 9.734c-12.5-12.5-32.766-12.5-45.247 0L9.375 24.816c-12.5 12.504-12.5 32.77 0 45.25L295.988 356.68c12.504 12.5 32.77 12.5 45.25 0l15.082-15.082c12.524-12.48 12.524-32.75.02-45.25zm0 0" fill="#ffffff" opacity="1" data-original="#f44336" class=""></path><path d="M295.988 9.734 9.375 296.348c-12.5 12.5-12.5 32.77 0 45.25l15.082 15.082c12.504 12.5 32.77 12.5 45.25 0L356.34 70.086c12.504-12.5 12.504-32.766 0-45.246L341.258 9.758c-12.5-12.524-32.766-12.524-45.27-.024zm0 0" fill="#ffffff" opacity="1" data-original="#f44336" class=""></path></g></g></svg>
                                </span>
                            </div>

                            <ul>
                                <li>
                                    <h3 class="sub-menu-heading">Popular</h3>
                                </li>
                                <li><a href="#">Hip-Hop/Rap</a></li>
                                <li><a href="#">Country</a></li>
                                <li><a href="#">Latin</a></li>
                                <li><a href="#">Alternative</a></li>
                                <!-- second -->
                                <li>
                                    <h3 class="sub-menu-heading">Discover More</h3>
                                </li>
                                <li><a href="#">Alternative</a></li>
                                <li><a href="#">Ballads/Romantic</a></li>
                                <li><a href="#">Blues</a></li>
                                <li><a href="#">Children's Music</a></li>
                                <li><a href="#">Classical</a></li>
                                <li><a href="#">Country</a></li>
                                <li><a href="#">Dance/Electronic</a></li>
                                <li><a href="#">Folk</a></li>
                                <li><a href="#">Hip-Hop/Rap</a></li>
                                <li><a href="#">Holiday</a></li>
                                <li><a href="#">Jazz</a></li>
                                <li><a href="#">Latin</a></li>
                                <li><a href="#">Medieval/Renaissance</a></li>
                                <li><a href="#">Metal</a></li>
                                <li><a href="#">New Age</a></li>
                                <li><a href="#">Other</a></li>
                                <li><a href="#">Pop</a></li>
                                <li><a href="#">R&amp;B</a></li>
                                <li><a href="#">Reggae</a></li>
                                <li><a href="#">Religious</a></li>
                                <li><a href="#">Rock</a></li>
                                <li><a href="#">World</a></li>
                            </ul>
                        </div>

                    </li>
                    <li>
                        <a href="#" title="Sports" class="mobile-menu">Sports</a>
                    </li>
                    <li>
                        <a href="#" title="Theater" class="mobile-menu">Theater</a>
                    </li>
                    <li>
                        <a href="#" title="Sell Tickets" class="mobile-menu">Sell Tickets</a>
                    </li>
                    <li>
                    <a href="#" title="Sign In" class="mobile-menu">Sign In</a>
                    </li>
                    <li>
                        <a href="/about-us.php" title="About Us" class="mobile-menu">About Us</a>
                    </li>
                    <li>
                        <a href="/contact/" title="Contact Us" class="mobile-menu">Contact Us</a>
                    </li>
                    <li>
                        <a href="/faq/" title="Faqs" class="mobile-menu">Faqs</a>
                    </li>
                    <li>
                        <a href="/privacy-policy/" title="Privacy Policy" class="mobile-menu">Privacy Policy</a>
                    </li>
                    <li>
                        <a href="/terms-and-conditions/" title="Terms of Use" class="mobile-menu">Terms of Use</a>
                    </li>
                    <li>
                        <a href="/cookie-policy/" title="Cookie Policy" class="mobile-menu">Cookie Policy</a>
                    </li>
                </ul>         
            </nav>
        </div>
    </div>
