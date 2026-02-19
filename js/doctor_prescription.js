// /assets/js/doctor_prescription.js

let selectedPatient = null;
let prescriptionItems = [];
let currentDraftId = null;

const el = (id) => document.getElementById(id);

function toast(msg) {
  alert(msg);
}

async function safeJson(res) {
  const text = await res.text();
  try {
    return JSON.parse(text);
  } catch {
    return { ok: false, error: "Invalid JSON response", raw: text };
  }
}

// ------------------------------
// PATIENT MODAL + SEARCH
// ------------------------------
const selectPatientBtn = el("selectPatientBtn");
const patientSelectionModal = el("patientSelectionModal");
const closePatientModal = el("closePatientModal");
const patientSearchInput = el("patientSearchInput");
const selectedPatientInfo = el("selectedPatientInfo");
const noPatientSelected = el("noPatientSelected");

function openPatientModal() {
  if (!patientSelectionModal) return;
  patientSelectionModal.classList.remove("hidden");
  if (patientSearchInput) patientSearchInput.value = "";
  loadPatients("");
}

function closePatientModalFn() {
  if (!patientSelectionModal) return;
  patientSelectionModal.classList.add("hidden");
}

selectPatientBtn?.addEventListener("click", openPatientModal);
closePatientModal?.addEventListener("click", closePatientModalFn);

patientSelectionModal?.addEventListener("click", (e) => {
  if (e.target === patientSelectionModal) closePatientModalFn();
});

patientSearchInput?.addEventListener("input", () => {
  loadPatients(patientSearchInput.value.trim());
});

// container for patients list
const patientListContainer = (() => {
  let node = document.getElementById("patientList");
  if (!node && patientSelectionModal) {
    // fallback: find a container inside modal (but avoid wiping the whole modal)
    node =
      patientSelectionModal.querySelector("#patientList") ||
      patientSelectionModal.querySelector(".patient-list") ||
      patientSelectionModal.querySelector(".space-y-2");
  }
  return node;
})();

function calcAge(dob) {
  if (!dob) return "";
  const d = new Date(dob);
  if (Number.isNaN(d.getTime())) return "";
  const today = new Date();
  let age = today.getFullYear() - d.getFullYear();
  const m = today.getMonth() - d.getMonth();
  if (m < 0 || (m === 0 && today.getDate() < d.getDate())) age--;
  return age;
}

async function loadPatients(q) {
  if (!patientListContainer) return;

  patientListContainer.innerHTML =
    `<div class="p-3 text-text-secondary">loading...</div>`;

  let res;
  try {
    res = await fetch(
      `../backend/doctor_patients_prescription_search.php?q=${encodeURIComponent(q)}`
    );
  } catch (err) {
    patientListContainer.innerHTML =
      `<div class="p-3 text-error-600">network error</div>`;
    return;
  }

  const data = await safeJson(res);

  if (!data.ok) {
    patientListContainer.innerHTML =
      `<div class="p-3 text-error-600">${data.error || "failed to load patients"}</div>`;
    return;
  }

  if (!Array.isArray(data.patients) || data.patients.length === 0) {
    patientListContainer.innerHTML =
      `<div class="p-3 text-text-secondary text-center">no patients found</div>`;
    return;
  }

  patientListContainer.innerHTML = data.patients
    .map((p) => {
      const fullName = p.full_name || `${p.first_name || ""} ${p.last_name || ""}`.trim();
      const initials =
        p.initials ||
        ((fullName.split(" ")[0]?.[0] || "P") + (fullName.split(" ").slice(-1)[0]?.[0] || "T")).toUpperCase();

      const age = p.date_of_birth ? calcAge(p.date_of_birth) : "";
      const gender = p.gender ? String(p.gender) : "";
      const mrn = p.mrn ? String(p.mrn) : "";

      return `
      <button type="button"
        class="patient-select-btn w-full text-left p-4 rounded-base hover:bg-secondary-50 transition-colors border border-border"
        data-patient-id="${p.patient_id}"
        data-visit-id="${p.visit_id || ""}"
        data-full-name="${encodeURIComponent(fullName)}"
        data-initials="${initials}"
        data-mrn="${encodeURIComponent(mrn)}"
        data-age="${age}"
        data-gender="${encodeURIComponent(gender)}"
        data-blood-type="${encodeURIComponent(p.blood_type || "")}"

      >
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 rounded-full bg-primary-100 text-primary-700 flex items-center justify-center font-semibold flex-shrink-0">
            ${initials}
          </div>
          <div class="flex-1">
            <p class="font-medium text-text-primary">${fullName}</p>
            <p class="text-sm text-text-secondary">
              ID: ${p.patient_id}${mrn ? ` • MRN: ${mrn}` : ""}${age ? ` • Age: ${age}` : ""}${gender ? ` • ${gender}` : ""}
            </p>
          </div>
        </div>
      </button>
      `;
    })
    .join("");

    patientListContainer.querySelectorAll(".patient-select-btn").forEach((btn) => {
        btn.addEventListener("click", async () => {
          const p = {
            patient_id: Number(btn.dataset.patientId || 0),
            visit_id: Number(btn.dataset.visitId || 0) || null,
            mrn: decodeURIComponent(btn.dataset.mrn || ""),
            full_name: decodeURIComponent(btn.dataset.fullName || ""),
            initials: btn.dataset.initials || "PT",
            age: btn.dataset.age || "",
            gender: decodeURIComponent(btn.dataset.gender || ""),
            blood_type: decodeURIComponent(btn.dataset.bloodType || ""),
          };
      
          if (!p.patient_id) return toast("invalid patient selected");
      
          // ✅ reset draft + items when changing patient
          currentDraftId = null;
          prescriptionItems = [];
          renderItems();
      
          selectedPatient = p;
          window.PrescriptionAlerts?.loadPatientAlerts(p.patient_id);

          // ✅ create draft (no IIFE)
          try {
            const res = await fetch("../backend/doctor_prescription_create.php", {
              method: "POST",
              headers: { "Content-Type": "application/json" },
              body: JSON.stringify({
                action: "start_draft",
                patient_id: p.patient_id,
                visit_id: p.visit_id || null,
                allow_substitution: el("allowSubstitution")?.checked ? 1 : 0,
                special_instructions: el("specialInstructions")?.value || ""
              })
            });
      
            const data = await safeJson(res);
            if (!data.ok) return toast("failed to create draft: " + (data.error || ""));
      
            currentDraftId = data.prescription_id;
      
          } catch (err) {
            return toast("network error creating draft");
          }
      
          // ✅ update UI
          noPatientSelected?.classList.add("hidden");
          selectedPatientInfo?.classList.remove("hidden");
      
          el("patientInitials").textContent = p.initials || "PT";
          el("patientFullName").textContent = p.full_name || "---";
          el("patientMeta").textContent = `Patient ID: ${p.patient_id}${p.mrn ? ` • MRN: ${p.mrn}` : ""}`;
          el("patientAge").textContent = p.age || "--";
          el("patientGender").textContent = p.gender || "--";
          el("patientBloodType").textContent = p.blood_type || "--";
      
          el("clinicalAlerts")?.classList.remove("hidden");
          closePatientModalFn();
      
          toast("draft created. you can now add medicines.");
        });
      });
      
    
}

// ------------------------------
// MEDICINE SELECT (DB driven)
// ------------------------------
const medicationSelect = el("medicationSelect");

async function loadMedicines(q = "") {
  if (!medicationSelect) return;

  let res;
  try {
    res = await fetch(
      `../backend/doctor_medicines_search.php?q=${encodeURIComponent(q)}`
    );
  } catch {
    toast("network error loading medicines");
    return;
  }

  const data = await safeJson(res);

  if (!data.ok) {
    toast(data.error || "failed to load medicines");
    return;
  }

  const meds = Array.isArray(data.medicines) ? data.medicines : [];

  medicationSelect.innerHTML =
    `<option value="">Select medication...</option>` +
    meds
      .map(
        (m) =>
          `<option value="${m.id}">${m.medicine_name} (${m.category})</option>`
      )
      .join("");
}

loadMedicines("");

// Global search dropdown (optional)
const globalSearchInput = el("globalMedicationSearch");
const globalSearchResults = el("globalSearchResults");
const clearGlobalSearch = el("clearGlobalSearch");

let searchTimer = null;

globalSearchInput?.addEventListener("input", () => {
  const q = globalSearchInput.value.trim();
  clearGlobalSearch?.classList.toggle("hidden", q.length === 0);

  if (searchTimer) clearTimeout(searchTimer);

  searchTimer = setTimeout(async () => {
    let res;
    try {
      res = await fetch(
        `../backend/doctor_medicines_search.php?q=${encodeURIComponent(q)}`
      );
    } catch {
      if (globalSearchResults) {
        globalSearchResults.classList.remove("hidden");
        globalSearchResults.innerHTML =
          `<div class="p-4 text-center text-text-secondary">network error</div>`;
      }
      return;
    }

    const data = await safeJson(res);

    if (!globalSearchResults) return;

    if (!data.ok) {
      globalSearchResults.classList.remove("hidden");
      globalSearchResults.innerHTML =
        `<div class="p-4 text-center text-text-secondary">${data.error || "failed"}</div>`;
      return;
    }

    const meds = Array.isArray(data.medicines) ? data.medicines : [];

    if (meds.length === 0) {
      globalSearchResults.classList.remove("hidden");
      globalSearchResults.innerHTML =
        `<div class="p-4 text-center text-text-secondary">no medications found</div>`;
      return;
    }

    globalSearchResults.classList.remove("hidden");
    globalSearchResults.innerHTML = meds
      .map(
        (m) => `
      <button type="button"
        class="medication-result w-full text-left p-3 hover:bg-secondary-50 transition-colors border-b border-border last:border-b-0"
        data-id="${m.id}"
      >
        <div class="flex items-center justify-between">
          <div>
            <p class="font-medium text-text-primary">${m.medicine_name}</p>
            <p class="text-sm text-text-secondary">${m.category} • stock: ${m.current_stock ?? "-"}</p>
          </div>
          <svg class="w-5 h-5 text-text-tertiary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
          </svg>
        </div>
      </button>
    `
      )
      .join("");

    globalSearchResults
      .querySelectorAll(".medication-result")
      .forEach((btn) => {
        btn.addEventListener("click", () => {
          if (medicationSelect) medicationSelect.value = btn.dataset.id;
          globalSearchResults.classList.add("hidden");
        });
      });
  }, 250);
});

clearGlobalSearch?.addEventListener("click", () => {
  if (!globalSearchInput || !globalSearchResults) return;
  globalSearchInput.value = "";
  clearGlobalSearch.classList.add("hidden");
  globalSearchResults.classList.add("hidden");
});

document.addEventListener("click", (e) => {
  if (!globalSearchInput || !globalSearchResults) return;
  if (!globalSearchInput.contains(e.target) && !globalSearchResults.contains(e.target)) {
    globalSearchResults.classList.add("hidden");
  }
});

// ------------------------------
// ITEMS LIST (ADD MULTIPLE MEDS)
// ------------------------------
const addItemBtn = el("addItemBtn");
const clearItemsBtn = el("clearItemsBtn");
const itemsTbody = el("itemsTbody");
const itemsCount = el("itemsCount");

function renderItems() {
  if (!itemsTbody || !itemsCount) return;

  itemsCount.textContent = `${prescriptionItems.length} item(s)`;

  if (prescriptionItems.length === 0) {
    itemsTbody.innerHTML = `
      <tr>
        <td colspan="6" class="py-3 text-center text-text-tertiary">
          no medicine added yet
        </td>
      </tr>
    `;
    return;
  }

  itemsTbody.innerHTML = prescriptionItems
    .map(
      (it, idx) => `
    <tr class="border-b border-border">
      <td class="py-2">${it.medicine_label}</td>
      <td class="py-2">${it.dosage_amount}${it.dosage_unit}</td>
      <td class="py-2">${it.frequency_template}</td>
      <td class="py-2">${it.duration_amount} ${it.duration_unit}</td>
      <td class="py-2">${it.route_admin}</td>
      <td class="py-2 text-right">
        <button type="button" class="btn btn-outline btn-sm" data-remove="${idx}">remove</button>
      </td>
    </tr>
  `
    )
    .join("");

  itemsTbody.querySelectorAll("[data-remove]").forEach((btn) => {
    btn.addEventListener("click", () => {
      const i = Number(btn.dataset.remove);
      prescriptionItems.splice(i, 1);
      renderItems();
    });
  });
}

addItemBtn?.addEventListener("click", async () => {
    if (!medicationSelect) return toast("medication select not found");

  const medicine_id = medicationSelect.value;
  if (!medicine_id) return toast("select medication first");

  const dosage_amount = el("dosageAmount")?.value;
  const dosage_unit = el("dosageUnit")?.value;
  const frequency_template = el("frequencyTemplate")?.value;
  const duration_amount = el("durationAmount")?.value;
  const duration_unit = el("durationUnit")?.value;
  const route_admin = el("routeAdmin")?.value;

  const item_instructions = el("specialInstructions")?.value || "";



  
  const medicine_label =
    medicationSelect.options[medicationSelect.selectedIndex]?.text || "medicine";

    if (!currentDraftId) {
        return toast("No draft prescription created yet");
      }
      
      try {
        const res = await fetch("../backend/doctor_prescription_create.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            action: "add_item",
            prescription_id: currentDraftId,
            medicine_id: Number(medicine_id),
            dosage_amount: Number(dosage_amount),
            dosage_unit,
            frequency_template,
            duration_amount: Number(duration_amount),
            duration_unit,
            route_admin,
            item_instructions
          })
        });
      
        const data = await res.json();
        if (!data.ok) {
          return toast(data.error || "Failed to add item");
        }
        window.PrescriptionAlerts?.checkMedicineAlerts(selectedPatient?.patient_id, Number(medicine_id));

      } catch (err) {
        return toast("Network error adding item");
      }
      

  prescriptionItems.push({
    medicine_id: Number(medicine_id),
    medicine_label,
    dosage_amount: Number(dosage_amount),
    dosage_unit,
    frequency_template,
    duration_amount: Number(duration_amount),
    duration_unit,
    route_admin,
    item_instructions,
  });

  // reset medicine inputs only
  medicationSelect.value = "";
  if (el("dosageAmount")) el("dosageAmount").value = "";
  if (el("frequencyTemplate")) el("frequencyTemplate").value = "";
  if (el("durationAmount")) el("durationAmount").value = "";
  if (el("routeAdmin")) el("routeAdmin").value = "";

  // NOTE: keep specialInstructions because you also use it for prescription-level.
  // if you want separate prescription notes field, add new textarea in HTML.

  renderItems();
});

clearItemsBtn?.addEventListener("click", () => {
  prescriptionItems = [];
  renderItems();
});

renderItems();

// ------------------------------
// FINAL SAVE: CREATE PRESCRIPTION (ALL ITEMS)
// ------------------------------
const prescriptionForm = el("prescriptionForm");

// ✅ disable browser validation (removes required blocking)
if (prescriptionForm) {
  prescriptionForm.noValidate = true;
  prescriptionForm.querySelectorAll("[required]").forEach((x) => x.required = false);
}

prescriptionForm?.addEventListener("submit", async (e) => {
  e.preventDefault();

  if (!currentDraftId) return toast("No draft to finalize");
  if (prescriptionItems.length === 0) return toast("add at least 1 medicine first");

  try {
    const res = await fetch("../backend/doctor_prescription_create.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        action: "finalize",
        prescription_id: currentDraftId
      })
    });

    const data = await safeJson(res);
    if (!data.ok) return toast(data.error || "Failed to finalize");

    toast("Prescription finalized!");

    if (data.pdf_url) window.open(data.pdf_url, "_blank");

    // reset
    currentDraftId = null;
    prescriptionItems = [];
    renderItems();

  } catch (err) {
    toast("Network error finalizing");
  }
});
