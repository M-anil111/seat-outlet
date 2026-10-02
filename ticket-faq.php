<?php include 'header.php'; ?>


<style>
        

        main.faq-page .faq-intro{
            padding-top:100px;
        }

        /* Hero / Inner Banner */
        .faq-page .hero-section {
            position: relative;
            background-color: #05070b;
            color: #ffffff;
            overflow: hidden;
            padding: 80px 0 90px;
        }

        .faq-page .hero-section::before {
            content: "";
            position: absolute;
            right: -20%;
            top: -30%;
            width: 55%;
            height: 170%;
            background: linear-gradient(135deg, #0b1120 0%, #2556E0 55%, #0b1120 100%);
            transform: skewX(-18deg);
            opacity: 0.9;
            z-index: 0;
        }

        .faq-page .hero-inner {
            position: relative;
            z-index: 1;
        }

        .faq-page .hero-title {
            font-weight: 800;
            line-height: 1.1;
            font-size: 52px;
            margin-bottom: 8px;
        }

        .faq-page .hero-subtitle {
            color: #d1d5db;
            font-size: 16px;
            max-width: 740px;
            margin: 0 auto;
        }
        

        /* INTRO (TITLE + DESCRIPTION) */
        .faq-page .faq-intro {
            text-align: center;
        }

        .faq-page .faq-intro-title {
            font-size: 38px;
            font-weight: 600;
            color: #111827;
            margin-bottom: 0.6rem;
        }

        .faq-page .faq-intro-text {
            font-size: 0.98rem;
            color: #4b5563;
            margin-inline: auto;
        }

        /* FAQ SECTION */
        .faq-page .faq-section {
            padding-bottom:100px;
        }

        .faq-page .faq-card {
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 18px 55px rgba(15, 23, 42, 0.12);
            border: 1px solid rgba(209, 213, 219, 0.8);
            padding: 0;
        }

        .faq-page .faq-card h3 {
            font-size: 21px;
            font-weight: 600;
            padding: 25px 30px;
            background-color: #2556E0;
            border-radius: 10px 10px 0 0;
            color: #fff;
            margin: 0;
        }

        .faq-page .faq-card .accordion{
            padding: 25px 30px;
        }

        .faq-page .accordion-item {
            border-radius: 0.85rem !important;
            border: 1px solid #e5e7eb;
            overflow: hidden;
            margin-bottom: 0.75rem;
            background-color: #ffffff;
        }

        .faq-page .accordion-button {
            padding-top: 0.9rem;
            padding-bottom: 0.9rem;
            font-weight: 500;
            font-size:18px;
        }

        .faq-page .accordion-button:not(.collapsed) {
            color: #2556E0;
            background-color: rgba(37, 86, 224, 0.03);
            box-shadow: inset 0 -1px 0 rgba(229, 231, 235, 0.7);
        }

        .faq-page .accordion-button:focus {
            box-shadow: 0 0 0 0.15rem rgba(37, 86, 224, 0.28);
            border-color: #2556E0;
        }

        .faq-page .accordion-body {
            font-size: 0.95rem;
            color: #4b5563;
            line-height: 1.7;
            padding-top: 15px;
        }

        .faq-page .accordion-button::after {
            filter: hue-rotate(200deg);
        }

        @media (max-width: 991.98px) {
            .faq-page .hero-title {
                font-size: 40px;
            }

            main.faq-page .faq-intro{
                padding-top:60px;
            }
            .faq-page .faq-section{
                padding-bottom:60px
            }
            .faq-page .faq-intro-title{
                font-size:32px;
            }
            .faq-page .accordion-button{
                font-size: 16px;
    padding-right: 10px;
            }
        }

        @media (max-width: 575.98px) {
            .faq-page .faq-card {
                padding: 0;
            }
            .faq-page .hero-title {
                font-size: 30px;
            }
            main.faq-page .faq-intr {
                padding-top:50px;
            }
            .faq-page .faq-section{
                padding-bottom:50px
            }
            .faq-page .faq-intro-title{
                font-size:28px;
            }
        }
    </style>

<main class="faq-page">
       
        <section class="hero-section">
            <div class="container">
                <div class="row justify-content-center text-center">
                    <div class="col-lg-9 hero-inner">
                        <h1 class="hero-title">Ticket FAQ: Frequently Asked Questions</h1>
                        <p class="hero-subtitle mt-3">Everything you need to know about buying, receiving, and using your event tickets.
                        Clear answers so you can focus on the experience, not the logistics.</p>
                    </div>
                </div>
            </div>
        </section>
        <!-- Title & Description Section -->
        <section class="faq-intro">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <h2 class="faq-intro-title">Event Ticket Help & Category FAQs</h2>
                        <p class="faq-intro-text mb-lg-5 mb-4">
                        Find helpful answers related to ticket purchases across our event categories, including concerts, sports, theatre, and festivals. Each section provides important information about ticket delivery, entry requirements, and event-specific policies to help you prepare for a smooth and enjoyable experience.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- FAQ Section -->
        <section class="faq-section">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-6">
                        <div class="faq-card mb-4">
                            <!-- 1. Ticket Purchase & Delivery -->
                            <h3 class="mb-0">Ticket Purchase & Delivery</h3>
                            <div class="accordion mb-0" id="purchaseFaq">
                                <div class="accordion-item border-0">
                                    <button class="accordion-button bg-transparent" data-bs-toggle="collapse" data-bs-target="#purchaseOne">
                                        How do I purchase tickets?
                                    </button>
                                    <div id="purchaseOne" class="accordion-collapse collapse show" data-bs-parent="#purchaseFaq">
                                        <div class="accordion-body">
                                            Select your event, choose seats or ticket type, and complete checkout using available payment methods.
                                        </div>
                                    </div>
                                </div>
                                <div class="accordion-item border-0">
                                    <button class="accordion-button bg-transparent collapsed" data-bs-toggle="collapse" data-bs-target="#purchaseTwo">
                                        When will I receive my tickets?
                                    </button>
                                    <div id="purchaseTwo" class="accordion-collapse collapse" data-bs-parent="#purchaseFaq">
                                        <div class="accordion-body">
                                            Tickets may be delivered immediately or closer to the event date depending on organizer release timing.
                                        </div>
                                    </div>
                                </div>
                                <div class="accordion-item border-0">
                                    <button class="accordion-button bg-transparent collapsed" data-bs-toggle="collapse" data-bs-target="#purchaseThree">
                                        How can I access my tickets?
                                    </button>
                                    <div id="purchaseThree" class="accordion-collapse collapse" data-bs-parent="#purchaseFaq">
                                        <div class="accordion-body">
                                            Use your order confirmation email. It has the delivery details for your order.
                                        </div>
                                    </div>
                                </div>
                                <div class="accordion-item border-0">
                                    <button class="accordion-button bg-transparent collapsed" data-bs-toggle="collapse" data-bs-target="#purchaseFour">
                                        Can I transfer my ticket to someone else?
                                    </button>
                                    <div id="purchaseFour" class="accordion-collapse collapse" data-bs-parent="#purchaseFaq">
                                        <div class="accordion-body">
                                            Ticket transfers depend on event policies. If allowed, transfer instructions will be provided.
                                        </div>
                                    </div>
                                </div>
                                <div class="accordion-item border-0">
                                    <button class="accordion-button bg-transparent collapsed" data-bs-toggle="collapse" data-bs-target="#purchaseFive">
                                        Do I need to print my ticket?
                                    </button>
                                    <div id="purchaseFive" class="accordion-collapse collapse" data-bs-parent="#purchaseFaq">
                                        <div class="accordion-body">
                                            Most events accept mobile tickets. Printing is only required if specified on the event page.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="faq-card mb-4">
                               <!-- 2. Event Entry & Venue Rules -->
                            <h3 class="mb-0">Event Entry & Venue Rules</h3>
                            <div class="accordion mb-0" id="entryFaq">
                                <div class="accordion-item border-0">
                                    <button class="accordion-button bg-transparent" data-bs-toggle="collapse" data-bs-target="#entryOne">
                                        What do I need to bring to the event?
                                    </button>
                                    <div id="entryOne" class="accordion-collapse collapse show" data-bs-parent="#entryFaq">
                                        <div class="accordion-body">
                                            Bring your valid ticket and government-issued ID if required by the venue.
                                        </div>
                                    </div>
                                </div>
                                <div class="accordion-item border-0">
                                    <button class="accordion-button bg-transparent collapsed" data-bs-toggle="collapse" data-bs-target="#entryTwo">
                                        Are there age restrictions for events?
                                    </button>
                                    <div id="entryTwo" class="accordion-collapse collapse" data-bs-parent="#entryFaq">
                                        <div class="accordion-body">
                                            Some events have age limits. Please review event details before purchasing tickets.
                                        </div>
                                    </div>
                                </div>
                                <div class="accordion-item border-0">
                                    <button class="accordion-button bg-transparent collapsed" data-bs-toggle="collapse" data-bs-target="#entryThree">
                                        Can I leave and re-enter the venue?
                                    </button>
                                    <div id="entryThree" class="accordion-collapse collapse" data-bs-parent="#entryFaq">
                                        <div class="accordion-body">
                                            Re-entry policies vary by venue and event organizer.
                                        </div>
                                    </div>
                                </div>
                                <div class="accordion-item border-0">
                                    <button class="accordion-button bg-transparent collapsed" data-bs-toggle="collapse" data-bs-target="#entryFour">
                                        What items are not allowed inside venues?
                                    </button>
                                    <div id="entryFour" class="accordion-collapse collapse" data-bs-parent="#entryFaq">
                                        <div class="accordion-body">
                                            Restricted items usually include large bags, outside food, and professional cameras.
                                        </div>
                                    </div>
                                </div>
                                <div class="accordion-item border-0">
                                    <button class="accordion-button bg-transparent collapsed" data-bs-toggle="collapse" data-bs-target="#entryFive">
                                        What happens if I arrive late?
                                    </button>
                                    <div id="entryFive" class="accordion-collapse collapse" data-bs-parent="#entryFaq">
                                        <div class="accordion-body">
                                            Late entry rules vary. Some venues delay entry until suitable breaks in the event.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="faq-card mb-4">
                            <!-- 3. Refunds, Cancellations & Rescheduling -->
                            <h3 class="mb-0">Refunds, Cancellations & Rescheduling</h3>
                            <div class="accordion mb-0" id="refundFaq">

                                <div class="accordion-item border-0">
                                    <button class="accordion-button bg-transparent" data-bs-toggle="collapse" data-bs-target="#refundOne">
                                        Are tickets refundable?
                                    </button>
                                    <div id="refundOne" class="accordion-collapse collapse show" data-bs-parent="#refundFaq">
                                        <div class="accordion-body">
                                            Tickets are not refundable for a change of plans. If an event is canceled, the order is refunded (delivery fees excluded) under the guarantee.
                                        </div>
                                    </div>
                                </div>

                                <div class="accordion-item border-0">
                                    <button class="accordion-button bg-transparent collapsed" data-bs-toggle="collapse" data-bs-target="#refundTwo">
                                        What happens if an event is canceled?
                                    </button>
                                    <div id="refundTwo" class="accordion-collapse collapse" data-bs-parent="#refundFaq">
                                        <div class="accordion-body">
                                            Under the 100% guarantee, a canceled event is refunded in full, delivery fees excluded. Contact us with your order ID and we will help with the next steps.
                                        </div>
                                    </div>
                                </div>

                                <div class="accordion-item border-0">
                                    <button class="accordion-button bg-transparent collapsed" data-bs-toggle="collapse" data-bs-target="#refundThree">
                                        What if my event is rescheduled?
                                    </button>
                                    <div id="refundThree" class="accordion-collapse collapse" data-bs-parent="#refundFaq">
                                        <div class="accordion-body">
                                            Tickets typically remain valid for the new date. Whether a refund is available for a rescheduled event depends on TicketNetwork's policies and the organizer.
                                        </div>
                                    </div>
                                </div>

                                <div class="accordion-item border-0">
                                    <button class="accordion-button bg-transparent collapsed" data-bs-toggle="collapse" data-bs-target="#refundFour">
                                        Can I exchange my ticket?
                                    </button>
                                    <div id="refundFour" class="accordion-collapse collapse" data-bs-parent="#refundFaq">
                                        <div class="accordion-body">
                                            Ticket exchange depends on the event and organizer rules.
                                        </div>
                                    </div>
                                </div>

                                <div class="accordion-item border-0">
                                    <button class="accordion-button bg-transparent collapsed" data-bs-toggle="collapse" data-bs-target="#refundFive">
                                        How long does refund processing take?
                                    </button>
                                    <div id="refundFive" class="accordion-collapse collapse" data-bs-parent="#refundFaq">
                                        <div class="accordion-body">
                                            Timing depends on the payment provider and on the event, so we cannot give a fixed number of days. Contact us with your order ID for an update.
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>


                    </div>
                    <div class="col-lg-6">
                        <div class="faq-card mb-4">
                            <!-- 4. Payments & Orders -->
                            <h3 class="mb-0">Payments & Orders</h3>
                            <div class="accordion mb-0" id="paymentFaq">

                                <div class="accordion-item border-0">
                                    <button class="accordion-button bg-transparent" data-bs-toggle="collapse" data-bs-target="#paymentOne">
                                        What payment methods are accepted?
                                    </button>
                                    <div id="paymentOne" class="accordion-collapse collapse show" data-bs-parent="#paymentFaq">
                                        <div class="accordion-body">
                                            Most major credit cards, debit cards, and online payment options are accepted.
                                        </div>
                                    </div>
                                </div>

                                <div class="accordion-item border-0">
                                    <button class="accordion-button bg-transparent collapsed" data-bs-toggle="collapse" data-bs-target="#paymentTwo">
                                        Why did my payment fail?
                                    </button>
                                    <div id="paymentTwo" class="accordion-collapse collapse" data-bs-parent="#paymentFaq">
                                        <div class="accordion-body">
                                            Payment failures may occur due to bank restrictions, insufficient funds, or technical errors.
                                        </div>
                                    </div>
                                </div>

                                <div class="accordion-item border-0">
                                    <button class="accordion-button bg-transparent collapsed" data-bs-toggle="collapse" data-bs-target="#paymentThree">
                                        Will I receive an order confirmation?
                                    </button>
                                    <div id="paymentThree" class="accordion-collapse collapse" data-bs-parent="#paymentFaq">
                                        <div class="accordion-body">
                                            Yes, order confirmations are sent via email after successful payment.
                                        </div>
                                    </div>
                                </div>

                                <div class="accordion-item border-0">
                                    <button class="accordion-button bg-transparent collapsed" data-bs-toggle="collapse" data-bs-target="#paymentFour">
                                        Can I download an invoice?
                                    </button>
                                    <div id="paymentFour" class="accordion-collapse collapse" data-bs-parent="#paymentFaq">
                                        <div class="accordion-body">
                                            Your order confirmation email is your record of the purchase. If you need something else for an expense report, contact us with your order ID.
                                        </div>
                                    </div>
                                </div>

                                <div class="accordion-item border-0">
                                    <button class="accordion-button bg-transparent collapsed" data-bs-toggle="collapse" data-bs-target="#paymentFive">
                                        Are promo codes accepted?
                                    </button>
                                    <div id="paymentFive" class="accordion-collapse collapse" data-bs-parent="#paymentFaq">
                                        <div class="accordion-body">
                                            Promo codes can be applied during checkout if available.
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="faq-card mb-4">
                            <h3 class="mb-0">Event Categories FAQs</h3>
                            <div class="accordion" id="categoryFaq">

                                <div class="accordion-item border-0">
                                    <button class="accordion-button bg-transparent" data-bs-toggle="collapse" data-bs-target="#categoryOne">
                                        Do different event types have different rules?
                                    </button>
                                    <div id="categoryOne" class="accordion-collapse collapse show" data-bs-parent="#categoryFaq">
                                        <div class="accordion-body">
                                            Yes, concerts, sports, theatre, and festivals may have unique entry or ticket policies.
                                        </div>
                                    </div>
                                </div>

                                <div class="accordion-item border-0">
                                    <button class="accordion-button bg-transparent collapsed" data-bs-toggle="collapse" data-bs-target="#categoryTwo">
                                        Where can I find category-specific information?
                                    </button>
                                    <div id="categoryTwo" class="accordion-collapse collapse" data-bs-parent="#categoryFaq">
                                        <div class="accordion-body">
                                            Category rules are usually mentioned on the event listing page.
                                        </div>
                                    </div>
                                </div>

                                <div class="accordion-item border-0">
                                    <button class="accordion-button bg-transparent collapsed" data-bs-toggle="collapse" data-bs-target="#categoryThree">
                                        Do festivals have different ticket access?
                                    </button>
                                    <div id="categoryThree" class="accordion-collapse collapse" data-bs-parent="#categoryFaq">
                                        <div class="accordion-body">
                                            Festivals may include multi-day access and special entry procedures.
                                        </div>
                                    </div>
                                </div>

                                <div class="accordion-item border-0">
                                    <button class="accordion-button bg-transparent collapsed" data-bs-toggle="collapse" data-bs-target="#categoryFour">
                                        Are sports tickets assigned seating?
                                    </button>
                                    <div id="categoryFour" class="accordion-collapse collapse" data-bs-parent="#categoryFaq">
                                        <div class="accordion-body">
                                            Most sports events include assigned seating, but some may offer general admission.
                                        </div>
                                    </div>
                                </div>

                                <div class="accordion-item border-0">
                                    <button class="accordion-button bg-transparent collapsed" data-bs-toggle="collapse" data-bs-target="#categoryFive">
                                        Do theatre events have special entry timing?
                                    </button>
                                    <div id="categoryFive" class="accordion-collapse collapse" data-bs-parent="#categoryFaq">
                                        <div class="accordion-body">
                                            Theatre events often require early arrival and may restrict late entry.
                                        </div>
                                    </div>
                                </div>

                            </div>

                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="faq-card mb-4">
                            <!-- 6. Orders & Customer Support -->
                            <h3 class="mb-0">Orders & Customer Support</h3>
                            <div class="accordion mb-0" id="accountFaq">

                                <div class="accordion-item border-0">
                                    <button class="accordion-button bg-transparent" data-bs-toggle="collapse" data-bs-target="#accountOne">
                                        Do I need an account to purchase tickets?
                                    </button>
                                    <div id="accountOne" class="accordion-collapse collapse show" data-bs-parent="#accountFaq">
                                        <div class="accordion-body">
                                            No. Seat Outlet does not have customer accounts or logins. You continue to checkout from the event page, and your confirmation email is your record of the order.
                                        </div>
                                    </div>
                                </div>

                                <div class="accordion-item border-0">
                                    <button class="accordion-button bg-transparent collapsed" data-bs-toggle="collapse" data-bs-target="#accountTwo">
                                        How can I find my order?
                                    </button>
                                    <div id="accountTwo" class="accordion-collapse collapse" data-bs-parent="#accountFaq">
                                        <div class="accordion-body">
                                            Search your inbox (and spam folder) for the order confirmation email. If you cannot find it, contact us with the event name and the email address you used.
                                        </div>
                                    </div>
                                </div>

                                <div class="accordion-item border-0">
                                    <button class="accordion-button bg-transparent collapsed" data-bs-toggle="collapse" data-bs-target="#accountThree">
                                        What if I did not get my confirmation email?
                                    </button>
                                    <div id="accountThree" class="accordion-collapse collapse" data-bs-parent="#accountFaq">
                                        <div class="accordion-body">
                                            Check your spam folder first, then contact us with the event name and the email address you used at checkout and we will look into it.
                                        </div>
                                    </div>
                                </div>

                                <div class="accordion-item border-0">
                                    <button class="accordion-button bg-transparent collapsed" data-bs-toggle="collapse" data-bs-target="#accountFour">
                                        How can I update my personal details?
                                    </button>
                                    <div id="accountFour" class="accordion-collapse collapse" data-bs-parent="#accountFaq">
                                        <div class="accordion-body">
                                            Contact us with your order ID and tell us what needs to change. Some changes depend on the seller and the event.
                                        </div>
                                    </div>
                                </div>

                                <div class="accordion-item border-0">
                                    <button class="accordion-button bg-transparent collapsed" data-bs-toggle="collapse" data-bs-target="#accountFive">
                                        How do I contact customer support?
                                    </button>
                                    <div id="accountFive" class="accordion-collapse collapse" data-bs-parent="#accountFaq">
                                        <div class="accordion-body">
                                            Use the <a href="/ticket-customer-service">contact form</a> or email support@seatoutlet.com. Include your order ID if you have one.
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

<?php soSeoCopy('ticket-faq'); ?>
<?php include 'footer.php'; ?>