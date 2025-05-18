<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

$pageTitle = "Home";
include 'includes/header.php';
?>
<link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/custom.css">
<?php

// Fetch available rooms
$stmt = $pdo->query("SELECT * FROM rooms WHERE is_available = TRUE");
$rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<meta name="viewport" content="width=device-width, initial-scale=1">

<div class="hero-section">
    <div class="container-fluid text-center">
        <h1 class="display-4">Experience Authentic Japanese Onsen</h1>
        <p class="lead">Relax and rejuvenate in our traditional hot spring baths</p>
        <div>
            <a href="#rooms" class="btn btn-hero-cta btn-lg mt-3">View Rooms</a>
            <a href="#about" class="btn btn-lg mt-3 ms-md-3 btn-hero-learn-more">Learn More</a>
        </div>
    </div>
</div>

<section id="about" class="bg-light py-5 mb-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h2>About OnsenHub</h2>
                <p>OnsenHub brings you the finest selection of traditional Japanese hot spring resorts. Our carefully curated collection offers authentic onsen experiences with modern comforts.</p>
                <p>Each of our partner onsens maintains the highest standards of cleanliness and hospitality, ensuring you have a truly relaxing and rejuvenating experience.</p>
            </div>
            <div class="col-md-6">
                <img src="<?php echo SITE_URL; ?>/assets/img/onsen.jpg" alt="Traditional Onsen" class="img-fluid rounded">
            </div>
        </div>
    </div>
</section>

<section id="features" class="features-angled-section">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold">Why Choose <span class="text-orange">OnsenHub</span></h2>
            <p class="lead">Experience the best hot spring reservation service with these benefits</p>
        </div>
        <div class="row text-center g-4">
            <div class="col-md-4">
                <div class="p-4 bg-white rounded shadow-sm h-100">
                    <div class="feature-icon bg-orange text-black rounded-circle mb-3 mx-auto d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                        <i class="fas fa-clock fa-2x"></i>
                    </div>
                    <h4>Easy & Fast Booking</h4>
                    <p>Reserve your onsen room quickly with our user-friendly platform and instant confirmation.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-4 bg-white rounded shadow-sm h-100">
                    <div class="feature-icon bg-orange text-black rounded-circle mb-3 mx-auto d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                        <i class="fas fa-shield-alt fa-2x"></i>
                    </div>
                    <h4>Trusted Partners</h4>
                    <p>We collaborate only with onsens that meet high standards of hygiene and hospitality.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-4 bg-white rounded shadow-sm h-100">
                    <div class="feature-icon bg-orange text-black rounded-circle mb-3 mx-auto d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                        <i class="fas fa-star fa-2x"></i>
                    </div>
                    <h4>Exclusive Offers</h4>
                    <p>Enjoy special discounts and packages available only through OnsenHub.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="rooms" class="mb-5">
    <div class="container py-4">
        <h2 class="text-center mb-4">Our Onsen Rooms</h2>
        <div class="row">
            <?php foreach ($rooms as $room): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100">
                    <img src="<?php echo SITE_URL; ?>/assets/img/<?php echo $room['image'] ?? 'default.jpg'; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($room['name']); ?>">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($room['name']); ?></h5>
                        <p class="card-text"><?php echo htmlspecialchars(substr($room['description'], 0, 100)); ?>...</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-black fw-bold">₱<?php echo number_format($room['price'], 2); ?></span>
                            <span class="badge bg-secondary">Capacity: <?php echo $room['capacity']; ?></span>
                        </div>
                    </div>
                    <div class="card-footer bg-white">
                        <a href="<?php echo isLoggedIn() ? SITE_URL . '/bookings/create.php?room_id=' . $room['id'] : SITE_URL . '/auth/login.php'; ?>" class="btn btn-orange w-100">
                            Book Now
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section id="contact" class="bg-orange text-white pt-5 pb-5">
    <div class="container">
        <div class="row align-items-stretch">
            <div class="col-lg-6 text-dark">
                <h2 class="display-5 fw-bold mb-4">Contact Us</h2>
                <p class="lead fs-4 mb-4">Have questions or need assistance? We're here to help!</p>
                <form id="contactForm" method="post" action="#">
                    <div class="mb-3">
                        <input type="text" name="name" class="form-control form-control-lg fs-5 text-dark" placeholder="Your Name" required>
                    </div>
                    <div class="mb-3">
                        <input type="email" name="email" class="form-control form-control-lg fs-5 text-dark" placeholder="Your Email" required>
                    </div>
                    <div class="mb-3">
                        <textarea name="message" class="form-control form-control-lg fs-5 text-dark" rows="4" placeholder="Your Message" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-orange btn-lg px-5 py-3 fs-4">Send Message</button>
                </form>
            </div>
            <div class="col-lg-6">
                <div class="p-4 bg-black rounded shadow-sm h-100 text-white">
                    <div class="mt-3 ms-3">
                        <h5 class="text-orange fs-3 mb-4">Contact Information</h5>
                        <ul class="list-unstyled fs-5">
                            <li class="d-flex align-items-center mb-3"><i class="fas fa-map-marker-alt fa-fw me-3 text-orange"></i>123 Onsen Street, Philippines</li>
                            <li class="d-flex align-items-center mb-3"><i class="fas fa-phone fa-fw me-3 text-orange"></i>+81 123-456-7890</li>
                            <li class="d-flex align-items-center mb-3"><i class="fas fa-envelope fa-fw me-3 text-orange"></i>info@onsenhub.com</li>
                        </ul>
                        <div class="social-icons mt-4 fs-3">
                            <a href="#" class="text-white me-3"><i class="fab fa-facebook-f fa-fw"></i></a>
                            <a href="#" class="text-white me-3"><i class="fab fa-instagram fa-fw"></i></a>
                            <a href="#" class="text-white me-3"><i class="fab fa-twitter fa-fw"></i></a>
                            <a href="#" class="text-white"><i class="fab fa-pinterest fa-fw"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Contact Success Overlay -->
<div id="contact-message-overlay" class="success" style="display: none;" title="Click to dismiss">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="currentColor" style="margin-right: 8px; vertical-align: middle;"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
    <span style="vertical-align: middle;">Your message has been sent successfully! We will get back to you shortly.</span>
</div>

<?php include 'includes/footer.php'; ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js"></script>
<script src="<?php echo SITE_URL; ?>/assets/js/main.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const contactForm = document.getElementById('contactForm');
    const contactMessageOverlay = document.getElementById('contact-message-overlay');

    if (contactForm && contactMessageOverlay) {
        contactForm.addEventListener('submit', function(event) {
            event.preventDefault(); // Prevent actual form submission

            contactMessageOverlay.style.display = 'flex'; 

            // Function to hide the overlay
            const hideOverlay = () => {
                if (contactMessageOverlay) {
                    contactMessageOverlay.style.display = 'none';
                }
            };

            // Hide overlay on click
            contactMessageOverlay.addEventListener('click', hideOverlay, { once: true });

            // Hide overlay after 5 seconds
            setTimeout(hideOverlay, 5000);

            // Reset the form fields
            contactForm.reset();
        });
    }
});
</script>
