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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">    
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <link rel="stylesheet" href="<?php echo HOME_URL; ?>/css/style.css?v=<?php echo time(); ?>">



</head>

<body>
    <div class="header-top-section">
        <!-- Top Utility Bar -->
        <div class="tm-topbar">
            <div class="container-fluid d-flex justify-content-between align-items-center px-3 px-md-4 px-lg-5">
                <div class="tm-country">
                    <button type="button" class="btn btn-link">
                        <svg fill="none" viewBox="0 0 512 512" width="1.5em" height="1.5em" aria-hidden="true" class="sc-fc75cc60-5 lbOkgz">
                            <path fill="#FFF" d="M503.2 322.8c5.7-21.3 8.8-43.7 8.8-66.8l-8.8-66.8a254.6 254.6 0 0 0-28.8-66.8l-59-66.7A255 255 0 0 0 256 0h-.2A255 255 0 0 0 96.6 55.7l-59 66.7a254.6 254.6 0 0 0-28.8 66.8L0 256v.1c0 23 3 45.4 8.8 66.7l28.8 66.8a257.3 257.3 0 0 0 59 66.7L256 512l159.4-55.7a257.3 257.3 0 0 0 59-66.7z"></path>
                            <path fill="#D80027" d="M503.2 189.2c5.7 21.3 8.8 43.7 8.8 66.8H0c0-23.1 3-45.5 8.8-66.8zM415.4 55.7a257.3 257.3 0 0 1 59 66.7H37.6a257.3 257.3 0 0 1 59-66.7zm59 333.9c12.6-20.6 22.4-43 28.8-66.8H8.8a254.6 254.6 0 0 0 28.8 66.8zm-59 66.7H96.6A255 255 0 0 0 255.8 512h.4a255 255 0 0 0 159.2-55.7"></path>
                            <path fill="#0052B4" d="M0 245.6A256 256 0 0 1 256 0v256H0z"></path>
                            <path fill="#FFF" fill-rule="evenodd" d="M109.5 46a256 256 0 0 1 26.2-16l1 3h27.8L142 49.2l8.7 26.6L128 59.5l-22.6 16.4 8.6-26.6zm-80 90.4c6-11.1 12.7-21.8 20.1-32l3.8 11.7h28l-22.7 16.4 8.7 26.6-22.6-16.4L22.2 159l7.4-22.7Zm181.7-130 8.6 26.5h28L225 49.3l8.7 26.6-22.6-16.4-22.6 16.4 8.7-26.6L174.7 33h27.9l8.6-26.5ZM128 89.6l8.6 26.5h28l-22.7 16.4 8.7 26.6-22.6-16.4-22.6 16.4 8.6-26.6-22.5-16.4h27.9zm91.8 26.5-8.6-26.5-8.6 26.5h-28l22.7 16.4-8.7 26.6 22.6-16.4 22.6 16.4-8.7-26.6 22.6-16.4zm-175 56.7 8.6 26.5h28l-22.7 16.4 8.7 26.6-22.6-16.4-22.6 16.4 8.7-26.6-22.6-16.4h27.9zm91.8 26.5-8.6-26.5-8.6 26.5h-28l22.6 16.4-8.6 26.6 22.6-16.4 22.6 16.4-8.7-26.6 22.6-16.4zm74.6-26.5 8.6 26.5h28L225 215.7l8.7 26.6-22.6-16.4-22.6 16.4 8.7-26.6-22.6-16.4h27.9l8.6-26.5Z" clip-rule="evenodd"></path>
                        </svg> US
                    </button>
                </div>

                <div class="tm-top-links d-none d-md-flex align-items-center">
                    <a href="#"><i class="bi bi-building"></i> Hotels</a>
                     <a href="#" class="tm-account">
                            <i class="bi bi-person"></i>
                            <span class="d-none d-xxl-inline">Sign In/Register</span>
                        </a>
                    <!-- <a href="#">Sell</a>
                    <a href="#"><i class="bi bi-gift"></i> Gift Cards</a>
                    <a href="#">Help</a>
                    <a href="#">VIP</a>
                    <span class="paypal"><img src="../artists/paypal_small.svg" alt="RAYE" class="img-fluid"></span> -->
                </div>
            </div>
        </div>

        <!-- MAIN BLUE HEADER -->
        <header class="tm-header">
            <div class="container-fluid px-3 px-md-4 px-lg-5">
                <div class="d-flex align-items-center justify-content-between py-4">

                    <!-- LEFT -->
                    <div class="d-flex align-items-center gap-4">
                        <!-- Logo -->
                        <a href="/" class="tm-logo">SeatOutlet</a>

                        <!-- Navigation -->
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

                    </div>

                    <!-- RIGHT -->
                    <div class="d-flex align-items-center gap-3">
                        <!-- Search -->
                        <div class="tm-search d-none d-md-flex">
                            <div class="tm-search-bar">
                                <div class="tm-search-label">SEARCH</div>
                                <input type="text" placeholder="Artist, Event or Venue">
                            </div>
                            <button type="submit" class="btn btn-link-search">
                                <i class="bi bi-search"></i></button>
                        </div>

                        <!-- Account -->
                        <!-- <a href="#" class="tm-account">
                            <i class="bi bi-person"></i>
                            <span class="d-none d-xxl-inline">Sign In/Register</span>
                        </a> -->
                    </div>

                </div>
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
