<?php 

require_once 'functions.php';
// header.php reads these variable names (the old $pageTitle / $metaRobots / $canonicalURL were ignored: the page was indexable).
$pageMetaTitle       = "Thank You | Seat Outlet";
$pageMetaDescription = "Thank you for your message. Browse upcoming concerts, sports, theater and live events at Seat Outlet.";
$pageRobots          = "noindex, follow";
$pageFocusKeyword    = "Thank you";   // shown in the strip above the header, which is the page's H1 (it used to say "Buy Concert Tickets")
$pageCanonicalUrl    = rtrim(HOME_URL, '/') . "/thank-you";
include 'header.php'; 
?>




<style>

.thankyou-section{
padding:80px 0;
text-align:center;
}

.success-checkmark {
width:100px;
height:100px;
border-radius:50%;
border:10px solid #2556e0;
margin:0 auto 25px;
position:relative;
animation:scaleIn .4s ease-in-out;
}

.success-checkmark::after{
content:"";
position:absolute;
left:27px;
top:14px;
width:25px;
height:50px;
border-right:10px solid #2556e0;
border-bottom:10px solid #2556e0;
transform:rotate(45deg);
animation:draw .6s ease forwards;
}

@keyframes scaleIn{
0%{transform:scale(0)}
100%{transform:scale(1)}
}

@keyframes draw{
0%{height:0;width:0}
100%{height:50px;width:25px}
}
</style>





<section class="thankyou-section">

<div class="container">

<div class="success-checkmark"></div>

<h1 class="mb-3">Thank you</h1>

<p class="lead">
We received your message.
</p>

<p>
Our team will review it and get back to you.<br>
Meanwhile, browse upcoming events happening near you.
</p>

<p><a class="btn btn-primary" href="/buy-tickets-online">Browse events</a></p>




</div>

</div>

</section>







<?php include 'footer.php'; ?>