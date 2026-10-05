<?php
require_once __DIR__ . '/inc/contact.php';
require_once __DIR__ . '/inc/guarantee.php';
soContactRoute();   // handles the form POST before any output
include 'header.php';
?>

<style>
        
        .contact-us-page::before {
    content: '';
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: 
        linear-gradient(180deg, rgba(37, 86, 224, 0.04) 0%, transparent 50%),
        repeating-linear-gradient(
            0deg,
            transparent,
            transparent 60px,
            rgba(37, 86, 224, 0.03) 60px,
            rgba(37, 86, 224, 0.03) 61px
        ),
        repeating-linear-gradient(
            90deg,
            transparent,
            transparent 60px,
            rgba(37, 86, 224, 0.03) 60px,
            rgba(37, 86, 224, 0.03) 61px
        );
    pointer-events: none;
    z-index: 0;
}

/* Hero */
.contact-us-page .hero-section {
    position: relative;
    background-color: #05070b;
    color: #ffffff;
    overflow: hidden;
    padding: clamp(50px, 10vw, 80px) 0 clamp(60px, 12vw, 90px);
}

.contact-us-page .hero-section::before {
    content: "";
    position: absolute;
    right: -20%;
    top: -30%;
    width: 55%;
    height: 170%;
    background: linear-gradient(135deg, #0b1120 0%, #0056d6 55%, #0b1120 100%);
    transform: skewX(-18deg);
    opacity: 0.9;
    z-index: 0;
}

.contact-us-page .hero-inner {
    position: relative;
    z-index: 1;
}

.contact-us-page .hero-eyebrow {
    font-size: 12.8px;
    letter-spacing: 0.16em;
    text-transform: uppercase;
    color: #9ca3af;
    margin-bottom: 10px;
}

.contact-us-page .hero-title {
    font-weight: 800;
    line-height: 1.1;
    font-size: clamp(32px, 5vw, 52px);
    margin-bottom: 18px;
}

.contact-us-page .hero-title span.blue {
    color: #2556E0;
}

.contact-us-page .hero-subtitle {
    color: #d1d5db;
    font-size: clamp(14px, 2.5vw, 16px);
    max-width: 640px;
    margin: 0 auto;
}

/* Contact Section */
.contact-us-page .contact-section {
    position: relative;
    z-index: 1;
    padding: 64px 0 80px;
}

/* Contact Info Cards */
.contact-us-page .contact-info {
    margin-bottom: 32px;
}

.contact-us-page .info-card {
    background: #ffffff;
    border: 1px solid rgba(37, 86, 224, 0.15);
    border-radius: 16px;
    padding: 24px;
    transition: all 0.3s ease;
    box-shadow: 0 2px 12px rgba(37, 86, 224, 0.08);
    height: 100%;
}

.contact-us-page .info-card:hover {
    border-color: #2556e0;
    background: rgba(37, 86, 224, 0.12);
    transform: translateY(-2px);
}

.contact-us-page .info-card .icon {
    width: 70px;
    height: 70px;
    background: linear-gradient(135deg, #2556e0, #4a73e8);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 20px;
    padding: 0;
}

.contact-us-page .info-card h3 {
    font-size: 22px;
    font-weight: 600;
    margin-bottom: 15px;
    color: #1a1a2e;
}

.contact-us-page .info-card p {
    color: #5c5c6d;
    font-size: 14px;
}

.contact-us-page .info-card a {
    color: #2556e0;
    text-decoration: none;
    font-weight: 500;
    margin-top: 15px;
    display: inline-block;
}

.contact-us-page .info-card a:hover {
    text-decoration: underline;
}

/* Contact Form */
.contact-us-page .contact-form-wrapper {
    background: #ffffff;
    border: 1px solid rgba(37, 86, 224, 0.15);
    border-radius: 24px;
    padding: 32px;
    position: relative;
    overflow: hidden;
    box-shadow: 0 4px 24px rgba(37, 86, 224, 0.08);
}

.contact-us-page .contact-form-wrapper::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, #2556e0, #4a73e8);
}

.contact-us-page .contact-form h2 {
    font-size: 28px;
    margin-bottom: 8px;
}

.contact-us-page .contact-form .subtitle {
    color: #5c5c6d;
    margin-bottom: 32px;
    font-size: 15.2px;
}

.contact-us-page .form-row .form-group {
    margin-bottom: 0;
}

.contact-us-page .form-group {
    margin-bottom: 20px;
}

.contact-us-page .form-group label {
    display: block;
    font-size: 14px;
    font-weight: 500;
    margin-bottom: 8px;
    color: #1a1a2e;
}

.contact-us-page .contact-form .form-group input,
.contact-us-page .contact-form .form-group select,
.contact-us-page .contact-form .form-group textarea,
.contact-us-page .contact-form .form-control {
    width: 100%;
    padding: 16px 20px;
    background: #f8f9fc;
    border: 1px solid rgba(37, 86, 224, 0.15);
    border-radius: 12px;
    color: #1a1a2e;
    font-size: 16px;
    transition: all 0.2s;
}

.contact-us-page .form-group input::placeholder,
.contact-us-page .form-group textarea::placeholder {
    color: #5c5c6d;
    opacity: 0.8;
}

.contact-us-page .contact-form .form-group input:focus,
.contact-us-page .contact-form .form-group select:focus,
.contact-us-page .contact-form .form-group textarea:focus,
.contact-us-page .contact-form .form-control:focus {
    outline: none;
    border-color: #2556e0;
    background: rgba(37, 86, 224, 0.12);
    box-shadow: none;
}

.contact-us-page .form-group textarea {
    min-height: 140px;
    resize: vertical;
}

.contact-us-page .form-group select {
    cursor: pointer;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23a0a0a0' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 16px center;
    padding-right: 40px;
}

.contact-us-page .submit-btn {
    width: 100%;
    padding: 16px 32px;
    background: linear-gradient(135deg, #2556e0, #4a73e8);
    border: none;
    border-radius: 12px;
    color: white;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    margin-top: 8px;
}

.contact-us-page .submit-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 30px rgba(37, 86, 224, 0.35);
}

.contact-us-page .submit-btn:active {
    transform: translateY(0);
}

/* Quick Help */
.contact-us-page .quick-help {
    position: relative;
    z-index: 1;
    padding: 64px 0 80px;
}

.contact-us-page .quick-help h2 {
    font-size: clamp(24px, 4vw, 32px);
    text-align: center;
    margin-bottom: 32px;
}

.contact-us-page .help-card {
    background: #ffffff;
    border: 1px solid rgba(37, 86, 224, 0.15);
    border-radius: 16px;
    padding: 24px;
    transition: all 0.2s;
    box-shadow: 0 2px 12px rgba(37, 86, 224, 0.08);
    height: 100%;
}

.contact-us-page .help-card:hover {
    border-color: #2556e0;
}

.contact-us-page .help-card h4 {
    font-size: 16px;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.contact-us-page .help-card h4::before {
    content: '?';
    width: 24px;
    height: 24px;
    background: rgba(37, 86, 224, 0.12);
    color: #2556e0;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 700;
}

.contact-us-page .help-card p {
    color: #5c5c6d;
    font-size: 14.4px;
    line-height: 1.6;
}

.contact-us-page .contact-title{
    color: #000;
    font-size: 48px;
    font-weight: 600;
    margin-bottom: 20px;
}

/* ================= MEDIA QUERIES (BOTTOM) ================= */

@media (min-width: 992px) {
    .contact-us-page .contact-info {
        margin-bottom: 0;
    }
}

@media (min-width: 768px) {
    .contact-us-page .info-card {
        padding: 28px;
    }

    .contact-us-page .contact-form-wrapper {
        padding: 48px;
    }

    .contact-us-page .help-card {
        padding: 32px;
    }
}

@media (max-width: 1365px){
    .contact-us-page .contact-title{
        font-size: 42px;
    }
}

@media (max-width:1199px){
    .contact-us-page .contact-title{
        font-size: 38px;
    }
}

@media (max-width: 991px) {
    .privacy-page .hero-title {
        font-size: 40px;
    }
}
@media (max-width: 767px) {
    .privacy-page .hero-title {
        font-size: 30px;
    }
}
</style>

<main class="contact-us-page">
    <section class="hero-section">
        <div class="container">
            <div class="row justify-content-center text-center">
                <div class="col-lg-8 hero-inner">
                    <div class="hero-eyebrow">Contact Us</div>
                    <h1 class="hero-title">
                        <span>Get in</span> <span class="blue">Touch</span>
                    </h1>
                    <p class="hero-subtitle">
                    Have a question about your tickets or need assistance? Our ticket customer service team is here to help you every step of the way.
                    </p>
                </div>
            </div>
        </div>
    </section>
    <section class="contact-section">
        <div class="container">
            <div class="row align-items-center g-lg-5 g-4">
                <!-- Contact Info Cards -->
                <div class="col-12 col-md-4 col-lg-6">
                    <div class="row contact-info">
                        <div class="col-12 col-sm-12 col-lg-12 mb-lg-4 mb-3 mt-0">
                            <h2 class="contact-title">Ways to Reach Us</h2>
                            <p>Email us or use the form. Our support team can help with ticket delivery, order questions and event changes. Include your order ID from the confirmation email so we can find your order faster.</p>
                        </div>
                        <div class="col-12 col-sm-12 col-lg-12">
                            <div class="info-card">
                                <div class="icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" version="1.1" xmlns:xlink="http://www.w3.org/1999/xlink" width="35" height="35" x="0" y="0" viewBox="0 0 512 512" style="enable-background:new 0 0 512 512" xml:space="preserve" class=""><g><path d="M467 76H45C20.137 76 0 96.262 0 121v270c0 24.885 20.285 45 45 45h422c24.655 0 45-20.03 45-45V121c0-24.694-20.057-45-45-45zm-6.302 30L287.82 277.967c-8.5 8.5-19.8 13.18-31.82 13.18s-23.32-4.681-31.848-13.208L51.302 106h409.396zM30 384.894V127.125L159.638 256.08 30 384.894zM51.321 406l129.587-128.763 22.059 21.943c14.166 14.166 33 21.967 53.033 21.967s38.867-7.801 53.005-21.939l22.087-21.971L460.679 406H51.321zM482 384.894 352.362 256.08 482 127.125v257.769z" fill="#ffffff" opacity="1" data-original="#ffffff" class=""></path></g></svg>
                                </div>
                                <h3>Email Us</h3>
                                <p>For general inquiries and support</p>
                                <a href="mailto:support@seatoutlet.com">support@seatoutlet.com</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Contact Form -->
                <div class="col-12 col-md-6">
                    <div class="contact-form-wrapper">
                        <?php echo soContactForm(['title' => 'Send a Message', 'intro' => 'Fill out the form and a member of our team will reply to you by email.']); ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <section class="quick-help">
        <div class="container">
            <h2 class="mb-4">Quick Help</h2>
            <div class="row g-3 g-md-4">
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="help-card">
                        <h4>Find your order</h4>
                        <p>Your order confirmation email has your order number and delivery details. Include the order number when you write to us.</p>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="help-card">
                        <h4>Guarantee and refunds</h4>
                        <p><?php echo soContactH(soGuaranteeSentence()); ?> <a href="/worry-free-guarantee">Read the full guarantee</a>.</p>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="help-card">
                        <h4>Business enquiries</h4>
                        <p>Choose "Partnership" as the topic in the form above, or see the <a href="/ticket-partner-program">partner page</a>.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<script src="<?php echo soContactH(soAsset('js/contact-form.js')); ?>" defer></script>
<?php soSeoCopy('ticket-customer-service'); ?>
<?php include 'footer.php'; ?>