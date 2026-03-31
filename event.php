<?php include 'header.php'; ?>

<?php 
    // Sanitize and validate event ID from query string.
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

    if ($id <= 0) {
        echo '<div class="container"><p>Invalid event.</p></div>';
        include 'footer.php';
        exit;
    }

    $event = getTnEventById($id);
 
    if (empty($event) || empty($event['text']['name'])) {
        echo '<div class="container"><p>Event not found.</p></div>';
        include 'footer.php';
        exit;
    }

    $eventNameSafe = htmlspecialchars($event['text']['name'], ENT_QUOTES, 'UTF-8');
    $mapScriptUrl  = 'https://mapwidget3.seatics.com/js?eventId=' . $id . '&websiteConfigId=12498';
?>

<div class="hero">
    <div class="hero-content">
        <h1><?php echo $eventNameSafe; ?></h1>
    </div>
</div>

<div id="tn-maps" style="height:500px; margin-top: 50px;"></div>
<script src="<?php echo htmlspecialchars($mapScriptUrl, ENT_QUOTES, 'UTF-8'); ?>"></script>

<?php include 'footer.php'; ?>
