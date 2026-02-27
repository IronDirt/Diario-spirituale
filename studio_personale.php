<?php
// Configura il percorso di salvataggio delle sessioni
$session_path = __DIR__ . '/sessions';
if (!is_dir($session_path)) mkdir($session_path, 0777, true);
session_save_path($session_path);

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
    <title>Studio Personale - Diario Spirituale</title>
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">
    <link rel="stylesheet" href="dark-mode.css?v=<?php echo filemtime('dark-mode.css'); ?>">
    <script src="script.js?v=<?php echo filemtime('script.js'); ?>"></script>
</head>
<body id="studio-personale-page">
    <div class="box">
        <h2 class="titolo-centrato">
            <img src="img/personale.png" alt="Studio Personale">
            Studio Personale
        </h2>

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

        <div class="footer-actions">
            <div style="flex: 1;"></div> 

            <a href="home.php" class="back" style="margin: 0;">← Dashboard</a>
            
            <div style="flex: 1; display: flex; justify-content: flex-end;">
                <div class="settings-icon-inline" onclick="document.getElementById('overlay').style.display='flex'">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58a.49.49 0 0 0 .12-.61l-1.92-3.32a.488.488 0 0 0-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54a.484.484 0 0 0-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87a.49.49 0 0 0 .12.61l2.03 1.58c-.05.3-.09.63-.09.94s.02.64.07.94l-2.03 1.58a.49.49 0 0 0-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32a.49.49 0 0 0-.12-.61l-2.03-1.58zM12 15.5c-1.93 0-3.5-1.57-3.5-3.5s1.57-3.5 3.5-3.5 3.5 1.57 3.5 3.5-1.57 3.5-3.5 3.5z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div id="overlay" class="overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); justify-content:center; align-items:center; z-index:1000;">
            <div class="settings-card" style="background:white; padding:20px; border-radius:10px; text-align:center; max-width:400px; width:90%;">
                <h3>Impostazioni Studio Personale</h3>
                <form method="post">
                    <div class="setting-group-block">
                        <p class="setting-title">Nessuna impostazione disponibile</p>
                        <p style="color: #999; font-size: 0.9em;">Le opzioni di configurazione verranno aggiunte a breve.</p>
                    </div>

                    <div class="settings-footer">
                        <button type="button" onclick="document.getElementById('overlay').style.display='none'" class="btn btn-close">CHIUDI</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="page-copyright">&copy; <?php echo date('Y'); ?> Diario Spirituale</div>
</body>
</html>
