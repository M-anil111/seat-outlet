<section class="teams-nearby bg-white py-3 teams-section">
  <div class="container py-md-5 py-3 slider-bg text-white">
    <div class="d-flex justify-content-between align-items-center">
      <h2 class="section__title section__title--center fw-bold fs-4 mb-4 text-black">
        Top Teams
      </h2>
      <div class="slider-arrows"></div>
    </div>
   
    <div class="category-scroll-wrapper">
      <ul class="nav nav-pills mb-3 category-scroll" role="tablist">
        <li class="nav-item" role="presentation">
          <button class="sport-cat active" data-bs-toggle="pill" data-bs-target="#tab-NFL" type="button" data-slug="NFL">NFL</button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="sport-cat" data-bs-toggle="pill" data-bs-target="#tab-NBA" type="button" data-slug="NBA">NBA</button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="sport-cat" data-bs-toggle="pill" data-bs-target="#tab-MLB" type="button" data-slug="MLB">MLB</button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="sport-cat" data-bs-toggle="pill" data-bs-target="#tab-NHL" type="button" data-slug="NHL">NHL</button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="sport-cat" data-bs-toggle="pill" data-bs-target="#tab-MLS" type="button" data-slug="MLS">MLS</button>
        </li>
      </ul>
    </div>
    <div class="tab-content tab-pane show active" id="sportsTabContent">
      <?php echo generateTeamSkeleton(4); ?>
    </div>
  </div>
</section>