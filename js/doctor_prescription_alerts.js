// /assets/js/doctor_prescription_alerts.js
// handles rendering + fetching clinical alerts (allergy/interactions)

const elA = (id) => document.getElementById(id);

async function safeJsonA(res) {
  const text = await res.text();
  try {
    return JSON.parse(text);
  } catch {
    return { ok: false, error: "Invalid JSON response", raw: text };
  }
}

// ---- alert UI builder ----
function alertIcon(type) {
  if (type === "error") {
    return `<svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
    </svg>`;
  }

  // warning (default)
  return `<svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
  </svg>`;
}

function alertCard({ type = "warning", title = "alert", message = "" }) {
  const base = "alert flex gap-3 items-start";
  const cls =
    type === "error" ? "alert-error"
    : type === "warning" ? "alert-warning"
    : "alert-info";

  return `
    <div class="${base} ${cls}">
      ${alertIcon(type)}
      <div>
        <p class="font-medium">${title}</p>
        <p class="text-sm mt-1">${message}</p>
      </div>
    </div>
  `;
}

/**
 * Render alerts into #alertsContainer.
 * Make sure you have this HTML:
 * <div id="clinicalAlerts" class="hidden">
 *   <div id="alertsContainer" class="space-y-3"></div>
 * </div>
 */
function renderClinicalAlerts(alerts = []) {
  const container = elA("alertsContainer");
  const wrapper = elA("clinicalAlerts");

  if (!container) return;

  if (!Array.isArray(alerts) || alerts.length === 0) {
    container.innerHTML = "";
    if (wrapper) wrapper.classList.add("hidden");
    return;
  }

  container.innerHTML = alerts.map(alertCard).join("");
  if (wrapper) wrapper.classList.remove("hidden");
}

// ---- fetch helpers (you can adjust endpoints) ----

/**
 * Load alerts for patient when selected.
 * Expected JSON:
 * { ok:true, alerts:[{type,title,message}, ...] }
 */
async function loadPatientAlerts(patient_id) {
  if (!patient_id) return renderClinicalAlerts([]);

  try {
    const res = await fetch(
      `../backend/doctor_patient_alerts.php?patient_id=${encodeURIComponent(patient_id)}`
    );
    const data = await safeJsonA(res);
    if (!data.ok) return renderClinicalAlerts([]);
    renderClinicalAlerts(data.alerts || []);
  } catch (e) {
    renderClinicalAlerts([]);
  }
}

/**
 * Check alerts when adding a medicine.
 * Expected JSON:
 * { ok:true, alerts:[{type,title,message}, ...] }
 */
async function checkMedicineAlerts(patient_id, medicine_id) {
  if (!patient_id || !medicine_id) return;

  try {
    const res = await fetch(`../backend/doctor_medication_alerts.php`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ patient_id, medicine_id })
    });

    const data = await safeJsonA(res);
    if (!data.ok) return;

    // replace current alerts with new result
    renderClinicalAlerts(data.alerts || []);
  } catch (e) {
    // ignore
  }
}

// expose globally so doctor_prescription.js can call them
window.PrescriptionAlerts = {
  renderClinicalAlerts,
  loadPatientAlerts,
  checkMedicineAlerts
};
