// ../js/doctor_medical_certificate.js
(() => {
    // -----------------------------
    // helpers
    // -----------------------------
    const $ = (id) => document.getElementById(id);
  
    function toISODate(d) {
      const pad = (n) => String(n).padStart(2, "0");
      return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
    }
  
    function calcDurationDays(fromISO, toISO) {
      if (!fromISO || !toISO) return 0;
      const from = new Date(fromISO);
      const to = new Date(toISO);
      const diffTime = to.getTime() - from.getTime();
      const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24)) + 1;
      return diffDays > 0 ? diffDays : 0;
    }
  
    function showToast(msg) {
      alert(msg);
    }
  
    function escapeHtml(str) {
      if (str === null || str === undefined) return "";
      return String(str)
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
    }
  
    function computeAge(dobISO) {
      if (!dobISO) return "";
      const dob = new Date(dobISO);
      const today = new Date();
      let age = today.getFullYear() - dob.getFullYear();
      const m = today.getMonth() - dob.getMonth();
      if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) age--;
      return age;
    }
  
    // -----------------------------
    // state
    // -----------------------------
    let selectedPatient = null;
  
    // -----------------------------
    // elements (ONLY those that exist now)
    // -----------------------------
    const selectPatientBtn = $("selectPatientBtn");
    const patientSelectionModal = $("patientSelectionModal");
    const closePatientModal = $("closePatientModal");
    const patientSearchInput = $("patientSearchInput");
    const patientResults = $("patientResults");
    const patientEmptyState = $("patientEmptyState");
  
    const selectedPatientInfo = $("selectedPatientInfo");
    const noPatientSelected = $("noPatientSelected");
    const selectedPatientId = $("selectedPatientId");
    const selectedTemplateType = $("selectedTemplateType");
  
    const certificateForm = $("certificateForm");
    const downloadPDFBtn = $("downloadPDFBtn");
    const viewHistoryBtn = $("viewHistoryBtn");
  
    const fromDate = $("fromDate");
    const toDate = $("toDate");
    const durationDisplay = $("durationDisplay");
  
    const includeDigitalStamp = $("includeDigitalStamp");
    const includeQRCode = $("includeQRCode");
  
    // patient card fields
    const selectedPatientInitials = $("selectedPatientInitials");
    const selectedPatientName = $("selectedPatientName");
    const selectedPatientMrn = $("selectedPatientMrn");
    const selectedPatientAge = $("selectedPatientAge");
    const selectedPatientGender = $("selectedPatientGender");
    const selectedPatientBlood = $("selectedPatientBlood");
  
    // -----------------------------
    // modal open/close
    // -----------------------------
    function openPatientModal() {
      if (!patientSelectionModal) return;
      patientSelectionModal.classList.remove("hidden");
      if (patientSearchInput) patientSearchInput.value = "";
      if (patientResults) patientResults.innerHTML = "";
      if (patientEmptyState) patientEmptyState.classList.add("hidden");
      patientSearchInput?.focus();
      searchPatients("");
    }
  
    function closePatientModalFn() {
      patientSelectionModal?.classList.add("hidden");
    }
  
    // -----------------------------
    // patient search
    // -----------------------------
    let searchTimer = null;
  
    async function searchPatients(q) {
      try {
        const res = await fetch(`../backend/doctor_patients_search.php?q=${encodeURIComponent(q)}`, {
          credentials: "same-origin",
        });
  
        const raw = await res.text();
        let data;
  
        try {
          data = JSON.parse(raw);
        } catch {
          console.error("API returned non-JSON:", raw);
          throw new Error("patients_search.php returned HTML / non-JSON. Check Network → Response.");
        }
  
        if (!data.ok) throw new Error(data.error || "failed to load patients");
        renderPatientResults(data.patients || []);
      } catch (err) {
        console.error(err);
        if (patientResults) patientResults.innerHTML = "";
        if (patientEmptyState) {
          patientEmptyState.classList.remove("hidden");
          patientEmptyState.textContent = err.message || "failed to load patients";
        }
      }
    }
  
    function renderPatientResults(list) {
      if (!patientResults) return;
      patientResults.innerHTML = "";
  
      if (!list.length) {
        patientEmptyState?.classList.remove("hidden");
        return;
      }
      patientEmptyState?.classList.add("hidden");
  
      list.forEach((p) => {
        const initials =
          `${(p.first_name || "").trim().charAt(0)}${(p.last_name || "").trim().charAt(0)}`.toUpperCase() || "PT";
  
        const btn = document.createElement("button");
        btn.type = "button";
        btn.className =
          "patient-select-btn w-full text-left p-4 rounded-base hover:bg-secondary-50 transition-colors border border-border";
        btn.innerHTML = `
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-primary-100 text-primary-700 flex items-center justify-center font-semibold flex-shrink-0">
              ${initials}
            </div>
            <div class="flex-1">
              <p class="font-medium text-text-primary">${escapeHtml(p.first_name)} ${escapeHtml(p.last_name)}</p>
              <p class="text-sm text-text-secondary">MRN: ${escapeHtml(p.mrn)} • Gender: ${escapeHtml(p.gender)} • Blood: ${escapeHtml(p.blood_type || "n/a")}</p>
            </div>
          </div>
        `;
  
        btn.addEventListener("click", () => {
          selectedPatient = p;
          if (selectedPatientId) selectedPatientId.value = p.patient_id;
  
          // show patient card
          selectedPatientInfo?.classList.remove("hidden");
          noPatientSelected?.classList.add("hidden");
  
          // fill patient card fields (safe)
          if (selectedPatientInitials) selectedPatientInitials.textContent = initials;
          if (selectedPatientName) selectedPatientName.textContent = `${p.first_name || ""} ${p.last_name || ""}`.trim();
          if (selectedPatientMrn) selectedPatientMrn.textContent = p.mrn || "--";
          if (selectedPatientGender) selectedPatientGender.textContent = p.gender || "--";
          if (selectedPatientBlood) selectedPatientBlood.textContent = p.blood_type || "n/a";
          if (selectedPatientAge) selectedPatientAge.textContent = computeAge(p.date_of_birth) || "--";
  
          // ✅ load patient-specific recent certificates on the side
          loadRecentCertificates(p.patient_id);
  
          closePatientModalFn();
        });
  
        patientResults.appendChild(btn);
      });
    }
  
    // -----------------------------
    // template selection
    // -----------------------------
    function setupTemplateCards() {
      const templateCards = document.querySelectorAll(".template-card");
      templateCards.forEach((c) => c.classList.remove("active"));
      if (selectedTemplateType) selectedTemplateType.value = "";
  
      templateCards.forEach((card) => {
        card.addEventListener("click", () => {
          templateCards.forEach((c) => c.classList.remove("active"));
          card.classList.add("active");
  
          const t = card.getAttribute("data-template") || "";
          if (selectedTemplateType) selectedTemplateType.value = t;
  
          const msg = $("templateRequiredMsg");
          if (msg) msg.classList.add("hidden");
        });
      });
    }
  
    // -----------------------------
    // duration
    // -----------------------------
    function updateDurationUI() {
      const days = calcDurationDays(fromDate?.value, toDate?.value);
      if (durationDisplay) {
        durationDisplay.textContent = days ? `${days} day${days !== 1 ? "s" : ""}` : "0 days";
      }
    }
  
    // -----------------------------
    // recent certificates (side history)
    // -----------------------------
    async function loadRecentCertificates(patientId) {
      const statusEl = $("recentCertificatesStatus");
      const listEl = $("recentCertificatesList");
      if (!statusEl || !listEl) return;
  
      statusEl.textContent = "loading recent certificates...";
      statusEl.classList.remove("hidden");
      listEl.classList.add("hidden");
      listEl.innerHTML = "";
  
      const url = patientId
        ? `../backend/doctor_medical_certificate_history.php?patient_id=${encodeURIComponent(patientId)}`
        : `../backend/doctor_medical_certificate_history.php`;
  
      try {
        const res = await fetch(url, { credentials: "same-origin" });
        const data = await res.json();
  
        if (!data.ok) throw new Error(data.error || "failed to load certificates");
  
        const items = (Array.isArray(data.items) ? data.items : []).slice(0, 10);
        if (!items.length) {
          statusEl.textContent = patientId
            ? "no certificates found for this patient."
            : "no recent certificates created yet.";
          return;
        }
  
        listEl.innerHTML = items.map((x) => {
          const title = escapeHtml((x.template_type || "medical certificate").replaceAll("_", " ").toUpperCase());
          const diag = escapeHtml(x.diagnosis || "");
          const range = `${escapeHtml(x.leave_from || "")} - ${escapeHtml(x.leave_to || "")}`;
  
          const pName = escapeHtml(x.patient_name || "—");
          const pMrn = escapeHtml(x.mrn || "");
  
          const link = x.download_url ? escapeHtml(x.download_url) : "";
          const disabled = link ? "" : "opacity-50 pointer-events-none";
  
          return `
            <div class="flex items-center justify-between p-3 rounded-base hover:bg-secondary-50 transition-colors border border-border">
              <div class="min-w-0">
                <p class="font-medium text-text-primary truncate">${title}</p>
                <p class="text-sm text-text-secondary truncate">${range} • ${diag}</p>
                <p class="text-xs text-text-tertiary truncate">patient: ${pName}${pMrn ? ` • mrn: ${pMrn}` : ""}</p>
              </div>
              <a href="${link}" target="_blank" class="btn btn-ghost btn-sm ${disabled}" aria-label="View">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
              </a>
            </div>
          `;
        }).join("");
  
        statusEl.classList.add("hidden");
        listEl.classList.remove("hidden");
      } catch (err) {
        console.error(err);
        statusEl.textContent = err.message || "failed to load recent certificates";
      }
    }
  
    // -----------------------------
    // history button (optional)
    // -----------------------------
    async function viewHistory() {
      if (!selectedPatientId?.value) {
        showToast("Select a patient to view their certificate history");
        return;
      }
  
      try {
        const res = await fetch(
          `../backend/doctor_medical_certificate_history.php?patient_id=${encodeURIComponent(selectedPatientId.value)}`,
          { credentials: "same-origin" }
        );
        const data = await res.json();
        if (!data.ok) throw new Error(data.error || "failed to load history");
  
        const lines = (data.items || []).map(
          (x) => `${x.certificate_number} • ${x.leave_from} to ${x.leave_to} • ${x.diagnosis}`
        );
        showToast(lines.length ? lines.join("\n") : "no certificates yet");
      } catch (err) {
        console.error(err);
        showToast(err.message || "failed to load history");
      }
    }
  
    // -----------------------------
    // submit (save to DB)
    // -----------------------------
    async function submitCertificate(e) {
      e.preventDefault();
  
      if (!selectedTemplateType?.value) {
        showToast("Please choose a certificate template");
        const msg = $("templateRequiredMsg");
        if (msg) msg.classList.remove("hidden");
        return;
      }
  
      if (!selectedPatientId?.value) {
        showToast("Please select a patient first");
        return;
      }
  
      const diagnosisVal = $("diagnosis")?.value;
      if (!diagnosisVal) {
        showToast("Please select diagnosis");
        return;
      }
      if (!fromDate?.value || !toDate?.value) {
        showToast("Please select leave period");
        return;
      }
  
      const payload = {
        patient_id: Number(selectedPatientId.value),
        template_type: selectedTemplateType.value,
  
        diagnosis: $("diagnosis").options[$("diagnosis").selectedIndex].text,
        restriction_level: $("restrictionLevel")?.value,
        leave_from: fromDate.value,
        leave_to: toDate.value,
  
        additional_instructions: ($("additionalInstructions")?.value || "").trim(),
        follow_up_date: $("followUpDate")?.value || null,
  
        include_digital_stamp: includeDigitalStamp?.checked ? 1 : 0,
        include_qr_code: includeQRCode?.checked ? 1 : 0,
      };
  
      try {
        const res = await fetch("../backend/doctor_medical_certificate_create.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          credentials: "same-origin",
          body: JSON.stringify(payload),
        });
  
        const raw = await res.text();
        let data;
  
        try {
          data = JSON.parse(raw);
        } catch {
          console.error("Create API returned non-JSON:", raw);
          throw new Error("Create API returned HTML/non-JSON. Check Network → Response.");
        }
  
        if (!data.ok) throw new Error(data.error || "failed to save certificate");
  
        showToast(`Medical certificate saved! Certificate No: ${data.certificate_number}`);
  
        // refresh recent list (patient mode)
        loadRecentCertificates(selectedPatientId.value);
  
        if (data.download_url) window.open(data.download_url, "_blank");
      } catch (err) {
        console.error(err);
        showToast(err.message || "error saving certificate");
      }
    }
  
    // -----------------------------
    // buttons
    // -----------------------------
    function setupButtons() {
      // if you removed print preview UI, don't call window.print here
      const refreshBtn = $("btnRefreshCertificates");
      if (refreshBtn) {
        refreshBtn.addEventListener("click", () => {
          // if no patient selected, show doctor recent
          loadRecentCertificates(selectedPatientId?.value ? selectedPatientId.value : null);
        });
      }
  
      viewHistoryBtn?.addEventListener("click", viewHistory);
  
      downloadPDFBtn?.addEventListener("click", () => {
        showToast("Save a certificate first, then open it from the recent list (eye icon).");
      });
    }
  
    // -----------------------------
    // init
    // -----------------------------
    function initDefaults() {
      const today = new Date();
      if (fromDate) fromDate.value = toISODate(today);
      if (toDate) toDate.value = toISODate(today);
      if (durationDisplay) durationDisplay.textContent = "1 day";
    }
  
    function bindEvents() {
      selectPatientBtn?.addEventListener("click", openPatientModal);
      closePatientModal?.addEventListener("click", closePatientModalFn);
  
      patientSelectionModal?.addEventListener("click", (e) => {
        if (e.target === patientSelectionModal) closePatientModalFn();
      });
  
      patientSearchInput?.addEventListener("input", () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => searchPatients(patientSearchInput.value.trim()), 250);
      });
  
      fromDate?.addEventListener("change", updateDurationUI);
      toDate?.addEventListener("change", updateDurationUI);
  
      certificateForm?.addEventListener("submit", submitCertificate);
    }
  
    document.addEventListener("DOMContentLoaded", () => {
      initDefaults();
      bindEvents();
      setupTemplateCards();
      setupButtons();
      updateDurationUI();
  
      // ✅ load doctor recent history by default
      loadRecentCertificates(null);
    });
  })();
  