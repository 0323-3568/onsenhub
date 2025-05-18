</main>

<footer class="bg-black text-white py-4 mt-0">
    <div class="container">
        <div class="row">
            <div class="col-md-4">
                <h5 class="mb-3">
                    <span class="text-orange fw-bold">Onsen</span><span class="text-white">Hub</span>
                </h5>
                <p>Your gateway to authentic Japanese hot spring experiences.</p>
            </div>
            <div class="col-md-4">
                <h5>Quick Links</h5>
                <ul class="list-unstyled">
                    <li><a href="<?php echo SITE_URL; ?>" class="text-white">Home</a></li>
                    <?php if (isAdmin()): ?>
                    <li><a href="<?php echo SITE_URL; ?>/admin/dashboard.php" class="text-white">Dashboard</a></li>
                    <?php elseif (isLoggedIn()): ?>
                    <li><a href="<?php echo SITE_URL; ?>/bookings/list.php" class="text-white">My Bookings</a></li>
                    <?php else: // Not logged in ?>
                    <li><a href="<?php echo SITE_URL; ?>/auth/login.php" class="text-white">Login</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/auth/register.php" class="text-white">Register</a></li>
                    <?php endif; ?>
                </ul>
            </div>
            <div class="col-md-4">
                <h5>Contact Us</h5>
                <address>
                    <i class="bi bi-geo-alt"></i> 123 Onsen Street, Philippines<br>
                    <i class="bi bi-telephone"></i> +81 123-456-7890<br>
                    <i class="bi bi-envelope"></i> info@onsenhub.com
                </address>
            </div>
        </div>
        <hr>
        <div class="text-center">
            &copy; <?php echo date('Y'); ?> OnsenHub. All rights reserved.
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo SITE_URL; ?>/assets/js/script.js"></script>
<script src="<?php echo SITE_URL; ?>/assets/js/custom.js"></script>
</body>
</html>
