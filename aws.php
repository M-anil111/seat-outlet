<?php 
    include 'header.php'; 



    // $location = getUserLocationFromCookie();
    // $location['latitude'];
    // $location['longitude'];
    
    $lat = 33.6973;
    $lng = -117.9087;
    
    $categories = getAllConcertsNestedCategories();
    echo '<pre>';print_r($categories);exit;

    include 'footer.php'; 

?>