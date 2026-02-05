/* =========================================================
   MODAL STATE
========================================================= */
let disposalMode = "add"; // "add" | "edit"
let pendingEditData = null;

/* =========================================================
   DOM READY
========================================================= */
document.addEventListener("DOMContentLoaded", () => {
    document.getElementById("addNewRecordBtn")
        ?.addEventListener("click", openAddDisposalModal);

    document.getElementById("closeDisposalModal")
        ?.addEventListener("click", closeDisposalModal);

    document.getElementById("cancelDisposal")
        ?.addEventListener("click", closeDisposalModal);
});

/* =========================================================
   OPEN ADD
========================================================= */
function openAddDisposalModal() {
    disposalMode = "add";
    pendingEditData = null;

    resetFormState();

    document.getElementById("disposalModalTitle").textContent =
        "Add Disposal Record";

    document.getElementById("medicineSelect").disabled = false;

    loadDisposalMedicines(); // ✅ ALWAYS reload

    document.getElementById("addDisposalModal").classList.remove("hidden");
}

/* =========================================================
   OPEN EDIT
========================================================= */
document.addEventListener("click", e => {
    const btn = e.target.closest(".editItemBtn");
    if (!btn) return;

    openEditDisposalModal(btn);
});

function openEditDisposalModal(btn) {
    disposalMode = "edit";

    pendingEditData = {
        id: btn.dataset.id,
        medicineId: btn.dataset.medicineId,
        batch: btn.dataset.batch,
        quantity: btn.dataset.quantity,
        value: btn.dataset.value,
        method: btn.dataset.method,
        date: btn.dataset.date,
        notes: btn.dataset.notes
    };

    resetFormState();

    document.getElementById("disposalModalTitle").textContent =
        "Edit Disposal Record";

    document.getElementById("editDisposalId").value = pendingEditData.id;

    loadDisposalMedicines(); // ✅ reload fresh options

    document.getElementById("addDisposalModal").classList.remove("hidden");

    // ⏳ wait until dropdown is populated, then apply edit data
    waitForMedicineOptions(applyEditData);
}

/* =========================================================
   APPLY EDIT DATA (AFTER DROPDOWN LOAD)
========================================================= */
function applyEditData() {
    if (!pendingEditData) return;

    const medicineSelect = document.getElementById("medicineSelect");

    medicineSelect.value = pendingEditData.medicineId;
    medicineSelect.disabled = true;

    medicineSelect.dispatchEvent(
        new Event("change", { bubbles: true })
    );

    document.getElementById("batchNumber").value = pendingEditData.batch || "";
    document.getElementById("disposalQuantity").value = pendingEditData.quantity || "";
    document.getElementById("disposalValue").value = pendingEditData.value || "";
    document.getElementById("disposalMethod").value = pendingEditData.method || "";
    document.getElementById("disposalDate").value = pendingEditData.date || "";
    document.getElementById("disposalNotes").value = pendingEditData.notes || "";
}

/* =========================================================
   HELPERS
========================================================= */
function waitForMedicineOptions(callback) {
    const select = document.getElementById("medicineSelect");

    const timer = setInterval(() => {
        if (select && select.options.length > 1) {
            clearInterval(timer);
            callback();
        }
    }, 50);
}

function resetFormState() {
    const form = document.getElementById("disposalForm");
    if (form) form.reset();

    document.getElementById("editDisposalId").value = "";
    document.getElementById("medicineSelect").disabled = false;
}

/* =========================================================
   CLOSE / RESET
========================================================= */
function closeDisposalModal() {
    resetFormState();

    document.getElementById("disposalModalTitle").textContent =
        "Add Disposal Record";

    document.getElementById("addDisposalModal").classList.add("hidden");

    disposalMode = "add";
    pendingEditData = null;
}
