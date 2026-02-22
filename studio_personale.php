<?php
// Configura i parametri del cookie di sessione PRIMA di session_start()
session_set_cookie_params([
    'lifetime' => 2592000,  // 30 giorni
    'path' => '/',
    'secure' => false,      // Metti true se usi HTTPS
    'httponly' => true,
    'samesite' => 'Lax'
]);
ini_set('session.gc_maxlifetime', 2592000);
ini_set('session.cookie_lifetime', 2592000);
session_start();

// 1. Definiamo la cartella base
$cartella_db = 'database';

// 2. Controllo sicurezza: se non sei loggato, torni al login
if (!isset($_SESSION['autenticato'])) { 
    header("Location: index.php"); 
    exit; 
}

// 3. Impostiamo il percorso della cartella dell'utente
$path = $_SESSION['user_dir'] . '/';
if (!is_dir($path)) {
    mkdir($path, 0777, true);
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Studio Personale - Diario Spirituale</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <link rel="icon" type="image/png" href="icona_diario_personale.png">
    <link rel="apple-touch-icon" href="icona_diario_personale.png">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="Diario">
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="home.php" class="back-button">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
            </a>
            <h1>Studio Personale</h1>
        </div>

        <div class="content-placeholder">
            <div style="text-align: center; padding: 50px 20px; color: #999;">
                <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin: 0 auto 20px;">
                    <path d="M12 11C14.2091 11 16 9.20914 16 7C16 4.79086 14.2091 3 12 3C9.79086 3 8 4.79086 8 7C8 9.20914 9.79086 11 12 11Z"/>
                    <path d="M4 21C4 17.134 7.13401 14 11 14H13C16.866 14 20 17.134 20 21"/>
                    <path d="M7 17L9 19L7 21"/>
                    <path d="M17 17L15 19L17 21"/>
                </svg>
                <p style="font-size: 1.1em; margin: 0;">Contenuto in arrivo</p>
                <p style="font-size: 0.9em; margin-top: 10px;">Questa sezione sarà presto disponibile</p>
            </div>
        </div>
    </div>

    <div class="page-copyright">&copy; <?php echo date('Y'); ?> Diario Spirituale</div>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="script.js"></script>
</body>
</html>
