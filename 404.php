<?php 
include 'header.php'; 
?>



<style>


        .container-fluid-bg{
background: radial-gradient(circle at center, #1e3a8a 0%, #0f172a 100%);
min-height:50vh;
color:#fff;
display:flex;
align-items:center;
justify-content:center;
padding:100px 20px 100px 20px;
}

.error-container{
max-width:650px;
text-align:center;
}

.float-animation{
animation: float 3s ease-in-out infinite;
}

@keyframes float{
0%{transform:translateY(0)}
50%{transform:translateY(-20px)}
100%{transform:translateY(0)}
}

.icon-large{
font-size:120px;
color:#3b82f6;
filter:drop-shadow(0 10px 20px rgba(0,0,0,0.6));
}

/* .star-group{
position:absolute;
top:-15px;
right:-20px;
}

.star-group i{
color:#facc15;
font-size:18px;
} */

.error-code{
font-size:110px;
font-weight:900;
background:linear-gradient(90deg,#60a5fa,#2563eb);
-webkit-background-clip:text;
-webkit-text-fill-color:transparent;
}

.subtitle{
font-size:32px;
font-weight:700;
}

.description{
color:#cbd5e1;
max-width:450px;
margin:auto;
}

.btn-home{
background:linear-gradient(90deg,#60a5fa,#2563eb);
border:none;
padding:12px 30px;
border-radius:30px;
font-weight:600;
box-shadow:0 10px 25px rgba(0,0,0,0.4);
}

.btn-home:hover{
background:#1d4ed8;
}

@media (max-width: 768px) {
.container-fluid-bg { padding: 0px 20px 0px 20px;}}
</style>
<div class="container-fluid-bg">
<div class="error-container">

<div class="position-relative d-inline-block float-animation mb-4">

<div class="icon-large">
<svg xmlns="http://www.w3.org/2000/svg" version="1.1" xmlns:xlink="http://www.w3.org/1999/xlink" width="256" height="256" x="0" y="0" viewBox="0 0 24 24" style="enable-background:new 0 0 512 512" xml:space="preserve" class=""><g><path fill="#549bff" d="M19.75 7v9a.75.75 0 0 1-.75.75H5a.75.75 0 0 1-.75-.75V7A2.752 2.752 0 0 1 7 4.25h10A2.752 2.752 0 0 1 19.75 7zM5 17.25a.75.75 0 0 0-.75.75v2a.75.75 0 0 0 1.5 0v-2a.75.75 0 0 0-.75-.75zm14 0a.75.75 0 0 0-.75.75v2a.75.75 0 0 0 1.5 0v-2a.75.75 0 0 0-.75-.75z" opacity="1" data-original="#549bff" class=""></path><path fill="#bad7ff" d="M19.5 10.25h-1a2.252 2.252 0 0 0-2.25 2.25.75.75 0 0 1-.75.75h-7a.75.75 0 0 1-.75-.75 2.252 2.252 0 0 0-2.25-2.25h-1a2.252 2.252 0 0 0-2.25 2.25V15A3.755 3.755 0 0 0 6 18.75h12A3.755 3.755 0 0 0 21.75 15v-2.5a2.252 2.252 0 0 0-2.25-2.25z" opacity="1" data-original="#bad7ff" class=""></path></g></svg>
</div>



</div>

<div class="mb-4">

<h1 class="error-code">404</h1>

<h2 class="subtitle">Oops! Seat Not Found</h2>

<p class="description">
Looks like this seat has been reserved or doesn't exist. Let's get you back to the comfort of our main collection.
</p>

</div>

<div class="pt-3">

<a href="/" class="btn btn-home">
Back to Home
</a>

</div>

</div>
</div>



<?php include 'footer.php'; ?>