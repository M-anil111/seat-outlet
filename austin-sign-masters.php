<?php
$pageRobots = 'noindex, follow';   // thin partner page: not worth a crawl, kept for visitors
include 'header.php';
?>

<style>
    :root {
      --asm-blue:        #2563ff;
      --asm-blue-hover:  #1a4fd6;
      --asm-blue-soft:   rgba(37,99,255,.10);
      --asm-blue-border: rgba(37,99,255,.25);
      --asm-dark:        #111827;
      --asm-mid:         #F3F6FF;
      --asm-card:        #ffffff;
      --asm-border:      #e5e9f2;
      --asm-muted:       #6b7280;
      --asm-text:        #1f2937;
    }

    .asm-page *, .asm-page *::before, .asm-page *::after { box-sizing: border-box; }

    .asm-page {
      background: #ffffff;
      color: var(--asm-text);
      font-size: 1rem;
      line-height: 1.7;
      overflow-x: hidden;
    }

    .asm-page h1, .asm-page h2, .asm-page h3,
.asm-page h2.h1 { letter-spacing: .01em; color: var(--asm-dark); }

    .asm-breadcrumb { font-size: .85rem; color: var(--asm-muted); padding: 18px 0 0; }
    .asm-breadcrumb a { color: var(--asm-muted); text-decoration: none; }
    .asm-breadcrumb a:hover { color: var(--asm-blue); }
    .asm-breadcrumb .current { color: var(--asm-text); }

    .asm-hero { background: var(--asm-mid); padding: 20px 0 60px; }
    .asm-eyebrow {
      font-size: .78rem; font-weight: 700; letter-spacing: .12em;
      text-transform: uppercase; color: var(--asm-blue); margin-bottom: .6rem; display: block;
    }
    .asm-hero h1,
.asm-hero h2.h1 { font-size: clamp(2.2rem, 4.5vw, 3.2rem); font-weight: 700; line-height: 1.15; margin-bottom: .75rem; }
    .asm-hero p.lead { color: var(--asm-muted); font-size: 1.05rem; max-width: 480px; }
    .asm-hero-visual {
      aspect-ratio: 4/3; border-radius: 16px; overflow: hidden; position: relative;
      background: linear-gradient(135deg, #dce6ff 0%, #f3f6ff 60%, #e9f0ff 100%);
      display: flex; align-items: center; justify-content: center;
      box-shadow: 0 20px 50px rgba(37,99,255,.15);
    }
    .asm-hero-visual i { font-size: 4rem; color: rgba(37,99,255,.35); }

    .btn-asm-primary {
      background: var(--asm-blue); color: #fff; border: none;
      font-weight: 600; font-size: .95rem; padding: 13px 28px; border-radius: 6px;
      transition: background .2s, transform .15s;
    }
    .btn-asm-primary:hover { background: var(--asm-blue-hover); color: #fff; transform: translateY(-2px); }
    .btn-asm-outline {
      border: 1px solid var(--asm-blue-border); color: var(--asm-blue);
      background: #fff; font-weight: 600; font-size: .95rem; padding: 13px 28px; border-radius: 6px;
      transition: background .2s, border-color .2s;
    }
    .btn-asm-outline:hover { background: var(--asm-blue-soft); border-color: var(--asm-blue); color: var(--asm-blue); }

    .asm-subnav { background: #fff; border-bottom: 1px solid var(--asm-border); }
    .asm-subnav .nav-link {
      color: var(--asm-text); font-weight: 500; font-size: .92rem; padding: 16px 18px;
      border-bottom: 2px solid transparent;
    }
    .asm-subnav .nav-link.active, .asm-subnav .nav-link:hover { color: var(--asm-blue); border-color: var(--asm-blue); }

    .asm-section { padding: 80px 0; }
    .asm-divider { width: 44px; height: 3px; background: var(--asm-blue); margin-bottom: 1.4rem; }
    .asm-image-box {
      aspect-ratio: 4/3; border-radius: 14px; overflow: hidden;
      background: linear-gradient(135deg, #eef2ff 0%, #f7f9ff 100%);
      display: flex; align-items: center; justify-content: center;
      border: 1px solid var(--asm-border);
    }
    .asm-image-box i { font-size: 3rem; color: rgba(37,99,255,.30); }

    .asm-tag { display: inline-block; background: var(--asm-blue-soft); color: var(--asm-blue); border: 1px solid var(--asm-blue-border);
      font-size: .82rem; font-weight: 500; padding: 6px 14px; border-radius: 20px; margin: 0 8px 8px 0; }

    .asm-solution-card { background: var(--asm-card); border: 1px solid var(--asm-border); border-radius: 12px; padding: 26px 22px; height: 100%;
      transition: transform .25s, box-shadow .25s; }
    .asm-solution-card:hover { transform: translateY(-4px); box-shadow: 0 14px 40px rgba(37,99,255,.12); }
    .asm-solution-icon {
      width: 44px; height: 44px; border-radius: 8px; background: var(--asm-blue-soft);
      display: flex; align-items: center; justify-content: center; color: var(--asm-blue); font-size: 1.2rem; margin-bottom: 14px;
    }
    .asm-solution-card h5 { font-size: 1rem; font-weight: 600; margin-bottom: 6px; }
    .asm-solution-card p { font-size: .88rem; color: var(--asm-muted); margin: 0 0 10px; }
    .asm-solution-card a { font-size: .85rem; font-weight: 600; color: var(--asm-blue); text-decoration: none; }
    .asm-solution-card a:hover { color: var(--asm-blue-hover); }

    .asm-industry-bg { background: var(--asm-mid); }
    .asm-industry-item { background: var(--asm-card); border: 1px solid var(--asm-border); border-radius: 10px; padding: 20px 22px;
      display: flex; align-items: center; gap: 14px; height: 100%; }
    .asm-industry-icon { width: 40px; height: 40px; border-radius: 8px; background: var(--asm-blue-soft);
      display: flex; align-items: center; justify-content: center; color: var(--asm-blue); font-size: 1.1rem; flex-shrink: 0; }
    .asm-industry-item span { font-weight: 500; font-size: .95rem; }

    .asm-io-card { background: var(--asm-card); border: 1px solid var(--asm-border); border-radius: 12px; padding: 28px 26px; height: 100%; }
    .asm-io-head { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; }
    .asm-io-head .asm-io-icon { width: 40px; height: 40px; border-radius: 8px; background: var(--asm-blue-soft);
      display: flex; align-items: center; justify-content: center; color: var(--asm-blue); font-size: 1.1rem; }
    .asm-io-head h5 { font-size: 1.05rem; font-weight: 600; margin: 0; }
    .asm-io-list { list-style: none; padding: 0; margin: 0; }
    .asm-io-list li { display: flex; align-items: center; justify-content: space-between; padding: 10px 0; border-top: 1px solid var(--asm-border); font-size: .92rem; }
    .asm-io-list li:first-child { border-top: none; }
    .asm-io-list li i { color: var(--asm-blue); }

    .asm-why-item { text-align: left; }
    .asm-why-icon {
      width: 46px; height: 46px; border-radius: 8px; background: var(--asm-blue-soft);
      display: flex; align-items: center; justify-content: center; color: var(--asm-blue); font-size: 1.3rem; margin-bottom: 14px;
    }
    .asm-why-item h5 { font-size: 1rem; font-weight: 600; margin-bottom: 6px; }
    .asm-why-item p { font-size: .9rem; color: var(--asm-muted); margin: 0; }

    .asm-cta { background: var(--asm-blue); position: relative; overflow: hidden; }
    .asm-cta::before {
      content: 'AUSTIN SIGN MASTERS AUSTIN SIGN MASTERS AUSTIN SIGN MASTERS ';
      position: absolute; top: 50%; left: 0; transform: translateY(-50%);
      font-size: 4rem; white-space: nowrap; font-weight: 700;
      color: rgba(255,255,255,.06); letter-spacing: .06em; pointer-events: none;
    }
    .asm-cta h2 { color: #fff; font-size: 1.6rem; margin-bottom: 4px; }
    .asm-cta p { color: rgba(255,255,255,.85); margin: 0; }
    .btn-asm-white {
      background: #fff; color: var(--asm-blue); font-weight: 700; border: none;
      padding: 13px 30px; border-radius: 6px; transition: background .2s, transform .15s;
    }
    .btn-asm-white:hover { background: #eef2ff; color: var(--asm-blue-hover); transform: translateY(-2px); }

    @media (max-width: 575.98px) {
      .asm-hero { padding: 16px 0 40px; }
      .asm-section { padding: 56px 0; }
    }
</style>

<div class="asm-page">

  <!-- Breadcrumb -->
  <div class="container asm-breadcrumb">
    <a href="<?php echo HOME_URL; ?>/">Home</a> / <a href="<?php echo HOME_URL; ?>/what-we-do">What We Do</a> / <span class="current">Austin Texas Sign Companies</span>
  </div>

  <!-- Hero -->
  <section class="asm-hero">
    <div class="container">
      <div class="row align-items-center g-5 pt-4">
        <div class="col-lg-6">
          <span class="asm-eyebrow">Austin Business Signage</span>
          <h1>Austin Texas Sign Companies</h1>
          <p class="lead">Austin Sign Masters creates custom signs, storefront displays, vehicle graphics, and branded visuals for Austin businesses.</p>
          <a href="#solutions" class="btn btn-asm-primary mt-2">Explore Sign Solutions <i class="bi bi-arrow-right ms-1"></i></a>
        </div>
        <div class="col-lg-6">
          <div class="asm-hero-visual">
            <i class="bi bi-signpost-split"></i>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Sub nav -->
  <nav class="asm-subnav">
    <div class="container">
      <div class="nav flex-nowrap overflow-auto">
        <a class="nav-link active" href="#solutions">Solutions</a>
        <a class="nav-link" href="#storefronts">Storefronts</a>
        <a class="nav-link" href="#vehicle-wraps">Vehicle Wraps</a>
        <a class="nav-link" href="#industries">Industries</a>
        <a class="nav-link" href="#indoor-outdoor">Indoor &amp; Outdoor</a>
        <a class="nav-link" href="#events">Events</a>
      </div>
    </div>
  </nav>

  <!-- Solutions -->
  <section class="asm-section pb-0" id="solutions">
    <div class="container">
      <h2>Custom Sign Solutions for Austin Businesses</h2>
      <p class="text-secondary" style="max-width:820px;">
        Austin Sign Masters helps local businesses make a lasting impression with high-quality custom signage and graphics. From storefront signs to vehicle wraps and event displays, we create visual solutions that strengthen your brand and attract more customers.
      </p>
      <div class="row g-4 mt-2">
        <div class="col-sm-6 col-lg-3">
          <div class="asm-solution-card">
            <div class="asm-solution-icon"><i class="bi bi-shop-window"></i></div>
            <h5>Storefront Signs</h5>
            <p>Eye-catching exterior signs that bring in more customers.</p>
            <a href="#storefronts">Learn more <i class="bi bi-arrow-right"></i></a>
          </div>
        </div>
        <div class="col-sm-6 col-lg-3">
          <div class="asm-solution-card">
            <div class="asm-solution-icon"><i class="bi bi-truck"></i></div>
            <h5>Vehicle Graphics</h5>
            <p>Professional wraps and decals that turn your vehicles into moving billboards.</p>
            <a href="#vehicle-wraps">Learn more <i class="bi bi-arrow-right"></i></a>
          </div>
        </div>
        <div class="col-sm-6 col-lg-3">
          <div class="asm-solution-card">
            <div class="asm-solution-icon"><i class="bi bi-easel2"></i></div>
            <h5>Indoor Signage</h5>
            <p>Branded interior signs for offices, retail spaces and more.</p>
            <a href="#indoor-outdoor">Learn more <i class="bi bi-arrow-right"></i></a>
          </div>
        </div>
        <div class="col-sm-6 col-lg-3">
          <div class="asm-solution-card">
            <div class="asm-solution-icon"><i class="bi bi-house-door"></i></div>
            <h5>Event Displays</h5>
            <p>Custom displays, banners and signage for trade shows and events.</p>
            <a href="#events">Learn more <i class="bi bi-arrow-right"></i></a>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Professional branding -->
  <section class="asm-section">
    <div class="container">
      <div class="row g-5 align-items-center">
        <div class="col-lg-6">
          <div class="asm-image-box"><i class="bi bi-palette2"></i></div>
        </div>
        <div class="col-lg-6">
          <h2>Austin Signage Company for Professional Branding</h2>
          <div class="asm-divider"></div>
          <p class="text-secondary">
            We combine creative design, quality materials and expert craftsmanship to deliver signage that represents your brand and gets noticed. Our team works with you from concept to installation, ensuring every detail aligns with your goals and makes a strong impact.
          </p>
          <a href="#solutions" class="btn btn-asm-primary mt-2">Our Process <i class="bi bi-arrow-right ms-1"></i></a>
        </div>
      </div>
    </div>
  </section>

  <!-- Storefronts -->
  <section class="asm-section" id="storefronts">
    <div class="container">
      <div class="row g-5 align-items-center">
        <div class="col-lg-6">
          <span class="asm-eyebrow">Storefront Signs</span>
          <h2>Storefront Signs That Attract Customers</h2>
          <div class="asm-divider"></div>
          <p class="text-secondary">
            Your storefront sign is often the first impression customers have of your business. We design and fabricate custom exterior signs that are bold, professional and built to last in the Austin climate.
          </p>
          <div>
            <span class="asm-tag">Channel Letters</span>
            <span class="asm-tag">Window Graphics</span>
            <span class="asm-tag">Logo Displays</span>
          </div>
        </div>
        <div class="col-lg-6">
          <div class="asm-image-box"><i class="bi bi-shop"></i></div>
        </div>
      </div>
    </div>
  </section>

  <!-- Vehicle wraps -->
  <section class="asm-section" id="vehicle-wraps" style="background:var(--asm-mid);">
    <div class="container">
      <div class="row g-5 align-items-center">
        <div class="col-lg-6 order-lg-2">
          <span class="asm-eyebrow">Vehicle Graphics</span>
          <h2>Vehicle Wraps and Mobile Advertising Solutions</h2>
          <div class="asm-divider"></div>
          <p class="text-secondary">
            Turn your vehicles into powerful marketing tools with custom wraps and graphics. We create high-impact designs that get your brand seen all over Austin, from downtown to the neighborhoods.
          </p>
          <div>
            <span class="asm-tag">Full Wraps</span>
            <span class="asm-tag">Partial Wraps</span>
            <span class="asm-tag">Fleet Graphics</span>
          </div>
        </div>
        <div class="col-lg-6 order-lg-1">
          <div class="asm-image-box"><i class="bi bi-truck-front"></i></div>
        </div>
      </div>
    </div>
  </section>

  <!-- Industries -->
  <section class="asm-section asm-industry-bg" id="industries">
    <div class="container">
      <h2>Commercial Signage for Multiple Industries</h2>
      <p class="text-secondary" style="max-width:760px;">
        We work with businesses across Austin to create custom signage solutions tailored to their industry, brand and unique needs.
      </p>
      <div class="row g-4 mt-2">
        <?php
          $industries = [
            ['bi-cup-hot',    'Restaurants'],
            ['bi-bag',        'Retail'],
            ['bi-plus-circle','Medical'],
            ['bi-house-door', 'Real Estate'],
            ['bi-building',   'Corporate Offices'],
            ['bi-people',     'Events'],
          ];
          foreach ($industries as $ind) {
        ?>
        <div class="col-sm-6 col-lg-4">
          <div class="asm-industry-item">
            <div class="asm-industry-icon"><i class="bi <?php echo $ind[0]; ?>"></i></div>
            <span><?php echo $ind[1]; ?></span>
          </div>
        </div>
        <?php } ?>
      </div>
    </div>
  </section>

  <!-- Indoor & outdoor -->
  <section class="asm-section" id="indoor-outdoor">
    <div class="container">
      <h2>Indoor and Outdoor Signage Solutions</h2>
      <div class="row g-4 mt-2">
        <div class="col-md-6">
          <div class="asm-io-card">
            <div class="asm-io-head">
              <div class="asm-io-icon"><i class="bi bi-building"></i></div>
              <h5>Indoor</h5>
            </div>
            <ul class="asm-io-list">
              <li>Lobby signs <i class="bi bi-chevron-right"></i></li>
              <li>Wall graphics <i class="bi bi-chevron-right"></i></li>
              <li>Wayfinding <i class="bi bi-chevron-right"></i></li>
              <li>Reception signage <i class="bi bi-chevron-right"></i></li>
            </ul>
          </div>
        </div>
        <div class="col-md-6">
          <div class="asm-io-card">
            <div class="asm-io-head">
              <div class="asm-io-icon"><i class="bi bi-cloud-sun"></i></div>
              <h5>Outdoor</h5>
            </div>
            <ul class="asm-io-list">
              <li>Monument signs <i class="bi bi-chevron-right"></i></li>
              <li>Building signs <i class="bi bi-chevron-right"></i></li>
              <li>LED signs <i class="bi bi-chevron-right"></i></li>
              <li>Banners <i class="bi bi-chevron-right"></i></li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Events -->
  <section class="asm-section" id="events" style="background:var(--asm-mid);">
    <div class="container">
      <div class="row g-5 align-items-center">
        <div class="col-lg-6">
          <div class="asm-image-box"><i class="bi bi-easel"></i></div>
        </div>
        <div class="col-lg-6">
          <h2>Event Signs and Promotional Displays</h2>
          <div class="asm-divider"></div>
          <p class="text-secondary">
            Make your next event a success with custom banners, backdrops, pop-up displays and more. We create portable, professional displays that help you stand out at trade shows, festivals and community events.
          </p>
          <div>
            <span class="asm-tag">Banners</span>
            <span class="asm-tag">Backdrops</span>
            <span class="asm-tag">Pop-up Displays</span>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Why choose -->
  <section class="asm-section">
    <div class="container">
      <h2>Why Businesses Choose Austin Sign Masters</h2>
      <div class="row g-4 mt-4">
        <div class="col-md-4">
          <div class="asm-why-item">
            <div class="asm-why-icon"><i class="bi bi-pencil-square"></i></div>
            <h5>Custom Design</h5>
            <p>Unique, on-brand designs tailored to your business.</p>
          </div>
        </div>
        <div class="col-md-4">
          <div class="asm-why-item">
            <div class="asm-why-icon"><i class="bi bi-graph-up"></i></div>
            <h5>Brand Visibility</h5>
            <p>High-impact signage that helps you get noticed.</p>
          </div>
        </div>
        <div class="col-md-4">
          <div class="asm-why-item">
            <div class="asm-why-icon"><i class="bi bi-gear"></i></div>
            <h5>Installation Support</h5>
            <p>Professional installation and ongoing support.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- CTA -->
  <section class="asm-cta py-5">
    <div class="container position-relative" style="z-index:1">
      <div class="row align-items-center g-4">
        <div class="col-lg-8">
          <span class="asm-eyebrow" style="color:#c9d8ff;">Helping Austin Businesses Stand Out</span>
          <h2 class="mb-2">Let Your Business Get Noticed</h2>
          <p class="mb-0">From storefront signs to vehicle wraps and event displays, Austin Sign Masters has the solutions to bring your brand to life.</p>
        </div>
        <div class="col-lg-4 text-lg-end">
          <a href="<?php echo HOME_URL; ?>/contact" class="btn btn-asm-white btn-lg">Get in Touch <i class="bi bi-arrow-right ms-1"></i></a>
        </div>
      </div>
    </div>
  </section>

</div>

<?php include 'footer.php'; ?>
