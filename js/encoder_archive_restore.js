/* ==============================================
   RESTORE STATE
============================================== */
let restoreTarget = {
    type: null, // "supplier" | "medicine"
    id: null
};

/* ==============================================
   INIT
============================================== */
document.addEventListener("DOMContentLoaded", () => {

    // Delegated click for restore buttons inside tables
    document.addEventListener("click", e => {

        const supplierBtn = e.target.closest(".restoreSupplierBtn");
        const medicineBtn = e.target.closest(".restoreMedicineBtn");

        if (supplierBtn) {
            openRestoreConfirm("supplier", supplierBtn.dataset.id);
        }

        if (medicineBtn) {
            openRestoreConfirm("medicine", medicineBtn.dataset.id);
        }
    });

    // Confirm restore
    document.getElementById("confirmRestore")
        .addEventListener("click", handleRestore);

    // Cancel restore
    document.getElementById("cancelRestore")
        .addEventListener("click", closeRestoreModal);
});

/* ==============================================
   OPEN CONFIRM MODAL
============================================== */
function openRestoreConfirm(type, id) {

    restoreTarget.type = type;
    restoreTarget.id = id;

    const message = document.getElementById("restoreConfirmMessage");

    message.textContent =
        type === "supplier"
            ? "Are you sure you want to restore this supplier?"
            : "Are you sure you want to restore this medicine?";

    document.getElementById("restoreConfirmModal")
        .classList.remove("hidden");
}

/* ==============================================
   CLOSE MODAL
============================================== */
function closeRestoreModal() {
    restoreTarget = { type: null, id: null };
    document.getElementById("restoreConfirmModal")
        .classList.add("hidden");
}

/* ==============================================
   HANDLE RESTORE
============================================== */
function handleRestore() {

    if (!restoreTarget.id || !restoreTarget.type) return;

    const endpoint =
        restoreTarget.type === "supplier"
            ? "../backend/encoder_archive_restoresupplier.php"
            : "../backend/encoder_archive_restoremedicine.php";

    fetch(endpoint, {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({
            id: restoreTarget.id
        })
    })
    .then(res => res.json())
    .then(res => {

        if (!res.success) {
            alert(res.message || "Restore failed.");
            return;
        }

        closeRestoreModal();

        /* =========================================
           AUTO REFRESH SECTION
        ========================================= */

        if (restoreTarget.type === "supplier") {

            // If current page becomes empty after restore, go back 1 page
            if (document.querySelectorAll("#suppliersArchiveTable tr").length === 1 && suppliersPage > 1) {
                suppliersPage--;
            }

            loadArchivedSuppliers();

        } else {

            if (document.querySelectorAll("#medicinesArchiveTable tr").length === 1 && medicinesPage > 1) {
                medicinesPage--;
            }

            loadArchivedMedicines();
        }

        // Always refresh statistics
        if (typeof loadArchivedSuppliers === "function") {
            loadArchivedSuppliers();
        }

        if (typeof loadArchivedMedicines === "function") {
            loadArchivedMedicines();
        }

    })
    .catch(err => {
        console.error("Restore error:", err);
        alert("Something went wrong.");
    });
}
