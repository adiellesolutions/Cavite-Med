document.addEventListener("DOMContentLoaded", () => {
    const viewAllRecentBtn = document.getElementById("viewAllRecentBtn");
    const recentPatientsModal = document.getElementById("recentPatientsModal");
    const closeRecentPatientsModal = document.getElementById("closeRecentPatientsModal");
  
    const recentPatientsModalList = document.getElementById("recentPatientsModalList");
    const recentPatientsSearch = document.getElementById("recentPatientsSearch");
    const recentPatientsCount = document.getElementById("recentPatientsCount");
  
    if (!viewAllRecentBtn || !recentPatientsModal || !closeRecentPatientsModal) return;
  
    let allRecent = [];
  
    function escapeHtml(str) {
      return String(str ?? "")
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
    }
  
    function initials(name) {
      const parts = String(name || "").trim().split(/\s+/);
      const a = parts[0]?.[0] || "";
      const b = parts[1]?.[0] || "";
      return (a + b).toUpperCase() || "??";
    }
  
    function timeAgo(dateStr) {
      if (!dateStr) return "";
      const dt = new Date(dateStr.replace(" ", "T"));
      if (isNaN(dt)) return "";
      const diff = Math.floor((Date.now() - dt.getTime()) / 1000);
      if (diff < 60) return `${diff}s ago`;
      const m = Math.floor(diff / 60);
      if (m < 60) return `${m}m ago`;
      const h = Math.floor(m / 60);
      if (h < 24) return `${h}h ago`;
      const d = Math.floor(h / 24);
      return `${d}d ago`;
    }
  
    function render(list) {
      if (!recentPatientsModalList) return;
  
      if (!list.length) {
        recentPatientsModalList.innerHTML = `<div class="text-sm text-text-secondary">No results.</div>`;
        if (recentPatientsCount) recentPatientsCount.textContent = "0 patients";
        return;
      }
  
      if (recentPatientsCount) recentPatientsCount.textContent = `${list.length} patient(s)`;
  
      recentPatientsModalList.innerHTML = list
        .map((p) => {
          return `
            <div class="card p-3 card-hover cursor-pointer" data-patient-id="${escapeHtml(p.patient_id)}">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-primary-100 text-primary-700 font-semibold flex items-center justify-center text-sm">
                  ${escapeHtml(initials(p.full_name))}
                </div>
                <div class="flex-1 min-w-0">
                  <h4 class="text-sm font-medium text-text-primary truncate">${escapeHtml(p.full_name)}</h4>
                  <p class="text-xs text-text-secondary">MRN: ${escapeHtml(p.mrn)}</p>
                </div>
                <span class="text-xs text-text-tertiary">${escapeHtml(timeAgo(p.last_viewed_at))}</span>
              </div>
            </div>
          `;
        })
        .join("");
    }
  
    async function loadAllRecentPatients() {
      if (!recentPatientsModalList) return;
  
      recentPatientsModalList.innerHTML = `<div class="text-sm text-text-secondary">Loading...</div>`;
  
      try {
        // ✅ Option A: reuse same endpoint BUT it must return "recent_all"
        // ✅ Option B: create a new endpoint e.g. medical_staff_recent_patients_all.php
        const res = await fetch("../backend/medical_staff_sidebar_lists.php", {
          credentials: "same-origin",
        });
        const data = await res.json();
        if (!data.ok) throw new Error(data.error || "Failed to load recent patients");
  
        // IMPORTANT:
        // If your PHP only returns 5 recent, the modal will also show only 5.
        // Ideally: return `recent_all` from PHP.
        allRecent = data.recent_all || data.recent || [];
  
        render(allRecent);
      } catch (err) {
        console.error(err);
        recentPatientsModalList.innerHTML = `<div class="text-sm text-error">Failed to load recent patients.</div>`;
        if (recentPatientsCount) recentPatientsCount.textContent = "";
      }
    }
  
    function openModal() {
      recentPatientsModal.classList.remove("hidden");
      loadAllRecentPatients();
      if (recentPatientsSearch) recentPatientsSearch.value = "";
    }
  
    function closeModal() {
      recentPatientsModal.classList.add("hidden");
    }
  
    // Open
    viewAllRecentBtn.addEventListener("click", openModal);
  
    // Close button
    closeRecentPatientsModal.addEventListener("click", closeModal);
  
    // Close on outside click
    recentPatientsModal.addEventListener("click", (e) => {
      if (e.target === recentPatientsModal) closeModal();
    });
  
    // Search filter
    if (recentPatientsSearch) {
      recentPatientsSearch.addEventListener("input", () => {
        const q = recentPatientsSearch.value.trim().toLowerCase();
        if (!q) return render(allRecent);
  
        const filtered = allRecent.filter((p) => {
          const name = String(p.full_name || "").toLowerCase();
          const mrn = String(p.mrn || "").toLowerCase();
          return name.includes(q) || mrn.includes(q);
        });
  
        render(filtered);
      });
    }
  
    // Click a patient in modal (event delegation)
    document.addEventListener("click", (e) => {
      const card = e.target.closest("#recentPatientsModal [data-patient-id]");
      if (!card) return;
  
      const patientId = card.getAttribute("data-patient-id");
      if (!patientId) return;
  
      window.location.href = `medical_staff_patient_records.php?patient_id=${encodeURIComponent(patientId)}`;
    });
  
    // Close on ESC (only this modal)
    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape" && !recentPatientsModal.classList.contains("hidden")) {
        closeModal();
      }
    });
  });
  