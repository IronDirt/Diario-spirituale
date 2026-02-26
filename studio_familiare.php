<?php
ob_start(); // Cattura output non desiderato

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

$cartella_db = 'database';

if (!isset($_SESSION['autenticato'])) {
    header("Location: index.php");
    exit;
}

$path = $_SESSION['user_dir'] . '/';
if (!is_dir($path)) {
    mkdir($path, 0777, true);
}

$file_studio = $path . 'studio_familiare.json';
$studi = file_exists($file_studio) ? json_decode(file_get_contents($file_studio), true) : [];
if (!is_array($studi)) $studi = [];

// Gestisci richiesta AJAX per ricaricare solo la lista
if (isset($_GET['get_lista'])) {
    // Ricarica i dati freschi dal JSON
    $studi = file_exists($file_studio) ? json_decode(file_get_contents($file_studio), true) : [];
    if (!is_array($studi)) $studi = [];
}

function pulisci_testo($val) {
    return htmlspecialchars(trim($val), ENT_QUOTES);
}

function normalizza_link($link) {
    $link = trim($link);
    if ($link === '') return '';
    if (!preg_match('#^https?://#i', $link)) {
        $link = 'https://' . $link;
    }
    return $link;
}

function formatta_data($data) {
    if (empty($data)) return '';
    $dt = DateTime::createFromFormat('Y-m-d', $data);
    return $dt ? $dt->format('d/m/Y') : $data;
}

function formatta_data_orario($data, $orario) {
    $giorni = ['Dom', 'Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab'];
    $mesi = ['gen', 'feb', 'mar', 'apr', 'mag', 'giu', 'lug', 'ago', 'set', 'ott', 'nov', 'dic'];
    
    if (empty($data) && empty($orario)) return '';
    
    $risultato = '';
    if (!empty($data)) {
        $dt = DateTime::createFromFormat('Y-m-d', $data);
        if ($dt) {
            $giorno_settimana = $giorni[(int)$dt->format('w')];
            $giorno = (int)$dt->format('d');
            $mese = $mesi[(int)$dt->format('m') - 1];
            $risultato = "$giorno_settimana $giorno $mese";
        }
    }
    
    if (!empty($orario)) {
        if (!empty($risultato)) {
            $risultato .= " - $orario";
        } else {
            $risultato = $orario;
        }
    }
    
    return $risultato;
}

function crea_testo_share($studio) {
    $parti = [];
    $titolo = trim(htmlspecialchars_decode($studio['titolo'] ?? '', ENT_QUOTES));
    if ($titolo !== '') $parti[] = $titolo;

    $descrizione = trim(htmlspecialchars_decode($studio['descrizione'] ?? '', ENT_QUOTES));
    if ($descrizione !== '') $parti[] = $descrizione;

    $link = trim(htmlspecialchars_decode($studio['link'] ?? '', ENT_QUOTES));
    if ($link !== '') $parti[] = $link;

    $data = trim($studio['data'] ?? '');
    $orario = trim($studio['orario'] ?? '');
    if ($data !== '' || $orario !== '') {
        $data_format = $data !== '' ? formatta_data($data) : '';
        if ($data_format !== '' && $orario !== '') {
            $parti[] = $data_format . ' ' . $orario;
        } else {
            $parti[] = $data_format !== '' ? $data_format : $orario;
        }
    }

    return implode("\n\n", $parti);
}

if (isset($_POST['aggiungi_studio'])) {
    $titolo = pulisci_testo($_POST['titolo'] ?? '');
    $descrizione = pulisci_testo($_POST['descrizione'] ?? '');
    $appunti = pulisci_testo($_POST['appunti'] ?? '');
    $link = pulisci_testo(normalizza_link($_POST['link'] ?? ''));
    $data = trim($_POST['data'] ?? '');
    $orario = trim($_POST['orario'] ?? '');
    
    $isAjax = isset($_POST['ajax']) || isset($_GET['ajax']);
    $success = false;

    if ($titolo !== '') {
        $studi[] = [
            'id' => uniqid(),
            'titolo' => $titolo,
            'descrizione' => $descrizione,
            'appunti' => $appunti,
            'link' => $link,
            'data' => $data,
            'orario' => $orario,
            'completata' => false,
            'data_creazione' => time()
        ];
        file_put_contents($file_studio, json_encode(array_values($studi), JSON_PRETTY_PRINT));
        $success = true;
    }
    
    if ($isAjax) {
        ob_clean(); // Pulisce qualsiasi output prima del JSON
        header('Content-Type: application/json');
        echo json_encode(['success' => $success]);
        exit;
    }
    
    header("Location: studio_familiare.php");
    exit;
}

if (isset($_POST['aggiorna_studio'])) {
    $id = $_POST['studio_id'] ?? '';
    foreach ($studi as &$studio) {
        if ($studio['id'] === $id) {
            $titolo_modifica = pulisci_testo($_POST['titolo_modifica'] ?? '');
            if ($titolo_modifica === '') {
                $titolo_modifica = $studio['titolo'] ?? '';
            }
            $studio['titolo'] = $titolo_modifica;
            $studio['descrizione'] = pulisci_testo($_POST['descrizione_modifica'] ?? '');
            $studio['appunti'] = pulisci_testo($_POST['appunti_modifica'] ?? '');
            $studio['link'] = pulisci_testo(normalizza_link($_POST['link_modifica'] ?? ''));
            $studio['data'] = trim($_POST['data_modifica'] ?? '');
            $studio['orario'] = trim($_POST['orario_modifica'] ?? '');
            break;
        }
    }
    unset($studio);
    file_put_contents($file_studio, json_encode(array_values($studi), JSON_PRETTY_PRINT));
    
    if (isset($_POST['ajax']) || isset($_GET['ajax'])) {
        ob_clean(); // Pulisce qualsiasi output prima del JSON
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }
    
    header("Location: studio_familiare.php");
    exit;
}

if (isset($_GET['azione'])) {
    $id_da_gestire = $_GET['id'] ?? '';

    if ($_GET['azione'] === 'toggle') {
        foreach ($studi as &$studio) {
            if ($studio['id'] === $id_da_gestire) {
                $studio['completata'] = empty($studio['completata']);
                if ($studio['completata']) {
                    $studio['data_completamento'] = time();
                } else {
                    unset($studio['data_completamento']);
                }
                break;
            }
        }
        unset($studio);
    } elseif ($_GET['azione'] === 'elimina') {
        $studi = array_filter($studi, function ($studio) use ($id_da_gestire) {
            return $studio['id'] !== $id_da_gestire;
        });
    }

    file_put_contents($file_studio, json_encode(array_values($studi), JSON_PRETTY_PRINT));

    if (isset($_GET['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }

    header("Location: studio_familiare.php");
    exit;
}

usort($studi, function ($a, $b) {
    $a_completed = !empty($a['completata']);
    $b_completed = !empty($b['completata']);

    // Non completate vanno prima delle completate
    if ($a_completed !== $b_completed) {
        return $a_completed <=> $b_completed;
    }

    // Se entrambe completate, ordina per data di completamento (decrescente)
    if ($a_completed && $b_completed) {
        $a_time = $a['data_completamento'] ?? 0;
        $b_time = $b['data_completamento'] ?? 0;
        return $b_time <=> $a_time;
    }

    // Se entrambe non completate
    $a_has_data = !empty($a['data']);
    $b_has_data = !empty($b['data']);

    // Quelle con data vanno prima di quelle senza data
    if ($a_has_data !== $b_has_data) {
        return $b_has_data <=> $a_has_data; // inverte per avere true (con data) prima
    }

    // Entrambe hanno data: ordina per data decrescente (più recenti prima)
    if ($a_has_data && $b_has_data) {
        return strcmp($b['data'], $a['data']);
    }

    // Nessuna ha data: ordina per data_creazione decrescente
    $a_time = $a['data_creazione'] ?? 0;
    $b_time = $b['data_creazione'] ?? 0;
    return $b_time <=> $a_time;
});
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Studio Familiare - Diario Spirituale</title>
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">
    <script src="script.js?v=<?php echo filemtime('script.js'); ?>"></script>
</head>
<body id="studio-familiare-page">
    <div class="box">
        <h2 class="titolo-centrato">
            <img src="img/familiare.png" alt="Studio Familiare">
            Studio Familiare
        </h2>

        <form method="post" class="studio-form">
            <div class="studio-title-row">
                <input type="text" name="titolo" placeholder="Titolo studio familiare..." required>
                <button type="button" class="studio-expand-btn" id="studioFormToggle" title="Mostra dettagli">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </button>
            </div>
            <div class="studio-extra-fields" id="studioExtraFields" style="display:none;">
                <textarea name="descrizione" rows="2" placeholder="Descrizione..."></textarea>
                <textarea name="appunti" rows="2" placeholder="Appunti..."></textarea>
                <input type="text" name="link" placeholder="Link...">
                <div class="studio-form-row">
                    <input type="date" name="data" placeholder="Data">
                    <input type="time" name="orario" placeholder="Orario">
                </div>
            </div>
            <button type="submit" name="aggiungi_studio" class="btn btn-save">Aggiungi</button>
        </form>

        <div class="studio-list">
            <?php if (empty($studi)): ?>
                <p style="color:#888; font-size:0.95em;">Nessuno studio familiare ancora.</p>
            <?php else: ?>
                <?php foreach ($studi as $studio):
                    $payload = [
                        'id' => $studio['id'],
                        'titolo' => htmlspecialchars_decode($studio['titolo'] ?? '', ENT_QUOTES),
                        'descrizione' => htmlspecialchars_decode($studio['descrizione'] ?? '', ENT_QUOTES),
                        'appunti' => htmlspecialchars_decode($studio['appunti'] ?? '', ENT_QUOTES),
                        'link' => htmlspecialchars_decode($studio['link'] ?? '', ENT_QUOTES),
                        'data' => $studio['data'] ?? '',
                        'orario' => $studio['orario'] ?? ''
                    ];
                    $share_text = crea_testo_share($studio);
                ?>
                    <div class="studio-item <?php echo !empty($studio['completata']) ? 'completed' : ''; ?>" id="studio-<?php echo $studio['id']; ?>">
                        <div class="btn-check <?php echo !empty($studio['completata']) ? 'checked' : ''; ?>" onclick="toggleStudioFamiliare('<?php echo $studio['id']; ?>')"></div>
                        <div class="studio-title" title="<?php echo $studio['titolo']; ?>" style="font-weight: normal;">
                            <?php echo $studio['titolo']; ?>
                        </div>
                        <div class="studio-actions">
                            <button type="button" class="studio-icon-btn studio-open-btn" data-studio="<?php echo htmlspecialchars(json_encode($payload), ENT_QUOTES); ?>" title="Apri">
                                <img src="img/note.png" alt="Apri" style="width: 16px; height: 16px;">
                            </button>
                            <?php if (!empty($studio['link'])): ?>
                                <a href="<?php echo $studio['link']; ?>" class="studio-icon-btn studio-link-btn" target="_blank" rel="noopener" title="Apri link">
                                    <img src="img/link.png" alt="Link" style="width: 16px; height: 16px;">
                                </a>
                            <?php endif; ?>
                            <button type="button" class="studio-icon-btn studio-share-btn" data-share-text="<?php echo htmlspecialchars($share_text, ENT_QUOTES) ?>" title="Condividi">
                                <img src="img/share.png" alt="Condividi" style="width: 16px; height: 16px;">
                            </button>
                            <a href="#" class="btn-delete delete-studio-btn" data-id="<?php echo $studio['id']; ?>" title="Elimina">🗑️</a>
                        </div>
                        <?php if (!empty($studio['data']) || !empty($studio['orario'])): ?>
                            <div class="studio-datetime" style="font-size: 0.75em; color: #999; display: flex; align-items: center; gap: 6px;">
                                <img src="img/clock-line-icon.svg" alt="Data/Ora" class="icona-orologio-meta">
                                <?php echo formatta_data_orario($studio['data'] ?? '', $studio['orario'] ?? ''); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
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
                <h3>Impostazioni Studio Familiare</h3>
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

    <div id="studioModal" class="modal-overlay">
        <div class="modal-content studio-modal-content">
            <div class="studio-modal-header">
                <h3>Studio Familiare</h3>
                <button type="button" id="studioEditToggle" class="studio-icon-btn" title="Modifica">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 20h9"></path>
                        <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"></path>
                    </svg>
                </button>
            </div>
            <form id="studioModalForm" method="post">
                <input type="hidden" name="studio_id" id="studioModalId">

                <label for="studioModalTitolo">Titolo</label>
                <input type="text" id="studioModalTitolo" name="titolo_modifica" data-studio-field readonly>

                <label for="studioModalDescrizione">Descrizione</label>
                <textarea id="studioModalDescrizione" name="descrizione_modifica" rows="3" data-studio-field readonly></textarea>

                <label for="studioModalAppunti">Appunti</label>
                <textarea id="studioModalAppunti" name="appunti_modifica" rows="3" data-studio-field readonly></textarea>

                <label for="studioModalLinkInput">Link</label>
                <input type="text" id="studioModalLinkInput" name="link_modifica" data-studio-field readonly>

                <div class="studio-form-row">
                    <div>
                        <label for="studioModalData">Data</label>
                        <input type="date" id="studioModalData" name="data_modifica" data-studio-field readonly>
                    </div>
                    <div>
                        <label for="studioModalOrario">Orario</label>
                        <input type="time" id="studioModalOrario" name="orario_modifica" data-studio-field readonly>
                    </div>
                </div>

                <div class="studio-modal-actions">
                    <a href="#" id="studioModalLink" class="studio-icon-btn studio-link-btn" target="_blank" rel="noopener" style="display:none;">Apri link</a>
                    <button type="submit" name="aggiorna_studio" id="studioSaveBtn" class="btn btn-save" style="display:none;">Salva</button>
                    <button type="button" id="studioModalClose" class="btn btn-close">Chiudi</button>
                </div>
            </form>
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

    <div class="page-copyright">&copy; <?php echo date('Y'); ?> Diario Spirituale</div>
    <script src="script.js"></script>
</body>
</html>
