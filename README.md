# Diario Spirituale

Un'applicazione web per il tracciamento spirituale e il raggiungimento degli obiettivi personali.

## 📋 Descrizione

Diario Spirituale è un'applicazione web interattiva che permette agli utenti di monitorare il loro progresso spirituale, gestire gli obiettivi personali e tracciare le loro attività di studio e servizio.

## ✨ Funzionalità

- **Autenticazione Utente**: Sistema di login sicuro con cookie "Ricordami"
- **Profilo Personale**: Gestione del proprio profilo utente
- **Mete/Obiettivi**: Tracciamento degli obiettivi personali
- **Studio Personale**: Registrazione del tempo e dei progressi nello studio personale
- **Studio Familiare**: Monitoraggio delle attività di studio in famiglia
- **Servizio**: Tracciamento delle ore di servizio
- **Applicazione Web Progressiva (PWA)**: Installabile come app nativa sui dispositivi

## 🛠️ Tecnologie

- **Backend**: PHP
- **Frontend**: HTML, CSS, JavaScript
- **Database**: JSON (file-based)
- **PWA**: Manifest.json per supporto installazione

## 📁 Struttura del Progetto

```
├── index.php                 # Pagina di login
├── home.php                  # Pagina principale (dashboard)
├── profilo.php              # Gestione profilo utente
├── mete.php                 # Gestione obiettivi
├── studio_personale.php     # Tracciamento studio personale
├── studio_familiare.php     # Tracciamento studio familiare
├── servizio.php             # Tracciamento servizio
├── style.css                # Stili CSS
├── script.js                # Script JavaScript
├── manifest.json            # Manifest PWA
├── database/                # Database JSON
│   ├── utenti.json         # Dati utenti
│   └── user_*/             # Cartelle utente personalizzate
└── img/                     # Immagini del progetto
```

## 🚀 Utilizzo

1. Accedi all'applicazione tramite la pagina di login
2. Crea un nuovo account o accedi con le tue credenziali
3. Naviga tra le diverse sezioni per tracciare i tuoi progressi
4. Puoi installare l'app sul tuo dispositivo tramite il menu PWA

**Creato da**: Loris Salerno
