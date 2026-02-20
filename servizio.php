<?php
ini_set('session.gc_maxlifetime', 2592000);
ini_set('session.cookie_lifetime', 2592000);
session_start();

// --- LOGICA INVIO EMAIL AGGIORNATA ---
if (isset($_GET['azione']) && $_GET['azione'] === 'invia_rapporto') {
    $email_utente = $_SESSION['utente'] ?? ''; 
    $nome_utente = $_SESSION['nome_completo'] ?? 'Utente';
    $periodo = $_GET['periodo'] ?? '1'; 
    
    $file_dati = $_SESSION['user_dir'] . '/ore_servizio.json';
    $dati_email = file_exists($file_dati) ? json_decode(file_get_contents($file_dati), true) : [];
    
    usort($dati_email, function($a, $b) {
        return strtotime(str_replace('/', '-', $b['data'])) - strtotime(str_replace('/', '-', $a['data']));
    });

    $testo_periodo = ($periodo === 'prec') ? "Mese precedente" : "Ultimi $periodo mesi";
    $corpo = "Rapporto di servizio per $nome_utente\n";
    $corpo .= "Periodo: $testo_periodo\n";
    $corpo .= "----------------------------\n";

    $tot_min_email = 0;
    $minuti_mese_corrente = 0;
    $mesi_distinti = [];
    $corpo_temp = ""; // Usato per accumulare i dati del mese

    foreach ($dati_email as $index => $item) {
        $mese_anno = $item['mese'] . '-' . $item['anno'];
        
        // Se cambiamo mese e non è il primo giro, scriviamo il totale del mese precedente
        if (!empty($mesi_distinti) && $mese_anno !== end($mesi_distinti)) {
            $h_m = floor($minuti_mese_corrente/60);
            $m_m = $minuti_mese_corrente%60;
            $corpo .= "\nTOTALE MESE: {$h_m}h {$m_m}m\n";
            $corpo .= "----------------------------\n";
            $minuti_mese_corrente = 0; // Resetta per il nuovo mese
        }

        // Controllo filtri (Mese precedente o Numero mesi)
        if (!in_array($mese_anno, $mesi_distinti)) {
            if ($periodo === 'prec') {
                $mese_scorso = date("m-Y", strtotime("-1 month"));
                if ($mese_anno !== $mese_scorso) continue;
            } else {
                if (count($mesi_distinti) >= (int)$periodo) break;
            }
            $mesi_distinti[] = $mese_anno;
            $corpo .= "\n--- " . data_it($item['mese']) . " " . $item['anno'] . " ---\n";
        }

        $corpo .= "- Data: {$item['data']} | Tempo: {$item['ore']}h {$item['minuti']}m\n";
        
        $min_riga = ($item['ore'] * 60) + $item['minuti'];
        $minuti_mese_corrente += $min_riga;
        $tot_min_email += $min_riga;
    }

    // Scrive il totale dell'ultimo mese elaborato
    if ($minuti_mese_corrente > 0) {
        $h_m = floor($minuti_mese_corrente/60);
        $m_m = $minuti_mese_corrente%60;
        $corpo .= "\nTOTALE MESE: {$h_m}h {$m_m}m\n";
    }

    $corpo .= "\n============================\n";
    $corpo .= "TOTALE COMPLESSIVO: " . floor($tot_min_email/60) . "h " . ($tot_min_email%60) . "m\n";
    $corpo .= "============================\n";

    $headers = "From: Diario Spirituale <no-reply@tuosito.it>\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    if (mail($email_utente, "Rapporto di Servizio - $nome_utente", $corpo, $headers)) {
        header("Location: servizio.php?invio=ok");
    } else {
        header("Location: servizio.php?invio=errore");
    }
    exit;
}

// 1. Definiamo la cartella base
$cartella_db = 'database';

// 2. Controllo sicurezza: se non sei loggato, torni al login
if (!isset($_SESSION['autenticato'])) { 
    header("Location: index.php"); 
    exit; 
}

// 3. Impostiamo il percorso della cartella dell'utente
$path = $_SESSION['user_dir'] . '/';

// 4. Creiamo la cartella se non esiste (per sicurezza)
if (!is_dir($path)) {
    mkdir($path, 0777, true);
}

$file_dati = $path . 'ore_servizio.json';
$dati = file_exists($file_dati) ? json_decode(file_get_contents($file_dati), true) : [];

$mese_sel = $_GET['m'] ?? date("m");
$anno_sel = $_GET['a'] ?? date("Y");

// --- GESTIONE IMPOSTAZIONI OBIETTIVI ---
$file_settings = $path . 'impostazioni.json';
$settings = file_exists($file_settings) ? json_decode(file_get_contents($file_settings), true) : [
    'obj_mese_attivo' => false,
    'obj_mese_ore' => 0,
    'obj_anno_attivo' => false,
    'obj_anno_ore' => 0
];

if (isset($_POST['salva_settings'])) {
    $settings['obj_mese_attivo'] = isset($_POST['obj_mese_attivo']);
    $settings['obj_mese_ore'] = (int)$_POST['obj_mese_ore'];
    $settings['obj_anno_attivo'] = isset($_POST['obj_anno_attivo']);
    $settings['obj_anno_ore'] = (int)$_POST['obj_anno_ore'];
    file_put_contents($file_settings, json_encode($settings));
    header("Location: servizio.php?m=$mese_sel&a=$anno_sel");
    exit;
}

// --- GESTIONE SALVATAGGIO TIMER (AGGIORNATA AJAX) ---
if (isset($_GET['azione']) && $_GET['azione'] === 'salva_timer') {
    $ore = (int)($_GET['ore'] ?? 0);
    $minuti = (int)($_GET['minuti'] ?? 0);
    $mese_sel = $_GET['m'] ?? date("m");
    $anno_sel = $_GET['a'] ?? date("Y");
    $oggi_m = date("m");
    $oggi_a = date("Y");

    $data_record = ($mese_sel == $oggi_m && $anno_sel == $oggi_a) ? date("d/m/Y") : date("t", strtotime("$anno_sel-$mese_sel-01"))."/$mese_sel/$anno_sel";

    if ($ore > 0 || $minuti > 0) {
        $nuovo_record = [
            'id' => time(), 
            'data' => $data_record, 
            'mese' => $mese_sel, 
            'anno' => $anno_sel, 
            'ore' => $ore, 
            'minuti' => $minuti, 
            'note' => "Timer"
        ];
        $dati[] = $nuovo_record;
        file_put_contents($file_dati, json_encode(array_values($dati), JSON_PRETTY_PRINT));

        if (isset($_GET['ajax'])) {
            echo json_encode(['status' => 'success', 'record' => $nuovo_record, 'totali' => calcolaTotali($dati, $mese_sel, $anno_sel)]);
            exit;
        }
    }
    header("Location: servizio.php?m=$mese_sel&a=$anno_sel");
    exit;
}


function data_it($data) {
    $mesi = ['01'=>'Gennaio','02'=>'Febbraio','03'=>'Marzo','04'=>'Aprile','05'=>'Maggio','06'=>'Giugno','07'=>'Luglio','08'=>'Agosto','09'=>'Settembre','10'=>'Ottobre','11'=>'Novembre','12'=>'Dicembre'];
    return $mesi[$data];
}

// Ordine mesi: dal meno recente al più recente (corrente a destra)
$menu_mesi = [];
for ($i = 2; $i >= 0; $i--) {
    $ts = strtotime("-$i month");
    $menu_mesi[] = [
        'm' => date("m", $ts), 
        'a' => date("Y", $ts), 
        'n' => data_it(date("m", $ts))
    ];
}

// --- GESTIONE SALVATAGGIO MANUALE (AGGIORNATA AJAX) ---
if (isset($_POST['salva'])) {
    $ore = (int)$_POST['ore'];
    $minuti = (int)$_POST['minuti'];
    $note = htmlspecialchars($_POST['note'] ?? '');
    
    $data_record = ($mese_sel == date("m") && $anno_sel == date("Y")) ? date("d/m/Y") : date("t", strtotime("$anno_sel-$mese_sel-01"))."/$mese_sel/$anno_sel";
    
    $nuovo_record = ['id' => time(), 'data' => $data_record, 'mese' => $mese_sel, 'anno' => $anno_sel, 'ore' => $ore, 'minuti' => $minuti, 'note' => $note];
    $dati[] = $nuovo_record;
    file_put_contents($file_dati, json_encode(array_values($dati), JSON_PRETTY_PRINT));

    if (isset($_POST['ajax'])) {
        // Aggiungi questa riga per formattare la data come piace a te
        // Usiamo una funzione per trasformare 19/02/2026 in "Gio 19 feb"
        $data_formattata = formattaDataPerTabella($data_record); 

        echo json_encode([
            'status' => 'success', 
            'record' => array_merge($nuovo_record, ['data_display' => $data_formattata]), 
            'totali' => calcolaTotali($dati, $mese_sel, $anno_sel)
        ]);
        exit;
    }
    header("Location: servizio.php?m=$mese_sel&a=$anno_sel");
    exit;
}

// Gestione eliminazione record (Versione AJAX)
if (isset($_GET['azione']) && $_GET['azione'] === 'elimina' && isset($_GET['id'])) {
    $id_da_eliminare = $_GET['id'];
    $dati = array_filter($dati, function($item) use ($id_da_eliminare) { 
        return $item['id'] != $id_da_eliminare; 
    });
    file_put_contents($file_dati, json_encode(array_values($dati), JSON_PRETTY_PRINT));

    // Se la richiesta è AJAX, restituiamo i nuovi totali in formato JSON
    if (isset($_GET['ajax'])) {
        $nuovo_tot_min = 0;
        foreach ($dati as $item) {
            if ($item['mese'] == $mese_sel && $item['anno'] == $anno_sel) {
                $nuovo_tot_min += ($item['ore'] * 60) + $item['minuti'];
            }
        }
        
        // Calcolo anno di servizio (Settembre - Agosto)
        $tot_min_anno = 0;
        $inizio_anno_servizio = ($mese_sel >= 9) ? $anno_sel : $anno_sel - 1;
        foreach ($dati as $item) {
            $m = (int)$item['mese'];
            $a = (int)$item['anno'];
            if (($a == $inizio_anno_servizio && $m >= 9) || ($a == $inizio_anno_servizio + 1 && $m <= 8)) {
                $tot_min_anno += ($item['ore'] * 60) + $item['minuti'];
            }
        }

        echo json_encode([
            'status' => 'success',
            'ore_mese' => floor($nuovo_tot_min / 60),
            'min_mese' => ($nuovo_tot_min % 60),
            'tot_ore_mese' => floor($nuovo_tot_min / 60),
            'tot_ore_anno' => floor($tot_min_anno / 60)
        ]);
        exit;
    }

    header("Location: servizio.php?m=$mese_sel&a=$anno_sel");
    exit;
}

$filtrati = array_filter($dati, function($item) use ($mese_sel, $anno_sel) {
    return $item['mese'] == $mese_sel && $item['anno'] == $anno_sel;
});

$tot_min = 0;
foreach ($filtrati as $item) { $tot_min += ($item['ore'] * 60) + $item['minuti']; }

// Calcolo totale annuale (Settembre - Agosto)
$tot_min_anno = 0;
$anno_servizio_inizio = ($mese_sel >= 9) ? $anno_sel : $anno_sel - 1;

foreach ($dati as $item) {
    $m = (int)$item['mese'];
    $a = (int)$item['anno'];
    // Verifica se il record appartiene all'intervallo Settembre(anno) -> Agosto(anno+1)
    if (($a == $anno_servizio_inizio && $m >= 9) || ($a == $anno_servizio_inizio + 1 && $m <= 8)) {
        $tot_min_anno += ($item['ore'] * 60) + $item['minuti'];
    }
}

$ore_tot_mese = floor($tot_min / 60);
$ore_tot_anno = floor($tot_min_anno / 60);

function calcolaTotali($dati, $m_sel, $a_sel) {
    $min_m = 0; $min_a = 0;
    $inizio_a = ($m_sel >= 9) ? $a_sel : $a_sel - 1;
    foreach ($dati as $i) {
        $m = (int)$i['mese']; $a = (int)$i['anno'];
        if ($m == $m_sel && $a == $a_sel) $min_m += ($i['ore'] * 60) + $i['minuti'];
        if (($a == $inizio_a && $m >= 9) || ($a == $inizio_a + 1 && $m <= 8)) $min_a += ($i['ore'] * 60) + $i['minuti'];
    }
    return ['h_m' => floor($min_m/60), 'm_m' => $min_m%60, 'h_a' => floor($min_a/60)];
}

function formattaDataPerTabella($data_ita) {
    // Converte da GG/MM/AAAA a timestamp
    $timestamp = strtotime(str_replace('/', '-', $data_ita));
    $giorni = ['Dom', 'Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab'];
    $mesi = ['', 'gen', 'feb', 'mar', 'apr', 'mag', 'giu', 'lug', 'ago', 'set', 'ott', 'nov', 'dic'];
    
    $giorno_sett = $giorni[date('w', $timestamp)];
    $giorno_num = date('j', $timestamp);
    $mese_nome = $mesi[(int)date('n', $timestamp)];
    
    return "$giorno_sett $giorno_num $mese_nome";
}

?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Servizio di Campo</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">
    <script src="script.js?v=<?php echo filemtime('script.js'); ?>"></script>
</head>
<body id="servizio-page">
    <div class="box">
        <h2 class="titolo-centrato">
            <img src="img/clock-svgrepo-com.svg" alt="Mete">
            Servizio
        </h2>
        <div class="nav-mesi">
            <?php foreach ($menu_mesi as $m): ?>
                <a href="?m=<?php echo $m['m']; ?>&a=<?php echo $m['a']; ?>" 
                   class="btn-mese <?php echo ($mese_sel == $m['m']) ? 'attivo' : ''; ?>">
                    <?php echo substr($m['n'], 0, 3); ?>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="totale-box" style="padding: 20px;">
            <p style="margin:0; font-size:0.8em; text-transform: uppercase; letter-spacing: 1px; opacity: 0.9;">Totale Ore</p>
            <h1 style="margin:5px 0 0 0; font-size: 2.5em;"><?php echo floor($tot_min/60)."h ".($tot_min%60)."m"; ?></h1>
        </div>


        <?php if ($settings['obj_mese_attivo'] || $settings['obj_anno_attivo']): ?>
        <div class="obiettivi-container">
            <?php if ($settings['obj_mese_attivo'] && $settings['obj_mese_ore'] > 0): 
                $perc_mese = min(100, round(($ore_tot_mese / $settings['obj_mese_ore']) * 100)); ?>
                <div class="obiettivo-label">
                    <span>Mese</span>
                    <span><?php echo $ore_tot_mese; ?> / <?php echo $settings['obj_mese_ore']; ?>h</span>
                </div>
                <div class="progress-container">
                    <div class="progress-bar progress-bar-mensile" style="width: <?php echo $perc_mese; ?>%;"></div>
                </div>
            <?php endif; ?>

            <?php if ($settings['obj_anno_attivo'] && $settings['obj_anno_ore'] > 0): 
                $perc_anno = min(100, round(($ore_tot_anno / $settings['obj_anno_ore']) * 100)); ?>
                <div class="obiettivo-label">
                    <span>Anno (Set-Ago)</span>
                    <span><?php echo $ore_tot_anno; ?> / <?php echo $settings['obj_anno_ore']; ?>h</span>
                </div>
                <div class="progress-container">
                    <div class="progress-bar progress-bar-anno" style="width: <?php echo $perc_anno; ?>%;"></div>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php 
        // Mostra il timer solo se il mese e l'anno selezionati corrispondono a quelli attuali
        $oggi_m = date("m");
        $oggi_a = date("Y");
        if ($mese_sel == $oggi_m && $anno_sel == $oggi_a): 
        ?>
        <div id="timer-container">
            <div id="display">--:--:--</div>
            <div class="timer-controls">
                <button id="startBtn" onclick="startTimer()" class="btn">
                    <svg class="icon" viewBox="0 0 1792 1792"><path d="M1576 927L248 1665q-23 13-39.5 3t-16.5-36V160q0-26 16.5-36t39.5 3l1328 738q23 13 23 31t-23 31z"/></svg>
                </button>
                
                <button id="pauseBtn" onclick="pauseTimer()" class="btn" style="display: none;">
                    <svg class="icon" viewBox="0 0 530 1000"><path d="M440 150c60 0 90 21.333 90 64c0 0 0 570 0 570c0 44 -30 66 -90 66c-60 0 -90 -22 -90 -66c0 0 0 -570 0 -570c0 -42.667 30 -64 90 -64c0 0 0 0 0 0m-350 0c60 0 90 21.333 90 64c0 0 0 570 0 570c0 44 -30 66 -90 66c-60 0 -90 -22 -90 -66c0 0 0 -570 0 -570c0 -42.667 30 -64 90 -64c0 0 0 0 0 0" /></svg>
                </button>
                
                <button id="stopBtn" onclick="stopTimer()" class="btn" style="display: none;">
                    <svg class="icon" viewBox="0 0 1792 1792"><path d="M1664 192v1408q0 26-19 45t-45 19H192q-26 0-45-19t-19-45V192q0-26 19-45t45-19h1408q26 0 45 19t19 45z"/></svg>
                </button>
            </div>
        </div>
        <?php endif; ?>    
        <form method="post" onsubmit="return validaTempo()">
            <div class="input-group">
                <input type="number" name="ore" id="ore" placeholder="Ore" min="0">
                <input type="number" name="minuti" id="minuti" placeholder="Min" min="0" max="59">
            </div>
            <button type="submit" name="salva" class="btn" style="background:#2ecc71;">SALVA</button>
        </form>

        <table>
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Tempo</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($filtrati)): ?>
                    <tr><td colspan="3" class="vuoto">Nessun dato.</td></tr>
                <?php endif; ?>
                <?php foreach (array_reverse($filtrati) as $item): ?>
                <tr>
                    <td>
                        <?php 
                            $giorni = ['Dom', 'Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab'];
                            // Mesi con l'iniziale minuscola
                            $mesi_it = ['01'=>'gen','02'=>'feb','03'=>'mar','04'=>'apr','05'=>'mag','06'=>'giu','07'=>'lug','08'=>'ago','09'=>'set','10'=>'ott','11'=>'nov','12'=>'dic'];
                            
                            $parti = explode('/', $item['data']);
                            $data_iso = $parti[2] . '-' . $parti[1] . '-' . $parti[0];
                            $timestamp = strtotime($data_iso);

                            $g_sett = $giorni[date('w', $timestamp)];
                            $g_num = date('d', $timestamp);
                            $m_nome = $mesi_it[date('m', $timestamp)];
                            
                            echo "$g_sett $g_num $m_nome";
                        ?>
                    </td>
                    <td><strong><?php echo $item['ore']."h ".$item['minuti']."m"; ?></strong></td>
                    <td>
                        <a href="javascript:void(0);" onclick="confermaEliminaServizio('<?php echo $item['id']; ?>', '<?php echo $mese_sel; ?>', '<?php echo $anno_sel; ?>')" class="del-btn" style="text-decoration:none;">🗑️</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
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

        <div id="overlay" class="overlay" style="<?php echo (isset($_GET['invio']) || (isset($_GET['azione']) && $_GET['azione'] === 'invia_rapporto')) ? 'display:flex;' : ''; ?>">

            <div class="settings-card">
                <h3>Impostazioni Servizio</h3>
                <form method="post">
                    <div class="setting-group-block">
                        <p class="setting-title">Obiettivo Mensile</p>
                        <div class="setting-inline-row">
                            <input type="checkbox" name="obj_mese_attivo" <?php echo $settings['obj_mese_attivo'] ? 'checked' : ''; ?>>
                            <input type="number" name="obj_mese_ore" value="<?php echo $settings['obj_mese_ore']; ?>">
                            <span>ore</span>
                        </div>
                    </div>

                    <div class="setting-group-block">
                        <p class="setting-title">Obiettivo Annuale</p>
                        <div class="setting-inline-row">
                            <input type="checkbox" name="obj_anno_attivo" <?php echo $settings['obj_anno_attivo'] ? 'checked' : ''; ?>>
                            <input type="number" name="obj_anno_ore" value="<?php echo $settings['obj_anno_ore']; ?>">
                            <span>ore</span>
                        </div>
                    </div>

                    <hr style="border: 0; border-top: 1px solid #eee; margin: 20px 0;">

                    <div class="setting-group-block">
                        <p class="setting-title">Inviati Rapporto</p>
                        <div class="setting-inline-row" style="gap: 8px;">
                            <select id="periodo_email" style="padding: 8px; border-radius: 8px; flex: 1; margin:0;">
                                <option value="1">Mese corrente</option>
                                <option value="prec">Mese precedente</option>
                                <option value="3">Ultimi 3 mesi</option>
                                <option value="6">Ultimi 6 mesi</option>
                                <option value="12">Ultimo anno</option>
                            </select>
                            <a href="#" id="btnInviaRapporto" onclick="inviaRapporto(event)" class="btn-email-link-small">
                                <span id="btn-icon">✉️</span> 
                                <span id="btn-text">Invia</span>
                            </a>
                        </div>
                        
                        <?php if(isset($_GET['invio'])): ?>
                            <div class="<?php echo $_GET['invio'] == 'ok' ? 'status-msg-ok' : 'status-msg-error'; ?>">
                                <?php if($_GET['invio'] == 'ok'): ?>
                                    <span>✅</span> Rapporto inviato con successo!
                                <?php else: ?>
                                    <span>❌</span> Errore durante l'invio.
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="settings-footer">
                        <button type="submit" name="salva_settings" class="btn btn-save">SALVA</button>
                        <button type="button" onclick="window.location.href='servizio.php?m=<?php echo $mese_sel; ?>&a=<?php echo $anno_sel; ?>'" class="btn btn-close">CHIUDI</button>
                    </div>
                </form>
            </div>
        </div>

    <script src="script.js"></script>
    <div id="customConfirm" class="modal-overlay" style="display:none;">
        <div class="modal-content">
            <h3 id="modalTitle">Conferma</h3>
            <p id="modalText">Sei sicuro di voler procedere?</p>
            <div class="modal-buttons">
                <button id="confirmOk" class="btn-modal-primary">Sì</button>
                <button id="confirmCancel" class="btn-modal-secondary">No</button>
            </div>
        </div>
    </div>
</body>
</html>