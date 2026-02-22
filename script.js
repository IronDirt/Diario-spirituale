document.addEventListener("DOMContentLoaded", function () {
  console.log("Sistema pronto");

  // Se siamo nella pagina mete, giriamo le frecce delle sezioni già aperte
  if (document.getElementById("mete-page")) {
    const containers = document.querySelectorAll(".sub-container");
    containers.forEach((container) => {
      if (container.style.display === "block") {
        const id = container.id.replace("sub-", "");
        const freccia = document.getElementById("arrow-" + id);
        if (freccia) freccia.style.transform = "rotate(180deg)";
      }
    });

    // aggiorniamo anche le barre (globale e locali) una volta al caricamento
    if (typeof aggiornaBarraProgressiva === "function") {
      aggiornaBarraProgressiva();
    }
  }
});

// --- FUNZIONI METE ---

function toggleSubMete(id) {
  const container = document.getElementById("sub-" + id);
  const freccia = document.getElementById("arrow-" + id);
  if (!container || !freccia) return;

  if (container.style.display === "none" || container.style.display === "") {
    container.style.display = "block";
    freccia.style.transform = "rotate(180deg)";
  } else {
    container.style.display = "none";
    freccia.style.transform = "rotate(0deg)";
  }
}

function aggiungiSubAjax(event, parentId) {
  event.preventDefault();
  const form = event.target;
  const input = form.querySelector('input[name="testo_sotto_meta"]');
  const testo = input.value;
  if (!testo.trim()) return;

  const formData = new FormData();
  formData.append("aggiungi_sotto_meta", "1");
  formData.append("parent_id", parentId);
  formData.append("testo_sotto_meta", testo);
  formData.append("ajax", "1");

  fetch("mete.php", { method: "POST", body: formData })
    .then((response) => response.text())
    .then((data) => {
      const newId = data.trim();
      const div = document.createElement("div");
      div.className = "sub-meta-item";
      div.id = "row-" + newId;
      div.style.opacity = "0";
      div.style.transition = "all 0.4s ease";

      // NOTA: Qui usiamo confermaEliminazione (SweetAlert) invece del confirm standard
      div.innerHTML = `
            <div class="btn-check-small" onclick="toggleSubAjax('${newId}', this)"></div>
            <span class="sub-meta-text">${testo}</span>
            <div class="btn-delete-small" style="cursor:pointer;" onclick="confermaEliminazione('${newId}', this)">🗑️</div>
        `;

      form.after(div);
      setTimeout(() => {
        div.style.opacity = "1";
      }, 10);
      input.value = "";
    });
}

function toggleSubAjax(id, elemento) {
  const riga = elemento.closest(".sub-meta-item");
  const testo_sotto_meta = riga.querySelector(".sub-meta-text");

  // 1. Cambiamo l'aspetto visivo IMMEDIATAMENTE (Feedback immediato)
  riga.classList.toggle("completed");
  elemento.classList.toggle("checked");

  // 2. Gestiamo gli stili del testo
  if (elemento.classList.contains("checked")) {
    if (testo_sotto_meta) {
      testo_sotto_meta.style.textDecoration = "line-through";
      testo_sotto_meta.style.color = "#888";
    }
  } else {
    if (testo_sotto_meta) {
      testo_sotto_meta.style.textDecoration = "none";
      testo_sotto_meta.style.color = "#333";
    }
  }

  // 3. AGGIORNA LA BARRA SUBITO (Non dentro il .then)
  if (typeof aggiornaBarraProgressiva === "function") {
    aggiornaBarraProgressiva();
  }

  const isChecked = elemento.classList.contains("checked") ? 1 : 0;

  // 4. Chiamata al server (lavora in background)
  fetch(`mete.php?azione=toggle_sub&id=${id}&stato=${isChecked}&ajax=1`)
    .then((response) => response.text())
    .then((data) => {
      console.log("Stato salvato sul server");
      // Se vuoi essere pignolo, controlli il data.trim() qui,
      // ma la barra deve già essersi mossa!
    })
    .catch((error) => {
      console.error("Errore:", error);
      // In caso di errore di connessione, avvisiamo l'utente
      alert(
        "Errore di connessione. Le modifiche potrebbero non essere salvate.",
      );
    });
}

function toggleMainAjax(id, elemento) {
  // 1. Applichiamo subito i cambiamenti grafici (Feedback immediato)
  elemento.classList.toggle("checked");

  // Cerchiamo il testo della meta principale per barrarlo
  const card = elemento.closest(".meta-card");
  // Nota: è meglio se in mete.php aggiungi una classe al testo, es: class="meta-text"
  const testo =
    card.querySelector(".meta-text") ||
    card.querySelector('div[style*="margin-bottom"]');

  if (elemento.classList.contains("checked")) {
    if (testo) {
      testo.style.textDecoration = "line-through";
      testo.style.color = "#888";
    }
  } else {
    if (testo) {
      testo.style.textDecoration = "none";
      testo.style.color = "#333";
    }
  }

  // 2. Aggiorniamo la barra progressiva SUBITO
  aggiornaBarraProgressiva();

  // 3. Salviamo nel database tramite AJAX
  fetch(`mete.php?azione=toggle&id=${id}&ajax=1`)
    .then((response) => {
      if (!response.ok) throw new Error("Errore di rete");
      console.log("Meta " + id + " aggiornata con successo");
    })
    .catch((error) => {
      console.error("Errore nell'aggiornamento:", error);
      // Opzionale: se fallisce il salvataggio, potresti rimettere la checkbox come prima
    });
}

// Funzione per eliminare la sottometa con effetto visivo e chiamata al server
function eliminaSubAjax(id, elemento) {
  const riga = elemento.closest(".sub-meta-item");

  // Effetto visivo di sparizione
  riga.style.transform = "translateX(-50px)";
  riga.style.opacity = "0";

  setTimeout(() => {
    riga.remove();
    // Aggiorna la barra dopo aver rimosso l'elemento dal DOM
    if (typeof aggiornaBarraProgressiva === "function") {
      aggiornaBarraProgressiva();
    }
  }, 400);

  // Chiamata al server con l'azione specifica per sottometa
  fetch(`mete.php?azione=elimina_sottometa&id=${id}&ajax=1`).then(
    (response) => {
      if (!response.ok)
        console.error("Errore durante l'eliminazione sul server");
    },
  );
}

// --- LOGICA TIMER CON PERSISTENZA ---

let startTime,
  tInterval,
  running = false,
  savedTime = 0;

// Al caricamento della pagina, recuperiamo lo stato precedente
document.addEventListener("DOMContentLoaded", function () {
  const storedSavedTime = localStorage.getItem("timerSavedTime");
  const storedStartTime = localStorage.getItem("timerStartTime");
  const isRunning = localStorage.getItem("timerRunning") === "true";

  if (storedSavedTime) savedTime = parseInt(storedSavedTime);

  if (isRunning && storedStartTime) {
    startTime = parseInt(storedStartTime);
    running = true;
    // Facciamo ripartire l'intervallo visivo
    tInterval = setInterval(updateDisplay, 1000);
    updateDisplay(); // <--- AGGIUNGI QUESTA: aggiorna subito senza aspettare il primo secondo
    toggleButtons(true);
  } else {
    const display = document.getElementById("display");
    if (display) display.innerHTML = formatTime(savedTime);
    toggleButtons(false);
  }
});

function updateDisplay() {
  const display = document.getElementById("display");
  let now = new Date().getTime();
  let diff = now - startTime + savedTime;
  if (display) display.innerHTML = formatTime(diff);
}

function formatTime(ms) {
  let h = Math.floor(ms / 3600000);
  let m = Math.floor((ms % 3600000) / 60000);
  let s = Math.floor((ms % 60000) / 1000);
  return `${h < 10 ? "0" + h : h}:${m < 10 ? "0" + m : m}:${s < 10 ? "0" + s : s}`;
}

function startTimer() {
  if (!running) {
    startTime = new Date().getTime();
    tInterval = setInterval(updateDisplay, 1000);
    running = true;

    // Salviamo lo stato nel browser
    localStorage.setItem("timerStartTime", startTime);
    localStorage.setItem("timerRunning", "true");

    toggleButtons(true);
  }
}

function pauseTimer() {
  if (running) {
    clearInterval(tInterval);
    savedTime += new Date().getTime() - startTime;
    running = false;

    // Aggiorniamo lo stato nel browser
    localStorage.setItem("timerSavedTime", savedTime);
    localStorage.setItem("timerRunning", "false");

    toggleButtons(false);
  }
}

// 1. Modifica lo stopTimer per usare AJAX
function stopTimer() {
  clearInterval(tInterval);
  let totalMs = savedTime;
  if (running) totalMs += new Date().getTime() - startTime;

  let ore = Math.floor(totalMs / 3600000);
  let minuti = Math.floor((totalMs % 3600000) / 60000);

  // Se il tempo è 0 ore e 0 minuti (meno di 60 secondi)
  if (ore === 0 && minuti === 0) {
    // Usiamo il tuo modal personalizzato come avviso
    window.chiediConferma(
      "Tempo insufficiente",
      "Il tempo registrato è inferiore a un minuto e non può essere salvato.",
      function () {
        resetTimer(); // Cosa fare quando preme OK
      },
    );

    // Nascondiamo temporaneamente il tasto "No" perché è un avviso, non una scelta
    const btnCancel = document.getElementById("confirmCancel");
    if (btnCancel) {
      btnCancel.style.display = "none";
      // Lo riattiviamo dopo un secondo o quando si chiude per non romperlo nelle altre chiamate
      const ripristinaBottone = () => {
        btnCancel.style.display = "inline-block";
        document
          .getElementById("confirmOk")
          .removeEventListener("click", ripristinaBottone);
      };
      document
        .getElementById("confirmOk")
        .addEventListener("click", ripristinaBottone);
    }
    return;
  }

  window.chiediConferma(
    "Salva Tempo",
    `Registrare <strong>${ore}h</strong> e <strong>${minuti}m</strong>?`,
    function () {
      const urlParams = new URLSearchParams(window.location.search);
      const m = urlParams.get("m") || new Date().getMonth() + 1;
      const a = urlParams.get("a") || new Date().getFullYear();

      fetch(
        `servizio.php?azione=salva_timer&ore=${ore}&minuti=${minuti}&m=${m}&a=${a}&ajax=1`,
      )
        .then((res) => res.json())
        .then((data) => {
          if (data.status === "success") {
            resetTimer();
            aggiungiRigaTabella(data.record);
            aggiornaUI(data.totali);
          }
        });
    },
  );
}

// 2. Gestione Salvataggio Manuale AJAX
document.addEventListener("submit", function (e) {
  if (
    e.target.closest("form") &&
    e.target.querySelector('button[name="salva"]')
  ) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);
    formData.append("salva", "1");
    formData.append("ajax", "1");

    fetch(window.location.href, { method: "POST", body: formData })
      .then((res) => res.json())
      .then((data) => {
        if (data.status === "success") {
          form.reset();
          aggiungiRigaTabella(data.record);
          aggiornaUI(data.totali);
        }
      });
  }
});

// Funzioni per aggiornare la pagina al volo
function aggiungiRigaTabella(record) {
  const tbody = document.querySelector("table tbody");
  const rigaVuota = tbody.querySelector(".vuoto");
  if (rigaVuota) rigaVuota.remove();

  const tr = document.createElement("tr");
  tr.style.opacity = "0";
  tr.style.backgroundColor = "#e8f5e9";

  // USIAMO record.data_display invece di record.data
  // Se per qualche motivo data_display non esiste, usiamo data come backup
  const dataMostrata = record.data_display || record.data;

  tr.innerHTML = `
        <td>${dataMostrata}</td>
        <td><strong>${record.ore}h ${record.minuti}m</strong></td>
        <td>
            <a href="javascript:void(0);" 
               onclick="confermaEliminaServizio('${record.id}', '${record.mese}', '${record.anno}')" 
               class="del-btn" 
               style="text-decoration: none !important; outline: none; border: none;">
               🗑️
            </a>
        </td>
    `;

  tbody.insertBefore(tr, tbody.firstChild);

  setTimeout(() => {
    tr.style.transition = "all 0.5s";
    tr.style.opacity = "1";
    tr.style.backgroundColor = "transparent";
  }, 10);
}

function aggiornaUI(totali) {
  // Aggiorna H1
  document.querySelector(".totale-box h1").innerText =
    `${totali.h_m}h ${totali.m_m}m`;
  // Aggiorna barre e testi obiettivi
  aggiornaGraficaObiettivi(totali.h_m, totali.h_a);
}

function confermaEliminaServizio(id, mese, anno) {
  window.chiediConferma("Elimina", "Sicuro di volerlo eliminare?", function () {
    // Chiamata AJAX al server
    fetch(`servizio.php?azione=elimina&id=${id}&m=${mese}&a=${anno}&ajax=1`)
      .then((response) => response.json())
      .then((data) => {
        if (data.status === "success") {
          // 1. Individua la riga nella tabella e rimuovila con effetto
          const cestino = document.querySelector(`a[onclick*="'${id}'"]`);
          if (cestino) {
            const riga = cestino.closest("tr");
            riga.style.transition = "all 0.4s ease";
            riga.style.opacity = "0";
            riga.style.transform = "translateX(20px)";
            setTimeout(() => riga.remove(), 400);
          }

          // 2. Aggiorna il totale ore in cima (H1)
          const displayTotale = document.querySelector(".totale-box h1");
          if (displayTotale) {
            displayTotale.innerText = `${data.ore_mese}h ${data.min_mese}m`;
          }

          // 3. Aggiorna le barre progressive e i testi degli obiettivi
          aggiornaGraficaObiettivi(data.tot_ore_mese, data.tot_ore_anno);
        }
      })
      .catch((error) => console.error("Errore durante l'eliminazione:", error));
  });
}

// Nuova funzione di supporto per aggiornare le barre senza ricaricare
function aggiornaGraficaObiettivi(oreMese, oreAnno) {
  // Recuperiamo i target (obiettivi) dagli elementi stessi se presenti
  const labelMese = document.querySelector(".progress-bar-mensile")
    ?.parentElement?.previousElementSibling;
  const labelAnno =
    document.querySelector(".progress-bar-anno")?.parentElement
      ?.previousElementSibling;

  if (labelMese) {
    const targetMese = parseInt(labelMese.innerText.split("/")[1]);
    labelMese.querySelectorAll("span")[1].innerText =
      `${oreMese} / ${targetMese}h`;
    const percMese = Math.min(100, Math.round((oreMese / targetMese) * 100));
    document.querySelector(".progress-bar-mensile").style.width =
      percMese + "%";
  }

  if (labelAnno) {
    const targetAnno = parseInt(labelAnno.innerText.split("/")[1]);
    labelAnno.querySelectorAll("span")[1].innerText =
      `${oreAnno} / ${targetAnno}h`;
    const percAnno = Math.min(100, Math.round((oreAnno / targetAnno) * 100));
    document.querySelector(".progress-bar-anno").style.width = percAnno + "%";
  }
}

function resetTimer() {
  clearInterval(tInterval);
  running = false;
  savedTime = 0;
  localStorage.removeItem("timerStartTime");
  localStorage.removeItem("timerSavedTime");
  localStorage.removeItem("timerRunning");

  const display = document.getElementById("display");
  if (display) display.innerHTML = "00:00:00";
  toggleButtons(false);
}

// Funzione per gestire la visibilità dei pulsanti Start/Pause e mostrare SEMPRE Stop
function toggleButtons(isRunning) {
  const startBtn = document.getElementById("startBtn");
  const pauseBtn = document.getElementById("pauseBtn");
  const stopBtn = document.getElementById("stopBtn");

  if (isRunning) {
    startBtn.style.display = "none";
    pauseBtn.style.display = "flex";
  } else {
    startBtn.style.display = "flex";
    pauseBtn.style.display = "none";
  }

  // Il tasto stop deve essere visibile se c'è del tempo accumulato o se sta correndo
  if (isRunning || savedTime > 0) {
    stopBtn.style.display = "flex";
  } else {
    stopBtn.style.display = "none";
  }
}

function inviaRapporto() {
  const btn = document.querySelector(".btn-email-link-small");
  const periodo = document.getElementById("periodo_email").value;
  btn.innerHTML = "⏳ Invio...";
  setTimeout(() => {
    window.location.href = `servizio.php?azione=invia_rapporto&periodo=${periodo}`;
  }, 600);
}

function aggiornaBarraProgressiva() {
  const meteCard = document.querySelectorAll(".meta-wrapper");
  let totalProgress = 0;
  const totalMainGoals = meteCard.length;

  // Se non ci sono mete, usciamo
  if (totalMainGoals === 0) return;

  meteCard.forEach((card) => {
    // --- progressione locale ---
    let cardPerc = 0;
    const mainCheck = card.querySelector(".btn-check");
    const isMainChecked = mainCheck && mainCheck.classList.contains("checked");
    const sottoMete = card.querySelectorAll(".sub-meta-item");

    if (isMainChecked) {
      cardPerc = 100;
    } else if (sottoMete.length > 0) {
      const completate = card.querySelectorAll(
        ".btn-check-small.checked",
      ).length;
      cardPerc = Math.round((completate / sottoMete.length) * 90);
    } else {
      cardPerc = 0;
    }

    // applica alla barra locale se presente
    const localBar = card.querySelector(".meta-progress-bar");
    if (localBar) {
      localBar.style.width = cardPerc + "%";
    }
    // aggiorna testo percentuale (destra della barra)
    const localText = card.querySelector(".meta-progress-text");
    if (localText) {
      localText.innerText = cardPerc + "%";
    }

    // --- manteniamo la logica globale già esistente ---
    let goalVal = 0;
    if (sottoMete.length > 0) {
      if (isMainChecked) goalVal += 1 / 3;
      const completate = card.querySelectorAll(
        ".btn-check-small.checked",
      ).length;
      goalVal += (completate / sottoMete.length) * (2 / 3);
    } else {
      goalVal = isMainChecked ? 1 : 0;
    }
    totalProgress += goalVal;
  });

  // Calcolo percentuale finale
  const nuovaPerc = Math.round((totalProgress / totalMainGoals) * 100);

  // --- AGGIORNAMENTO DOM ---

  // Aggiorna la barra verde globale
  const progressBar = document.getElementById("main-progress-bar");
  if (progressBar) {
    progressBar.style.width = nuovaPerc + "%";
  }

  // Aggiorna il testo numerico
  const progressText = document.getElementById("progress-percentage");
  if (progressText) {
    progressText.innerText = nuovaPerc + "%";
  } else {
    console.warn("Elemento 'progress-percentage' non trovato nel DOM!");
  }
}

document.addEventListener("DOMContentLoaded", function () {
  const modal = document.getElementById("customConfirm");
  const mTitle = document.getElementById("modalTitle");
  const mText = document.getElementById("modalText");
  const btnOk = document.getElementById("confirmOk");
  const btnCancel = document.getElementById("confirmCancel");

  let actionToExecute = null;

  // 1. FUNZIONE UNIVERSALE
  window.chiediConferma = function (titolo, messaggio, callback) {
    mTitle.innerText = titolo;
    mText.innerHTML = messaggio;
    modal.style.display = "flex";
    actionToExecute = callback;
  };

  btnCancel.onclick = () => (modal.style.display = "none");

  btnOk.onclick = () => {
    if (actionToExecute) actionToExecute();
    modal.style.display = "none";
  };

  window.onclick = (event) => {
    if (event.target == modal) modal.style.display = "none";
  };

  // 2. ELIMINAZIONE META PRINCIPALE (AJAX)
  document.querySelectorAll(".delete-link").forEach((link) => {
    link.addEventListener("click", function (e) {
      e.preventDefault();
      const id = this.getAttribute("data-id");

      // CORREZIONE: Assicurati che .meta-item sia la classe usata in mete.php per il contenitore
      const riga =
        this.closest(".meta-item") ||
        this.closest(".meta-wrapper") ||
        this.closest(".meta-card");

      window.chiediConferma(
        "Elimina",
        "Sei sicuro di eliminare la meta?",
        () => {
          // Fetch con parametro ajax=1
          fetch(`mete.php?azione=elimina&id=${id}&ajax=1`).then(() => {
            if (riga) {
              riga.style.opacity = "0";
              riga.style.transform = "scale(0.9)";
              setTimeout(() => {
                riga.remove();
                if (typeof aggiornaBarraProgressiva === "function")
                  aggiornaBarraProgressiva();
              }, 300);
            }
          });
        },
      );
    });
  });
});

// 3. ELIMINAZIONE SOTTO-META (AJAX)
function confermaEliminazione(id, elemento) {
  window.chiediConferma(
    "Elimina",
    "Sei sicuro di eliminare la sotto-meta?",
    function () {
      // CORREZIONE: Aggiunto &ajax=1 e corretto il percorso
      fetch(`mete.php?azione=elimina_sottometa&id=${id}&ajax=1`).then(() => {
        // CORREZIONE: Usa una classe specifica per sicurezza (.sub-meta-item)
        const li = elemento.closest("li") || elemento.closest(".sub-meta-item");
        if (li) {
          li.style.opacity = "0";
          li.style.transform = "translateX(-20px)";
          setTimeout(() => {
            li.remove();
            // CORREZIONE: Aggiorna la barra dopo l'eliminazione
            if (typeof aggiornaBarraProgressiva === "function")
              aggiornaBarraProgressiva();
          }, 300);
        }
      });
    },
  );
}
