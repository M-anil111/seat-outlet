<?php
$pageRobots = 'noindex, follow';   // thin partner page: not worth a crawl, kept for visitors
include 'header.php';
?>

<style>
    :root {
      --vp-blue:        #2563ff;
      --vp-blue-hover:  #1a4fd6;
      --vp-blue-soft:   rgba(37,99,255,.10);
      --vp-blue-border: rgba(37,99,255,.25);
      --vp-dark:        #111827;
      --vp-mid:         #F3F6FF;
      --vp-card:        #ffffff;
      --vp-border:      #e5e9f2;
      --vp-muted:       #6b7280;
      --vp-text:        #1f2937;
    }

    .vp-page *, .vp-page *::before, .vp-page *::after { box-sizing: border-box; }

    .vp-page {
      background: #ffffff;
      color: var(--vp-text);
      font-size: 1rem;
      line-height: 1.7;
      overflow-x: hidden;
    }

    .vp-page h1, .vp-page h2, .vp-page h3,
.vp-page h2.h1 { letter-spacing: .01em; color: var(--vp-dark); }

    .vp-breadcrumb { font-size: .85rem; color: var(--vp-muted); padding: 18px 0 0; }
    .vp-breadcrumb a { color: var(--vp-muted); text-decoration: none; }
    .vp-breadcrumb a:hover { color: var(--vp-blue); }
    .vp-breadcrumb .current { color: var(--vp-text); }

    .vp-hero { background: var(--vp-mid); padding: 20px 0 60px; }
    .vp-eyebrow {
      font-size: .78rem; font-weight: 700; letter-spacing: .12em;
      text-transform: uppercase; color: var(--vp-blue); margin-bottom: .6rem; display: block;
    }
    .vp-hero h1,
.vp-hero h2.h1 { font-size: clamp(2.2rem, 4.5vw, 3.2rem); font-weight: 700; line-height: 1.15; margin-bottom: .75rem; }
    .vp-hero p.lead { color: var(--vp-muted); font-size: 1.05rem; max-width: 460px; }
    .vp-hero-visual {
      aspect-ratio: 4/3; border-radius: 16px; overflow: hidden; position: relative;
      background: linear-gradient(135deg, #dce6ff 0%, #f3f6ff 60%, #e9f0ff 100%);
      display: flex; align-items: center; justify-content: center;
      box-shadow: 0 20px 50px rgba(37,99,255,.15);
    }
    .vp-hero-visual i { font-size: 4rem; color: rgba(37,99,255,.35); }

    .btn-vp-primary {
      background: var(--vp-blue); color: #fff; border: none;
      font-weight: 600; font-size: .95rem; padding: 13px 28px; border-radius: 6px;
      transition: background .2s, transform .15s;
    }
    .btn-vp-primary:hover { background: var(--vp-blue-hover); color: #fff; transform: translateY(-2px); }

    .vp-subnav { background: #fff; border-bottom: 1px solid var(--vp-border); }
    .vp-subnav .nav-link {
      color: var(--vp-text); font-weight: 500; font-size: .92rem; padding: 16px 18px;
      border-bottom: 2px solid transparent;
    }
    .vp-subnav .nav-link.active, .vp-subnav .nav-link:hover { color: var(--vp-blue); border-color: var(--vp-blue); }

    .vp-section { padding: 80px 0; }
    .vp-divider { width: 44px; height: 3px; background: var(--vp-blue); margin: 0 auto 1.4rem; }
    .vp-divider.left { margin: 0 0 1.4rem; }

    .vp-tag { display: inline-block; background: var(--vp-blue-soft); color: var(--vp-blue); border: 1px solid var(--vp-blue-border);
      font-size: .82rem; font-weight: 500; padding: 6px 14px; border-radius: 20px; margin: 0 8px 8px 0; }

    /* platform icon row */
    .vp-platform-row { display: flex; flex-wrap: wrap; justify-content: center; gap: 2.2rem; margin: 2.2rem 0 3rem; }
    .vp-platform { text-align: center; font-size: .82rem; color: var(--vp-muted); font-weight: 500; }
    .vp-platform-icon { width: 52px; height: 52px; border-radius: 50%; background: var(--vp-card); border: 1px solid var(--vp-border);
      display: flex; align-items: center; justify-content: center; font-size: 1.4rem; color: var(--vp-blue); margin: 0 auto 8px; }

    /* generic mock UI card */
    .vp-mock-card { background: var(--vp-card); border: 1px solid var(--vp-border); border-radius: 12px; overflow: hidden; height: 100%; }
    .vp-mock-head { background: var(--vp-mid); border-bottom: 1px solid var(--vp-border); padding: 12px 16px; font-size: .82rem; font-weight: 600; color: var(--vp-text); }
    .vp-mock-body { padding: 24px; min-height: 180px; display: flex; align-items: center; justify-content: center; flex-direction: column; gap: 10px; }
    .vp-mock-body i { font-size: 2.4rem; color: rgba(37,99,255,.30); }
    .vp-mock-body span { font-size: .85rem; color: var(--vp-muted); }

    .vp-image-box {
      aspect-ratio: 4/3; border-radius: 14px; overflow: hidden;
      background: linear-gradient(135deg, #eef2ff 0%, #f7f9ff 100%);
      display: flex; align-items: center; justify-content: center;
      border: 1px solid var(--vp-border);
    }
    .vp-image-box i { font-size: 3rem; color: rgba(37,99,255,.30); }

    .vp-shot { border-radius: 12px; overflow: hidden; border: 1px solid var(--vp-border); box-shadow: 0 10px 30px rgba(37,99,255,.10); background: #fff; }
    .vp-shot img { width: 100%; display: block; }

    /* use case cards */
    .vp-usecase-bg { background: var(--vp-mid); }
    .vp-usecase-card { background: var(--vp-card); border: 1px solid var(--vp-border); border-radius: 12px; padding: 24px 22px; height: 100%;
      display: flex; gap: 14px; align-items: flex-start; }
    .vp-usecase-icon { width: 44px; height: 44px; border-radius: 50%; background: var(--vp-blue-soft); flex-shrink: 0;
      display: flex; align-items: center; justify-content: center; color: var(--vp-blue); font-size: 1.2rem; }
    .vp-usecase-card h5 { font-size: 1rem; font-weight: 600; margin-bottom: 4px; }
    .vp-usecase-card p { font-size: .87rem; color: var(--vp-muted); margin: 0; }

    /* automation flow */
    .vp-flow-trigger { background: var(--vp-card); border: 1px solid var(--vp-border); border-radius: 12px; padding: 18px 20px; display: flex; align-items: center; gap: 12px; }
    .vp-flow-trigger .vp-flow-icon { width: 38px; height: 38px; border-radius: 8px; background: var(--vp-blue-soft);
      display: flex; align-items: center; justify-content: center; color: var(--vp-blue); }
    .vp-flow-branches { list-style: none; padding: 0; margin: 16px 0 0; }
    .vp-flow-branches li { font-size: .88rem; color: var(--vp-muted); padding: 8px 0 8px 20px; border-left: 2px dashed var(--vp-blue-border); margin-left: 18px; }
    .vp-automation-item { display: flex; gap: 14px; align-items: flex-start; margin-bottom: 1.6rem; }
    .vp-automation-icon { width: 40px; height: 40px; border-radius: 8px; background: var(--vp-blue-soft); flex-shrink: 0;
      display: flex; align-items: center; justify-content: center; color: var(--vp-blue); font-size: 1.1rem; }
    .vp-automation-item h5 { font-size: .98rem; font-weight: 600; margin-bottom: 2px; }
    .vp-automation-item p { font-size: .86rem; color: var(--vp-muted); margin: 0; }

    /* why choose */
    .vp-why-item { text-align: left; }
    .vp-why-icon {
      width: 46px; height: 46px; border-radius: 8px; background: var(--vp-blue-soft);
      display: flex; align-items: center; justify-content: center; color: var(--vp-blue); font-size: 1.3rem; margin-bottom: 14px;
    }
    .vp-why-item h5 { font-size: 1rem; font-weight: 600; margin-bottom: 6px; }
    .vp-why-item p { font-size: .9rem; color: var(--vp-muted); margin: 0; }

    .vp-cta { background: var(--vp-blue); border-radius: 16px; overflow: hidden; }
    .vp-cta h2 { color: #fff; font-size: 1.5rem; margin-bottom: 4px; }
    .vp-cta p { color: rgba(255,255,255,.85); margin: 0; }
    .btn-vp-white {
      background: #fff; color: var(--vp-blue); font-weight: 700; border: none;
      padding: 13px 30px; border-radius: 6px; transition: background .2s, transform .15s;
    }
    .btn-vp-white:hover { background: #eef2ff; color: var(--vp-blue-hover); transform: translateY(-2px); }

    .text-center-h2 { text-align: center; max-width: 620px; margin: 0 auto; }

    @media (max-width: 575.98px) {
      .vp-hero { padding: 16px 0 40px; }
      .vp-section { padding: 56px 0; }
    }
</style>

<div class="vp-page">

  <!-- Breadcrumb -->
  <div class="container vp-breadcrumb">
    <a href="<?php echo HOME_URL; ?>/">Home</a> / <a href="<?php echo HOME_URL; ?>/what-we-do">What We Do</a> / <span class="current">Viralpep</span>
  </div>

  <!-- Hero -->
  <section class="vp-hero">
    <div class="container">
      <div class="row align-items-center g-5 pt-4">
        <div class="col-lg-6">
          <span class="vp-eyebrow">Social Media Management</span>
          <h1>Social Media Management Tool</h1>
          <p class="lead">Viralpep helps teams create, schedule, collaborate, and track social content from one dashboard.</p>
          <a href="#workflow" class="btn btn-vp-primary mt-2">Explore Viralpep <i class="bi bi-arrow-right ms-1"></i></a>
        </div>
        <div class="col-lg-6">
          <div class="vp-hero-visual">
            <img src="<?php echo HOME_URL; ?>/images/viralpep-hero-dashboard.webp" alt="Viralpep dashboard" style="width:100%;height:100%;object-fit:cover;">
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Sub nav -->
  <nav class="vp-subnav">
    <div class="container">
      <div class="nav flex-nowrap overflow-auto justify-content-center">
        <a class="nav-link active" href="#workflow">Workflow</a>
        <a class="nav-link" href="#scheduling">Scheduling</a>
        <a class="nav-link" href="#creation">Creation</a>
        <a class="nav-link" href="#collaboration">Collaboration</a>
        <a class="nav-link" href="#analytics">Analytics</a>
        <a class="nav-link" href="#automation">Automation</a>
      </div>
    </div>
  </nav>

  <!-- Workflow -->
  <section class="vp-section" id="workflow">
    <div class="container">
      <div class="text-center-h2 mb-3">
        <h2>Simplify Your Social Media Workflow</h2>
        <p class="text-secondary">Manage all your social media content in one place. Plan, create, schedule and publish across multiple platforms with a streamlined, collaborative workflow.</p>
      </div>

      <div class="text-center mb-5">
        <img src="<?php echo HOME_URL; ?>/images/viralpep-platform-icons.webp" alt="Facebook, Instagram, LinkedIn, X/Twitter, Pinterest" style="max-width:100%;height:auto;">
      </div>

      <div class="row g-4">
        <div class="col-md-6">
          <div class="vp-shot"><img src="<?php echo HOME_URL; ?>/images/viralpep-create-post.webp" alt="Create Post composer"></div>
        </div>
        <div class="col-md-6">
          <div class="vp-shot"><img src="<?php echo HOME_URL; ?>/images/viralpep-post-preview.webp" alt="Post Preview"></div>
        </div>
      </div>
    </div>
  </section>

  <!-- Scheduling -->
  <section class="vp-section" id="scheduling" style="background:var(--vp-mid);">
    <div class="container">
      <div class="row g-5 align-items-center">
        <div class="col-lg-6">
          <div class="vp-shot"><img src="<?php echo HOME_URL; ?>/images/viralpep-content-calendar.webp" alt="Content Calendar"></div>
        </div>
        <div class="col-lg-6">
          <h2>Advanced Scheduling Tools for Smarter Content Planning</h2>
          <div class="vp-divider left"></div>
          <p class="text-secondary">
            Plan ahead with a visual content calendar, schedule posts across multiple platforms, and add first comments to boost engagement.
          </p>
          <div>
            <span class="vp-tag">Visual Calendar</span>
            <span class="vp-tag">Bulk Scheduling</span>
            <span class="vp-tag">First Comments</span>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Creation -->
  <section class="vp-section" id="creation">
    <div class="container">
      <div class="row g-5 align-items-center">
        <div class="col-lg-6">
          <h2>Create Better Content Faster</h2>
          <div class="vp-divider left"></div>
          <p class="text-secondary">
            Design, edit and organize your content with built-in tools and templates. Access your media library, create platform-optimized posts, and preview exactly how they will look.
          </p>
          <div>
            <span class="vp-tag">Media Library</span>
            <span class="vp-tag">Content Templates</span>
            <span class="vp-tag">Post Preview</span>
          </div>
        </div>
        <div class="col-lg-6">
          <div class="vp-shot"><img src="<?php echo HOME_URL; ?>/images/viralpep-media-library.webp" alt="Media Library"></div>
        </div>
      </div>
    </div>
  </section>

  <!-- Collaboration -->
  <section class="vp-section" id="collaboration" style="background:var(--vp-mid);">
    <div class="container">
      <div class="row g-5 align-items-center">
        <div class="col-lg-6">
          <div class="vp-shot"><img src="<?php echo HOME_URL; ?>/images/viralpep-team-collaboration.webp" alt="Team Collaboration"></div>
        </div>
        <div class="col-lg-6">
          <h2>Team Collaboration Made Simple</h2>
          <div class="vp-divider left"></div>
          <p class="text-secondary">
            Work together with your team, assign roles, review content and get approvals - all within Viralpep.
          </p>
          <div>
            <span class="vp-tag">Draft</span>
            <span class="vp-tag">Review</span>
            <span class="vp-tag">Approved</span>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Analytics -->
  <section class="vp-section" id="analytics">
    <div class="container">
      <div class="row g-5 align-items-center">
        <div class="col-lg-6">
          <h2>Analytics and Performance Tracking</h2>
          <div class="vp-divider left"></div>
          <p class="text-secondary">
            Understand what's working with clear, visual analytics. Track engagement trends, monitor content performance, and get insights to improve your strategy.
          </p>
        </div>
        <div class="col-lg-6">
          <div class="vp-shot"><img src="<?php echo HOME_URL; ?>/images/viralpep-performance-overview.webp" alt="Performance Overview"></div>
        </div>
      </div>
    </div>
  </section>

  <!-- Use cases -->
  <section class="vp-section vp-usecase-bg">
    <div class="container">
      <div class="text-center-h2 mb-5">
        <h2>Built for Agencies, Businesses, and Creators</h2>
        <p class="text-secondary">Viralpep adapts to your needs, whether you're managing multiple clients or growing your own brand.</p>
      </div>
      <div class="row g-4">
        <?php
          $usecases = [
            ['bi-people-fill', 'Agencies', 'Manage multiple client accounts with ease.'],
            ['bi-shop', 'Small Businesses', 'Grow your brand with simple, effective tools.'],
            ['bi-bag-fill', 'eCommerce', 'Showcase products and drive engagement.'],
            ['bi-person-fill', 'Creators', 'Plan and publish content across all your platforms.'],
            ['bi-rocket-takeoff-fill', 'Startups', 'Build your presence and scale faster.'],
            ['bi-heart-fill', 'Nonprofits', 'Share your mission and make a bigger impact.'],
          ];
          foreach ($usecases as $u) {
        ?>
        <div class="col-md-6 col-lg-4">
          <div class="vp-usecase-card">
            <div class="vp-usecase-icon"><i class="bi <?php echo $u[0]; ?>"></i></div>
            <div>
              <h5><?php echo $u[1]; ?></h5>
              <p><?php echo $u[2]; ?></p>
            </div>
          </div>
        </div>
        <?php } ?>
      </div>
    </div>
  </section>

  <!-- Automation -->
  <section class="vp-section" id="automation">
    <div class="container">
      <div class="text-center-h2 mb-5">
        <h2>Social Media Automation That Saves Time</h2>
        <p class="text-secondary">Set up automated workflows to keep your content running smoothly, even when you're busy.</p>
      </div>
      <div class="row g-5 align-items-center">
        <div class="col-lg-5">
          <div class="vp-flow-trigger">
            <div class="vp-flow-icon"><i class="bi bi-file-earmark-plus"></i></div>
            <div>
              <div style="font-size:.78rem;color:var(--vp-muted);text-transform:uppercase;letter-spacing:.06em;">When</div>
              <div style="font-weight:600;font-size:.92rem;">New content is added</div>
            </div>
          </div>
          <ul class="vp-flow-branches">
            <li>Auto schedule to selected platforms</li>
            <li>Add to recurring queue</li>
            <li>Notify team for review</li>
          </ul>
        </div>
        <div class="col-lg-7">
          <div class="vp-automation-item">
            <div class="vp-automation-icon"><i class="bi bi-clock-history"></i></div>
            <div>
              <h5>Auto Scheduling</h5>
              <p>Automatically publish content at the best times for your audience.</p>
            </div>
          </div>
          <div class="vp-automation-item">
            <div class="vp-automation-icon"><i class="bi bi-cloud-upload"></i></div>
            <div>
              <h5>Bulk Uploads</h5>
              <p>Upload and schedule multiple posts at once.</p>
            </div>
          </div>
          <div class="vp-automation-item">
            <div class="vp-automation-icon"><i class="bi bi-arrow-repeat"></i></div>
            <div>
              <h5>Recurring Content</h5>
              <p>Set evergreen content to post on repeat.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Why choose -->
  <section class="vp-section" style="background:var(--vp-mid);">
    <div class="container">
      <div class="text-center-h2 mb-5">
        <h2>Why Businesses Choose Viralpep</h2>
        <p class="text-secondary">Everything you need to manage social media more effectively, in one powerful platform.</p>
      </div>
      <div class="row g-4">
        <div class="col-sm-6 col-lg-3">
          <div class="vp-why-item">
            <div class="vp-why-icon"><i class="bi bi-cursor"></i></div>
            <h5>Easy to Use</h5>
            <p>A clean, intuitive interface for teams of all sizes.</p>
          </div>
        </div>
        <div class="col-sm-6 col-lg-3">
          <div class="vp-why-item">
            <div class="vp-why-icon"><i class="bi bi-diagram-3"></i></div>
            <h5>Multi-platform Planning</h5>
            <p>Plan and manage all your social channels together.</p>
          </div>
        </div>
        <div class="col-sm-6 col-lg-3">
          <div class="vp-why-item">
            <div class="vp-why-icon"><i class="bi bi-people"></i></div>
            <h5>Team Collaboration</h5>
            <p>Streamline reviews and approvals.</p>
          </div>
        </div>
        <div class="col-sm-6 col-lg-3">
          <div class="vp-why-item">
            <div class="vp-why-icon"><i class="bi bi-bar-chart"></i></div>
            <h5>Clear Reporting</h5>
            <p>Get actionable insights without the complexity.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- CTA -->
  <section class="vp-section pt-0">
    <div class="container">
      <div class="vp-cta p-5">
        <div class="row align-items-center g-4">
          <div class="col-lg-8">
            <h2>A Smarter Way to Manage Social Media</h2>
            <p>Discover how Viralpep can help your team create, schedule and grow with confidence.</p>
          </div>
          <div class="col-lg-4 text-lg-end">
            <a href="<?php echo HOME_URL; ?>/contact" class="btn btn-vp-white btn-lg">Get in Touch <i class="bi bi-arrow-right ms-1"></i></a>
          </div>
        </div>
      </div>
    </div>
  </section>

</div>

<?php include 'footer.php'; ?>
