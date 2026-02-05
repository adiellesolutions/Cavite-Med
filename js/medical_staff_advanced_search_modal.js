// ../js/advanced_search_modal.js
document.addEventListener("DOMContentLoaded", () => {
    const advancedSearchBtn = document.getElementById("advancedSearchBtn");
    const advancedSearchModal = document.getElementById("advancedSearchModal");
    const closeAdvancedSearch = document.getElementById("closeAdvancedSearch");
    const resetAdvancedSearch = document.getElementById("resetAdvancedSearch");
    const advancedSearchForm = document.getElementById("advancedSearchForm");
  
    const doctorSelect = document.getElementById("adv_doctor_id");
    const healthCenterSelect = document.getElementById("adv_health_center_id");
  
    const advResults = document.getElementById("advResults");
    const advResultsCount = document.getElementById("advResultsCount");
  
    // If this page has no advanced modal, do nothing
    if (!advancedSearchBtn || !advancedSearchModal || !advancedSearchForm) return;
  
    function escapeHtml(str) {
      return String(str ?? "")
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
    }
  
    function openModal() {
      advancedSearchModal.classList.remove("hidden");
    }
    function closeModal() {
      advancedSearchModal.classList.add("hidden");
    }
  
    // Open/Close
    advancedSearchBtn.addEventListener("click", openModal);
    if (closeAdvancedSearch) closeAdvancedSearch.addEventListener("click", closeModal);
  
    // Outside click close
    advancedSearchModal.addEventListener("click", (e) => {
      if (e.target === advancedSearchModal) closeModal();
    });
  
    // Reset
    if (resetAdvancedSearch) {
      resetAdvancedSearch.addEventListener("click", () => {
        advancedSearchForm.reset();
        advResultsCount.textContent = "No results";
        advResults.innerHTML = `<div class="text-sm text-text-secondary">Search results will appear here.</div>`;
      });
    }
  
    // Escape key close (only this modal)
    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape") closeModal();
    });
  
    // Load dropdown options (Doctors + Locations)
    async function loadFilters() {
      try {
        const res = await fetch("../backend/medical_staff_advanced_search_filters.php", {
          credentials: "same-origin",
        });
        const data = await res.json();
        if (!data.ok) throw new Error(data.error || "Failed to load filters");
  
        // Doctors
        if (doctorSelect) {
          doctorSelect.innerHTML = `<option value="">All Doctors</option>`;
          (data.doctors || []).forEach((d) => {
            const opt = document.createElement("option");
            opt.value = d.id;
            opt.textContent = d.name;
            doctorSelect.appendChild(opt);
          });
        }
  
        // Locations
        if (healthCenterSelect) {
          healthCenterSelect.innerHTML = `<option value="">All Locations</option>`;
          (data.locations || []).forEach((l) => {
            const opt = document.createElement("option");
            opt.value = l.id;
            opt.textContent = l.name;
            healthCenterSelect.appendChild(opt);
          });
        }
      } catch (err) {
        console.error(err);
        // ok lang kahit walang dropdown data
      }
    }
  
    // Call once when page loads
    loadFilters();
  
    // Submit Advanced Search -> Real DB
    advancedSearchForm.addEventListener("submit", async (e) => {
      e.preventDefault();
  
      advResultsCount.textContent = "Searching...";
      advResults.innerHTML = `<div class="text-sm text-text-secondary">Searching...</div>`;
  
      try {
        const formData = new FormData(advancedSearchForm);
  
        const res = await fetch("../backend/medical_staff_patient_advanced_search.php", {
          method: "POST",
          body: formData,
          credentials: "same-origin",
        });
  
        const data = await res.json();
        if (!data.ok) throw new Error(data.error || "Search failed");
  
        const results = data.results || [];
        advResultsCount.textContent = `${results.length} result(s)`;
  
        if (!results.length) {
          advResults.innerHTML = `<div class="text-sm text-text-secondary">No matching patients found.</div>`;
          return;
        }
  
        advResults.innerHTML = results
          .map(
            (r) => `
            <div class="border border-border rounded-base p-3 hover:bg-secondary-50 cursor-pointer transition-colors"
                 data-patient-id="${escapeHtml(r.patient_id)}">
              <div class="flex items-center justify-between">
                <div>
                  <div class="text-sm font-semibold text-text-primary">${escapeHtml(r.full_name)}</div>
                  <div class="text-xs text-text-secondary">
                    MRN: ${escapeHtml(r.mrn)}${r.phone ? ` • ${escapeHtml(r.phone)}` : ""}
                  </div>
                  ${r.dob ? `<div class="text-xs text-text-tertiary">DOB: ${escapeHtml(r.dob)}</div>` : ""}
                </div>
                <div class="text-xs text-primary">Open →</div>
              </div>
            </div>
          `
          )
          .join("");
  
        // Click result
        advResults.querySelectorAll("[data-patient-id]").forEach((card) => {
          card.addEventListener("click", () => {
            const pid = card.getAttribute("data-patient-id");
            // choose one:
            // window.location.href = `patient_records_management_portal.php?patient_id=${encodeURIComponent(pid)}`;
            alert("Selected patient_id: " + pid);
            closeModal();
          });
        });
      } catch (err) {
        console.error(err);
        advResultsCount.textContent = "Error";
        advResults.innerHTML = `<div class="text-sm text-error">Failed to search. Please try again.</div>`;
      }
    });
  });
  