<?php include 'header.php'; ?>

<style>

.ticketingtruths-page .section-padding {
    padding: 60px 0;
}

.trusted-question + .trusted-question{
    margin-top:30px;
}

/* Hero Section */
.ticketingtruths-page .hero-section {
    position: relative;
    background-color: #05070b;
    color: #ffffff;
    overflow: hidden;
    padding-bottom:120px;
}

.ticketingtruths-page .hero-section::before {
    content: "";
    position: absolute;
    right: -18%;
    top: -25%;
    width: 55%;
    height: 170%;
    background: linear-gradient(135deg, #0b1120 0%, #0056d6 55%, #0b1120 100%);
    transform: skewX(-18deg);
    opacity: 0.9;
    z-index: 0;
}

.ticketingtruths-page .hero-inner {
    position: relative;
    z-index: 1;
}

.ticketingtruths-page .hero-eyebrow {
    font-size: 12.8px;
    letter-spacing: 0.16em;
    text-transform: uppercase;
    color: #9ca3af;
    margin-bottom: 9.6px;
}

.hero-title {
    font-weight: 800;
    line-height: 1.1;
    font-size: 70px;
    position: relative;
    margin-bottom: 20px;
}

.ticketingtruths-page .hero-title .hero-title-blue {
    color: #2556E0;
    letter-spacing: 0.06em;
    display: block;
}

.ticketingtruths-page .hero-title .hero-title-white {
    color: #ffffff;
    letter-spacing: 0.06em;
}

.ticketingtruths-page .hero-subtitle {
    color: #d1d5db;
    font-size: 16px;
    text-align: center;
    z-index: 2;
    position: relative;
}

.ticketingtruths-page .section-light {
    background-color: #f5f5f7;
}

.ticketingtruths-page .section-blue {
    background-color: #0056d6;
    color: #ffffff;
}

.ticketingtruths-page .section-blue p {
    color: #e5e7eb;
}

.ticketingtruths-page .section-heading {
    font-size:28px;
    font-weight: 700;
    margin-bottom: 12.8px;
}

.ticketingtruths-page .section-body {
    font-size: 15.2px;
    max-width: 520px;
}

.ticketingtruths-page .section-image-wrapper {
    text-align: center;
    position: relative;
    margin-top: -120px;
}

/* CTA section */
.ticketingtruths-page .cta-section {
    background-image:url(../images/cta-banner.webp) ;
    color: #ffffff;
    padding: 100px 0;
    text-align: center;
    position: relative;
}
.ticketingtruths-page .cta-section::before {
    content: "";
    position: absolute;
    width: 100%;
    height: 100%;
    left: 0;
    top: 0;
    background-color: #00000087;
    z-index: 0;
}
.ticketingtruths-page .cta-title {
    font-size: 38px;
    font-weight: 700;
    margin-bottom: 18px;
}

.ticketingtruths-page .cta-text {
    max-width: 620px;
    margin: 0 auto 24px;
    font-size: 16px;
    color: #e5e7eb;
}

.ticketingtruths-page .btn-cta {
    border-radius: 999px;
    padding: 8.8px 30.4px;
    font-weight: 600;
    font-size: 14.4px;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    background-color: #ffffff;
    color: #0056d6;
    border: none;
}

.ticketingtruths-page .btn-cta:hover {
    background-color: #e5e7eb;
    color: #0041a3;
}
.cta-content{
    position: relative;
}


/* ================= MEDIA QUERIES ================= */

@media (min-width: 768px) {
    .ticketingtruths-page .section-image-wrapper {
        text-align: right;
    }

    .ticketingtruths-page .section-image-wrapper.img-left {
        text-align: left;
    }
}

@media (max-width: 1365px){
    .hero-title{
        font-size: 60px;
    }
    
}
@media (max-width: 1199px){
    .ticketingtruths-page .cta-section{
        padding: 80px 0;
    }
    .ticketingtruths-page .section-heading{
        font-size: 24px;
    }
}
@media (max-width: 991px){
    .hero-title {
        font-size: 50px;
    }
    .ticketingtruths-page .section-image-wrapper{
        margin-top: -90px;
    }
    .ticketingtruths-page .cta-title{
        font-size: 32px;
    }
}
@media (max-width: 767.98px) {
    .ticketingtruths-page .cta-title{
        font-size: 28px;
    }
    .ticketingtruths-page .section-padding {
        padding: 50px 0;
    }

    .ticketingtruths-page .hero-section::before {
        right: -40%;
        width: 80%;
        height: 150%;
    }

    .ticketingtruths-page .hero-subtitle {
        font-size: 14.4px;
    }

    .ticketingtruths-page .hero-image-wrapper {
        position: static;
        width: 70%;
        max-width: 320px;
        margin: 30px auto 0;
    }
    .ticketingtruths-page .section-image-wrapper{
        margin-top: 30px;
    }
    .hero-title {
        font-size: 32px;
    }
    .ticketingtruths-page .cta-section {
        padding: 50px 0;
    }
}
    </style>

    <!-- Main Content -->
    <main class="ticketingtruths-page">
<!-- Hero Section -->
      <section class="hero-section section-padding">
        <div class="container">
            
            <!-- Hero Content -->
            <div class="row justify-content-center align-items-center">
                <div class="col-lg-7 col-md-8">
                    <h1 class="hero-title text-center">
                        <span class="hero-title-white">TICKETING</span> 
                        <span class="hero-title-blue">TRUTHS</span>
                    </h1>
                    <p class="hero-subtitle">
                        Why are concert tickets so expensive? Here is a straightforward look at how ticketing really works:
                        who sets prices, where the fees go, and how money flows
                        across primary and resale ticket marketplaces.
                    </p>
                </div>
            </div>
        </div>
    </section>
        <!-- Section 1: Text Left / Image Right -->
        <section class="section-light section-padding">
            <div class="container">
                <div class="row align-items-center gx-lg-5">
                    <!-- Text -->
                    <div class="col-md-6 mb-4 mb-lg-0">
                        <!-- <div class="section-question">What is a ticket?</div> -->
                        <div class="trusted-question">
                            <h2 class="section-heading">What is a ticket?</h2>
                            <p class="section-body">
                            A ticket is your official confirmation that grants you access to an event, concert, sports game, or entertainment venue. It verifies your reservation and ensures a smooth entry experience.
                            </p>
                        </div>
                        <div class="trusted-question">
                            <h2 class="section-heading">Who issues the ticket?</h2>
                            <p class="section-body">
                            Event organizers, venues, promoters, or official ticketing partners typically issue tickets. These may include stadiums, arenas, theaters, concert halls, festivals, and other event locations.
                            </p>
                        </div>
                    </div>

                    <!-- Image -->
                    <div class="col-md-6">
                        <div class="section-image-wrapper">
                            <img
                                src="/images/ticket-trusted.webp"
                                alt="Live event crowd" width="750" height="875" decoding="async"/>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section 2: Blue - Image Left / Text Right -->
        <section class="section-blue section-padding">
            <div class="container">
                <div class="row align-items-center gx-lg-5 flex-md-row flex-column-reverse">
                    <!-- Image -->
                    <div class="col-md-6 mb-4 mb-lg-0">
                        <div class="section-image-wrapper img-left">
                            <img
                                src="/images/stage.webp"
                                alt="Stage performance" width="750" height="843" decoding="async"/>
                        </div>
                    </div>

                    <!-- Text -->
                    <div class="col-md-6">
                        <div class="trusted-question">
                            <h2 class="section-heading">Who chooses the ticketing partner?</h2>
                            <p class="section-body">
                            Event organizers and venues select their ticketing provider based on reliability, technology, customer experience, and distribution reach. They often partner with companies that can efficiently manage ticket sales and audience entry.
                            </p>
                        </div>
                        <div class="trusted-question">
                            <h2 class="section-heading">What does SeatOutlet do?</h2>
                            <p class="section-body">
                            SeatOutlet connects fans with verified event tickets through a secure and user-friendly platform. We simplify ticket discovery, provide reliable purchasing options, and help customers find seats for their favorite events quickly and safely.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section 3: Light - Text Left / Image Right -->
        <section class="section-light section-padding">
            <div class="container">
                <div class="row align-items-center gx-lg-5">
                    <!-- Text -->
                    <div class="col-md-6 mb-4 mb-lg-0">
                        <div class="trusted-question">
                            <h2 class="section-heading">Who sets the ticket prices?</h2>
                            <p class="section-body">
                            Ticket pricing is determined by event organizers, performers, or promoters. Prices may vary based on demand, seat location, event popularity, and availability.
                            </p>
                        </div>
                        <div class="trusted-question">
                            <h2 class="section-heading">Who decides event dates and ticket release schedules?</h2>
                            <p class="section-body">
                            Event schedules and ticket release timelines are decided by event organizers, performers, and promoters. They determine when tickets become available and how they are distributed to the public.
                            </p>
                        </div>
                    </div>

                    <!-- Image -->
                    <div class="col-md-6">
                        <div class="section-image-wrapper">
                            <img
                                src="/images/ticket-trusted.webp"
                                alt="Box office staff" width="750" height="875" decoding="async"/>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section 4: Blue - Image Left / Text Right -->
        <section class="section-blue section-padding">
            <div class="container">
                <div class="row align-items-center gx-lg-5 flex-md-row flex-column-reverse">
                    <!-- Image -->
                    <div class="col-md-6 mb-4 mb-lg-0">
                        <div class="section-image-wrapper img-left">
                            <img
                                src="/images/stage.webp"
                                alt="Fans cheering" width="750" height="843" decoding="async"/>
                        </div>
                    </div>

                    <!-- Text -->
                    <div class="col-md-6">
                        <div class="trusted-question">
                            <h2 class="section-heading">Who receives the revenue from ticket sales?</h2>
                            <p class="section-body">
                            Most ticket revenue goes to the event organizers, performers, and promoters. Ticketing platforms like SeatOutlet provide the marketplace and services that help connect buyers and sellers.
                            </p>
                        </div>
                        <div class="trusted-question">
                            <h2 class="section-heading">Who sets service and processing fees?</h2>
                            <p class="section-body">
                            Service and processing fees help cover payment processing, customer support, platform maintenance, and security systems. These fees ensure safe and reliable transactions for buyers and sellers.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section 5: Light - Text Left / Image Right -->
        <section class="section-light section-padding">
            <div class="container">
                <div class="row align-items-center gx-lg-5">
                    <!-- Text -->
                    <div class="col-md-6 mb-4 mb-lg-0">
                        <div class="trusted-question">
                            <h2 class="section-heading">Why are some tickets available on resale marketplaces?</h2>
                            <p class="section-body">
                            Sometimes ticket holders cannot attend events and choose to resell their tickets. Resale marketplaces allow fans to safely transfer tickets to other buyers, often based on market demand and availability.
                            </p>
                        </div>
                        <div class="trusted-question">
                            <h2 class="section-heading">Do artists or event organizers receive money from resale tickets?</h2>
                            <p class="section-body">
                            In most cases, resale transactions are handled between ticket holders and buyers. Policies may vary depending on the event organizer and regional regulations.
                            </p>
                        </div>
                    </div>

                    <!-- Image -->
                    <div class="col-md-6">
                        <div class="section-image-wrapper">
                            <img
                                src="/images/ticket-trusted.webp"
                                alt="Support staff" width="750" height="875" decoding="async"/>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Final CTA Section -->
        <section class="cta-section">
            <div class="container">
               <div class="cta-content">
               <h2 class="cta-title">Want to Find Your Next Event?</h2>
                <p class="cta-text">
                Explore thousands of verified tickets and discover unforgettable live experiences with SeatOutlet.
                </p>
                <button type="button" class="btn btn-cta">
                    Learn More
                </button>
               </div>
            </div>
        </section>
    </main>



<?php soSeoCopy('why-are-concert-tickets-so-expensive'); ?>
<?php include 'footer.php'; ?>