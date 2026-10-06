<?php // Include-only file: answer 404 if it is requested directly over the web (it would render a fragment or an error).
if (PHP_SAPI !== 'cli' && isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) { http_response_code(404); exit; } ?>
<div class="modal fade" id="staticBackdrop" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered">
		<div class="modal-content pb-4">
			<div class="modal-header">
				<h5 class="modal-title" id="staticBackdropLabel">Our 100% Guarantee</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<p>Shop tickets with total confidence, knowing your purchase is protected.
					Our 100% guarantee means secure checkout, valid tickets and delivery in time for the event.
					From start to showtime, we’ve got you covered, worry free.</p>
			</div>
		</div>
	</div>
</div>

<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasRight" aria-labelledby="offcanvasRightLabel">
	<div class="offcanvas-header">
		
		<button type="button" class="btn btn-outline-primary" data-bs-dismiss="offcanvas" aria-label="Close"> <i class="bi bi-arrow-left"></i></button>
        <h5 class="offcanvas-title ms-3" id="offcanvasRightLabel">Event information</h5>
	</div>
	<div class="offcanvas-body px-4" id="offcanvasBody">
        <hr class="mt-0">
        <div class="event-details mb-4">
            <div class="text-muted fs-5 fw-bold mb-3" id="offcanvasDate"></div>            
            <div class="fw-bold" id="offcanvasVenue"></div>
            <div class="fw-bold mb-1" id="offcanvasLocation"></div>
            <div class="text-muted mb-4" id="offcanvasTitle"></div>            
            <a href="/" class="btn btn-primary d-flex align-items-center justify-content-center gap-2" id="offcanvasId">
                Buy Tickets
                <i class="bi bi-chevron-right"></i>
            </a>
        </div>
        <hr>
        <div class="performer-section my-4 ">
            <div class="performer-label fs-5 fw-semibold mb-1">
                LINEUP
            </div>
            <ul id="offcanvasPerformers"></ul>
        </div>
        <hr>
        <div class="venue-section my-4">
            <div class="venue-label fs-5 fw-semibold mb-1">
                VENUE
            </div>
            <a class="venue-link fs-6" id="venue-link" aria-label="Venue details"></a>
        </div>
	</div>
</div>