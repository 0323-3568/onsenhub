<?php
if (!isset($overlayMessage) || !isset($overlayType)) {
    return;
}
?>

<link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/overlay.css">

<div id="register-overlay" class="<?php echo htmlspecialchars($overlayType); ?>" title="Click to dismiss">
    <?php if ($overlayType === 'success'): ?>
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
    <?php else: ?>
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M18.3 5.71L12 12l6.3 6.29-1.42 1.42L10.59 13.4 4.29 19.7 2.87 18.29 9.17 12 2.87 5.71 4.29 4.29 10.59 10.6 16.88 4.29z"/></svg>
    <?php endif; ?>
    <span><?php echo htmlspecialchars($overlayMessage); ?></span>
</div>

<script>
    // Overlay dismissal on click or after 5 seconds
    const overlay = document.getElementById('register-overlay');
    if (overlay) {
        overlay.addEventListener('click', () => {
            overlay.style.display = 'none';
        });
        setTimeout(() => {
            if (overlay) {
                overlay.style.display = 'none';
            }
        }, 5000);
    }
</script>
