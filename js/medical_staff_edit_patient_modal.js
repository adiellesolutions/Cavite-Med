document.addEventListener("DOMContentLoaded", () => {
    const modal = document.getElementById("editPatientModal");
    const overlay = document.getElementById("editPatientOverlay");
    const btnClose = document.getElementById("btnCloseEditModal");
    const btnCancel = document.getElementById("btnCancelEdit");
    const form = document.getElementById("editPatientForm");
    const msg = document.getElementById("editPatientMsg");
    const btnEdit = document.getElementById("btnEditPatient");
  
    const f = (id) => document.getElementById(id);
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
  
    function setMsg(text, ok = false) {
      if (!msg) return;
      msg.textContent = text || "";
      msg.className = ok ? "text-sm text-success" : "text-sm text-error";
    }
  
    function openModal() {
      if (!modal) return;
      modal.classList.remove("hidden");
      document.body.style.overflow = "hidden";
    }
  
    function closeModal() {
      if (!modal) return;
      modal.classList.add("hidden");
      document.body.style.overflow = "";
      setMsg("");
    }
  
    function fillFormFromPatient(p) {
      // if any field is missing, stop to avoid JS crash
      if (!fields.patient_id) {
        console.error("Edit modal fields not found. Check your ep_* IDs in HTML.");
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
      return true;
    }
  
    // OPEN modal
    if (btnEdit) {
      btnEdit.addEventListener("click", () => {
        const p = window.__selectedPatient;
  
        if (!p || !p.patient_id) {
          setMsg("Select a patient first.");
          return;
        }
  
        const ok = fillFormFromPatient(p);
        if (!ok) return;
  
        openModal();
      });
    }
  
    // CLOSE modal
    [overlay, btnClose, btnCancel].forEach((el) => {
      if (!el) return;
      el.addEventListener("click", closeModal);
    });
  
    // ESC to close
    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape" && modal && !modal.classList.contains("hidden")) closeModal();
    });
  
    // SAVE
    if (form) {
      form.addEventListener("submit", async (e) => {
        e.preventDefault();
  
        try {
          setMsg("");
          const fd = new FormData(form);
  
          const res = await fetch("../backend/medical_staff_update_patient.php", {
            method: "POST",
            body: fd,
            credentials: "same-origin",
          });
  
          const data = await res.json();
          if (!data.ok) throw new Error(data.error || "Update failed");
  
          closeModal();
  
          // refresh UI
          if (typeof window.loadPatient === "function") {
            window.loadPatient(String(data.patient_id));
          } else {
            const url = new URL(window.location.href);
            url.searchParams.set("patient_id", data.patient_id);
            window.location.href = url.toString();
          }
        } catch (err) {
          console.error(err);
          setMsg(err.message || "Failed to save.");
        }
      });
    }
  });
  