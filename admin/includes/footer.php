<?php // Include-only file: answer 404 if it is requested directly over the web (it would render a fragment or an error).
if (PHP_SAPI !== 'cli' && isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) { http_response_code(404); exit; } ?>
        </div>
    </div>

    <div class="admin-features">
        <div class="admin-feature">
            <div class="admin-feature-icon"><i class="bi bi-shield-check"></i></div>
            <div>
                <div class="admin-feature-title">Secure Access</div>
                <div class="admin-feature-desc">Your data is protected</div>
            </div>
        </div>
        <div class="admin-feature">
            <div class="admin-feature-icon"><i class="bi bi-people"></i></div>
            <div>
                <div class="admin-feature-title">Admin Control</div>
                <div class="admin-feature-desc">Manage your website</div>
            </div>
        </div>
        <div class="admin-feature">
            <div class="admin-feature-icon"><i class="bi bi-gear"></i></div>
            <div>
                <div class="admin-feature-title">Easy Management</div>
                <div class="admin-feature-desc">Update content anytime</div>
            </div>
        </div>
    </div>

    <script src="assets/admin.js"></script>
</body>

</html>
