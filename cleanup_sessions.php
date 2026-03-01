<?php
/**
 * Script di pulizia delle sessioni scadute e vuote
 * Elimina file di sessione più vecchi di 3 giorni (gc_maxlifetime)
 */

$session_path = __DIR__ . '/sessions';
$gc_maxlifetime = 259200; // 3 giorni (come in index.php)
$now = time();
$deleted_count = 0;
$empty_count = 0;

if (!is_dir($session_path)) {
    echo "❌ Cartella sessioni non trovata: $session_path\n";
    exit(1);
}

$files = scandir($session_path);

foreach ($files as $file) {
    // Salta le cartelle speciali
    if ($file === '.' || $file === '..' || is_dir("$session_path/$file")) {
        continue;
    }

    $file_path = "$session_path/$file";
    $file_age = $now - filemtime($file_path);
    $file_size = filesize($file_path);

    // Elimina file scaduti (più vecchi di gc_maxlifetime)
    if ($file_age > $gc_maxlifetime) {
        if (unlink($file_path)) {
            $deleted_count++;
            echo "🗑️  Eliminato (scaduto): $file (età: " . round($file_age / 86400, 1) . " giorni)\n";
        }
    }
    // Elimina file vuoti (0 KB)
    elseif ($file_size === 0) {
        if (unlink($file_path)) {
            $empty_count++;
            echo "🗑️  Eliminato (vuoto): $file\n";
        }
    }
}

echo "\n✅ Pulizia completata:\n";
echo "   - File scaduti eliminati: $deleted_count\n";
echo "   - File vuoti eliminati: $empty_count\n";
echo "   - Totale eliminati: " . ($deleted_count + $empty_count) . "\n";
?>
