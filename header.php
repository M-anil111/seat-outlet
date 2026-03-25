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

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick-theme.css">    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo HOME_URL; ?>/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo HOME_URL; ?>/css/skeleton.css?v=<?php echo time(); ?>">

    <script src="https://maps.googleapis.com/maps/api/js?key=<?php echo GAPI_KEY; ?>&libraries=places"></script>
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
            <div class="container-fluid px-3 px-md-4 px-lg-5 pb-4">
                <div class="d-flex align-items-center justify-content-between py-4">
                    <!-- LEFT -->
                    <div class="d-flex align-items-center gap-4">
                        <!-- Logo -->
                        <a href="/" class="tm-logo" style="width:256px; height:auto;"><img src="<?php echo HOME_URL; ?>/assets/seatoutlet-logo.svg" alt="Seat Outlet"></a>
                    </div>
                    <!-- RIGHT -->
                    <div class="d-flex align-items-center gap-3">
                        <nav class="tm-nav-wrapper d-none d-sm-none d-md-none d-lg-block d-xl-block d-xxl-block">
                            <ul class="tm-nav" id="mainMenu">
                                <li class="menu-item"><a href="#">Concerts</a></li>
                                <li class="menu-item"><a href="#">Sports</a></li>
                                <li class="menu-item"><a href="#">Theatre</a></li>
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
                <form method="post" action="<?php echo HOME_URL; ?>/search.php">
                    <div class="search-bar-container d-flex flex-column flex-sm-row p-1">
                        <div class="d-flex align-items-center gap-2 px-3 py-2 flex-fill header-location-close">
                            <svg class="icon" style="color: rgb(50 85 223);" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            <input type="text" placeholder="City or Zip Code" class="w-100" autocomplete="off" id="locationInputHeader" name="locationInputHeader" value="<?php echo !empty($_POST['locationInputHeader']) ? $_POST['locationInputHeader'] : ''; ?>" />
                            <button type="button" id="locationHeaderReset" class="d-none location-close">												
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-x-octagon" viewBox="0 0 16 16">
                                    <path d="M4.54.146A.5.5 0 0 1 4.893 0h6.214a.5.5 0 0 1 .353.146l4.394 4.394a.5.5 0 0 1 .146.353v6.214a.5.5 0 0 1-.146.353l-4.394 4.394a.5.5 0 0 1-.353.146H4.893a.5.5 0 0 1-.353-.146L.146 11.46A.5.5 0 0 1 0 11.107V4.893a.5.5 0 0 1 .146-.353zM5.1 1 1 5.1v5.8L5.1 15h5.8l4.1-4.1V5.1L10.9 1z"/>
                                    <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708"/>
                                </svg>
                            </button>
                            <div id="locationResultsHeader" class="tn-dropdown-menu dropdown"></div>                           
                        </div>
                        <div class="d-flex align-items-center so-date-picker-wrapper gap-2 px-3 py-2 flex-fill border-start" style="border-color: rgba(0,0,0,0.1);">
                            <svg class="icon" style="color: rgb(50 85 223);" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <input type="text" id="customDatePicker" placeholder="Select Date Range" autocomplete="off" readonly value="<?php echo !empty($dateTitle) ? $dateTitle : ''; ?>"> 
                            <div class="filter-arrow"><i id="dateArrowHeader" class="bi bi-chevron-down"></i></div>
                        </div>
                        <div class="d-flex align-items-center gap-2 px-3 py-2 flex-fill border-start header-venus-close" style="border-color: rgba(0,0,0,0.1);">
                            <svg class="icon" style="color: rgb(50 85 223);" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            <input type="text" placeholder="Artist, Event or Venue" class="w-100" autocomplete="off" id="keywordHeader" name="keywordHeader" value="<?php echo !empty($_POST['keywordHeader']) ? $_POST['keywordHeader'] : ''; ?>" />
                            <button type="button" id="keywordHeaderReset" class="d-none location-close">												
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-x-octagon" viewBox="0 0 16 16">
                                    <path d="M4.54.146A.5.5 0 0 1 4.893 0h6.214a.5.5 0 0 1 .353.146l4.394 4.394a.5.5 0 0 1 .146.353v6.214a.5.5 0 0 1-.146.353l-4.394 4.394a.5.5 0 0 1-.353.146H4.893a.5.5 0 0 1-.353-.146L.146 11.46A.5.5 0 0 1 0 11.107V4.893a.5.5 0 0 1 .146-.353zM5.1 1 1 5.1v5.8L5.1 15h5.8l4.1-4.1V5.1L10.9 1z"/>
                                    <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708"/>
                                </svg>
                            </button>
                            <div id="keywordResultsHeader" class="tn-dropdown-menu dropdown"></div>
                          
                        </div>
                        <input type="hidden" id="latHeader" name="latHeader" value="<?php echo !empty($_POST['latHeader']) ? $_POST['latHeader'] : ''; ?>">
                        <input type="hidden" id="lngHeader" name="lngHeader" value="<?php echo !empty($_POST['latHeader']) ? $_POST['latHeader'] : ''; ?>">
                        <input type="hidden" id="startInputHeader" name="startInputHeader" value="<?php echo !empty($_POST['startInputHeader']) ? $_POST['startInputHeader'] : ''; ?>">
                        <input type="hidden" id="endInputHeader" name="endInputHeader" value="<?php echo !empty($_POST['endInputHeader']) ? $_POST['endInputHeader'] : ''; ?>">
                        <input type="hidden" id="keywordType" name="keywordType" value="<?php echo !empty($_POST['keywordType']) ? $_POST['keywordType'] : ''; ?>">
                        <input type="hidden" id="keywordId" name="keywordId" value="<?php echo !empty($_POST['keywordId']) ? $_POST['keywordId'] : ''; ?>">
                        <button class="btn btn-stub-primary px-4 py-2 small fw-semibold rounded-pill">Search</button>
                    </div>
                </form>
            </div>
        </header>
    </div>
    <!-- Mobile Header -->


    <div class="offcanvas offcanvas-start" tabindex="-1" id="mobileMenu">
        <div class="offcanvas-header">
            <!-- <a href="/" class="tm-logo text-primary">SeatOutlet</a> -->
            <a href="/" class="tm-logo" style="width:200px; height:auto;"><img src="<?php echo HOME_URL; ?>/assets/blue-logo.webp" alt="Seat Outlet"></a>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>
        <hr>
        <div class="offcanvas-body">
            <nav class="mobile-nav">
                <a href="#">Concerts</a>
                <a href="#">Sports</a>
                <a href="#">Theater</a>
                <a href="#">Sell Tickets</a>

            </nav>
        </div>
    </div>
