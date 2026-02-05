// CAVITE-MED/js/global_search_autocomplete.js
document.addEventListener("DOMContentLoaded", () => {
    const globalSearch = document.getElementById("globalSearch");
    const searchAutocomplete = document.getElementById("searchAutocomplete");
    const autocompleteResults = document.getElementById("autocompleteResults");
  
    // If search bar is not on this page, do nothing
    if (!globalSearch || !searchAutocomplete || !autocompleteResults) return;
  
    let searchTimer = null;
  
    function escapeHtml(str) {
      return String(str ?? "")
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
    }
  
    async function apiAutocomplete(query) {
      const res = await fetch(`../backend/medical_staff_patient_autocomplete.php?q=${encodeURIComponent(query)}`, {
        credentials: "same-origin",
      });
      const data = await res.json();
      if (!data.ok) throw new Error(data.error || "Autocomplete failed");
      return data.results || [];
    }
  
    // OPTIONAL: when you click a result, fetch full patient info
    async function apiGetPatient(patientId) {
      const res = await fetch(`../backend/medical_staff_patient_get.php?patient_id=${encodeURIComponent(patientId)}`, {
        credentials: "same-origin",
      });
      const data = await res.json();
      if (!data.ok) throw new Error(data.error || "Patient fetch failed");
      return data.patient;
    }
  
    function closeDropdown() {
      searchAutocomplete.classList.add("hidden");
      autocompleteResults.innerHTML = "";
    }
  
    function renderAutocomplete(results) {
      if (!results.length) {
        closeDropdown();
        return;
      }
  
      autocompleteResults.innerHTML = results
        .map(
          (r) => `
          <div class="px-3 py-2 hover:bg-secondary-50 rounded cursor-pointer transition-colors"
               data-patient-id="${escapeHtml(r.patient_id)}">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-medium text-text-primary">${escapeHtml(r.full_name)}</p>
                <p class="text-xs text-text-secondary">
                  MRN: ${escapeHtml(r.mrn)}${r.phone ? ` • ${escapeHtml(r.phone)}` : ""}
                </p>
              </div>
              <svg class="w-4 h-4 text-text-tertiary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
              </svg>
            </div>
          </div>
        `
        )
        .join("");
  
      searchAutocomplete.classList.remove("hidden");
  
      // Click event for each result
      autocompleteResults.querySelectorAll("[data-patient-id]").forEach((item) => {
        item.addEventListener("click", async () => {
          const patientId = item.getAttribute("data-patient-id");
  
          // Option A: redirect (simple)
          // window.location.href = `patient_records_management_portal.php?patient_id=${encodeURIComponent(patientId)}`;
  
          // Option B: fetch full details (your current code)
          try {
            const patient = await apiGetPatient(patientId);
            console.log("Loaded patient:", patient);
            alert(`Loaded: ${patient.full_name} (MRN: ${patient.mrn})`);
          } catch (e) {
            console.error(e);
            alert("Failed to load patient details.");
          }
  
          closeDropdown();
          globalSearch.value = "";
        });
      });
    }
  
    globalSearch.addEventListener("input", (e) => {
      const q = e.target.value.trim();
      clearTimeout(searchTimer);
  
      if (q.length < 2) {
        closeDropdown();
        return;
      }
  
      searchTimer = setTimeout(async () => {
        try {
          const results = await apiAutocomplete(q);
          renderAutocomplete(results);
        } catch (err) {
          console.error(err);
          closeDropdown();
        }
      }, 250);
    });
  
    // close dropdown when clicking outside
    document.addEventListener("click", (e) => {
      if (!globalSearch.contains(e.target) && !searchAutocomplete.contains(e.target)) {
        closeDropdown();
      }
    });
  
    // Keyboard shortcut Ctrl+K
    document.addEventListener("keydown", (e) => {
      if ((e.ctrlKey || e.metaKey) && e.key === "k") {
        e.preventDefault();
        globalSearch.focus();
      }
    });
  });
  