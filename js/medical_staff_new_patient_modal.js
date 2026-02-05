document.addEventListener("DOMContentLoaded", () => {
    console.log("NEW PATIENT MODAL JS LOADED ✅");
  
    const modal = document.getElementById("newPatientModal");
    const btnOpen = document.getElementById("newPatientBtn");
    const btnClose = document.getElementById("closeNewPatient");
    const btnCancel = document.getElementById("cancelNewPatient");
    const form = document.getElementById("newPatientForm");
    const msg = document.getElementById("newPatientMsg");
  
    if (!modal || !btnOpen || !btnClose || !btnCancel || !form) {
      console.error("Missing modal elements:", { modal, btnOpen, btnClose, btnCancel, form, msg });
      return;
    }
  
    function setMsg(text, ok = false) {
      if (!msg) return;
      msg.textContent = text || "";
      msg.className = ok ? "text-sm text-success" : "text-sm text-error";
    }
  
    function openModal() {
      modal.classList.remove("hidden");
      document.body.style.overflow = "hidden";
      setMsg("");
      console.log("Modal opened ✅");
    }
  
    function closeModal() {
      modal.classList.add("hidden");
      document.body.style.overflow = "";
      setMsg("");
      form.reset();
      console.log("Modal closed ✅");
    }
  
    // OPEN/CLOSE
    btnOpen.addEventListener("click", openModal);
    btnClose.addEventListener("click", closeModal);
    btnCancel.addEventListener("click", closeModal);
  
    // outside click close
    modal.addEventListener("click", (e) => {
      if (e.target === modal) closeModal();
    });
  
    // SUBMIT
    form.addEventListener("submit", async (e) => {
      e.preventDefault();
      console.log("Register submit clicked ✅");
  
      setMsg("");
  
      try {
        const fd = new FormData(form);
  
        // quick debug: check if required names are being sent
        console.log("FormData entries:");
        for (const [k, v] of fd.entries()) console.log(k, v);
  
        const res = await fetch("../backend/medical_staff_create_patient.php", {
          method: "POST",
          body: fd,
          credentials: "same-origin",
          headers: { "X-Requested-With": "XMLHttpRequest" },
        });
  
        const text = await res.text();
        console.log("RAW RESPONSE:", text);
  
        let data;
        try {
          data = JSON.parse(text);
        } catch {
          throw new Error("PHP did not return JSON. Check RAW RESPONSE in console.");
        }
  
        if (!res.ok) throw new Error(data.message || `HTTP ${res.status}`);
        if (!data.success) throw new Error(data.message || "Failed to create patient");
        
        setMsg(`Patient created. MRN: ${data.mrn}`, true);
        
        closeModal();
  
        // redirect/load patient
        const url = new URL(window.location.href);
        url.searchParams.set("patient_id", data.patient_id);
        window.location.href = url.toString();
  
      } catch (err) {
        console.error(err);
        setMsg(err.message || "Failed to create patient");
      }
    });
  });
  