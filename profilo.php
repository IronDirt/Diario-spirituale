<?php
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

// 4. Creiamo la cartella se non esiste (per sicurezza)
if (!is_dir($path)) {
    mkdir($path, 0777, true);
}

$cartella_db = 'database';
$file_utenti = $cartella_db . '/utenti.json';
$utenti = json_decode(file_get_contents($file_utenti), true);
$email_vecchia = $_SESSION['utente'];
$messaggio = "";

if (isset($_POST['aggiorna'])) {
    $nuovo_nome = htmlspecialchars($_POST['nome']);
    $nuovo_cognome = htmlspecialchars($_POST['cognome']);
    $email_nuova = strtolower(trim($_POST['email']));
    $pass1 = $_POST['password'];
    $pass2 = $_POST['conferma_password'];

    if ($email_nuova !== $email_vecchia && isset($utenti[$email_nuova])) {
        $messaggio = "<span style='color:red;'>Email già registrata.</span>";
    } else {
        $dati_utente = $utenti[$email_vecchia];
        $dati_utente['nome'] = $nuovo_nome;
        $dati_utente['cognome'] = $nuovo_cognome;

        if (!empty($pass1)) {
            if ($pass1 === $pass2) { $dati_utente['password'] = password_hash($pass1, PASSWORD_DEFAULT); }
            else { $messaggio = "<span style='color:red;'>Le password non coincidono.</span>"; }
        }

        if (empty($messaggio)) {
            if ($email_nuova !== $email_vecchia) {
                $nuova_dir = $cartella_db . '/user_' . md5($email_nuova);
                if (is_dir($_SESSION['user_dir'])) rename($_SESSION['user_dir'], $nuova_dir);
                unset($utenti[$email_vecchia]);
                $utenti[$email_nuova] = $dati_utente;
                $_SESSION['utente'] = $email_nuova;
                $_SESSION['user_dir'] = $nuova_dir;
            } else { $utenti[$email_vecchia] = $dati_utente; }
            file_put_contents($file_utenti, json_encode($utenti));
            $_SESSION['nome_completo'] = $nuovo_nome . " " . $nuovo_cognome;
            $messaggio = "<span style='color:green;'>Profilo aggiornato!</span>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profilo</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="card">
        <h2>Il mio Profilo</h2>
        <form method="post">
            <input type="text" name="nome" value="<?php echo $utenti[$_SESSION['utente']]['nome']; ?>" required>
            <input type="text" name="cognome" value="<?php echo $utenti[$_SESSION['utente']]['cognome']; ?>" required>
            <input type="email" name="email" value="<?php echo $_SESSION['utente']; ?>" required>
            <hr style="border:0; border-top:1px solid #eee; margin:15px 0;">
            <input type="password" name="password" placeholder="Nuova Password">
            <input type="password" name="conferma_password" placeholder="Conferma Password">
            <button type="submit" name="aggiorna" class="btn">Salva</button>
        </form>
        <div class="msg"><?php echo $messaggio; ?></div>
        <a href="home.php" class="back">← Torna alla Home</a>
    </div>
</body>
</html>