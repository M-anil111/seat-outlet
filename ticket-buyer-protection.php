<?php include 'header.php'; ?>

<style>
    /* Content layout: simple, step-by-step */

    .privacy-page .content-wrapper {
        max-width: 900px;
        margin: 0 auto;
    }

    .privacy-page .policy-section {
        border-top: 1px solid #e5e7eb;
        padding-top: 30px;
        margin-top: 30px;
    }

    .privacy-page .policy-section:first-of-type {
        border-top: none;
        padding-top: 0;
        margin-top: 0;
    }

    .privacy-page h2 {
        font-size: 24px;
        font-weight: 700;
        margin-bottom: 14px;
    }

    .privacy-page .policy-num {
        font-size: 22px;
        font-weight: 600;
        color: #0056d6;
        margin-bottom: 8px;
    }

    .privacy-page h3 {
        font-size: 17px;
        font-weight: 600;
        margin-top: 16px;
        margin-bottom: 8px;
    }

    .privacy-page p {
        font-size: 15px;
        color: #374151;
        margin-bottom: 10px;
    }

    .privacy-page ul {
        padding-left: 20px;
        margin-bottom: 10px;
    }

    .privacy-page ul li {
        font-size: 14.5px;
        color: #374151;
        margin-bottom: 4px;
        list-style: disc;
    }
    .feature-section h2 {
  font-size: 32px;
}

.feature-card {
  background: #fff;
  padding: 30px 20px;
  border-radius: 16px;
  transition: all 0.3s ease;
  height: 100%;
  box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.feature-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}

.feature-section .icon {
  width: 60px;
  height: 60px;
  background: linear-gradient(135deg, var(--clr-primary) 0%, #1a3fa8 40%, #0e2272 100%);
  color: #fff;
  font-size: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 12px;
  margin: 0 auto 15px;
}

.feature-card h5 {
  font-weight: 600;
  margin-bottom: 10px;
}

.feature-card p {
  font-size: 14px;
  color: #666;
}
.feature-card::before{
  display: none;
}
    /* Responsive */

    @media (max-width: 991px) {
        .privacy-page .hero-title {
            font-size: 40px;
        }
    }

    @media (max-width: 767.98px) {
        .privacy-page .section-padding {
            padding: 40px 0;
        }

        .privacy-page .hero-title {
            font-size: 30px;
        }
    }
</style>

<main class="privacy-page">

    <!-- Hero / Inner Banner -->
    <section class="hero-section">
        <div class="container">
            <div class="row justify-content-center text-center">
                <div class="col-lg-9 hero-inner">
                    <h1 class="hero-title">Ticket Buyer Protection</h1>
                   
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content (exact text, sections 1–14) -->
    <section class="feature-section py-5 text-center">
  <div class="container">
    
    <h2 class="fw-bold mb-3">Trust & Safety Focus</h2>
    <p class="text-muted mb-5">
    <?php echo getContentBlock('/buyer-protection', 'trust-safety-intro', 'We are committed to providing a safe and reliable ticket purchasing experience. Our Buyer Protection Guarantee ensures that every order placed through our platform is secure, authentic, and supported from purchase to event day.'); ?>
    </p>

    <div class="row g-4">

      <!-- Card 1 -->
      <div class="col-12 col-sm-6 col-lg-4">
        <div class="feature-card">
          <div class="icon"><i class="bi bi-ticket-perforated "></i></div>
          <h5>100% Valid Tickets</h5>
          <p>All tickets purchased through our platform are guaranteed to be valid and authentic. If there is ever an issue with the validity of your tickets, we will work to provide replacement tickets of equal or better value, or offer a full refund.</p>
        </div>
      </div>

      <!-- Card 2 -->
      <div class="col-12 col-sm-6 col-lg-4">
        <div class="feature-card">
          <div class="icon"><i class="bi bi-truck"></i></div>
          <h5>On-Time Delivery</h5>
          <p>Your tickets will arrive before the event. Delivery methods may include electronic delivery, mobile transfer, or physical shipping depending on the event and seller. If tickets do not arrive in time, our support team will assist you in resolving the issue quickly.</p>
        </div>
      </div>

      <!-- Card 3 -->
      <div class="col-12 col-sm-6 col-lg-4">
        <div class="feature-card">
          <div class="icon"><i class="bi bi-fingerprint"></i></div>
          <h5>Secure Transactions</h5>
          <p>All transactions are processed through secure payment systems designed to protect your personal and financial information. We use industry-standard security measures to ensure your data remains safe.</p>
        </div>
      </div>

      <!-- Card 4 -->
      <div class="col-12 col-sm-6 col-lg-4">
        <div class="feature-card">
          <div class="icon"><svg xmlns="http://www.w3.org/2000/svg" version="1.1" xmlns:xlink="http://www.w3.org/1999/xlink" width="24" height="24" x="0" y="0" viewBox="0 0 100 100" style="enable-background:new 0 0 512 512" xml:space="preserve" class=""><g><path d="m94.27 55.2-11-5.07v-22a1.25 1.25 0 0 0-.62-1.09L44.77 5.19a1.27 1.27 0 0 0-1.25 0L5.65 27.05A1.26 1.26 0 0 0 5 28.14v43.72A1.26 1.26 0 0 0 5.65 73l37.87 21.81h.19a1.32 1.32 0 0 0 .43.09 1.27 1.27 0 0 0 .43-.09h.2l15.11-8.72a30.88 30.88 0 0 0 12.59 8.83 1.09 1.09 0 0 0 .36.06 1.13 1.13 0 0 0 .36-.06c.23-.07 23.07-7.34 21.8-38.63a1.25 1.25 0 0 0-.72-1.09zm-50.13-6.27-13.23-7.64 35.65-20.58 13.24 7.64zM20 37.86l6.86 4v14.02L20 51.65zm1.27-2.16 35.65-20.61 7.19 4.15-35.7 20.61zm22.91-28 10.28 5.93-35.73 20.63-10.28-5.94zm-36.62 23 9.94 5.73v15.91a1.26 1.26 0 0 0 .6 1.07l9.35 5.78a1.28 1.28 0 0 0 .66.18 1.31 1.31 0 0 0 .61-.15 1.27 1.27 0 0 0 .64-1.1V43.26l13.53 7.84v40.46L7.52 71.14zm37.83 60.86V51.1l35.37-20.42V49l-7.41-3.41a1.19 1.19 0 0 0-1 0L51.39 55.2a1.26 1.26 0 0 0-.73 1.09c-.52 13.06 3.15 21.92 7.62 27.83zm27.45.85c-2.75-1-20.44-8.62-19.7-35.26l19.69-9.07 19.69 9.07c.75 26.85-16.8 34.23-19.68 35.26zm9.94-30.69a1.24 1.24 0 0 1 .22 1.75l-12.12 15a1.22 1.22 0 0 1-1 .47 1.24 1.24 0 0 1-1-.52l-6.21-8.69a1.25 1.25 0 1 1 2-1.46l5.26 7.35L81 61.91a1.24 1.24 0 0 1 1.78-.19z" fill="#fff" opacity="1" data-original="#fff" class=""></path></g></svg></div>
          <h5>Orders Guaranteed</h5>
          <p>Once your order is confirmed, it is protected by our guarantee. In the rare case that an issue occurs with your order, our support team will work to resolve it promptly.</p>
        </div>
      </div>

      <!-- Card 5 -->
      <div class="col-12 col-sm-6 col-lg-4">
        <div class="feature-card">
          <div class="icon"><svg xmlns="http://www.w3.org/2000/svg" version="1.1" xmlns:xlink="http://www.w3.org/1999/xlink" width="24" height="24" x="0" y="0" viewBox="0 0 50 50" style="enable-background:new 0 0 512 512" xml:space="preserve" class="hovered-paths"><g><path d="M24.63 44.93c.12.05.24.07.37.07s.25-.02.37-.07C31.83 42.35 36 36.18 36 29.23V23c0-.31-.15-.61-.4-.8l-4-3a.984.984 0 0 0-.6-.2H19c-.22 0-.43.07-.6.2l-4 3c-.25.19-.4.49-.4.8v6.23c0 6.95 4.17 13.12 10.63 15.7zM16 23.5l3.33-2.5h11.33L34 23.5v5.73c0 6-3.52 11.33-9 13.69-5.48-2.36-9-7.69-9-13.69z" fill="#ffffff" opacity="1" data-original="#ffffff" class="hovered-path"></path><path d="m23.21 33.71 6-6a.996.996 0 1 0-1.41-1.41l-5.29 5.29-1.29-1.29a.996.996 0 1 0-1.41 1.41l2 2c.37.39 1.01.39 1.4 0z" fill="#ffffff" opacity="1" data-original="#ffffff" class="hovered-path"></path><path d="M46 5h-3.06c-.31-2.87-1.93-5-3.94-5s-3.63 2.13-3.94 5h-6.12c-.31-2.87-1.93-5-3.94-5s-3.63 2.13-3.94 5h-6.12c-.31-2.87-1.93-5-3.94-5S7.37 2.13 7.06 5H4C1.79 5 0 6.79 0 9v37c0 2.21 1.79 4 4 4h42c2.21 0 4-1.79 4-4V9c0-2.21-1.79-4-4-4zm-7-3c.8 0 1.67 1.23 1.92 3h-3.85c.26-1.77 1.13-3 1.93-3zM25 2c.8 0 1.67 1.23 1.92 3h-3.85c.26-1.77 1.13-3 1.93-3zM11 2c.8 0 1.67 1.23 1.92 3H9.08C9.33 3.23 10.2 2 11 2zm37 44c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V16h46zm0-32H2V9c0-1.1.9-2 2-2h8.92c-.25 1.77-1.12 3-1.92 3-.55 0-1 .45-1 1s.45 1 1 1c2.01 0 3.63-2.13 3.94-5h11.98c-.25 1.77-1.12 3-1.92 3-.55 0-1 .45-1 1s.45 1 1 1c2.01 0 3.63-2.13 3.94-5h11.98c-.25 1.77-1.12 3-1.92 3-.55 0-1 .45-1 1s.45 1 1 1c2.01 0 3.63-2.13 3.94-5H46c1.1 0 2 .9 2 2z" fill="#fff" opacity="1" data-original="#fff" class="hovered-path"></path></g></svg></div>
          <h5>Event Cancellation Protection</h5>
          <p>If an event is canceled and not rescheduled, you will receive a full refund for your ticket purchase.</p>
        </div>
      </div>

      <!-- Card 6 -->
      <div class="col-12 col-sm-6 col-lg-4">
        <div class="feature-card">
          <div class="icon"><svg xmlns="http://www.w3.org/2000/svg" version="1.1" xmlns:xlink="http://www.w3.org/1999/xlink" width="24" height="24" x="0" y="0" viewBox="0 0 64 64" style="enable-background:new 0 0 512 512" xml:space="preserve" class=""><g><path d="M59.506 27.903A27.322 27.322 0 0 0 51.47 9.43C46.267 4.226 39.352 1.361 32 1.361c-14.849 0-26.982 11.819-27.505 26.542a6.474 6.474 0 0 0-3.852 5.911v7.262a6.475 6.475 0 0 0 6.468 6.468 4.19 4.19 0 0 0 4.185-4.185V31.53c0-2.167-1.662-3.934-3.775-4.144C8.301 14.559 18.979 4.361 32 4.361c6.552 0 12.713 2.554 17.35 7.191 4.258 4.258 6.75 9.808 7.125 15.835-2.111.212-3.77 1.978-3.77 4.143v11.828c0 2.185 1.688 3.963 3.826 4.148v2.989a6.111 6.111 0 0 1-6.105 6.104h-4.521a4.497 4.497 0 0 0-4.267-3.038h-4.66c-.669 0-1.311.142-1.899.416a4.547 4.547 0 0 0-2.64 4.122c0 1.214.473 2.354 1.33 3.207a4.504 4.504 0 0 0 3.209 1.332h4.66c1.932 0 3.635-1.249 4.27-3.039h4.518c5.021 0 9.105-4.084 9.105-9.104v-3.523a6.473 6.473 0 0 0 3.826-5.898v-7.262c0-2.634-1.586-4.902-3.851-5.909zM8.295 31.53v11.828c0 .653-.531 1.185-1.185 1.185a3.472 3.472 0 0 1-3.468-3.468v-7.262a3.472 3.472 0 0 1 3.468-3.468c.654 0 1.185.532 1.185 1.185zm34.848 26.891a1.546 1.546 0 0 1-1.506 1.219h-4.66c-.41 0-.795-.16-1.089-.454a1.523 1.523 0 0 1-.45-1.085 1.542 1.542 0 0 1 1.539-1.538h4.66c.41 0 .795.159 1.088.453.29.289.45.675.45 1.085.001.11-.011.22-.032.32zm17.214-17.346a3.472 3.472 0 0 1-3.468 3.468 1.186 1.186 0 0 1-1.185-1.185V31.53c0-.653.531-1.185 1.185-1.185a3.472 3.472 0 0 1 3.468 3.468z" fill="#ffffff" opacity="1" data-original="#ffffff" class=""></path><path d="M41.713 41.592a6.363 6.363 0 0 0 6.356-6.356V22.285c0-1.694-.662-3.29-1.864-4.492s-2.797-1.864-4.492-1.864H22.287a6.363 6.363 0 0 0-6.356 6.356v12.951a6.363 6.363 0 0 0 6.356 6.356h.119v3.356a3.109 3.109 0 0 0 3.112 3.124 3.05 3.05 0 0 0 2.196-.927l5.583-5.553zm-10.091-2.564-6.042 6.009c-.027.028-.047.049-.104.024-.069-.028-.069-.073-.069-.113v-4.856a1.5 1.5 0 0 0-1.5-1.5h-1.619a3.36 3.36 0 0 1-3.356-3.356V22.285a3.36 3.36 0 0 1 3.356-3.356h19.426c.894 0 1.735.35 2.371.985.636.636.985 1.478.985 2.371v12.951a3.36 3.36 0 0 1-3.356 3.356H32.68c-.397 0-.777.157-1.058.436z" fill="#ffffff" opacity="1" data-original="#ffffff" class=""></path><path d="M24.713 26.787c-1.22 0-2.213.994-2.213 2.213s.994 2.213 2.213 2.213c1.221 0 2.215-.994 2.215-2.213s-.993-2.213-2.215-2.213zM31.999 26.787c-1.22 0-2.213.994-2.213 2.213s.994 2.213 2.213 2.213c1.222 0 2.215-.994 2.215-2.213s-.993-2.213-2.215-2.213zM39.285 26.787c-1.22 0-2.213.994-2.213 2.213s.994 2.213 2.213 2.213c1.221 0 2.215-.994 2.215-2.213s-.994-2.213-2.215-2.213z" fill="#ffffff" opacity="1" data-original="#ffffff" class=""></path></g></svg></div>
          <h5>Dedicated Customer Support</h5>
          <p>Our customer support team is available to help you with any questions or concerns regarding your order. From purchase to event day, we are here to ensure a smooth and enjoyable experience.</p>
        </div>
      </div>

    </div>
  </div>
</section>
<section class="section-padding">
        <div class="container">
            <div class="content-wrapper">

                <!-- 1 -->
                <div class="policy-section" id="section-1">
                    <img src="/images/secure-payment-p3.png" class="img-fluid rounded mb-3" alt="Buyer protection secures every ticket order" loading="lazy" width="65" height="68" decoding="async">
                    <h2>Buyer Protection: Trusted Purchase Protection</h2>
                    <p>
                    Our buyer protection guarantee is backed by the same
                    <a href="https://consumer.ftc.gov/" target="_blank" rel="noopener">consumer-safety principles the FTC recommends</a>
                    for online purchases.
                    </p>
                    <p>To help ensure a safe experience, orders placed through the marketplace typically include protections such as:</p>
<ul>
<li>Guaranteed valid tickets for entry</li>
<li>On-time ticket delivery before the event</li>
<li>Secure payment processing</li>
<li>Customer support if issues arise</li>
</ul>
                    <p></p>

                </div>


                <div class="policy-section" id="section-10">
                    <h2>Our Commitment</h2>
                    <p>We want you to buy with confidence. Our Buyer Protection Guarantee is designed to make sure you receive the tickets you ordered and enjoy your event without worry - see our
                    <a href="/worry-free-guarantee">full guarantee policy</a> for details.</p>
                </div>
            </div>
        </div>
    </section>

</main>

<?php soSeoCopy('ticket-buyer-protection'); ?>
<?php include 'footer.php'; ?>