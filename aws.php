<?php 
    include 'header.php'; 

    echo '<pre>';

    $teams = getTeamsByCategory('NFL');
    print_r($teams);
    
    // $all = getAllSportsNestedCategories2(200);
    // print_r($all);

    include 'footer.php'; 

?>