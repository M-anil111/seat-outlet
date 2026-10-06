<?php require_once __DIR__ . '/inc/guarantee.php'; include 'header.php'; ?>

<style>
    /* Content layout: simple, step-by-step */

    .privacy-page .content-wrapper {
        max-width: none;   /* the page container sets the width, like the home page copy */
        margin: 0;
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

    /* Apple-style cards: quiet tiles on a soft panel, small icon, left-aligned copy */
    .feature-section { background: #f5f5f7; text-align: left; }
    .feature-section .so-lead { text-align: center; max-width: 720px; margin: 0 auto 36px; }
    .feature-section h2 { font-size: clamp(28px, 4vw, 40px); font-weight: 700; letter-spacing: -.02em; color: #1d1d1f; margin-bottom: 12px; }
    .feature-section .so-lead p { font-size: 17px; line-height: 1.5; color: #6e6e73; margin: 0; }
    .feature-card { background: #fff; border: 1px solid rgba(0,0,0,.06); border-radius: 20px; padding: 26px 24px 28px; height: 100%; box-shadow: none; transition: transform .25s ease, box-shadow .25s ease; }
    .feature-card:hover { transform: translateY(-3px); box-shadow: 0 12px 32px rgba(0,0,0,.08); }
    .feature-card::before { display: none; }
    .feature-section .icon { width: 44px; height: 44px; border-radius: 12px; background: #eaf1ff; color: #0056d6; font-size: 22px; display: flex; align-items: center; justify-content: center; margin: 0 0 18px; }
    .feature-section .icon svg { width: 24px; height: 24px; }
    .feature-section .icon svg path { fill: currentColor; }
    .feature-card h5 { font-size: 19px; font-weight: 600; letter-spacing: -.01em; color: #1d1d1f; margin-bottom: 8px; }
    .feature-card p { font-size: 15px; line-height: 1.55; color: #6e6e73; margin: 0; }
    .privacy-page .policy-section img { display: none; }
    .privacy-page .policy-section h2 { font-size: clamp(24px, 3vw, 32px); letter-spacing: -.02em; color: #1d1d1f; }
    .privacy-page .policy-section p, .privacy-page .policy-section li { font-size: 17px; line-height: 1.6; color: #424245; }
    .privacy-page .policy-section ul { list-style: none; padding: 0; margin: 14px 0 0; display: grid; gap: 10px; }
    .privacy-page .policy-section ul li { list-style: none; position: relative; padding: 14px 16px 14px 46px; background: #f5f5f7; border-radius: 14px; margin: 0; }
    .privacy-page .policy-section ul li::before { content: ""; position: absolute; left: 16px; top: 50%; width: 18px; height: 18px; margin-top: -9px; border-radius: 50%; background: #0056d6 url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3E%3Cpath d='M4 8.5l2.7 2.7L12 5.6' fill='none' stroke='%23fff' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E") center/12px no-repeat; }
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
    <section class="feature-section section-padding">
  <div class="container">
    
    <div class="so-lead">
    <h2>What is covered</h2>
    <p>
    <?php echo getContentBlock('/buyer-protection', 'trust-safety-intro', 'Every Seat Outlet order is fulfilled through the TicketNetwork marketplace and covered by its 100% guarantee. This page explains what that covers, in the same words as our guarantee page.'); ?>
    </p>
    </div>

    <div class="row g-4">

      <!-- Card 1 -->
      <div class="col-12 col-sm-6 col-lg-4">
        <div class="feature-card">
          <div class="icon"><i class="bi bi-ticket-perforated "></i></div>
          <h5>Authentic, Valid Tickets</h5>
          <p>Your tickets will be authentic and valid for entry.</p>
        </div>
      </div>

      <!-- Card 2 -->
      <div class="col-12 col-sm-6 col-lg-4">
        <div class="feature-card">
          <div class="icon"><i class="bi bi-truck"></i></div>
          <h5>Delivery Before the Event</h5>
          <p>Your tickets will be shipped in time for at least one delivery attempt before the event. Delivery may be electronic, a mobile transfer or physical shipping, depending on the event and seller.</p>
        </div>
      </div>

      <!-- Card 3 -->
      <div class="col-12 col-sm-6 col-lg-4">
        <div class="feature-card">
          <div class="icon"><i class="bi bi-fingerprint"></i></div>
          <h5>The Tickets You Ordered</h5>
          <p>You receive the tickets you ordered, or better. The guarantee covers your order, not the price: resale prices may be above or below face value.</p>
        </div>
      </div>

      <!-- Card 4 -->
      <div class="col-12 col-sm-6 col-lg-4">
        <div class="feature-card">
          <div class="icon"><svg xmlns="http://www.w3.org/2000/svg" version="1.1" xmlns:xlink="http://www.w3.org/1999/xlink" width="24" height="24" x="0" y="0" viewBox="0 0 100 100" style="enable-background:new 0 0 512 512" xml:space="preserve" class=""><g><path d="m94.27 55.2-11-5.07v-22a1.25 1.25 0 0 0-.62-1.09L44.77 5.19a1.27 1.27 0 0 0-1.25 0L5.65 27.05A1.26 1.26 0 0 0 5 28.14v43.72A1.26 1.26 0 0 0 5.65 73l37.87 21.81h.19a1.32 1.32 0 0 0 .43.09 1.27 1.27 0 0 0 .43-.09h.2l15.11-8.72a30.88 30.88 0 0 0 12.59 8.83 1.09 1.09 0 0 0 .36.06 1.13 1.13 0 0 0 .36-.06c.23-.07 23.07-7.34 21.8-38.63a1.25 1.25 0 0 0-.72-1.09zm-50.13-6.27-13.23-7.64 35.65-20.58 13.24 7.64zM20 37.86l6.86 4v14.02L20 51.65zm1.27-2.16 35.65-20.61 7.19 4.15-35.7 20.61zm22.91-28 10.28 5.93-35.73 20.63-10.28-5.94zm-36.62 23 9.94 5.73v15.91a1.26 1.26 0 0 0 .6 1.07l9.35 5.78a1.28 1.28 0 0 0 .66.18 1.31 1.31 0 0 0 .61-.15 1.27 1.27 0 0 0 .64-1.1V43.26l13.53 7.84v40.46L7.52 71.14zm37.83 60.86V51.1l35.37-20.42V49l-7.41-3.41a1.19 1.19 0 0 0-1 0L51.39 55.2a1.26 1.26 0 0 0-.73 1.09c-.52 13.06 3.15 21.92 7.62 27.83zm27.45.85c-2.75-1-20.44-8.62-19.7-35.26l19.69-9.07 19.69 9.07c.75 26.85-16.8 34.23-19.68 35.26zm9.94-30.69a1.24 1.24 0 0 1 .22 1.75l-12.12 15a1.22 1.22 0 0 1-1 .47 1.24 1.24 0 0 1-1-.52l-6.21-8.69a1.25 1.25 0 1 1 2-1.46l5.26 7.35L81 61.91a1.24 1.24 0 0 1 1.78-.19z" fill="#fff" opacity="1" data-original="#fff" class=""></path></g></svg></div>
          <h5>Fulfilled Through TicketNetwork</h5>
          <p>Orders are fulfilled through the TicketNetwork marketplace and covered by its 100% guarantee. Read the full terms in <a href="https://www.ticketnetwork.com/policies" target="_blank" rel="noopener">TicketNetwork's policies</a>.</p>
        </div>
      </div>

      <!-- Card 5 -->
      <div class="col-12 col-sm-6 col-lg-4">
        <div class="feature-card">
          <div class="icon"><svg xmlns="http://www.w3.org/2000/svg" version="1.1" xmlns:xlink="http://www.w3.org/1999/xlink" width="24" height="24" x="0" y="0" viewBox="0 0 50 50" style="enable-background:new 0 0 512 512" xml:space="preserve" class="hovered-paths"><g><path d="M24.63 44.93c.12.05.24.07.37.07s.25-.02.37-.07C31.83 42.35 36 36.18 36 29.23V23c0-.31-.15-.61-.4-.8l-4-3a.984.984 0 0 0-.6-.2H19c-.22 0-.43.07-.6.2l-4 3c-.25.19-.4.49-.4.8v6.23c0 6.95 4.17 13.12 10.63 15.7zM16 23.5l3.33-2.5h11.33L34 23.5v5.73c0 6-3.52 11.33-9 13.69-5.48-2.36-9-7.69-9-13.69z" fill="#ffffff" opacity="1" data-original="#ffffff" class="hovered-path"></path><path d="m23.21 33.71 6-6a.996.996 0 1 0-1.41-1.41l-5.29 5.29-1.29-1.29a.996.996 0 1 0-1.41 1.41l2 2c.37.39 1.01.39 1.4 0z" fill="#ffffff" opacity="1" data-original="#ffffff" class="hovered-path"></path><path d="M46 5h-3.06c-.31-2.87-1.93-5-3.94-5s-3.63 2.13-3.94 5h-6.12c-.31-2.87-1.93-5-3.94-5s-3.63 2.13-3.94 5h-6.12c-.31-2.87-1.93-5-3.94-5S7.37 2.13 7.06 5H4C1.79 5 0 6.79 0 9v37c0 2.21 1.79 4 4 4h42c2.21 0 4-1.79 4-4V9c0-2.21-1.79-4-4-4zm-7-3c.8 0 1.67 1.23 1.92 3h-3.85c.26-1.77 1.13-3 1.93-3zM25 2c.8 0 1.67 1.23 1.92 3h-3.85c.26-1.77 1.13-3 1.93-3zM11 2c.8 0 1.67 1.23 1.92 3H9.08C9.33 3.23 10.2 2 11 2zm37 44c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V16h46zm0-32H2V9c0-1.1.9-2 2-2h8.92c-.25 1.77-1.12 3-1.92 3-.55 0-1 .45-1 1s.45 1 1 1c2.01 0 3.63-2.13 3.94-5h11.98c-.25 1.77-1.12 3-1.92 3-.55 0-1 .45-1 1s.45 1 1 1c2.01 0 3.63-2.13 3.94-5h11.98c-.25 1.77-1.12 3-1.92 3-.55 0-1 .45-1 1s.45 1 1 1c2.01 0 3.63-2.13 3.94-5H46c1.1 0 2 .9 2 2z" fill="#fff" opacity="1" data-original="#fff" class="hovered-path"></path></g></svg></div>
          <h5>Event Cancellation Refund</h5>
          <p>A full refund (delivery fees excluded) if the event is canceled.</p>
        </div>
      </div>

      <!-- Card 6 -->
      <div class="col-12 col-sm-6 col-lg-4">
        <div class="feature-card">
          <div class="icon"><svg xmlns="http://www.w3.org/2000/svg" version="1.1" xmlns:xlink="http://www.w3.org/1999/xlink" width="24" height="24" x="0" y="0" viewBox="0 0 64 64" style="enable-background:new 0 0 512 512" xml:space="preserve" class=""><g><path d="M59.506 27.903A27.322 27.322 0 0 0 51.47 9.43C46.267 4.226 39.352 1.361 32 1.361c-14.849 0-26.982 11.819-27.505 26.542a6.474 6.474 0 0 0-3.852 5.911v7.262a6.475 6.475 0 0 0 6.468 6.468 4.19 4.19 0 0 0 4.185-4.185V31.53c0-2.167-1.662-3.934-3.775-4.144C8.301 14.559 18.979 4.361 32 4.361c6.552 0 12.713 2.554 17.35 7.191 4.258 4.258 6.75 9.808 7.125 15.835-2.111.212-3.77 1.978-3.77 4.143v11.828c0 2.185 1.688 3.963 3.826 4.148v2.989a6.111 6.111 0 0 1-6.105 6.104h-4.521a4.497 4.497 0 0 0-4.267-3.038h-4.66c-.669 0-1.311.142-1.899.416a4.547 4.547 0 0 0-2.64 4.122c0 1.214.473 2.354 1.33 3.207a4.504 4.504 0 0 0 3.209 1.332h4.66c1.932 0 3.635-1.249 4.27-3.039h4.518c5.021 0 9.105-4.084 9.105-9.104v-3.523a6.473 6.473 0 0 0 3.826-5.898v-7.262c0-2.634-1.586-4.902-3.851-5.909zM8.295 31.53v11.828c0 .653-.531 1.185-1.185 1.185a3.472 3.472 0 0 1-3.468-3.468v-7.262a3.472 3.472 0 0 1 3.468-3.468c.654 0 1.185.532 1.185 1.185zm34.848 26.891a1.546 1.546 0 0 1-1.506 1.219h-4.66c-.41 0-.795-.16-1.089-.454a1.523 1.523 0 0 1-.45-1.085 1.542 1.542 0 0 1 1.539-1.538h4.66c.41 0 .795.159 1.088.453.29.289.45.675.45 1.085.001.11-.011.22-.032.32zm17.214-17.346a3.472 3.472 0 0 1-3.468 3.468 1.186 1.186 0 0 1-1.185-1.185V31.53c0-.653.531-1.185 1.185-1.185a3.472 3.472 0 0 1 3.468 3.468z" fill="#ffffff" opacity="1" data-original="#ffffff" class=""></path><path d="M41.713 41.592a6.363 6.363 0 0 0 6.356-6.356V22.285c0-1.694-.662-3.29-1.864-4.492s-2.797-1.864-4.492-1.864H22.287a6.363 6.363 0 0 0-6.356 6.356v12.951a6.363 6.363 0 0 0 6.356 6.356h.119v3.356a3.109 3.109 0 0 0 3.112 3.124 3.05 3.05 0 0 0 2.196-.927l5.583-5.553zm-10.091-2.564-6.042 6.009c-.027.028-.047.049-.104.024-.069-.028-.069-.073-.069-.113v-4.856a1.5 1.5 0 0 0-1.5-1.5h-1.619a3.36 3.36 0 0 1-3.356-3.356V22.285a3.36 3.36 0 0 1 3.356-3.356h19.426c.894 0 1.735.35 2.371.985.636.636.985 1.478.985 2.371v12.951a3.36 3.36 0 0 1-3.356 3.356H32.68c-.397 0-.777.157-1.058.436z" fill="#ffffff" opacity="1" data-original="#ffffff" class=""></path><path d="M24.713 26.787c-1.22 0-2.213.994-2.213 2.213s.994 2.213 2.213 2.213c1.221 0 2.215-.994 2.215-2.213s-.993-2.213-2.215-2.213zM31.999 26.787c-1.22 0-2.213.994-2.213 2.213s.994 2.213 2.213 2.213c1.222 0 2.215-.994 2.215-2.213s-.993-2.213-2.215-2.213zM39.285 26.787c-1.22 0-2.213.994-2.213 2.213s.994 2.213 2.213 2.213c1.221 0 2.215-.994 2.215-2.213s-.994-2.213-2.215-2.213z" fill="#ffffff" opacity="1" data-original="#ffffff" class=""></path></g></svg></div>
          <h5>If Something Goes Wrong</h5>
          <p>If tickets do not arrive, do not match your order, or an event is canceled, contact our support team. We review every case against the event details and our policies, and work with the seller to make it right.</p>
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
                    <h2>What the guarantee covers</h2>
                    <?php echo soGuaranteeBlock(); ?>

                </div>


                <div class="policy-section" id="section-10">
                    <h2>Need help?</h2>
                    <p>Questions about an order? Use the <a href="/ticket-customer-service">contact form</a>. The <a href="/worry-free-guarantee">guarantee page</a> has the full wording.</p>
                </div>
            </div>
        </div>
    </section>

</main>

<?php soSeoCopy('ticket-buyer-protection'); ?>
<?php include 'footer.php'; ?>