<?php
// Rimuoviamo ogni possibile spazio bianco prima di <?php
ini_set('session.gc_maxlifetime', 2592000);
ini_set('session.cookie_lifetime', 2592000);
session_start();

$cartella_db = 'database';

if (!isset($_SESSION['autenticato'])) { 
    header("Location: index.php"); 
    exit; 
}

$path = $_SESSION['user_dir'] . '/';
if (!is_dir($path)) {
    mkdir($path, 0777, true);
}
$file_mete = $path . 'mete.json';

// --- GESTIONE AZIONE AJAX ---
if (isset($_GET['azione']) && $_GET['azione'] === 'toggle_sub' && isset($_GET['ajax'])) {
    // Pulisce l'output per evitare caratteri spuri
    ob_clean(); 
    
    $sub_id = $_GET['id'];
    $nuovo_stato = $_GET['stato']; 

    // Carichiamo i dati freschi
    $dati_attuali = file_exists($file_mete) ? json_decode(file_get_contents($file_mete), true) : [];

    $trovato = false;
    foreach ($dati_attuali as &$m) {
        if (isset($m['sotto_mete']) && is_array($m['sotto_mete'])) {
            foreach ($m['sotto_mete'] as &$sm) {
                if ($sm['id'] == $sub_id) {
                    // Convertiamo in booleano vero (true/false)
                    $sm['completata'] = ($nuovo_stato === "1" || $nuovo_stato === 1);
                    
                    if ($sm['completata']) {
                        $sm['data_completamento'] = time();
                    } else {
                        unset($sm['data_completamento']);
                    }
                    $trovato = true;
                    break 2;
                }
            }
        }
    }

    if ($trovato) {
        // Salvataggio
        file_put_contents($file_mete, json_encode(array_values($dati_attuali), JSON_PRETTY_PRINT));
        echo "OK"; // Risposta semplice per il JS
    } else {
        echo "ERRORE";
    }
    exit; // BLOCCA il resto del file, fondamentale!
}

// Caricamento per la visualizzazione pagina
$mete = file_exists($file_mete) ? json_decode(file_get_contents($file_mete), true) : [];


// --- LOGICA AGGIUNTA META PRINCIPALE ---
if (isset($_POST['aggiungi_meta'])) {
    $mete[] = [
        'id' => uniqid(), 
        'testo' => htmlspecialchars($_POST['testo_meta']), 
        'categoria' => $_POST['categoria'], 
        'completata' => false,
        'data_creazione' => time()
    ];
    file_put_contents($file_mete, json_encode(array_values($mete)));
    header("Location: mete.php"); 
    exit;
}

// --- LOGICA AGGIUNTA SOTTO-META (AJAX compatibile) ---
if (isset($_POST['aggiungi_sotto_meta'])) {
    $parent_id = $_POST['parent_id'];
    $nuova_id = uniqid();
    $nuova_sub = [
        'id' => $nuova_id,
        'testo' => htmlspecialchars($_POST['testo_sotto_meta']),
        'completata' => false,
        'data_creazione' => time()
    ];

    foreach ($mete as &$m) {
        if ($m['id'] === $parent_id) {
            if (!isset($m['sotto_mete'])) $m['sotto_mete'] = [];
            array_unshift($m['sotto_mete'], $nuova_sub);
        }
    }
    
    file_put_contents($file_mete, json_encode(array_values($mete)));

    if (isset($_POST['ajax'])) {
        echo $nuova_id;
        exit;
    }

    header("Location: mete.php?open=" . $parent_id . "#meta-" . $parent_id);
    exit;
}

// --- LOGICA AZIONI (TOGGLE / ELIMINA) ---
if (isset($_GET['azione'])) {
    $open_id = $_GET['open'] ?? ''; 
    $id_da_gestire = $_GET['id'] ?? '';

    if ($_GET['azione'] === 'toggle') {
        foreach ($mete as &$m) { 
            if ($m['id'] === $id_da_gestire) {
                $m['completata'] = !$m['completata'];
                $m['data_completamento'] = time();
            }
            if (isset($m['sotto_mete'])) {
                foreach ($m['sotto_mete'] as &$sm) {
                    if ($sm['id'] === $id_da_gestire) {
                        $sm['completata'] = !$sm['completata'];
                        $sm['data_completamento'] = time();
                    }
                }
            }
        }
    } 
    // AZIONE PER ELIMINARE LA META PRINCIPALE
    elseif ($_GET['azione'] === 'elimina') {
        $mete = array_filter($mete, function($m) use ($id_da_gestire) { 
            return $m['id'] !== $id_da_gestire; 
        });
    } 
    // AZIONE PER ELIMINARE SOLO UNA SOTTOMETA
    elseif ($_GET['azione'] === 'elimina_sottometa') {
        foreach ($mete as &$m) {
            if (isset($m['sotto_mete'])) {
                $m['sotto_mete'] = array_filter($m['sotto_mete'], function($sm) use ($id_da_gestire) { 
                    return $sm['id'] !== $id_da_gestire; 
                });
                $m['sotto_mete'] = array_values($m['sotto_mete']);
            }
        }
    }
    
    // Salva i dati (array_values resetta gli indici dopo array_filter)
    file_put_contents($file_mete, json_encode(array_values($mete)));

    // Se è una chiamata AJAX (dal cestino), interrompiamo qui l'esecuzione
    if (isset($_GET['ajax']) || isset($_GET['ajax_sub'])) {
        exit; 
    }

    // Altrimenti fai il redirect classico
    if ($open_id) {
        header("Location: mete.php?open=" . $open_id . "#meta-" . $open_id);
    } else {
        header("Location: mete.php");
    }
    exit;
}

// --- ORDINAMENTO FINALE PER VISUALIZZAZIONE ---
$ordine_cat = ['Breve termine' => 1, 'Medio termine' => 2, 'Lungo termine' => 3];

usort($mete, function($a, $b) use ($ordine_cat) {
    // 1. Ordina per stato di completamento (false viene prima di true)
    if ($a['completata'] !== $b['completata']) {
        return $a['completata'] <=> $b['completata'];
    }
    
    // 2. Se lo stato è uguale, ordina per categoria (Breve -> Medio -> Lungo)
    $cat_a = $ordine_cat[$a['categoria']] ?? 99;
    $cat_b = $ordine_cat[$b['categoria']] ?? 99;
    
    return $cat_a <=> $cat_b;
});

// Ordinamento delle sotto-mete (rimane invariato per coerenza)
foreach ($mete as &$m) {
    if (isset($m['sotto_mete']) && is_array($m['sotto_mete'])) {
        usort($m['sotto_mete'], function($a, $b) {
            return $a['completata'] <=> $b['completata'];
        });
    }
}
unset($m);

// --- CALCOLO PERCENTUALE PESATO (1/3 Meta, 2/3 Sotto-mete) ---
$total_main_goals = count($mete);
$total_progress = 0;

if ($total_main_goals > 0) {
    foreach ($mete as $m) {
        $goal_val = 0;
        
        // 1. Valore della Meta Principale (pesa 1/3)
        if ($m['completata']) {
            $goal_val += (1 / 3);
        }

        // 2. Valore delle Sotto-mete (pesano 2/3 in totale)
        if (isset($m['sotto_mete']) && !empty($m['sotto_mete'])) {
            $sub_count = count($m['sotto_mete']);
            $sub_completed = 0;
            foreach ($m['sotto_mete'] as $sm) {
                if ($sm['completata']) $sub_completed++;
            }
            // Aggiunge la frazione dei 2/3 in base alle sotto-mete fatte
            $goal_val += ($sub_completed / $sub_count) * (2 / 3);
        } elseif (!$m['completata']) {
            // Se non ci sono sotto-mete, la meta principale dovrebbe valere 1 intero? 
            // Seguendo la tua logica: se non ci sono sotto-mete, il "2/3" non esiste, 
            // quindi la meta principale torna a valere l'intero (1) per non bloccare la barra.
            $goal_val = ($m['completata']) ? 1 : 0;
        }

        $total_progress += $goal_val;
    }
    $perc = round(($total_progress / $total_main_goals) * 100);
} else {
    $perc = 0;
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mete</title>
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">
    <script src="script.js?v=<?php echo filemtime('script.js'); ?>"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-bootstrap-4/bootstrap-4.css">
</head>
<body id="mete-page">
<div class="container">
    <h2 class="titolo-centrato">
        <img src="img/bullseye-svgrepo-com.svg" alt="Mete">
        Mete
    </h2>

    <div class="progress-container" style="height: 12px; background: #eee; border-radius: 10px; overflow: hidden; margin: 15px 0;">
        <div id="main-progress-bar" class="progress-bar" style="width:<?php echo $perc; ?>%; height: 100%; background: #2ecc71; transition: width 0.5s;"></div>
    </div>
    <p style="font-size: 0.8em; color: #666; margin-top: 5px;">
    Completate: <span id="progress-percentage"><?php echo $perc; ?>%</span>
    </p>

    <form method="post" style="margin-bottom: 20px;">
        <input type="text" name="testo_meta" placeholder="Nuova meta..." required>
        <select name="categoria">
            <option>Breve termine</option>
            <option>Medio termine</option>
            <option>Lungo termine</option>
        </select>
        <button type="submit" name="aggiungi_meta" class="btn">Aggiungi</button>
    </form>

    <?php foreach ($mete as $m): 
        $classe_colore = 'badge-breve';
        if ($m['categoria'] === 'Medio termine') $classe_colore = 'badge-medio';
        if ($m['categoria'] === 'Lungo termine') $classe_colore = 'badge-lungo';
    ?>
        <div id="meta-<?php echo $m['id']; ?>" class="meta-wrapper">
        <div class="meta-card" style="display: flex; align-items: center; padding: 15px; gap: 15px;">
            <div class="btn-check <?php echo ($m['completata'] ?? false) ? 'checked' : ''; ?>" 
                 onclick="toggleMainAjax('<?php echo $m['id']; ?>', this)">
            </div>

            <div style="flex: 1; text-align: left;">
                <div style="margin-bottom: 4px; font-size: 1.05em; <?php echo $m['completata'] ? 'text-decoration:line-through;color:#888;' : ''; ?>">
                    <?php echo $m['testo']; ?>
                </div>
                
                <span class="badge-meta-elegante">
                    <img src="img/clock-line-icon.svg" alt="Orologio" class="icona-orologio-meta">
                    <?php echo $m['categoria']; ?>
                </span>
            </div>

            <div class="actions" style="display: flex; gap: 12px; align-items: center;">
                <span id="arrow-<?php echo $m['id']; ?>" class="btn-expand" onclick="toggleSubMete('<?php echo $m['id']; ?>')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </span>
                <a href="#" class="btn-delete delete-link" data-id="<?php echo $m['id']; ?>">🗑️</a>
            </div>
        </div>

            <div id="sub-<?php echo $m['id']; ?>" class="sub-container" style="display: <?php echo (isset($_GET['open']) && $_GET['open'] == $m['id']) ? 'block' : 'none'; ?>; background: #fafafa; border-top: 1px solid #eee;">
                
                <form onsubmit="aggiungiSubAjax(event, '<?php echo $m['id']; ?>')" class="sub-meta-form" method="post" style="display: flex; gap: 5px;">
                    <input type="hidden" name="parent_id" value="<?php echo $m['id']; ?>">
                    <input type="text" name="testo_sotto_meta" placeholder="Aggiugni sotto-meta..." required style="flex:1; font-size: 0.85em;">
                    <button type="submit" name="aggiungi_sotto_meta" class="btn-plus">+</button>
                </form>

                <div class="sub-list">
                <?php if (isset($m['sotto_mete'])): foreach ($m['sotto_mete'] as $sm): ?>
                    <div class="sub-meta-item <?php echo $sm['completata'] ? 'completed' : ''; ?>" id="row-<?php echo $sm['id']; ?>">
                        <div class="btn-check-small <?php echo $sm['completata'] ? 'checked' : ''; ?>" 
                             onclick="toggleSubAjax('<?php echo $sm['id']; ?>', this)">
                            <?php echo $sm['completata'] ? '✓' : ''; ?>
                        </div>
                        <span class="sub-meta-text" style="<?php echo $sm['completata'] ? 'text-decoration:line-through;color:#888;' : ''; ?>">
                            <?php echo $sm['testo']; ?>
                        </span>
                        <div class="btn-delete-small" onclick="confermaEliminazione('<?php echo $sm['id']; ?>', this)">🗑️</div>
                    </div>
                <?php endforeach; endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

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
    <div class="settings-card" style="background:white; padding:20px; border-radius:10px; text-align:center;">
        <h3>Impostazioni Mete</h3>
        <p>Configurazioni future disponibili qui.</p>
        <button type="button" onclick="document.getElementById('overlay').style.display='none'" class="btn" style="background:#bbb;">Chiudi</button>
    </div>
</div>

<div id="customConfirm" class="modal-overlay">
    <div class="modal-content">
        <h3 id="modalTitle">Conferma</h3>
        <p id="modalText">Vuoi procedere con questa operazione?</p>
        <div class="modal-buttons">
            <button id="confirmCancel" class="btn-modal-secondary">Annulla</button>
            <button id="confirmOk" class="btn-modal-primary">Procedi</button>
        </div>
    </div>
</div>

<script src="script.js"></script>
</body>
</html>