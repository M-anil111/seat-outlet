<?php include 'header.php'; ?>

<?php 
    $id = $_GET['id'];
    $event = getTnEventById($id);
?>

<div class="hero">
    <div class="hero-content">
        <h1><?php echo $event['text']['name']; ?></h1>
    </div>
</div>

<?php 
    echo '<div id="tn-maps" style="height:500px; margin-top: 50px;"></div><script src="https://mapwidget3.seatics.com/js?eventId='.$id.'&websiteConfigId=12498"></script>';
?>

<?php include 'footer.php'; ?>

   



