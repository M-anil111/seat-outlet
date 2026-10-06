<?php
$pageRobots = 'noindex, follow';   // thin partner page: not worth a crawl, kept for visitors
include 'header.php';
?>

<style>
    :root {
      --is-blue:        #2563ff;
      --is-blue-hover:  #1a4fd6;
      --is-blue-soft:   rgba(37,99,255,.10);
      --is-blue-border: rgba(37,99,255,.25);
      --is-dark:        #111827;
      --is-mid:         #F3F6FF;
      --is-card:        #ffffff;
      --is-border:      #e5e9f2;
      --is-muted:       #6b7280;
      --is-text:        #1f2937;
    }

    .it-sprinkles-page *, .it-sprinkles-page *::before, .it-sprinkles-page *::after { box-sizing: border-box; }

    .it-sprinkles-page {
      background: #ffffff;
      color: var(--is-text);
      font-size: 1rem;
      line-height: 1.7;
      overflow-x: hidden;
    }

    .it-sprinkles-page h1, .it-sprinkles-page h2, .it-sprinkles-page h3,
.it-sprinkles-page h2.h1 { letter-spacing: .01em; color: var(--is-dark); }

    /* breadcrumb */
    .is-breadcrumb { font-size: .85rem; color: var(--is-muted); padding: 18px 0 0; }
    .is-breadcrumb a { color: var(--is-muted); text-decoration: none; }
    .is-breadcrumb a:hover { color: var(--is-blue); }
    .is-breadcrumb .current { color: var(--is-text); }

    /* hero */
    .is-hero { background: var(--is-mid); padding: 20px 0 60px; }
    .is-eyebrow {
      font-size: .78rem; font-weight: 700; letter-spacing: .12em;
      text-transform: uppercase; color: var(--is-blue); margin-bottom: .6rem; display: block;
    }
    .is-hero h1,
.is-hero h2.h1 { font-size: clamp(2.2rem, 4.5vw, 3.2rem); font-weight: 700; line-height: 1.15; margin-bottom: .75rem; }
    .is-hero p.lead { color: var(--is-muted); font-size: 1.05rem; max-width: 480px; }
    .is-hero-visual {
      aspect-ratio: 4/3; border-radius: 16px; overflow: hidden; position: relative;
      background: linear-gradient(135deg, #dce6ff 0%, #f3f6ff 60%, #ffe9f3 100%);
      display: flex; align-items: center; justify-content: center;
      box-shadow: 0 20px 50px rgba(37,99,255,.15);
    }
    .is-hero-visual i { font-size: 4rem; color: rgba(37,99,255,.35); }

    .btn-is-primary {
      background: var(--is-blue); color: #fff; border: none;
      font-weight: 600; font-size: .95rem; padding: 13px 28px; border-radius: 6px;
      transition: background .2s, transform .15s;
    }
    .btn-is-primary:hover { background: var(--is-blue-hover); color: #fff; transform: translateY(-2px); }
    .btn-is-outline {
      border: 1px solid var(--is-blue-border); color: var(--is-blue);
      background: #fff; font-weight: 600; font-size: .95rem; padding: 13px 28px; border-radius: 6px;
      transition: background .2s, border-color .2s;
    }
    .btn-is-outline:hover { background: var(--is-blue-soft); border-color: var(--is-blue); color: var(--is-blue); }

    /* sub-nav tabs */
    .is-subnav { background: #fff; border-bottom: 1px solid var(--is-border); }
    .is-subnav .nav-link {
      color: var(--is-text); font-weight: 500; font-size: .92rem; padding: 16px 18px;
      border-bottom: 2px solid transparent;
    }
    .is-subnav .nav-link:hover { color: var(--is-blue); border-color: var(--is-blue-border); }

    /* section base */
    .is-section { padding: 80px 0; }
    .is-divider { width: 44px; height: 3px; background: var(--is-blue); margin-bottom: 1.4rem; }
    .is-image-box {
      aspect-ratio: 4/3; border-radius: 14px; overflow: hidden;
      background: linear-gradient(135deg, #eef2ff 0%, #f7f9ff 100%);
      display: flex; align-items: center; justify-content: center;
      border: 1px solid var(--is-border);
    }
    .is-image-box i { font-size: 3rem; color: rgba(37,99,255,.30); }

    .is-tag { display: inline-block; background: var(--is-blue-soft); color: var(--is-blue); border: 1px solid var(--is-blue-border);
      font-size: .82rem; font-weight: 500; padding: 6px 14px; border-radius: 20px; margin: 0 8px 8px 0; }

    /* occasion cards */
    .is-occasion-bg { background: var(--is-mid); }
    .is-occasion-card { background: var(--is-card); border: 1px solid var(--is-border); border-radius: 12px; overflow: hidden; height: 100%;
      transition: transform .25s, box-shadow .25s; }
    .is-occasion-card:hover { transform: translateY(-4px); box-shadow: 0 14px 40px rgba(37,99,255,.12); }
    .is-occasion-thumb { aspect-ratio: 16/10; background: linear-gradient(135deg, #dce6ff, #ffe9f3); display: flex; align-items: center; justify-content: center; }
    .is-occasion-thumb i { font-size: 2.2rem; color: rgba(37,99,255,.35); }
    .is-occasion-card .body { padding: 18px 20px; display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; }
    .is-occasion-card h5 { font-size: 1rem; font-weight: 600; margin-bottom: 4px; }
    .is-occasion-card p { font-size: .85rem; color: var(--is-muted); margin: 0; }
    .is-occasion-arrow {
      flex-shrink: 0; width: 34px; height: 34px; border-radius: 50%; background: var(--is-blue-soft);
      display: flex; align-items: center; justify-content: center; color: var(--is-blue); font-size: .85rem;
    }

    /* why choose */
    .is-why-item { background: var(--is-card); border: 1px solid var(--is-border); border-radius: 12px; padding: 30px 26px; height: 100%; }
    .is-why-icon {
      width: 46px; height: 46px; border-radius: 8px; background: var(--is-blue-soft);
      display: flex; align-items: center; justify-content: center; color: var(--is-blue); font-size: 1.3rem; margin-bottom: 14px;
    }
    .is-why-item h5 { font-size: 1rem; font-weight: 600; margin-bottom: 6px; }
    .is-why-item p { font-size: .9rem; color: var(--is-muted); margin: 0; }

    /* CTA strip */
    .is-cta { background: var(--is-blue); }
    .is-cta h2 { color: #fff; font-size: 1.6rem; margin-bottom: 4px; }
    .is-cta p { color: rgba(255,255,255,.85); margin: 0; }
    .btn-is-white {
      background: #fff; color: var(--is-blue); font-weight: 700; border: none;
      padding: 13px 30px; border-radius: 6px; transition: background .2s, transform .15s;
    }
    .btn-is-white:hover { background: #eef2ff; color: var(--is-blue-hover); transform: translateY(-2px); }

    @media (max-width: 575.98px) {
      .is-hero { padding: 16px 0 40px; }
      .is-section { padding: 56px 0; }
    }
</style>

<div class="it-sprinkles-page">

  <!-- Breadcrumb -->
  <div class="container is-breadcrumb">
    <a href="<?php echo HOME_URL; ?>/">Home</a> / <a href="<?php echo HOME_URL; ?>/what-we-do">What We Do</a> / <span class="current">It Sprinkles</span>
  </div>

  <!-- Hero -->
  <section class="is-hero">
    <div class="container">
      <div class="row align-items-center g-5 pt-4">
        <div class="col-lg-6">
          <span class="is-eyebrow">Austin Celebrations</span>
          <h1>Custom Cakes in Austin</h1>
          <p class="lead">Personalized cakes, cupcakes and desserts for every occasion.</p>
          <a href="#birthday-cakes" class="btn btn-is-primary mt-2">Explore Cake Options <i class="bi bi-arrow-right ms-1"></i></a>
        </div>
        <div class="col-lg-6">
          <div class="is-hero-visual">
            <i class="bi bi-cake2"></i>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Sub nav -->
  <nav class="is-subnav">
    <div class="container">
      <div class="nav flex-nowrap overflow-auto">
        <a class="nav-link" href="#birthday-cakes">Birthday Cakes</a>
        <a class="nav-link" href="#wedding-cakes">Wedding Cakes</a>
        <a class="nav-link" href="#cupcakes">Cupcakes</a>
        <a class="nav-link" href="#celebrations">Celebrations</a>
        <a class="nav-link" href="#flavors">Flavors</a>
      </div>
    </div>
  </nav>

  <!-- Intro copy -->
  <section class="is-section pb-0">
    <div class="container">
      <p class="text-secondary" style="max-width:820px;">
        It Sprinkles creates beautiful and delicious custom cakes in Austin for birthdays, weddings, baby showers, graduations, corporate events, and special celebrations.
        Known for creative cake designs and personalized dessert experiences, It Sprinkles helps customers bring their ideas to life with handcrafted cakes, cupcakes, and sweet treats made for every occasion.
      </p>
      <p class="text-secondary" style="max-width:820px;">
        Whether you are planning a birthday party, elegant wedding reception, holiday gathering, or family celebration, It Sprinkles offers professionally designed desserts tailored to your event style and theme.
        From modern cake artistry to fun celebration desserts, customers searching for custom cakes in Austin can discover personalized creations designed to make every event unforgettable.
      </p>
    </div>
  </section>

  <!-- Birthday Cakes -->
  <section class="is-section" id="birthday-cakes">
    <div class="container">
      <div class="row g-5 align-items-center">
        <div class="col-lg-6">
          <div class="is-image-box"><i class="bi bi-cake2-fill"></i></div>
        </div>
        <div class="col-lg-6">
          <span class="is-eyebrow">Birthday Cakes</span>
          <h2>Custom Birthday Cakes Austin Families Love</h2>
          <div class="is-divider"></div>
          <p class="text-secondary">
            Birthday celebrations deserve desserts that feel unique and memorable. It Sprinkles specializes in creating custom birthday cakes Austin customers can personalize for kids, adults, milestone birthdays, and themed celebrations.
            Whether planning a first birthday party or a major milestone celebration, It Sprinkles helps families discover creative dessert options while delivering high-quality custom cakes in Austin for every celebration style.
            Customers can personalize cake colors, themes, decorations, flavors, and event details to create desserts that match their vision perfectly.
          </p>
          <div>
            <span class="is-tag">Character cakes</span>
            <span class="is-tag">Luxury birthday cakes</span>
            <span class="is-tag">Floral cake designs</span>
            <span class="is-tag">Kids birthday cakes</span>
            <span class="is-tag">Sports-themed cakes</span>
            <span class="is-tag">Elegant minimalist cakes</span>
            <span class="is-tag">Tiered birthday cakes</span>
            <span class="is-tag">Custom photo cakes</span>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Wedding Cakes -->
  <section class="is-section is-occasion-bg" id="wedding-cakes">
    <div class="container">
      <div class="row g-5 align-items-center">
        <div class="col-lg-6 order-lg-2">
          <div class="is-image-box"><i class="bi bi-flower1"></i></div>
        </div>
        <div class="col-lg-6 order-lg-1">
          <span class="is-eyebrow">Wedding Cakes</span>
          <h2>Wedding Cakes Austin TX Couples Can Customize</h2>
          <div class="is-divider"></div>
          <p class="text-secondary">
            Weddings are one of life's most important celebrations, and It Sprinkles creates elegant wedding cakes Austin TX couples can customize for ceremonies, receptions, and engagement celebrations.
            Couples searching for custom cakes in Austin often want desserts that match their venue, wedding colors, and overall event theme. It Sprinkles works to create wedding desserts that combine presentation, creativity, and flavor for unforgettable celebrations.
            Whether hosting a small intimate wedding or a large formal reception, customers can explore personalized wedding dessert experiences tailored to their special day.
          </p>
          <div>
            <span class="is-tag">Multi-tier wedding cakes</span>
            <span class="is-tag">Elegant floral cakes</span>
            <span class="is-tag">Modern minimalist designs</span>
            <span class="is-tag">Rustic wedding cakes</span>
            <span class="is-tag">Luxury dessert tables</span>
            <span class="is-tag">Bridal shower desserts</span>
            <span class="is-tag">Wedding cupcakes & sweets</span>
            <span class="is-tag">Anniversary cakes</span>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Cupcakes -->
  <section class="is-section" id="cupcakes">
    <div class="container">
      <div class="row g-5 align-items-center">
        <div class="col-lg-6">
          <div class="is-image-box"><i class="bi bi-cup-hot-fill"></i></div>
        </div>
        <div class="col-lg-6">
          <span class="is-eyebrow">Cupcakes</span>
          <h2>Custom Cupcakes Austin Dessert Lovers Enjoy</h2>
          <div class="is-divider"></div>
          <p class="text-secondary">
            Cupcakes continue to be one of the most popular dessert options for parties, weddings, school events, and corporate gatherings. It Sprinkles creates delicious custom cupcakes Austin customers can personalize for multiple celebration themes and event styles.
            Customers searching for custom cupcakes Austin often want flexible dessert options for large groups, party displays, and themed dessert tables. It Sprinkles offers creative cupcake designs that combine flavor, presentation, and customization for every occasion.
          </p>
          <div>
            <span class="is-tag">Birthday cupcakes</span>
            <span class="is-tag">Wedding cupcakes</span>
            <span class="is-tag">Corporate event cupcakes</span>
            <span class="is-tag">Holiday-themed cupcakes</span>
            <span class="is-tag">Gender reveal cupcakes</span>
            <span class="is-tag">Baby shower cupcakes</span>
            <span class="is-tag">Custom logo cupcakes</span>
            <span class="is-tag">Seasonal collections</span>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Personalized cakes for every celebration -->
  <section class="is-section is-occasion-bg" id="celebrations">
    <div class="container">
      <div class="text-center mb-5">
        <span class="is-eyebrow">Celebrations for Every Occasion</span>
        <h2>Personalized Cakes for Every Celebration</h2>
        <div class="is-divider mx-auto"></div>
        <p class="text-secondary mx-auto" style="max-width:560px;">From intimate gatherings to big milestones, It Sprinkles creates custom desserts that make every occasion sweeter.</p>
      </div>
      <div class="row g-4">
        <?php
          $occasions = [
            ['bi-balloon-heart',  'Birthdays',       'Fun and creative cakes for all ages.'],
            ['bi-gem',            'Weddings',        'Elegant cakes for your special day.'],
            ['bi-emoji-smile',    'Baby Showers',    'Adorable designs to welcome your little one.'],
            ['bi-mortarboard',    'Graduations',     'Celebrate big achievements with custom cakes.'],
            ['bi-briefcase',      'Corporate Events','Branded and themed cakes for your team.'],
            ['bi-heart',          'Anniversaries',   'Beautiful cakes for lasting love and milestones.'],
          ];
          foreach ($occasions as $o) {
        ?>
        <div class="col-sm-6 col-lg-4">
          <div class="is-occasion-card">
            <div class="is-occasion-thumb"><i class="bi <?php echo $o[0]; ?>"></i></div>
            <div class="body">
              <div>
                <h5><?php echo $o[1]; ?></h5>
                <p><?php echo $o[2]; ?></p>
              </div>
              <div class="is-occasion-arrow"><i class="bi bi-arrow-right"></i></div>
            </div>
          </div>
        </div>
        <?php } ?>
      </div>
    </div>
  </section>

  <!-- Flavors -->
  <section class="is-section" id="flavors">
    <div class="container">
      <div class="row g-5 align-items-center">
        <div class="col-lg-6">
          <span class="is-eyebrow">Cake Flavors and Designs</span>
          <h2>Creative Cake Designs and Flavor Options</h2>
          <div class="is-divider"></div>
          <p class="text-secondary">
            Customers looking for custom cakes in Austin often want desserts that look impressive while tasting delicious. It Sprinkles offers creative cake styles paired with flavorful dessert options for every type of celebration.
            Customers can also customize fillings, frosting styles, decorative details, and presentation themes for birthdays, weddings, and event dessert tables.
          </p>
          <div>
            <span class="is-tag">Vanilla cake</span>
            <span class="is-tag">Chocolate cake</span>
            <span class="is-tag">Red velvet cake</span>
            <span class="is-tag">Strawberry cake</span>
            <span class="is-tag">Lemon cake</span>
            <span class="is-tag">Cookies and cream cake</span>
            <span class="is-tag">Funfetti cake</span>
            <span class="is-tag">Caramel & chocolate</span>
          </div>
        </div>
        <div class="col-lg-6">
          <div class="is-image-box"><i class="bi bi-slice"></i></div>
        </div>
      </div>
    </div>
  </section>

  <!-- Why choose It Sprinkles -->
  <section class="is-section is-occasion-bg">
    <div class="container">
      <div class="text-center mb-5">
        <span class="is-eyebrow">The It Sprinkles Difference</span>
        <h2>Why Customers Choose It Sprinkles</h2>
        <div class="is-divider mx-auto"></div>
        <p class="text-secondary mx-auto" style="max-width:600px;">It Sprinkles continues to grow as a trusted destination for personalized cakes, cupcakes, and event desserts in Austin.</p>
      </div>
      <div class="row g-4">
        <div class="col-md-4">
          <div class="is-why-item">
            <div class="is-why-icon"><i class="bi bi-palette2"></i></div>
            <h5>Personalized Designs</h5>
            <p>Unique cakes and desserts tailored to your vision.</p>
          </div>
        </div>
        <div class="col-md-4">
          <div class="is-why-item">
            <div class="is-why-icon"><i class="bi bi-cake"></i></div>
            <h5>Desserts for Every Event</h5>
            <p>From birthdays to weddings, we create for all of life's moments.</p>
          </div>
        </div>
        <div class="col-md-4">
          <div class="is-why-item">
            <div class="is-why-icon"><i class="bi bi-heart-fill"></i></div>
            <h5>Creative Presentation</h5>
            <p>Beautiful designs that taste as amazing as they look.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Austin celebrations -->
  <section class="is-section">
    <div class="container">
      <div class="row g-5 align-items-center">
        <div class="col-lg-6">
          <div class="is-image-box"><i class="bi bi-stars"></i></div>
        </div>
        <div class="col-lg-6">
          <span class="is-eyebrow">Austin Celebrations</span>
          <h2>Desserts Designed for Austin Celebrations</h2>
          <div class="is-divider"></div>
          <p class="text-secondary">
            Austin is known for creative events, unforgettable celebrations, and unique experiences. It Sprinkles proudly supports local celebrations by creating personalized desserts for birthdays, weddings, family gatherings, and special occasions throughout the area.
            Whether you need custom birthday cakes Austin families will love, elegant wedding cakes Austin TX couples can personalize, or delicious custom cupcakes Austin party guests will enjoy, It Sprinkles helps bring every celebration to life with handcrafted desserts and creative cake artistry.
          </p>
        </div>
      </div>
    </div>
  </section>

  <!-- CTA -->
  <section class="is-cta py-5">
    <div class="container">
      <div class="row align-items-center g-4">
        <div class="col-lg-8">
          <h2>Make Your Celebration Sweeter</h2>
          <p>Get in touch with It Sprinkles to explore custom cakes, cupcakes and desserts for your next event in Austin.</p>
        </div>
        <div class="col-lg-4 text-lg-end">
          <a href="<?php echo HOME_URL; ?>/contact" class="btn btn-is-white btn-lg">Get in Touch <i class="bi bi-arrow-right ms-1"></i></a>
        </div>
      </div>
    </div>
  </section>

</div>

<?php include 'footer.php'; ?>
