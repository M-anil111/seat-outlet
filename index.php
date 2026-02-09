<?php 
    include 'header.php'; 
    $performersResponse = getTnPerformers();
    $performers = $performersResponse['results'] ?? [];
?>

<!-- Header -->
<div class="hero">
    <div class="hero-content">
        <h1>All Performers</h1>
    </div>
</div>

<!-- Main Layout -->
<div class="container">

    <?php if (!empty($performers)) { ?>

        <div class="area-container mt-5 mb-5">
            <?php foreach ($performers as $performer) { 
                $count = getTnPerformerEventsCount($performer['id']);
                if($count > 0) {
            ?>
                <a href="/artist/<?php echo strtolower($performer['uriComponent']); ?>" target="_blank" class="area-box"><?php echo $performer['text']['name']; ?></a>
            <?php } } ?>
        </div>

    <?php } else { ?>

        <p>No performers found.</p>

    <?php } ?>    

</div>


<?php include 'footer.php'; ?>

