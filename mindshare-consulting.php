<?php include 'header.php'; ?>

<style>
    :root {
      --mc-blue:        #2563ff;
      --mc-blue-hover:  #1a4fd6;
      --mc-blue-soft:   rgba(37,99,255,.10);
      --mc-blue-border: rgba(37,99,255,.25);
      --mc-dark:        #111827;
      --mc-mid:         #F3F6FF;
      --mc-card:        #ffffff;
      --mc-border:      #e5e9f2;
      --mc-muted:       #6b7280;
      --mc-text:        #1f2937;
    }

    .mc-page *, .mc-page *::before, .mc-page *::after { box-sizing: border-box; }

    .mc-page {
      background: #ffffff;
      color: var(--mc-text);
      font-size: 1rem;
      line-height: 1.7;
      overflow-x: hidden;
    }

    .mc-page h1, .mc-page h2, .mc-page h3,
.mc-page h2.h1 { letter-spacing: .01em; color: var(--mc-dark); }

    /* breadcrumb */
    .mc-breadcrumb { font-size: .85rem; color: var(--mc-muted); padding: 18px 0 0; }
    .mc-breadcrumb a { color: var(--mc-muted); text-decoration: none; }
    .mc-breadcrumb a:hover { color: var(--mc-blue); }
    .mc-breadcrumb .current { color: var(--mc-text); }

    /* hero */
    .mc-hero { background: var(--mc-mid); padding: 20px 0 60px; }
    .mc-eyebrow {
      font-size: .78rem; font-weight: 700; letter-spacing: .12em;
      text-transform: uppercase; color: var(--mc-blue); margin-bottom: .6rem; display: block;
    }
    .mc-hero h1,
.mc-hero h2.h1 { font-size: clamp(2.2rem, 4.5vw, 3.2rem); font-weight: 700; line-height: 1.15; margin-bottom: .75rem; }
    .mc-hero p.lead { color: var(--mc-muted); font-size: 1.05rem; max-width: 480px; }
    .mc-hero-visual {
      aspect-ratio: 4/3; border-radius: 16px; overflow: hidden; position: relative;
      background: linear-gradient(135deg, #dce6ff 0%, #f3f6ff 60%, #e9f0ff 100%);
      display: flex; align-items: center; justify-content: center;
      box-shadow: 0 20px 50px rgba(37,99,255,.15);
    }
    .mc-hero-visual i { font-size: 4rem; color: rgba(37,99,255,.35); }

    .btn-mc-primary {
      background: var(--mc-blue); color: #fff; border: none;
      font-weight: 600; font-size: .95rem; padding: 13px 28px; border-radius: 6px;
      transition: background .2s, transform .15s;
    }
    .btn-mc-primary:hover { background: var(--mc-blue-hover); color: #fff; transform: translateY(-2px); }
    .btn-mc-outline {
      border: 1px solid var(--mc-blue-border); color: var(--mc-blue);
      background: #fff; font-weight: 600; font-size: .95rem; padding: 13px 28px; border-radius: 6px;
      transition: background .2s, border-color .2s;
    }
    .btn-mc-outline:hover { background: var(--mc-blue-soft); border-color: var(--mc-blue); color: var(--mc-blue); }

    /* sub-nav tabs */
    .mc-subnav { background: #fff; border-bottom: 1px solid var(--mc-border); }
    .mc-subnav .nav-link {
      color: var(--mc-text); font-weight: 500; font-size: .92rem; padding: 16px 18px;
      border-bottom: 2px solid transparent;
    }
    .mc-subnav .nav-link.active, .mc-subnav .nav-link:hover { color: var(--mc-blue); border-color: var(--mc-blue); }

    /* section base */
    .mc-section { padding: 80px 0; }
    .mc-divider { width: 44px; height: 3px; background: var(--mc-blue); margin-bottom: 1.4rem; }
    .mc-image-box {
      aspect-ratio: 4/3; border-radius: 14px; overflow: hidden;
      background: linear-gradient(135deg, #eef2ff 0%, #f7f9ff 100%);
      display: flex; align-items: center; justify-content: center;
      border: 1px solid var(--mc-border);
    }
    .mc-image-box i { font-size: 3rem; color: rgba(37,99,255,.30); }

    .mc-tag { display: inline-block; background: var(--mc-blue-soft); color: var(--mc-blue); border: 1px solid var(--mc-blue-border);
      font-size: .82rem; font-weight: 500; padding: 6px 14px; border-radius: 20px; margin: 0 8px 8px 0; }

    /* service cards */
    .mc-service-card { background: var(--mc-card); border: 1px solid var(--mc-border); border-radius: 12px; padding: 26px 22px; height: 100%;
      transition: transform .25s, box-shadow .25s; }
    .mc-service-card:hover { transform: translateY(-4px); box-shadow: 0 14px 40px rgba(37,99,255,.12); }
    .mc-service-icon {
      width: 44px; height: 44px; border-radius: 8px; background: var(--mc-blue-soft);
      display: flex; align-items: center; justify-content: center; color: var(--mc-blue); font-size: 1.2rem; margin-bottom: 14px;
    }
    .mc-service-card h5 { font-size: 1rem; font-weight: 600; margin-bottom: 6px; }
    .mc-service-card p { font-size: .88rem; color: var(--mc-muted); margin: 0; }

    /* checklist */
    .mc-check-list { list-style: none; padding: 0; margin: 0; }
    .mc-check-list li { display: flex; align-items: center; gap: 10px; font-size: .92rem; color: var(--mc-text); padding: 6px 0; }
    .mc-check-list i { color: var(--mc-blue); font-size: 1.1rem; flex-shrink: 0; }

    /* industries */
    .mc-industry-bg { background: var(--mc-mid); }
    .mc-industry-item { background: var(--mc-card); border: 1px solid var(--mc-border); border-radius: 10px; padding: 20px 22px;
      display: flex; align-items: center; gap: 14px; height: 100%; }
    .mc-industry-icon { width: 40px; height: 40px; border-radius: 8px; background: var(--mc-blue-soft);
      display: flex; align-items: center; justify-content: center; color: var(--mc-blue); font-size: 1.1rem; flex-shrink: 0; }
    .mc-industry-item span { font-weight: 500; font-size: .95rem; }

    /* why choose */
    .mc-why-bg { background: var(--mc-mid); }
    .mc-why-item { text-align: left; }
    .mc-why-icon {
      width: 46px; height: 46px; border-radius: 8px; background: var(--mc-blue-soft);
      display: flex; align-items: center; justify-content: center; color: var(--mc-blue); font-size: 1.3rem; margin-bottom: 14px;
    }
    .mc-why-item h5 { font-size: 1rem; font-weight: 600; margin-bottom: 6px; }
    .mc-why-item p { font-size: .9rem; color: var(--mc-muted); margin: 0; }

    /* CTA strip */
    .mc-cta { background: var(--mc-blue); border-radius: 16px; overflow: hidden; }
    .mc-cta h2 { color: #fff; font-size: 1.6rem; margin-bottom: 4px; }
    .mc-cta p { color: rgba(255,255,255,.85); margin: 0; }
    .mc-cta-card { background: #fff; border-radius: 12px; padding: 26px 24px; }
    .mc-cta-card h5 { font-size: 1.05rem; font-weight: 600; margin-bottom: 6px; color: var(--mc-dark); }
    .mc-cta-card p { font-size: .88rem; color: var(--mc-muted); margin-bottom: 16px; }

    @media (max-width: 575.98px) {
      .mc-hero { padding: 16px 0 40px; }
      .mc-section { padding: 56px 0; }
    }
</style>

<div class="mc-page">

  <!-- Breadcrumb -->
  <div class="container mc-breadcrumb">
    <a href="<?php echo HOME_URL; ?>/">Home</a> / <a href="<?php echo HOME_URL; ?>/what-we-do">What We Do</a> / <span class="current">Marketing Agency in Austin</span>
  </div>

  <!-- Hero -->
  <section class="mc-hero">
    <div class="container">
      <div class="row align-items-center g-5 pt-4">
        <div class="col-lg-6">
          <span class="mc-eyebrow">Austin Business Growth</span>
          <h1>Marketing Agency in Austin</h1>
          <p class="lead">Mindshare Consulting Inc helps Austin businesses grow through SEO, advertising, branding and digital marketing.</p>
          <div class="d-flex flex-wrap gap-3 mt-2">
            <a href="#overview" class="btn btn-mc-primary">Explore Services <i class="bi bi-arrow-right ms-1"></i></a>
            <a href="<?php echo HOME_URL; ?>/contact" class="btn btn-mc-outline">Get in Touch</a>
          </div>
        </div>
        <div class="col-lg-6">
          <div class="mc-hero-visual">
            <i class="bi bi-graph-up-arrow"></i>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Sub nav -->
  <nav class="mc-subnav">
    <div class="container">
      <div class="nav flex-nowrap overflow-auto">
        <a class="nav-link active" href="#overview">Overview</a>
        <a class="nav-link" href="#seo">SEO</a>
        <a class="nav-link" href="#advertising">Advertising</a>
        <a class="nav-link" href="#social-media">Social Media</a>
        <a class="nav-link" href="#web-design">Web Design</a>
        <a class="nav-link" href="#industries">Industries</a>
      </div>
    </div>
  </nav>

  <!-- Overview -->
  <section class="mc-section pb-0" id="overview">
    <div class="container">
      <h2>Full-Service Marketing Agency in Austin</h2>
      <p class="text-secondary" style="max-width:820px;">
        Mindshare Consulting Inc is a full-service marketing agency in Austin, helping businesses build stronger brands, reach more customers, and achieve sustainable growth. Our team combines strategy, creativity, and data to deliver marketing solutions that drive real results.
      </p>
      <div class="row g-4 mt-2">
        <div class="col-sm-6 col-lg-3">
          <div class="mc-service-card">
            <div class="mc-service-icon"><i class="bi bi-search"></i></div>
            <h5>Search Engine Optimization</h5>
            <p>Improve your online visibility and attract qualified traffic with strategic SEO.</p>
          </div>
        </div>
        <div class="col-sm-6 col-lg-3">
          <div class="mc-service-card">
            <div class="mc-service-icon"><i class="bi bi-megaphone"></i></div>
            <h5>Paid Advertising</h5>
            <p>Create and manage targeted ad campaigns that reach the right audience across multiple platforms.</p>
          </div>
        </div>
        <div class="col-sm-6 col-lg-3">
          <div class="mc-service-card">
            <div class="mc-service-icon"><i class="bi bi-people"></i></div>
            <h5>Social Media Marketing</h5>
            <p>Build engagement and brand awareness through compelling social media content and campaigns.</p>
          </div>
        </div>
        <div class="col-sm-6 col-lg-3">
          <div class="mc-service-card">
            <div class="mc-service-icon"><i class="bi bi-window"></i></div>
            <h5>Website Development</h5>
            <p>Design and develop modern, mobile-friendly websites that convert visitors into customers.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- SEO -->
  <section class="mc-section" id="seo">
    <div class="container">
      <div class="row g-5 align-items-center">
        <div class="col-lg-6">
          <div class="mc-image-box"><i class="bi bi-google"></i></div>
        </div>
        <div class="col-lg-6">
          <span class="mc-eyebrow">Search Engine Optimization</span>
          <h2>SEO Agency Austin TX Businesses Trust</h2>
          <div class="mc-divider"></div>
          <p class="text-secondary">
            Our SEO services help Austin businesses improve their online visibility, attract qualified traffic, and grow their customer base. We use proven strategies tailored to your industry and goals.
          </p>
          <div>
            <span class="mc-tag">Local SEO</span>
            <span class="mc-tag">Technical SEO</span>
            <span class="mc-tag">Content Strategy</span>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Advertising -->
  <section class="mc-section is-occasion-bg" id="advertising" style="background:var(--mc-mid);">
    <div class="container">
      <div class="row g-5 align-items-center">
        <div class="col-lg-6 order-lg-2">
          <div class="mc-image-box"><i class="bi bi-bullseye"></i></div>
        </div>
        <div class="col-lg-6 order-lg-1">
          <span class="mc-eyebrow">Paid Advertising</span>
          <h2>Advertising Agency Austin Businesses Can Grow With</h2>
          <div class="mc-divider"></div>
          <p class="text-secondary">
            Mindshare Consulting Inc creates and manages targeted advertising campaigns that help you reach the right audience and generate more leads. Whether you need search, social, or video ads, we develop data-driven strategies that deliver results.
          </p>
          <div>
            <span class="mc-tag">Google Search Ads</span>
            <span class="mc-tag">Meta Ads</span>
            <span class="mc-tag">Remarketing</span>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Social Media -->
  <section class="mc-section" id="social-media">
    <div class="container">
      <div class="row g-5 align-items-center">
        <div class="col-lg-6">
          <div class="mc-image-box"><i class="bi bi-phone"></i></div>
        </div>
        <div class="col-lg-6">
          <span class="mc-eyebrow">Social Media Marketing</span>
          <h2>Social Media Marketing Austin Brands Need</h2>
          <div class="mc-divider"></div>
          <p class="text-secondary">
            We help Austin businesses build engaged communities and stronger brand awareness through strategic social media marketing. Our team creates compelling content and manages campaigns that connect you with your audience.
          </p>
          <div>
            <span class="mc-tag">Facebook Marketing</span>
            <span class="mc-tag">Instagram Advertising</span>
            <span class="mc-tag">Content Creation</span>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Web Design -->
  <section class="mc-section" id="web-design" style="background:var(--mc-mid);">
    <div class="container">
      <div class="row g-5 align-items-center">
        <div class="col-lg-6 order-lg-2">
          <div class="mc-image-box"><i class="bi bi-laptop"></i></div>
        </div>
        <div class="col-lg-6 order-lg-1">
          <span class="mc-eyebrow">Website Development</span>
          <h2>Website Development and Brand Growth</h2>
          <div class="mc-divider"></div>
          <p class="text-secondary">
            Your website is often the first impression customers have of your business. We design and develop modern, mobile-friendly websites that not only look great but also convert visitors into customers.
          </p>
          <ul class="mc-check-list">
            <li><i class="bi bi-check-circle-fill"></i> Business websites</li>
            <li><i class="bi bi-check-circle-fill"></i> Mobile-friendly design</li>
            <li><i class="bi bi-check-circle-fill"></i> Landing pages</li>
            <li><i class="bi bi-check-circle-fill"></i> Conversion-focused layouts</li>
          </ul>
        </div>
      </div>
    </div>
  </section>

  <!-- Industries -->
  <section class="mc-section" id="industries">
    <div class="container">
      <span class="mc-eyebrow">Industries We Serve</span>
      <h2>Marketing Solutions for Multiple Industries</h2>
      <p class="text-secondary" style="max-width:760px;">
        Mindshare Consulting Inc works with businesses across a wide range of industries in Austin. We understand that every industry has unique challenges, and we create customized marketing strategies to help you succeed.
      </p>
      <div class="row g-4 mt-2">
        <?php
          $industries = [
            ['bi-cup-hot',          'Restaurants'],
            ['bi-house-door',       'Home Services'],
            ['bi-heart-pulse',      'Healthcare'],
            ['bi-building',         'Real Estate'],
            ['bi-cart3',            'eCommerce'],
            ['bi-briefcase',        'Professional Services'],
          ];
          foreach ($industries as $ind) {
        ?>
        <div class="col-sm-6 col-lg-4">
          <div class="mc-industry-item">
            <div class="mc-industry-icon"><i class="bi <?php echo $ind[0]; ?>"></i></div>
            <span><?php echo $ind[1]; ?></span>
          </div>
        </div>
        <?php } ?>
      </div>
    </div>
  </section>

  <!-- Why choose -->
  <section class="mc-section mc-why-bg">
    <div class="container">
      <span class="mc-eyebrow">Why Choose Us</span>
      <h2>Why Businesses Choose Mindshare Consulting Inc</h2>
      <p class="text-secondary" style="max-width:760px;">
        Austin businesses partner with Mindshare Consulting Inc because we take the time to understand their goals and deliver marketing strategies that make a difference.
      </p>
      <div class="row g-4 mt-2">
        <div class="col-md-4">
          <div class="mc-why-item">
            <div class="mc-why-icon"><i class="bi bi-bullseye"></i></div>
            <h5>Customized Strategies</h5>
            <p>Tailored marketing solutions designed around your business goals.</p>
          </div>
        </div>
        <div class="col-md-4">
          <div class="mc-why-item">
            <div class="mc-why-icon"><i class="bi bi-layers"></i></div>
            <h5>Multi-platform Support</h5>
            <p>Integrated strategies across search, social, and web for greater impact.</p>
          </div>
        </div>
        <div class="col-md-4">
          <div class="mc-why-item">
            <div class="mc-why-icon"><i class="bi bi-graph-up"></i></div>
            <h5>Lead Generation Focus</h5>
            <p>Strategies designed to attract qualified traffic and help you grow.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- CTA -->
  <section class="mc-section pt-0">
    <div class="container">
      <div class="mc-cta p-5">
        <div class="row align-items-center g-4">
          <div class="col-lg-7">
            <span class="mc-eyebrow" style="color:#c9d8ff;">Let's Grow Together</span>
            <h2>Helping Austin Businesses Grow Online</h2>
            <p>Whether you're a local startup or an established company, Mindshare Consulting Inc is here to help you grow. Our team is passionate about supporting Austin businesses with strategic marketing, creative solutions, and measurable results.</p>
          </div>
          <div class="col-lg-5">
            <div class="mc-cta-card">
              <h5>Ready to Grow Your Online Presence?</h5>
              <p>Let's create a custom marketing strategy for your business.</p>
              <a href="<?php echo HOME_URL; ?>/contact" class="btn btn-mc-primary">Contact Mindshare <i class="bi bi-arrow-right ms-1"></i></a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

</div>

<?php include 'footer.php'; ?>
