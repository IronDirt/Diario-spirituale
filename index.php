<?php
ini_set('session.gc_maxlifetime', 2592000);
ini_set('session.cookie_lifetime', 2592000);
session_start();


// --- 1. GESTIONE LOGOUT ---
if (isset($_GET['logout'])) { 
    session_unset(); 
    session_destroy(); 
    header("Location: index.php"); 
    setcookie('remember_me', '', time() - 3600, "/");
    exit; 
}

// --- 2. CONTROLLO SESSIONE ---
if (isset($_SESSION['autenticato'])) { 
    header("Location: home.php"); 
    exit; 
}

// --- 3. CONFIGURAZIONE DATABASE ---
$cartella_db = 'database';
if (!is_dir($cartella_db)) mkdir($cartella_db, 0777, true);
$file_utenti = $cartella_db . '/utenti.json';
$utenti = file_exists($file_utenti) ? json_decode(file_get_contents($file_utenti), true) : [];
$messaggio = "";

// --- 2.5 CONTROLLO COOKIE "RICORDAMI" ---
if (!isset($_SESSION['autenticato']) && isset($_COOKIE['remember_me'])) {
    list($c_email, $c_token) = explode(':', $_COOKIE['remember_me']);
    if (isset($utenti[$c_email]) && isset($utenti[$c_email]['token']) && $utenti[$c_email]['token'] === $c_token) {
        $_SESSION['autenticato'] = true;
        $_SESSION['utente'] = $c_email;
        $_SESSION['nome_completo'] = $utenti[$c_email]['nome'] . " " . $utenti[$c_email]['cognome'];
        $_SESSION['user_dir'] = $cartella_db . '/user_' . md5($c_email);
        header("Location: home.php");
        exit;
    }
}

// --- 4. LOGICA REGISTRAZIONE ---
if (isset($_POST['register'])) {
    $email = strtolower(trim($_POST['email']));
    $nome = htmlspecialchars(trim($_POST['nome']));
    $cognome = htmlspecialchars(trim($_POST['cognome']));
    $pass = $_POST['password'];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { 
        $messaggio = "<span style='color:#e74c3c;'>Email non valida.</span>"; 
    } elseif (isset($utenti[$email])) { 
        $messaggio = "<span style='color:#e74c3c;'>Email già registrata.</span>"; 
    } else {
        $utenti[$email] = [
            'password' => password_hash($pass, PASSWORD_DEFAULT), 
            'nome' => $nome, 
            'cognome' => $cognome
        ];
        file_put_contents($file_utenti, json_encode($utenti));
        $messaggio = "<span style='color:#2ecc71;'>Registrato con successo! Accedi ora.</span>";
    }
}

// --- 5. LOGICA LOGIN ---
if (isset($_POST['login'])) {
    $email = strtolower(trim($_POST['email']));
    if (isset($utenti[$email]) && password_verify($_POST['password'], $utenti[$email]['password'])) {
        $_SESSION['autenticato'] = true;
        $_SESSION['utente'] = $email;
        $_SESSION['nome_completo'] = $utenti[$email]['nome'] . " " . $utenti[$email]['cognome'];
        $_SESSION['user_dir'] = $cartella_db . '/user_' . md5($email);
        
        if (!is_dir($_SESSION['user_dir'])) mkdir($_SESSION['user_dir'], 0777, true);

        // Se l'utente vuole essere ricordato
        if (isset($_POST['remember'])) {
            $token = bin2hex(random_bytes(20));
            $utenti[$email]['token'] = $token; // Salviamo il token nel JSON
            file_put_contents($file_utenti, json_encode($utenti));
            setcookie('remember_me', $email . ':' . $token, time() + (86400 * 30), "/"); // Scade in 30 giorni
        }

        header("Location: home.php"); 
        exit;
    } else { 
        $messaggio = "<span style='color:#e74c3c;'>Credenziali errate.</span>"; 
    }
}

// --- 6. LOGICA RECUPERO ---
if (isset($_POST['recover'])) {
    $email = strtolower(trim($_POST['email']));
    if (isset($utenti[$email])) {
        $nuova_pass = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyz"), 0, 8);
        $utenti[$email]['password'] = password_hash($nuova_pass, PASSWORD_DEFAULT);
        file_put_contents($file_utenti, json_encode($utenti));
        $messaggio = "<div style='background:#fff3cd; padding:12px; border-radius:8px; border:1px solid #ffeeba; color:#856404;'>Nuova password: <code>$nuova_pass</code></div>";
    } else { 
        $messaggio = "<span style='color:#e74c3c;'>Email non trovata.</span>"; 
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accedi - Diario Spirituale</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <link rel="icon" type="image/png" href="icona_diario_personale.png">

    <link rel="apple-touch-icon" href="icona_diario_personale.png">

    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="Diario">
    <style>
        /* Stili extra per rifinire la parte inferiore */
        .links-container { margin-top: 25px; }
        .toggle-link { display: inline-block; cursor: pointer; transition: opacity 0.3s; font-size: 0.95em; color: #4a90e2; text-decoration: none; }
        .toggle-link:hover { opacity: 0.8; text-decoration: underline; }
        .recover-link { color: #bbb !important; font-size: 0.85em !important; margin-top: 12px; }
        .msg { margin-top: 20px; font-weight: 500; }
    </style>
</head>
<body>
    <div class="auth-card auth-container">
        
        <div id="login-box">
            <h2>Accedi</h2>
            <form method="post">
                <input type="email" name="email" placeholder="Email" required>
                
                <div class="password-wrapper">
                    <input type="password" name="password" id="pass_login" placeholder="Password" required>
                    <div class="toggle-password" onclick="togglePass('pass_login')">
                        <svg width="20" height="20" viewBox="0 0 24 24"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
                    </div>
                </div>
                <label class="custom-checkbox-container">
                    <input type="checkbox" name="remember">
                    <span class="checkmark"></span>
                    Ricordami su questo dispositivo
                </label>
                <button type="submit" name="login" class="btn">ACCEDI</button>
            </form>

            <div class="links-container">
                <span class="toggle-link" onclick="showBox('register-box')">Non hai un account? <b>Registrati</b></span>
                <br>
                <span class="toggle-link recover-link" onclick="showBox('recover-box')">Password dimenticata?</span>
            </div>
        </div>

        <div id="register-box" style="display:none;">
            <h2>Registrazione</h2>
            <form method="post">
                <input type="text" name="nome" placeholder="Nome" required>
                <input type="text" name="cognome" placeholder="Cognome" required>
                <input type="email" name="email" placeholder="Email" required>
                
                <div class="password-wrapper">
                    <input type="password" name="password" id="pass_reg" placeholder="Crea Password" required>
                    <div class="toggle-password" onclick="togglePass('pass_reg')">
                        <svg width="20" height="20" viewBox="0 0 24 24"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
                    </div>
                </div>
                
                <button type="submit" name="register" class="btn" style="background:#2ecc71;">REGISTRATI</button>
            </form>
            <div class="links-container">
                <span class="toggle-link" onclick="showBox('login-box')">Torna al Login</span>
            </div>
        </div>

        <div id="recover-box" style="display:none;">
            <h2>Recupero Password</h2>
            <form method="post">
                <input type="email" name="email" placeholder="Email dell'account" required>
                <button type="submit" name="recover" class="btn" style="background:#f39c12;">RECUPERA</button>
            </form>
            <div class="links-container">
                <span class="toggle-link" onclick="showBox('login-box')">Annulla</span>
            </div>
        </div>

        <div class="msg"><?php echo $messaggio; ?></div>
    </div>

    <script>
        const eyeOpen = '<path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>';
        const eyeClosed = '<path d="M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.82l2.92 2.92c1.51-1.26 2.7-2.89 3.44-4.74-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46C3.08 8.3 1.78 10.02 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z"/>';

        function togglePass(id) {
            const input = document.getElementById(id);
            const svg = input.nextElementSibling.querySelector('svg');
            
            if (input.type === "password") {
                input.type = "text";
                svg.innerHTML = eyeClosed;
            } else {
                input.type = "password";
                svg.innerHTML = eyeOpen;
            }
        }

        function showBox(id) {
            document.getElementById('login-box').style.display = 'none';
            document.getElementById('register-box').style.display = 'none';
            document.getElementById('recover-box').style.display = 'none';
            document.getElementById(id).style.display = 'block';
        }
    </script>
</body>
</html>