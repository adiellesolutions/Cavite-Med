document.addEventListener("DOMContentLoaded", () => {
  console.log("NEW PATIENT MODAL JS LOADED ✅");

  const modal = document.getElementById("newPatientModal");
  const btnOpen = document.getElementById("newPatientBtn");
  const btnClose = document.getElementById("closeNewPatient");
  const btnCancel = document.getElementById("cancelNewPatient");
  const form = document.getElementById("newPatientForm");
  const msg = document.getElementById("newPatientMsg");

  if (!modal || !btnOpen || !btnClose || !btnCancel || !form) {
    console.error("Missing modal elements.");
    return;
  }

  let isSubmitting = false; // 🔥 prevents duplicate submits

  function setMsg(text, ok = false) {
    if (!msg) return;
    msg.textContent = text || "";
    msg.className = ok ? "text-sm text-success" : "text-sm text-error";
  }

  function openModal() {
    modal.classList.remove("hidden");
    document.body.style.overflow = "hidden";
    setMsg("");
  }

  function closeModal() {
    modal.classList.add("hidden");
    document.body.style.overflow = "";
    setMsg("");
    form.reset();
    isSubmitting = false;
  }

  btnOpen.addEventListener("click", openModal);
  btnClose.addEventListener("click", closeModal);
  btnCancel.addEventListener("click", closeModal);

  modal.addEventListener("click", (e) => {
    if (e.target === modal) closeModal();
  });

  /* =========================
     SUBMIT (SAFE VERSION)
  ========================= */
  form.addEventListener("submit", async (e) => {
    e.preventDefault();

    // 🚫 Prevent double submit
    if (isSubmitting) return;

    isSubmitting = true;
    setMsg("Saving patient...");

    const submitBtn = form.querySelector("button[type='submit']");
    if (submitBtn) submitBtn.disabled = true;

    try {
      const fd = new FormData(form);

      const res = await fetch("../backend/medical_staff_create_patient.php", {
        method: "POST",
        body: fd,
        credentials: "same-origin",
        headers: { "X-Requested-With": "XMLHttpRequest" },
      });

      const text = await res.text();

      let data;
      try {
        data = JSON.parse(text);
      } catch {
        throw new Error("Server returned invalid response.");
      }

      if (!res.ok || !data.success) {
        throw new Error(data.message || "Failed to create patient.");
      }

      setMsg(`Patient created. MRN: ${data.mrn}`, true);

      // small delay for UX
      setTimeout(() => {
        const url = new URL(window.location.href);
        url.searchParams.set("patient_id", data.patient_id);
        window.location.href = url.toString();
      }, 800);

    } catch (err) {
      console.error(err);
      setMsg(err.message || "Failed to create patient.");
      isSubmitting = false;
      if (submitBtn) submitBtn.disabled = false;
    }
  });
});