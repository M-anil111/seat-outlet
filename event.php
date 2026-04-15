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
    $mapScriptUrl  = 'https://mapwidget3.seatics.com/js?eventId=' . $id . '&websiteConfigId=12498&mobileOptimized=true&includeJQuery=false&useDarkTheme=true';
    $defaultCategory = $event['defaultCategory'];
    $subcategory = '';
    if (!empty($defaultCategory)) {
        if ($defaultCategory['depth'] == 1) {
            $subcategory = $defaultCategory['text']['name'];
        } else {
            if (!empty($defaultCategory['ancestors'])) {
                foreach ($defaultCategory['ancestors'] as $ancestor) {
                    if ($ancestor['depth'] == 1) {
                        $subcategory = $ancestor['text']['name'];
                        break;
                    }
                }										
            }
        }					
    }
?>

 <div class="hero">
    <div class="hero-content">
        <h1><?php echo $eventNameSafe; ?></h1>
        <h2><?php echo 'Performer: ' . $event['performers'][0]['name']; ?></h2>
        <h4><?php echo 'Category: ' . $subcategory; ?></h4>
    </div>
</div>

<div id="tn-maps" style="height:500px; margin-top: 50px;"></div>
<script src="<?php echo htmlspecialchars($mapScriptUrl, ENT_QUOTES, 'UTF-8'); ?>"></script>
<input type="hidden" id="checkoutUrl" value="checkout.seatoutlet.com">
<script type="text/javascript">
	Seatics.config.checkoutUrl = $("#checkoutUrl").val();
	Seatics.config.enableLegalDisclosureMobile = true;
	Seatics.config.preCheckoutButtonHtml = 'Continue to Payment';
	Seatics.config.buyButtonContentHtml = '<div class="buy-btn">' +	'Buy Now' + '</div>';
	Seatics.config.defaultSort = Seatics.SortOptions.PriceAsc;
	Seatics.config.tgMarkTooltipText = 'We recommend this seller&#039;s tickets.';
	Seatics.config.enableMyList = true;
	Seatics.config.showCents = false;
	Seatics.config.skipPrecheckoutMobile = true;
	Seatics.config.showZoomControls = true;
	Seatics.config.ticketListOnRight = true;
	Seatics.config.legendExpanded = true;
	Seatics.config.skipPrecheckoutDesktop = true;
</script>




<?php include 'footer.php'; ?>
