<?php 
    include 'header.php'; 

    // Fetch performers; default to empty list if response is malformed.
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

    <?php if (!empty($performers)): ?>

        <div class="area-container mt-5">
            <?php foreach ($performers as $performer): ?>
                <?php
                    if (
                        empty($performer['id']) ||
                        empty($performer['text']['name']) ||
                        empty($performer['uriComponent'])
                    ) {
                        continue;
                    }

                    $artistUrl  = '/artist/' . strtolower($performer['uriComponent']);
                    $artistName = $performer['text']['name'];
                ?>
                <a
                    href="<?php echo htmlspecialchars($artistUrl, ENT_QUOTES, 'UTF-8'); ?>"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="area-box"
                >
                    <?php echo htmlspecialchars($artistName, ENT_QUOTES, 'UTF-8'); ?>
                </a>
            <?php endforeach; ?>
        </div>

    <?php else: ?>

        <p>No performers found.</p>

    <?php endif; ?>    

</div>

<?php include 'footer.php'; ?>

