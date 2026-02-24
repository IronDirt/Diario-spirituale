<?php
// Configura i parametri del cookie di sessione PRIMA di session_start()
session_set_cookie_params([
    'lifetime' => 259200,   // 3 giorni (la sessione non dura mai più del gc_maxlifetime)
    'path' => '/',
    'secure' => false,      // Metti true se usi HTTPS
    'httponly' => true,
    'samesite' => 'Lax'
]);
ini_set('session.gc_maxlifetime', 259200);    // 3 giorni - IMPORTANTE: deve corrispondere al cookie lifetime
ini_set('session.cookie_lifetime', 259200);   // 3 giorni
ini_set('session.gc_probability', 1);          // Forza il garbage collection
ini_set('session.gc_divisor', 100);            // Ogni 100 richieste
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
    <title>Studio Familiare - Diario Spirituale</title>
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
            <h1>Studio Familiare</h1>
        </div>

        <div class="content-placeholder">
            <div style="text-align: center; padding: 50px 20px; color: #999;">
                <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin: 0 auto 20px;">
                    <circle cx="9" cy="6" r="3"/>
                    <ellipse cx="9" cy="16" rx="6" ry="4"/>
                    <circle cx="17" cy="7" r="2"/>
                    <path d="M21 15C21 16.6569 19.2091 18 17 18C14.7909 18 13 16.6569 13 15C13 13.3431 14.7909 12 17 12C19.2091 12 21 13.3431 21 15Z"/>
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
