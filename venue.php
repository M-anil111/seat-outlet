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
</style>

<section class="location-section py-5">
    <div class="container">
  
      <div class="row g-3">
  
        <div class="col-12 col-md-6 col-lg-4">
          <a href="#" class="location-box">Covelli Centre in Youngstown, OH</a>
        </div>
  
        <div class="col-12 col-md-6 col-lg-4">
          <a href="#" class="location-box">Stambaugh Stadium in Youngstown, OH</a>
        </div>
  
        <div class="col-12 col-md-6 col-lg-4">
          <a href="#" class="location-box">Packard Music Hall in Warren, OH</a>
        </div>
  
        <div class="col-12 col-md-6 col-lg-4">
          <a href="#" class="location-box">Progressive Field in Cleveland, OH</a>
        </div>
  
        <div class="col-12 col-md-6 col-lg-4">
          <a href="#" class="location-box">Nationwide Arena in Columbus, OH</a>
        </div>
  
        <div class="col-12 col-md-6 col-lg-4">
          <a href="#" class="location-box">Rocket Arena in Cleveland, OH</a>
        </div>
  
        <div class="col-12 col-md-6 col-lg-4">
          <a href="#" class="location-box">PNC Park in Pittsburgh, PA</a>
        </div>
  
        <div class="col-12 col-md-6 col-lg-4">
          <a href="#" class="location-box">PPG Paints Arena in Pittsburgh, PA</a>
        </div>
  
        <div class="col-12 col-md-6 col-lg-4">
          <a href="#" class="location-box">Fiserv Forum in Milwaukee, WI</a>
        </div>
  
        
  
      </div>
    </div>
  </section>


  <?php include 'footer.php'; ?>