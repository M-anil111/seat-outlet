<?php include 'functions.php'; ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Seat Outlet</title>
    <meta name="robots" content="noindex nofollow">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick-theme.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="<?php echo HOME_URL; ?>/css/style.css?v=<?php echo time(); ?>">   

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
                <!-- <div class="ticker-fade ticker-fade-left"></div> -->

                <div class="ticker-track">
                    <div class="ticker-content">
                        <span class="ticker-item">
                            <span class="ticker-icon"></span>
                            Trusted marketplace for buying and selling live event tickets. Prices may vary from face value.
                        </span>

                        <span class="ticker-item">
                            <span class="ticker-icon"></span>
                            Trusted marketplace for buying and selling live event tickets. Prices may vary from face value.
                        </span>
                        <span class="ticker-item">
                            <span class="ticker-icon"></span>
                            Trusted marketplace for buying and selling live event tickets. Prices may vary from face value.
                        </span>
                        <span class="ticker-item">
                            <span class="ticker-icon"></span>
                            Trusted marketplace for buying and selling live event tickets. Prices may vary from face value.
                        </span>
                        <span class="ticker-item">
                            <span class="ticker-icon"></span>
                            Trusted marketplace for buying and selling live event tickets. Prices may vary from face value.
                        </span>
                        <span class="ticker-item">
                            <span class="ticker-icon"></span>
                            Trusted marketplace for buying and selling live event tickets. Prices may vary from face value.
                        </span>
                        <span class="ticker-item">
                            <span class="ticker-icon"></span>
                            Trusted marketplace for buying and selling live event tickets. Prices may vary from face value.
                        </span>
                        <span class="ticker-item">
                            <span class="ticker-icon"></span>
                            Trusted marketplace for buying and selling live event tickets. Prices may vary from face value.
                        </span>
                    </div>
                </div>

                <!-- <div class="ticker-fade ticker-fade-right"></div> -->
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
                        <a href="/" class="tm-logo">SeatOutlet</a>

                        <!-- Navigation -->
                        

                    </div>

                    <!-- RIGHT -->
                    <div class="d-flex align-items-center gap-3">
                    <nav class="tm-nav-wrapper">
                            <ul class="tm-nav" id="mainMenu">
                                <li class="menu-item"><a href="#">Concerts</a></li>
                                <li class="menu-item"><a href="#">Sports</a></li>
                                <li class="menu-item"><a href="#">Theatre</a></li>
                                <li class="menu-item"><a href="#">Festivals</a></li>
                                <li class="menu-item"><a href="#">Cities</a></li>

                                <li class="menu-item more-item more-item dropdown" id="moreItem">
                                    <a href="#" class="dropdown-toggle" data-bs-toggle="dropdown">More</a>
                                    <ul class="dropdown-menu" id="moreMenu"></ul>
                                </li>
                            </ul>

                        </nav>
                        <div class="tm-top-links d-none d-md-flex align-items-center">
                            <a href="#" class="tm-account">
                                    <i class="bi bi-person fs-3"></i>
                                    <span class="d-none d-xxl-inline">Sign In/Register</span>
                                </a>
                
                        </div>
                        <!-- Search -->
                        <!-- <div class="tm-search d-none d-md-flex">
                            <div class="tm-search-bar">
                                <div class="tm-search-label">SEARCH</div>
                                <input type="text" placeholder="Artist, Event or Venue">
                            </div>
                            <button type="submit" class="btn btn-link-search">
                                <i class="bi bi-search"></i></button>
                        </div> -->

                        <!-- Account -->
                        <!-- <a href="#" class="tm-account">
                            <i class="bi bi-person"></i>
                            <span class="d-none d-xxl-inline">Sign In/Register</span>
                        </a> -->
                    </div>

                </div>
                <form method="post" action="<?php echo HOME_URL; ?>/search.php">
                    <div class="search-bar-container d-flex flex-column flex-sm-row p-1 w-50 m-auto">
                        <div class="d-flex align-items-center gap-2 px-3 py-2 flex-fill">
                            <svg class="icon" style="color: rgb(50 85 223);" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            <input type="text" placeholder="City or Zip Code" class="w-100" autocomplete="off" id="locationInputHeader" value="<?php echo !empty($title) ? $title : ''; ?>" />
                            <div id="locationResultsHeader" class="tn-dropdown-menu dropdown"></div>
                        </div>
                        <div class="d-flex align-items-center gap-2 px-3 py-2 flex-fill border-start" style="border-color: rgba(0,0,0,0.1);">
                            <svg class="icon" style="color: rgb(50 85 223);" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <input type="text" placeholder="All Dates" class="w-100" autocomplete="off" id="dateRangeHeader" value="<?php echo !empty($dateTitle) ? $dateTitle : ''; ?>" />
                            <div class="filter-arrow"><i id="dateArrowHeader" class="bi bi-chevron-down"></i></div>
                        </div>
                        <div class="date-picker-wrapper">
                            <div id="datePickerSectionHeader" class="opacity-zero picker-wrapper">
                                <div class="calendar-wrapper">
                                    <div id="calendarHeader"></div>
                                </div>
                                <div class="footer-actions">
                                    <span class="reset-link" id="resetDatesHeader">Reset</span>
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-outline-secondary" id="cancelDatesHeader" type="button">Cancel</button>
                                        <button class="btn btn-primary" id="applyDatesHeader" type="button">Apply</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2 px-3 py-2 flex-fill border-start" style="border-color: rgba(0,0,0,0.1);">
                            <svg class="icon" style="color: rgb(50 85 223);" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            <input type="text" placeholder="Artist, Event or Venue" class="w-100" autocomplete="off" id="keywordHeader" name="keywordHeader" value="<?php echo !empty($keywordTitle) ? $keywordTitle : ''; ?>" />
                            <div id="keywordResultsHeader" class="tn-dropdown-menu dropdown"></div>
                        </div>
                        <input type="hidden" id="latHeader" name="latHeader"><input type="hidden" id="lngHeader" name="lngHeader">
                        <input type="hidden" id="startInputHeader" name="startInputHeader"><input type="hidden" id="endInputHeader" name="endInputHeader">
                        <input type="hidden" id="keywordType" name="keywordType"><input type="hidden" id="keywordId" name="keywordId">
                        <button class="btn btn-stub-primary px-4 py-2 small fw-semibold rounded-pill">Search</button>
                    </div>
                </form>
            </div>
        </header>
    </div>
    <!-- Mobile Header -->
    <header class="mobile-header">
        <div class="container-fluid px-3 px-md-4 px-lg-5">
            <div class="d-flex align-items-center justify-content-between py-2">

                <!-- Menu -->
                <button class="btn p-0" data-bs-toggle="offcanvas" data-bs-target="#mobileMenu">
                    <i class="bi bi-list fs-3 text-white"></i>
                </button>

                <!-- Logo -->
                <a href="/" class="mobile-logo">SeatOutlet</a>

                <!-- Actions -->
                <div class="d-flex gap-3">
                    <button class="btn p-0 text-white" data-bs-toggle="collapse" data-bs-target="#mobileSearch">
                        <i class="bi bi-search fs-5"></i>
                    </button>
                    <a href="#" class="text-dark">
                        <a href="#" class="tm-account">
                            <i class="bi bi-person"></i>
                        </a>
                    </a>
                </div>

            </div>

            <!-- Mobile Search -->
            <div class="collapse" id="mobileSearch">
                <div class="py-2">
                    <div class="tm-search w-100">
                        <div class="tm-search-bar">
                            <div class="tm-search-label">SEARCH</div>
                            <input type="text" placeholder="Artist, Event or Venue">
                        </div>
                        <button type="submit" class="btn btn-link-search">
                            <i class="bi bi-search"></i></button>
                    </div>
                    <!-- <input type="text" class="form-control" placeholder="Search events, artists, teams, or venues"> -->
                </div>
            </div>
        </div>
    </header>

    <div class="offcanvas offcanvas-start" tabindex="-1" id="mobileMenu">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title">Menu</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>

        <div class="offcanvas-body">
            <nav class="mobile-nav">
                <a href="#">Concerts</a>
                <a href="#">Sports</a>
                <a href="#">Theater</a>
                <a href="#">Sell Tickets</a>
                <hr>
                <a href="#">Sign In</a>
            </nav>
        </div>
    </div>
    </div>
