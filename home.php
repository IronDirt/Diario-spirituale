<?php
ini_set('session.gc_maxlifetime', 2592000);
ini_set('session.cookie_lifetime', 2592000);
session_start();

// --- GESTIONE LOGOUT ---
if (isset($_GET['logout'])) {
    // Svuota l'array di sessione
    session_unset();
    // Distrugge la sessione
    session_destroy();
    
    // IMPORTANTE: Cancella il cookie "Ricordami" altrimenti index.php ti ri-autentica subito
    if (isset($_COOKIE['remember_me'])) {
        setcookie('remember_me', '', time() - 3600, "/");
    }
    
    header("Location: index.php");
    exit;
}

// 1. Definiamo sempre la cartella base (per evitare dati persi)
$cartella_db = 'database';

// 2. Se non c'è la sessione, proviamo a vedere se index.php può ripristinarla col cookie
// Se non sei autenticato, vai alla index (che gestisce i cookie)
if (!isset($_SESSION['autenticato'])) { 
    header("Location: index.php"); 
    exit; 
}

// 3. Recuperiamo il percorso della cartella utente dalla sessione
$path = $_SESSION['user_dir'] . '/';

// Se per qualche motivo la cartella non esiste, creiamola subito
if (!is_dir($path)) {
    mkdir($path, 0777, true);
}

$path = $_SESSION['user_dir'] . '/';
$config_file = $path . 'config.json';
$default_config = ['servizio' => true, 'visite' => false, 'lettura' => false, 'mete' => true];
$config = file_exists($config_file) ? json_decode(file_get_contents($config_file), true) : $default_config;
$config = array_merge($default_config, (array)$config);

if (isset($_POST['salva_config'])) {
    foreach ($default_config as $key => $val) { $config[$key] = isset($_POST['w_'.$key]); }
    file_put_contents($config_file, json_encode($config));
    header("Location: home.php"); exit;
}

$ore_h = 0; $min_h = 0;

// Caricamento impostazioni obiettivi
$file_settings = $path . 'impostazioni.json';
$settings = file_exists($file_settings) ? json_decode(file_get_contents($file_settings), true) : [
    'obj_mese_attivo' => false, 'obj_mese_ore' => 0,
    'obj_anno_attivo' => false, 'obj_anno_ore' => 0
];

// Calcolo totale annuale (Settembre - Agosto)
$ore_file = $path . 'ore_servizio.json';
$dati_ore = file_exists($ore_file) ? json_decode(file_get_contents($ore_file), true) : [];

// 2. Caricamento Impostazioni Obiettivi
$file_settings = $path . 'impostazioni.json';
$settings = file_exists($file_settings) ? json_decode(file_get_contents($file_settings), true) : [
    'obj_mese_attivo' => false, 'obj_mese_ore' => 0,
    'obj_anno_attivo' => false, 'obj_anno_ore' => 0
];

// 3. Inizializzazione variabili calcolo
$oggi_m = (int)date("m");
$oggi_a = (int)date("Y");
$tot_min_mese = 0;
$tot_min_anno = 0;

// Determina l'inizio dell'anno di servizio (Settembre)
$anno_servizio_inizio = ($oggi_m >= 9) ? $oggi_a : $oggi_a - 1;

// 4. Ciclo unico per calcolare tutto
foreach ((array)$dati_ore as $item) {
    $m = (int)$item['mese'];
    $a = (int)$item['anno'];

    // Somma Mese Corrente
    if ($m == $oggi_m && $a == $oggi_a) {
        $tot_min_mese += ($item['ore'] * 60) + $item['minuti'];
    }

    // Somma Anno di Servizio (Settembre - Agosto)
    if (($a == $anno_servizio_inizio && $m >= 9) || ($a == $anno_servizio_inizio + 1 && $m <= 8)) {
        $tot_min_anno += ($item['ore'] * 60) + $item['minuti'];
    }
}

// 5. Variabili finali per il widget
$ore_h = floor($tot_min_mese / 60);
$min_h = $tot_min_mese % 60;
$ore_tot_anno = floor($tot_min_anno / 60);

// 6. Calcolo Mete (rimane uguale)
$perc_mete = 0;
$mete_file = $path . 'mete.json';
if (file_exists($mete_file)) {
    $mete_data = json_decode(file_get_contents($mete_file), true);
    $mete_array = (array)$mete_data;
    $total_main_goals = count($mete_array);
    $total_progress = 0;

    if ($total_main_goals > 0) {
        foreach ($mete_array as $m) {
            $goal_val = 0;
            
            // 1. Valore della Meta Principale (pesa 1/3)
            if (isset($m['completata']) && $m['completata'] == true) {
                $goal_val += (1 / 3);
            }

            // 2. Valore delle Sotto-mete (pesano 2/3 in totale)
            if (isset($m['sotto_mete']) && !empty($m['sotto_mete'])) {
                $sub_count = count($m['sotto_mete']);
                $sub_completed = 0;
                foreach ($m['sotto_mete'] as $sm) {
                    if (isset($sm['completata']) && $sm['completata'] == true) $sub_completed++;
                }
                $goal_val += ($sub_completed / $sub_count) * (2 / 3);
            } elseif (isset($m['completata']) && $m['completata'] == true) {
                // Se non ci sono sotto-mete, la meta completata vale 1 intero
                $goal_val = 1;
            }

            $total_progress += $goal_val;
        }
        $perc_mete = round(($total_progress / $total_main_goals) * 100);
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - Diario Spirituale</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <link rel="icon" type="image/png" href="icona_diario_personale.png">

    <link rel="apple-touch-icon" href="icona_diario_personale.png">

    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="Diario">
</head>
<body id="home-page">
    <div class="container home-container">

        
        <h1>Diario Spirituale</h1>
        <p class="citazione">
            <i>"I piani di chi è diligente portano di sicuro a ottimi risultati"</i><span> — Proverbi 21:5</span>
            
        </p>
        
        <div class="grid">
        <?php if ($config['servizio']): ?>
            <a href="servizio.php" class="widget">
                <img src="img/clock-svgrepo-com.svg" alt="Servizio" class="icona-svg-widget">
                <span>Servizio</span>
                
                <?php 
                // CASO 1: Obiettivo Mensile Attivo -> Mostra Barre Progressive
                if ($settings['obj_mese_attivo']): ?>
                    <div class="home-obj-wrapper mg-top-10">
                        <div class="home-obj-info">
                            <span>Mese</span>
                            <span><?php echo $ore_h; ?>/<?php echo $settings['obj_mese_ore']; ?>h</span>
                        </div>
                        <div class="home-progress-bar-bg">
                            <div class="progress-bar progress-bar-mensile" style="width: <?php echo min(100, round(($ore_h / max(1, $settings['obj_mese_ore'])) * 100)); ?>%;"></div>
                        </div>
                    </div>
                    <?php if ($settings['obj_anno_attivo']): ?>
                        <div class="home-obj-wrapper mg-top-8">
                            <div class="home-obj-info">
                                <span>Anno</span>
                                <span><?php echo $ore_tot_anno; ?>/<?php echo $settings['obj_anno_ore']; ?>h</span>
                            </div>
                            <div class="home-progress-bar-bg">
                                <div class="progress-bar progress-bar-annuale" style="width: <?php echo min(100, round(($ore_tot_anno / max(1, $settings['obj_anno_ore'])) * 100)); ?>%;"></div>
                            </div>
                        </div>
                    <?php endif; ?>

                <?php 
                // CASO 2 e 3: Mensile NON attivo -> Mostra la riga sottile "Mensile ... 10h 5m"
                else: ?>
                    <div class="info-line-wrapper">
                        <div class="info-line-text">
                            <span>Mensile</span>
                            <span><?php echo "$ore_h h"; ?></span>
                        </div>
                        <div class="info-line-separator"></div>
                    </div>

                    <?php // Se però l'annuale è attivo, mostra la sua barra progressiva sotto
                    if ($settings['obj_anno_attivo']): ?>
                        <div class="home-obj-wrapper mg-top-8">
                            <div class="home-obj-info">
                                <span>Anno</span>
                                <span><?php echo $ore_tot_anno; ?>/<?php echo $settings['obj_anno_ore']; ?>h</span>
                            </div>
                            <div class="home-progress-bar-bg">
                                <div class="progress-bar progress-bar-annuale" style="width: <?php echo min(100, round(($ore_tot_anno / max(1, $settings['obj_anno_ore'])) * 100)); ?>%;"></div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
                
            </a>
        <?php endif; ?>
            
            <!-- Visite e Lettura widget rimossi -->
            
            <?php if ($config['mete']): ?>
                <a href="mete.php" class="widget">
                    <img src="img/bullseye-svgrepo-com.svg" alt="Mete" class="icona-svg-widget">
                    <span>Mete</span>
                    
                    <div class="home-obj-wrapper mg-top-10">
                        <div class="home-obj-info">
                            <span>Raggiunte</span>
                            <span><?php echo $perc_mete; ?>%</span>
                        </div>
                        <div class="home-progress-bar-bg">
                            <div class="progress-bar" style="width: <?php echo $perc_mete; ?>%; background-color: #2ecc71;"></div>
                        </div>
                    </div>
                </a>
            <?php endif; ?>
        </div>

        <div class="footer-home">
            <a href="home.php?logout=1" class="logout-link" title="Disconnetti" style="position: relative; z-index: 10;">
                <img src="img/exit.png" alt="Esci" class="exit-icon">
            </a>
            
            <div class="settings-icon" onclick="document.getElementById('overlay').style.display='flex'">
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58a.49.49 0 0 0 .12-.61l-1.92-3.32a.488.488 0 0 0-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54a.484.484 0 0 0-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87a.49.49 0 0 0 .12.61l2.03 1.58c-.05.3-.09.63-.09.94s.02.64.07.94l-2.03 1.58a.49.49 0 0 0-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32a.49.49 0 0 0-.12-.61l-2.03-1.58zM12 15.5c-1.93 0-3.5-1.57-3.5-3.5s1.57-3.5 3.5-3.5 3.5 1.57 3.5 3.5-1.57 3.5-3.5 3.5z"></path>
                </svg>
            </div>

        </div>

        <div id="overlay">
            <div class="modal">
                <h3>Impostazioni Dashboard</h3>
                <p style="font-size: 0.9em; color: #666;">Scegli cosa vedere nella home:</p>
                <form method="post">
                    <label class="settings-option">
                        <input type="checkbox" name="w_servizio" <?php echo $config['servizio'] ? 'checked' : ''; ?>> 
                        <span>Registro Ore</span>
                    </label>
                    
                    <!-- opzioni Visite e Lettura rimosse dalle impostazioni -->
                    
                    <label class="settings-option">
                        <input type="checkbox" name="w_mete" <?php echo $config['mete'] ? 'checked' : ''; ?>> 
                        <span>Mete</span>
                    </label>
                    
                    <hr style="border: 0; border-top: 1px solid #eee; margin: 15px 0;">
                    
                    <a href="profilo.php" class="profile-link-new">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        <span>Gestisci il tuo Profilo</span>
                        <span style="margin-left: auto; opacity: 0.5;">→</span>
                    </a>
                    
                    <button type="submit" name="salva_config" class="btn">Salva Modifiche</button>
                    <button type="button" onclick="document.getElementById('overlay').style.display='none'" class="btn" style="background:#bbb; margin-top:5px;">Chiudi</button>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="script.js"></script>
</body>
</html>