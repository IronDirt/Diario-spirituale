<?php
// Replace G-XXXXXXXXXX with your actual Google Analytics 4 Measurement ID
define('GA_MEASUREMENT_ID', 'G-XXXXXXXXXX');
?>
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=<?= htmlspecialchars(GA_MEASUREMENT_ID, ENT_QUOTES, 'UTF-8') ?>"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){window.dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', '<?= htmlspecialchars(GA_MEASUREMENT_ID, ENT_QUOTES, 'UTF-8') ?>');
</script>
