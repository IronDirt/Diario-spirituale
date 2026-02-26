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

    // permetti di toccare/cliccare sul testo della meta per leggere il contenuto intero
    const texts = document.querySelectorAll(".meta-text");
    const modal = document.getElementById("customConfirm");
    const btnOk = document.getElementById("confirmOk");
    const btnCancel = document.getElementById("confirmCancel");

    const isTextTruncated = (element) =>
      element.scrollWidth > element.clientWidth;

    const showMetaPopup = (fullText) => {
      if (!fullText) return false;
      if (
        typeof window.chiediConferma !== "function" ||
        !modal ||
        !btnOk ||
        !btnCancel
      ) {
        return false;
      }

      const originalOkText = btnOk.textContent;
      const originalCancelDisplay = btnCancel.style.display;

      const restoreModal = () => {
        btnCancel.style.display = originalCancelDisplay || "";
        btnOk.textContent = originalOkText;
        modal.removeEventListener("click", onOverlayClick);
        btnOk.removeEventListener("click", restoreModal);
      };
      const onOverlayClick = (event) => {
        if (event.target === modal) restoreModal();
      };

      btnCancel.style.display = "none";
      btnOk.textContent = "Chiudi";
      modal.addEventListener("click", onOverlayClick);
      btnOk.addEventListener("click", restoreModal, { once: true });

      window.chiediConferma("Meta", fullText, () => {});
      return true;
    };

    texts.forEach((el) => {
      el.style.cursor = "pointer"; // suggerisce che è interattivo
      el.addEventListener("click", () => {
        const fullText = el.textContent.trim();
        if (!fullText) return;

        if (showMetaPopup(fullText)) return;
        alert(fullText);
      });
    });
  }
});

document.addEventListener("DOMContentLoaded", function () {
  if (!document.getElementById("studio-familiare-page")) return;

  const modal = document.getElementById("studioModal");
  const closeBtn = document.getElementById("studioModalClose");
  const editBtn = document.getElementById("studioEditToggle");
  const saveBtn = document.getElementById("studioSaveBtn");
  const form = document.getElementById("studioModalForm");

  if (!modal || !form || !editBtn || !saveBtn) return;

  const fields = form.querySelectorAll("[data-studio-field]");
  let hasChanges = false;
  let originalValues = {};

  const autoResizeTextarea = (textarea) => {
    if (!textarea) return;
    textarea.style.height = "auto";
    textarea.style.height = `${textarea.scrollHeight}px`;
  };

  const resizeAllTextareas = () => {
    form
      .querySelectorAll("textarea[data-studio-field]")
      .forEach((textarea) => autoResizeTextarea(textarea));
  };

  const setEditing = (isEditing) => {
    fields.forEach((field) => {
      // Per i campi date e time, non uso disabled ma gestisco con CSS
      if (field.type === "date" || field.type === "time") {
        field.readOnly = false; // Mai readonly per mantenere i controlli nativi
        field.classList.toggle("is-readonly", !isEditing);
      } else {
        field.readOnly = !isEditing;
        field.classList.toggle("is-readonly", !isEditing);
      }
      
      if (!isEditing) {
        field.classList.remove("is-modified");
      }
    });
    
    form.classList.toggle("is-editing", isEditing);
    
    if (isEditing) {
      // Salva i valori originali quando inizia la modifica
      fields.forEach((field) => {
        originalValues[field.id] = field.value;
      });
      editBtn.style.display = "none";
      saveBtn.style.display = "inline-block";
      saveBtn.textContent = "Salva";
      closeBtn.textContent = "Annulla";
      closeBtn.classList.remove("btn-close");
      closeBtn.classList.add("btn-cancel");
      hasChanges = false;
    } else {
      editBtn.style.display = "inline-block";
      saveBtn.style.display = "none";
      closeBtn.textContent = "Chiudi";
      closeBtn.classList.remove("btn-cancel");
      closeBtn.classList.add("btn-close");
      hasChanges = false;
      originalValues = {};
    }
  };

  const formatDate = (dateString) => {
    if (!dateString) return "";
    const date = new Date(dateString + "T00:00:00");
    const days = ["Dom", "Lun", "Mar", "Mer", "Gio", "Ven", "Sab"];
    const months = ["gen", "feb", "mar", "apr", "mag", "giu", "lug", "ago", "set", "ott", "nov", "dic"];
    const dayName = days[date.getDay()];
    const day = date.getDate();
    const month = months[date.getMonth()];
    return `${dayName} ${day} ${month}`;
  };

  const openModal = (data) => {
    const setValue = (id, value) => {
      const el = document.getElementById(id);
      if (el) {
        el.value = value || "";
        el.classList.remove("is-modified");
      }
    };

    setValue("studioModalId", data.id);
    setValue("studioModalTitolo", data.titolo);
    setValue("studioModalDescrizione", data.descrizione);
    setValue("studioModalAppunti", data.appunti);
    setValue("studioModalLinkInput", data.link);
    setValue("studioModalData", data.data);
    setValue("studioModalOrario", data.orario);

    setEditing(false);
    modal.style.display = "flex";
    
    // Ridimensiona i textareas dopo che il modal è visibile
    requestAnimationFrame(() => {
      resizeAllTextareas();
    });
  };

  // Monitora i cambiamenti nei campi
  fields.forEach((field) => {
    const checkModified = () => {
      if (form.classList.contains("is-editing")) {
        const isModified = originalValues[field.id] !== undefined && field.value !== originalValues[field.id];
        field.classList.toggle("is-modified", isModified);
        hasChanges = Array.from(fields).some(f => f.classList.contains("is-modified"));
      }
    };

    field.addEventListener("input", checkModified);
    field.addEventListener("change", checkModified);

    if (field.tagName === "TEXTAREA") {
      field.addEventListener("input", () => autoResizeTextarea(field));
    }
  });

  // Click sul pulsante Modifica
  if (editBtn) {
    editBtn.addEventListener("click", (e) => {
      e.preventDefault();
      console.log("Modifica cliccato");
      setEditing(true);
    });
  }

  document.querySelectorAll(".studio-open-btn").forEach((btn) => {
    btn.addEventListener("click", () => {
      const payload = btn.dataset.studio;
      if (!payload) return;
      try {
        const data = JSON.parse(payload);
        openModal(data);
      } catch (error) {
        console.error("Errore parsing studio:", error);
      }
    });
  });

  const toggleBtn = document.getElementById("studioFormToggle");
  const extraFields = document.getElementById("studioExtraFields");

  if (toggleBtn && extraFields) {
    toggleBtn.addEventListener("click", () => {
      const isOpen = extraFields.style.display === "block";
      extraFields.style.display = isOpen ? "none" : "block";
      toggleBtn.classList.toggle("open", !isOpen);
    });
  }

  // Toggle menu mobile icone
  document.querySelectorAll(".studio-menu-toggle").forEach((btn) => {
    btn.addEventListener("click", (e) => {
      e.stopPropagation();
      const menu = btn.nextElementSibling;
      const isOpen = menu.classList.contains("active");

      // Chiudi tutti gli altri menu aperti
      document.querySelectorAll(".studio-actions-menu.active").forEach((m) => {
        if (m !== menu) {
          m.classList.remove("active");
          m.previousElementSibling.classList.remove("active");
        }
      });

      // Toggle menu corrente
      menu.classList.toggle("active");
      btn.classList.toggle("active");
    });
  });

  // Chiudi menu quando si clicca fuori
  document.addEventListener("click", (e) => {
    if (!e.target.closest(".studio-actions")) {
      document
        .querySelectorAll(".studio-actions-menu.active")
        .forEach((menu) => {
          menu.classList.remove("active");
          menu.previousElementSibling.classList.remove("active");
        });
    }
  });

  // Aggiungi popup per titoli lunghi
  const isTextTruncated = (element) =>
    element.scrollWidth > element.clientWidth;

  const showStudioTitlePopup = (fullText) => {
    const confirmModal = document.getElementById("customConfirm");
    if (typeof window.chiediConferma !== "function" || !confirmModal) {
      alert(fullText);
      return;
    }

    const btnOk = document.getElementById("confirmOk");
    const btnCancel = document.getElementById("confirmCancel");
    const originalOkText = btnOk.textContent;
    const originalCancelDisplay = btnCancel.style.display;

    const restoreModal = () => {
      btnCancel.style.display = originalCancelDisplay || "";
      btnOk.textContent = originalOkText;
      confirmModal.removeEventListener("click", onOverlayClick);
      btnOk.removeEventListener("click", restoreModal);
    };

    const onOverlayClick = (event) => {
      if (event.target === confirmModal) restoreModal();
    };

    btnCancel.style.display = "none";
    btnOk.textContent = "Chiudi";
    confirmModal.addEventListener("click", onOverlayClick);
    btnOk.addEventListener("click", restoreModal, { once: true });

    window.chiediConferma("Studio Familiare", fullText, () => {});
  };

  document.querySelectorAll(".studio-title-text").forEach((titleEl) => {
    titleEl.addEventListener("click", () => {
      const fullText = titleEl.textContent.trim();
      if (!fullText) return;
      showStudioTitlePopup(fullText);
    });
  });

  document.querySelectorAll(".delete-studio-btn").forEach((btn) => {
    btn.addEventListener("click", (event) => {
      event.preventDefault();
      const id = btn.getAttribute("data-id");
      if (id) confermaEliminaStudio(id, btn);
    });
  });

  // Funzione per ricaricare la lista studi
  const ricaricaListaStudi = () => {
    fetch("studio_familiare.php?get_lista=1")
      .then((response) => response.text())
      .then((html) => {
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, "text/html");
        const nuovaLista = doc.querySelector(".studio-list");
        const listaCorrente = document.querySelector(".studio-list");

        if (nuovaLista && listaCorrente) {
          listaCorrente.innerHTML = nuovaLista.innerHTML;

          // Ri-attacca gli event listeners
          document.querySelectorAll(".studio-open-btn").forEach((btn) => {
            btn.addEventListener("click", () => {
              const payload = btn.dataset.studio;
              if (!payload) return;
              try {
                const data = JSON.parse(payload);
                openModal(data);
              } catch (error) {
                console.error("Errore parsing studio:", error);
              }
            });
          });

          document.querySelectorAll(".delete-studio-btn").forEach((btn) => {
            btn.addEventListener("click", (event) => {
              event.preventDefault();
              const id = btn.getAttribute("data-id");
              if (id) confermaEliminaStudio(id, btn);
            });
          });

          document.querySelectorAll(".studio-share-btn").forEach((btn) => {
            btn.addEventListener("click", condividiStudio);
          });

          // Ri-attacca listener menu mobile
          document.querySelectorAll(".studio-menu-toggle").forEach((btn) => {
            btn.addEventListener("click", (e) => {
              e.stopPropagation();
              const menu = btn.nextElementSibling;
              const isOpen = menu.classList.contains("active");

              document
                .querySelectorAll(".studio-actions-menu.active")
                .forEach((m) => {
                  if (m !== menu) {
                    m.classList.remove("active");
                    m.previousElementSibling.classList.remove("active");
                  }
                });

              menu.classList.toggle("active");
              btn.classList.toggle("active");
            });
          });

          // Ri-attacca listener titoli lunghi
          document.querySelectorAll(".studio-title-text").forEach((titleEl) => {
            titleEl.addEventListener("click", () => {
              const fullText = titleEl.textContent.trim();
              if (!fullText) return;
              showStudioTitlePopup(fullText);
            });
          });
        }
      })
      .catch((error) => console.error("Errore ricarica lista:", error));
  };

  // Form di aggiunta studio - AJAX
  const studioForm = document.querySelector(".studio-form");
  if (studioForm) {
    studioForm.addEventListener("submit", (event) => {
      event.preventDefault();
      const formData = new FormData(studioForm);
      formData.append("ajax", "1");
      formData.append("aggiungi_studio", "1"); // Aggiungi il parametro del button

      fetch("studio_familiare.php", {
        method: "POST",
        body: formData,
      })
        .then((response) => {
          console.log("Response status:", response.status);
          return response.text();
        })
        .then((text) => {
          console.log("Response text:", text);
          try {
            const data = JSON.parse(text);
            if (data.success) {
              studioForm.reset();
              const extraFields = document.getElementById("studioExtraFields");
              if (extraFields) extraFields.style.display = "none";
              ricaricaListaStudi();
            }
          } catch (e) {
            console.error("Errore parsing JSON:", e);
            console.error("Testo ricevuto:", text);
          }
        })
        .catch((error) => console.error("Errore aggiunta studio:", error));
    });
  }

  // Form di modifica studio - AJAX
  if (form) {
    form.addEventListener("submit", (event) => {
      event.preventDefault();
      const formData = new FormData(form);
      formData.append("ajax", "1");
      formData.append("aggiorna_studio", "1"); // Aggiungi il parametro del button

      fetch("studio_familiare.php", {
        method: "POST",
        body: formData,
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            // Torna in modalità visualizzazione senza chiudere il modal
            setEditing(false);
            resizeAllTextareas();
            ricaricaListaStudi();
          }
        })
        .catch((error) => console.error("Errore modifica studio:", error));
    });
  }

  if (closeBtn) {
    closeBtn.addEventListener("click", () => {
      // Se siamo in modalità modifica, ripristina i valori originali
      if (form.classList.contains("is-editing")) {
        fields.forEach((field) => {
          if (originalValues[field.id] !== undefined) {
            field.value = originalValues[field.id];
          }
        });
        setEditing(false);
        resizeAllTextareas();
      } else {
        // Altrimenti chiudi semplicemente il modal
        modal.style.display = "none";
        setEditing(false);
      }
    });
  }

  modal.addEventListener("click", (event) => {
    if (event.target === modal) {
      modal.style.display = "none";
      setEditing(false);
    }
  });
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
  // aggiungi o rimuovi la spunta proprio nel checkbox
  if (elemento.classList.contains("checked")) {
    elemento.innerText = "✓";
  } else {
    elemento.innerText = "";
  }

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
  // ferma immediatamente l'intervallo e disattiva lo stato "running"
  clearInterval(tInterval);

  let totalMs = savedTime;
  if (running) totalMs += new Date().getTime() - startTime;

  // aggiorna stato e pulsanti subito così l'utente vede il timer fermo
  running = false;
  toggleButtons(false);
  // rimuoviamo lo stato dal localStorage in modo che un eventuale refresh non riparta il timer
  localStorage.setItem("timerRunning", "false");
  localStorage.removeItem("timerStartTime");
  // il savedTime rimane in memoria per il calcolo ma non lo scriviamo più nel storage

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
    function () {
      // l'utente ha scelto NO: cancelliamo il tempo accumulato
      resetTimer();
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

function inviaMete(event) {
  event.preventDefault();
  const btn = document.getElementById("btnInviaMete");
  const filtro = document.getElementById("filtro_email_mete").value;
  btn.innerHTML = "⏳ Invio...";
  setTimeout(() => {
    window.location.href = `mete.php?azione=invia_mete&filtro=${encodeURIComponent(filtro)}`;
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
    // Se la meta principale è segnata come completata la consideriamo
    // totalmente chiusa: così la barra globale resta al 100% anche se
    // ci sono sotto‑mete non finite, esattamente come in PHP.
    if (isMainChecked) {
      goalVal = 1;
    } else {
      // meta principale non completata, pesiamo solo le sotto‑mete (max 2/3)
      if (sottoMete.length > 0) {
        const completate = card.querySelectorAll(
          ".btn-check-small.checked",
        ).length;
        goalVal += (completate / sottoMete.length) * (2 / 3);
      }
      // se non ci sono sotto‑mete rimane 0 finché non metti il visto sul
      // genitore, in linea con il calcolo server-side
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
  let cancelAction = null; // callback da chiamare quando si annulla

  // 1. FUNZIONE UNIVERSALE (ora supporta anche la callback di cancel)
  window.chiediConferma = function (titolo, messaggio, callback, cancelCb) {
    mTitle.innerText = titolo;
    mText.innerHTML = messaggio;
    modal.style.display = "flex";
    actionToExecute = callback;
    cancelAction = cancelCb || null;
  };

  btnCancel.onclick = () => {
    modal.style.display = "none";
    if (cancelAction) {
      cancelAction();
      cancelAction = null;
    }
  };

  btnOk.onclick = () => {
    if (actionToExecute) actionToExecute();
    modal.style.display = "none";
    actionToExecute = null;
    cancelAction = null;
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

function toggleStudioFamiliare(id) {
  if (!id) return;

  fetch(
    `studio_familiare.php?azione=toggle&id=${encodeURIComponent(id)}&ajax=1`,
  )
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        const item = document.getElementById(`studio-${id}`);
        const checkbox = item?.querySelector(".btn-check");
        if (item && checkbox) {
          item.classList.toggle("completed");
          checkbox.classList.toggle("checked");
        }
      }
    })
    .catch((error) => console.error("Errore toggle studio:", error));
}

function confermaEliminaStudio(id, elemento) {
  if (!window.chiediConferma) return;

  window.chiediConferma(
    "Elimina",
    "Sei sicuro di eliminare lo studio familiare?",
    function () {
      fetch(`studio_familiare.php?azione=elimina&id=${id}&ajax=1`).then(() => {
        const row = elemento.closest(".studio-item");
        if (row) {
          row.style.opacity = "0";
          row.style.transform = "scale(0.96)";
          setTimeout(() => row.remove(), 300);
        }
      });
    },
  );
}

function condividiStudio(event) {
  event.preventDefault();
  const shareText = this.getAttribute("data-share-text");

  if (!shareText) {
    alert("Nessun contenuto da condividere");
    return;
  }

  // Usa la Web Share API se disponibile
  if (navigator.share) {
    navigator
      .share({
        text: shareText,
        title: "Studio Familiare",
      })
      .catch(() => {
        // Se l'utente cancella il dialog nativo, non fare niente
      });
  } else {
    // Fallback: copia il testo negli appunti e mostra un messaggio
    navigator.clipboard
      .writeText(shareText)
      .then(() => {
        alert("Testo copiato negli appunti!");
      })
      .catch(() => {
        alert("Impossibile copiare il testo");
      });
  }
}

// Aggancia i listener ai pulsanti di condivisione
document.addEventListener("DOMContentLoaded", function () {
  const shareButtons = document.querySelectorAll(".studio-share-btn");
  shareButtons.forEach((btn) => {
    btn.addEventListener("click", condividiStudio);
  });
});
