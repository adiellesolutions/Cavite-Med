document.addEventListener("DOMContentLoaded", () => {
  const DEBUG = true;

  const log = (...a) => DEBUG && console.log("[EDIT MODAL]", ...a);
  const warn = (...a) => DEBUG && console.warn("[EDIT MODAL]", ...a);
  const err = (...a) => DEBUG && console.error("[EDIT MODAL]", ...a);

  // =========================
  // REQUIRED DOM (based on your HTML)
  // =========================
  const modal = document.getElementById("editPatientModal");
  const btnClose = document.getElementById("btnCloseEditModal");
  const btnCancel = document.getElementById("btnCancelEdit");
  const form = document.getElementById("editPatientForm");
  const msg = document.getElementById("editPatientMsg");
  const btnEdit = document.getElementById("btnEditPatient");

  // ✅ PROOF that file is loaded
  log("JS LOADED ✅");

  // ✅ DOM check
  log("DOM CHECK:", {
    modal: !!modal,
    btnClose: !!btnClose,
    btnCancel: !!btnCancel,
    form: !!form,
    msg: !!msg,
    btnEdit: !!btnEdit,
  });

  // If modal or edit button missing, nothing will work
  if (!modal) err("Missing #editPatientModal in HTML");
  if (!btnEdit) err("Missing #btnEditPatient (Edit button) in HTML");
  if (!form) err("Missing #editPatientForm in HTML");

  // ✅ IMPORTANT: scope inside modal para sure
  const f = (id) => (modal ? modal.querySelector("#" + id) : null);

  // =========================
  // helpers
  // =========================
  function setMsg(text, ok = false) {
    if (!msg) return;
    msg.textContent = text || "";
    msg.className = ok ? "text-sm text-success" : "text-sm text-error";
  }

  function openModal() {
    if (!modal) return;
    modal.classList.remove("hidden");
    document.body.style.overflow = "hidden";
    log("Modal opened");
  }

function closeModal() {
  if (!modal) return;
  modal.classList.add("hidden");
  document.body.style.overflow = "";
  setMsg("");

  // Clear emergency fields on close
  setVal("ep_ec1_full_name", "");
  setVal("ep_ec1_relationship", "");
  setVal("ep_ec1_phone", "");
  setVal("ep_ec1_email", "");
  setVal("ep_ec1_address", "");
  setChecked("ep_ec1_is_primary", false);

  setVal("ep_ec2_full_name", "");
  setVal("ep_ec2_relationship", "");
  setVal("ep_ec2_phone", "");
  setVal("ep_ec2_email", "");
  setVal("ep_ec2_address", "");
  setChecked("ep_ec2_is_primary", false);

  log("Modal closed & emergency cleared");
}


  const setVal = (id, val) => {
    const el = f(id);
    if (!el) return warn("Missing element:", id);
    el.value = val ?? "";
    log("setVal", id, "=>", el.value);
  };

  const setChecked = (id, val) => {
    const el = f(id);
    if (!el) return warn("Missing element:", id);
    el.checked = String(val ?? 0) === "1";
    log("setChecked", id, "=>", el.checked, "(raw:", val, ")");
  };



  // =========================
// EMERGENCY PRIMARY CONTROL
// =========================

const ec1Primary = f("ep_ec1_is_primary");
const ec2Primary = f("ep_ec2_is_primary");

if (ec1Primary && ec2Primary) {

  ec1Primary.addEventListener("change", function () {
    if (this.checked) ec2Primary.checked = false;
  });

  ec2Primary.addEventListener("change", function () {
    if (this.checked) ec1Primary.checked = false;
  });

}

  // =========================
  // PERSONAL FIELDS (based on your HTML ids)
  // =========================
  const fields = {
    patient_id: f("ep_patient_id"),
    first_name: f("ep_first_name"),
    middle_name: f("ep_middle_name"),
    last_name: f("ep_last_name"),
    preferred_name: f("ep_preferred_name"),
    marital_status: f("ep_marital_status"),
    occupation: f("ep_occupation"),
    preferred_language: f("ep_preferred_language"),
    date_of_birth: f("ep_date_of_birth"),
    gender: f("ep_gender"),
    blood_type: f("ep_blood_type"),
    status: f("ep_status"),
    phone: f("ep_phone"),
    email: f("ep_email"),
    address_line: f("ep_address_line"),
    city: f("ep_city"),
    state: f("ep_state"),
    zip_code: f("ep_zip_code"),
  };

  function fillFormFromPatient(p) {
    if (!fields.patient_id) {
      err("Personal fields missing. Check ep_* IDs in HTML.");
      return false;
    }

    fields.patient_id.value = p.patient_id ?? "";
    fields.first_name.value = p.first_name ?? "";
    fields.middle_name.value = p.middle_name ?? "";
    fields.last_name.value = p.last_name ?? "";
    fields.preferred_name.value = p.preferred_name ?? "";
    fields.marital_status.value = p.marital_status ?? "";
    fields.occupation.value = p.occupation ?? "";
    fields.preferred_language.value = p.preferred_language ?? "";
    fields.date_of_birth.value = p.date_of_birth ?? "";
    fields.gender.value = p.gender ?? "other";
    fields.blood_type.value = p.blood_type ?? "";
    fields.status.value = p.status ?? "active";
    fields.phone.value = p.phone ?? "";
    fields.email.value = p.email ?? "";
    fields.address_line.value = p.address_line ?? "";
    fields.city.value = p.city ?? "";
    fields.state.value = p.state ?? "";
    fields.zip_code.value = p.zip_code ?? "";

    log("Filled PERSONAL ✅");
    return true;
  }

  // =========================
  // CLICK EDIT -> FETCH -> FILL -> OPEN
  // =========================
  if (btnEdit) {
    btnEdit.addEventListener("click", async () => {
      log("Edit clicked");

      const selected = window.__selectedPatient;
      log("window.__selectedPatient =", selected);

      if (!selected || !selected.patient_id) {
        setMsg("Select a patient first.");
        warn("No selected patient/patient_id");
        return;
      }

      try {
        setMsg("");

        // ✅ safest URL for your structure
        const url = new URL("/HIMS/backend/medical_staff_patient_get.php", window.location.origin);
        url.searchParams.set("patient_id", String(selected.patient_id));

        log("FETCH URL:", url.toString());

        const res = await fetch(url.toString(), { credentials: "same-origin" });

        log("FETCH STATUS:", res.status, res.statusText);
        log("FETCH content-type:", res.headers.get("content-type"));
        log("FETCH response url:", res.url);
        log("FETCH redirected?:", res.redirected);

        const raw = await res.text();
        log("RAW preview:", raw.slice(0, 300));

        let data;
        try {
          data = JSON.parse(raw);
        } catch (e) {
          err("NOT JSON RESPONSE. RAW preview above.");
          throw new Error("Server did not return JSON. Check console RAW preview.");
        }

        log("PARSED JSON:", data);

        if (!data.ok) throw new Error(data.error || "Failed to fetch patient");

        // ✅ show which parts exist
        log("HAS patient?", !!data.patient, data.patient);
        log("HAS medical?", !!data.medical, data.medical);
        log("HAS insurance?", !!data.insurance, data.insurance);
        log("HAS emergency?", !!data.emergency, data.emergency);

        const ok = fillFormFromPatient(data.patient || {});
        if (!ok) return;

        // MEDICAL
        const m = data.medical || {};
        setVal("ep_allergies", m.allergies);
        setVal("ep_chronic_conditions", m.chronic_conditions);
        setVal("ep_current_medications", m.current_medications);
        setVal("ep_immunization_status", m.immunization_status ?? "unknown");
        log("Filled MEDICAL ✅");

        // INSURANCE
        const i = data.insurance || {};
        setVal("ep_coverage_type", i.coverage_type ?? "primary");
        setVal("ep_provider_name", i.provider_name);
        setVal("ep_policy_number", i.policy_number);
        setVal("ep_group_number", i.group_number);
        setVal("ep_effective_date", i.effective_date);
        setVal("ep_subscriber_name", i.subscriber_name);
        setVal("ep_relationship", i.relationship ?? "self");
        setVal("ep_verified_status", i.verified_status ?? "unverified");
        log("Filled INSURANCE ✅");

// ===============================
// EMERGENCY (OPTIONAL 2 CONTACTS)
// ===============================

const emergencyList = Array.isArray(data.emergency) ? data.emergency : [];

// Reset everything first (important when switching patients)
setVal("ep_ec1_full_name", "");
setVal("ep_ec1_relationship", "");
setVal("ep_ec1_phone", "");
setVal("ep_ec1_email", "");
setVal("ep_ec1_address", "");
setChecked("ep_ec1_is_primary", false);

setVal("ep_ec2_full_name", "");
setVal("ep_ec2_relationship", "");
setVal("ep_ec2_phone", "");
setVal("ep_ec2_email", "");
setVal("ep_ec2_address", "");
setChecked("ep_ec2_is_primary", false);

// Fill Contact 1 if exists
if (emergencyList.length >= 1) {
  const c1 = emergencyList[0];

  setVal("ep_ec1_full_name", c1.full_name || "");
  setVal("ep_ec1_relationship", c1.relationship || "");
  setVal("ep_ec1_phone", c1.phone || "");
  setVal("ep_ec1_email", c1.email || "");
  setVal("ep_ec1_address", c1.address || "");
  setChecked("ep_ec1_is_primary", c1.is_primary == 1);
}

// Fill Contact 2 if exists
if (emergencyList.length >= 2) {
  const c2 = emergencyList[1];

  setVal("ep_ec2_full_name", c2.full_name || "");
  setVal("ep_ec2_relationship", c2.relationship || "");
  setVal("ep_ec2_phone", c2.phone || "");
  setVal("ep_ec2_email", c2.email || "");
  setVal("ep_ec2_address", c2.address || "");
  setChecked("ep_ec2_is_primary", c2.is_primary == 1);
}

log("Filled EMERGENCY (OPTIONAL 2 CONTACTS) ✅");


        openModal();
      } catch (e) {
        err("LOAD ERROR:", e);
        setMsg(e.message || "Failed to load patient data.");
      }
    });
  }

  // =========================
  // CLOSE HANDLERS
  // =========================
  if (modal) {
    modal.addEventListener("click", (e) => {
      if (e.target === modal) closeModal();
    });
  }
  if (btnClose) btnClose.addEventListener("click", closeModal);
  if (btnCancel) btnCancel.addEventListener("click", closeModal);

  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape" && modal && !modal.classList.contains("hidden")) closeModal();
  });

  // =========================
  // SAVE (kept basic)
  // =========================
  if (form) {
    form.addEventListener("submit", async (e) => {
      e.preventDefault();

      try {
        setMsg("");
        const fd = new FormData(form);

        if (DEBUG) {
          const obj = {};
          fd.forEach((v, k) => (obj[k] = v));
          log("SUBMIT FORM DATA:", obj);
        }

        const res = await fetch("/HIMS/backend/medical_staff_update_patient.php", {
          method: "POST",
          body: fd,
          credentials: "same-origin",
        });

        log("SUBMIT STATUS:", res.status, res.statusText);

        const raw = await res.text();
        log("SUBMIT RAW preview:", raw.slice(0, 300));

        let data;
        try {
          data = JSON.parse(raw);
        } catch {
          throw new Error("Update response is not JSON. Check SUBMIT RAW preview.");
        }

        log("SUBMIT JSON:", data);

        if (!data.ok) throw new Error(data.error || "Update failed");

        closeModal();

        if (typeof window.loadPatient === "function") {
          const pid = fd.get("patient_id") || data.patient_id;
          window.loadPatient(String(pid));
        } else {
          location.reload();
        }
      } catch (e) {
        err("SAVE ERROR:", e);
        setMsg(e.message || "Failed to save.");
      }
    });
  }
});
