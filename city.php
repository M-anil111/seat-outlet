<?php include 'header.php'; ?>

<style>
    .location-section {
  background: linear-gradient(135deg, #2556e0 0%, #1a3fa8 40%, #0e2272 100%);
  position: relative;
}

/* Optional decorative shapes */
.location-section::before {
  content: "";
  position: absolute;
  top: 0;
  left: 0;
  width: 200px;
  height: 200px;
  background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 70%);
  pointer-events: none;
}

.location-box {
  display: block;
  padding: 14px 18px;
  text-align: center;
  color: #fff;
  border: 1px solid rgba(255,255,255,0.7);
  text-decoration: none;
  font-weight: 500;
  transition: 0.3s ease;
  background: rgba(0,0,0,0.15);
}

.location-box:hover {
  background: #fff;
  color: #1a3fa8;
  transform: translateY(-3px);
  box-shadow: 0 6px 20px rgba(0,0,0,0.2);
}


.lake-links-section {
  background: linear-gradient(135deg, #2556e0 0%, #1a3fa8 40%, #0e2272 100%);
  position: relative;
}

/* Optional decorative effect */
.lake-links-section::before {
  content: "";
  position: absolute;
  top: 0;
  right: 0;
  width: 220px;
  height: 220px;
  background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 70%);
  pointer-events: none;
}

/* Map styling */
.map-wrapper iframe {
  border-radius: 6px;
  box-shadow: 0 10px 25px rgba(0,0,0,0.3);
}

/* Link boxes */
.lake-link-box {
  display: block;
  padding: 14px 20px;
  border: 1px solid rgba(255,255,255,0.8);
  color: #fff;
  text-decoration: none;
  font-weight: 500;
  text-align: center;
  transition: 0.3s ease;
  background: rgba(0,0,0,0.2);
}

.lake-link-box:hover {
  background: #ffffff;
  color: #1a3fa8;
  transform: translateY(-3px);
  box-shadow: 0 6px 20px rgba(0,0,0,0.3);
}

/* Responsive heading alignment */
@media (max-width: 992px) {
  .links-wrapper h2 {
    text-align: center;
  }
}

/* ========== VIDEO BANNER + MODAL ========== */
.video-banner {
  position: relative;
  /* border-radius: 16px; */
  overflow: hidden;
  min-height: 490px;
  background-size: cover;
  background-position: center;
  background-color: #0b1220;
  box-shadow: 0 14px 40px rgba(0, 0, 0, 0.18);
}

.video-banner::after {
  content: "";
  position: absolute;
  inset: 0;
  background: linear-gradient(90deg, rgba(0, 0, 0, 0.38), rgba(0, 0, 0, 0.18));
  z-index: 0;
  pointer-events: none;
}

.video-play-btn {
  position: absolute;
  left: 50%;
  top: 50%;
  transform: translate(-50%, -50%);
  width: 84px;
  height: 84px;
  border-radius: 50%;
  border: 2px solid rgba(255, 255, 255, 0.9);
  background: rgba(0, 0, 0, 0.38);
  color: #fff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  z-index: 1;
  cursor: pointer;
  transition: transform 0.2s ease, background 0.2s ease, box-shadow 0.2s ease;
}

.video-play-btn i {
  font-size: 44px;
  line-height: 1;
  margin-left: 3px; /* visually center the play triangle */
}

.video-play-btn:hover {
  transform: translate(-50%, -50%) scale(1.05);
  background: rgba(0, 0, 0, 0.55);
  box-shadow: 0 16px 40px rgba(0, 0, 0, 0.25);
}

.video-play-btn:focus-visible {
  outline: 3px solid rgba(54, 100, 239, 0.85);
  outline-offset: 5px;
}

.video-modal .modal-body {
  background: #000;
  border-radius: 14px;
  overflow: hidden;
}

@media (max-width: 576px) {
  .video-banner {
    min-height: 200px;
  }
  .video-play-btn {
    width: 72px;
    height: 72px;
  }
  .video-play-btn i {
    font-size: 38px;
  }
}
</style>


<section class="container-fluid p-0">
  <div
    class="video-banner"
    style="background-image:url('assets/images/video-bg.jpg')"
    role="img"
    aria-label="Featured video"
  >
    <button
      type="button"
      class="video-play-btn"
      aria-label="Play video"
      data-bs-toggle="modal"
      data-bs-target="#videoModal"
      data-video-id="TfU0qjuZkJ4"
      data-video-src="https://youtu.be/TfU0qjuZkJ4"
    >
      <i class="bi bi-play-fill" aria-hidden="true"></i>
    </button>
  </div>
</section>

 <!-- Video Modal -->
  <div class="modal fade video-modal" id="videoModal" tabindex="-1" aria-hidden="true" aria-label="Video player">
    <div class="modal-dialog modal-dialog-centered modal-xl">
      <div class="modal-content border-0 bg-transparent">
        <div class="modal-body p-0 position-relative">
          <button type="button" class="btn-close btn-close-white position-absolute end-0 top-0 m-3" data-bs-dismiss="modal" aria-label="Close"></button>
          <div class="ratio ratio-16x9">
            <iframe
              id="videoModalIframe"
              src=""
              title="Video"
              allow="autoplay; encrypted-media; picture-in-picture"
              allowfullscreen
              referrerpolicy="strict-origin-when-cross-origin"
            ></iframe>
          </div>
        </div>
      </div>
    </div>
  </div>

<section class="location-section py-5">
    <div class="container">
  
      <div class="row g-3">
  
        <div class="col-12 col-md-6 col-lg-4">
          <a href="#" class="location-box">Events in Bastrop County, TX</a>
        </div>
  
        <div class="col-12 col-md-6 col-lg-4">
          <a href="#" class="location-box">Events in Bee Cave, TX</a>
        </div>
  
        <div class="col-12 col-md-6 col-lg-4">
          <a href="#" class="location-box">Events in Bexar County, TX</a>
        </div>
  
        <div class="col-12 col-md-6 col-lg-4">
          <a href="#" class="location-box">Events in Caldwell County, TX</a>
        </div>
  
        <div class="col-12 col-md-6 col-lg-4">
          <a href="#" class="location-box">Events in Central Austin, TX</a>
        </div>
  
        <div class="col-12 col-md-6 col-lg-4">
          <a href="#" class="location-box">Events in Comal County, TX</a>
        </div>
  
        <div class="col-12 col-md-6 col-lg-4">
          <a href="#" class="location-box">Events in East Austin, TX</a>
        </div>
  
        <div class="col-12 col-md-6 col-lg-4">
          <a href="#" class="location-box">Events in Guadalupe County, TX</a>
        </div>
  
        <div class="col-12 col-md-6 col-lg-4">
          <a href="#" class="location-box">Events in Hays County, TX</a>
        </div>
  
        <div class="col-12 col-md-6 col-lg-4">
          <a href="#" class="location-box">Events in Lakeway Area, TX</a>
        </div>
  
        <div class="col-12 col-md-6 col-lg-4">
          <a href="#" class="location-box">Events in North Austin, TX</a>
        </div>
  
        <div class="col-12 col-md-6 col-lg-4">
          <a href="#" class="location-box">Events in Northwest Austin, TX</a>
        </div>
  
        <div class="col-12 col-md-6 col-lg-4">
          <a href="#" class="location-box">Events in South Austin, TX</a>
        </div>
  
        <div class="col-12 col-md-6 col-lg-4">
          <a href="#" class="location-box">Events in Southwest Austin, TX</a>
        </div>
  
        <div class="col-12 col-md-6 col-lg-4">
          <a href="#" class="location-box">Events in Travis County, TX</a>
        </div>
  
        <div class="col-12 col-md-6 col-lg-4">
          <a href="#" class="location-box">Events in University of Texas, TX</a>
        </div>
  
        <div class="col-12 col-md-6 col-lg-4">
          <a href="#" class="location-box">Events in West Austin, TX</a>
        </div>
  
        <div class="col-12 col-md-6 col-lg-4">
          <a href="#" class="location-box">Events in Williamson County, TX</a>
        </div>
  
      </div>
    </div>
  </section>

<script>

     // Video modal: set iframe src on open, clear on close (stops playback)
  function buildAutoplayUrl(src) {
    if (!src) return '';

    const hasQuery = src.includes('?');
    const needsAutoplay = !/[?&]autoplay=/.test(src);
    const needsMute = !/[?&]mute=/.test(src);
    const params = [];

    if (needsAutoplay) params.push('autoplay=1');
    // Autoplay on most browsers requires muted audio.
    if (needsMute) params.push('mute=1');

    if (params.length === 0) return src;
    return `${src}${hasQuery ? '&' : '?'}${params.join('&')}`;
  }

  const videoModalEl = document.getElementById('videoModal');
  const videoIframe = document.getElementById('videoModalIframe');

  if (videoModalEl && videoIframe) {
    videoModalEl.addEventListener('show.bs.modal', (event) => {
      const triggerEl = event.relatedTarget;
      const rawSrc = triggerEl?.getAttribute?.('data-video-src') || '';
      videoIframe.src = buildAutoplayUrl(rawSrc);
    });

    videoModalEl.addEventListener('hidden.bs.modal', () => {
      videoIframe.src = '';
    });
  }

</script>

  <?php include 'footer.php'; ?>