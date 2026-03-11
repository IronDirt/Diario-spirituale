<?php
// Replace G-XXXXXXXXXX with your actual Google Analytics 4 Measurement ID
define('GA_MEASUREMENT_ID', 'G-XXXXXXXXXX');
?>
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo GA_MEASUREMENT_ID; ?>"></script>
<script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', '<?php echo GA_MEASUREMENT_ID; ?>');
</script>
