<?php 

$pageTitle = "Thank You | SeatOutlet";

$metaDescription = "Thank you for your submission. Discover upcoming concerts, sports games, theater shows, and live entertainment events on SeatOutlet.";

$metaRobots = "noindex, follow";

$canonicalURL = "https://www.seatoutlet.com/thank-you";
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

<h1 class="mb-3">Thank You!</h1>

<p class="lead">
Your request has been successfully submitted.
</p>

<p>
Our team will review your message and get back to you shortly.<br>  
Meanwhile, explore exciting upcoming events happening near you.
</p>




</div>

</div>

</section>




<!-- <script type="application/ld+json">
{
 "@context": "https://schema.org",
 "@type": "WebPage",
 "name": "Thank You",
 "url": "https://www.seatoutlet.com/thank-you",
 "description": "Thank you for your submission on SeatOutlet."
}
</script> -->
<script>
setTimeout(function(){
window.location.href="/";
},5000);
</script>


<?php include 'footer.php'; ?>