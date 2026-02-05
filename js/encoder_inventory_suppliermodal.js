document.addEventListener("DOMContentLoaded", () => {

    const modal = document.getElementById("addSupplierModal");
    const form  = document.getElementById("addSupplierForm");

    const openAddBtn = document.getElementById("addNewSupplierFromModal");
    const closeBtn   = document.getElementById("closeAddSupplierModal");
    const cancelBtn  = document.getElementById("cancelAddSupplier");

    const supplierId = document.getElementById("editSupplierId");

    if (!modal || !form || !openAddBtn) {
        console.warn("Supplier modal elements missing");
        return;
    }

    // 🔹 CONSTANT ACTIONS
    const ADD_ACTION    = "../backend/encoder_inventory_supplier_add.php";
    const UPDATE_ACTION = "../backend/encoder_inventory_supplier_update.php";

    // 🔹 HARD RESET → always return to ADD mode
    function resetToAddMode() {
        form.reset();              // clear all inputs
        supplierId.value = "";     // clear hidden ID
        form.action = ADD_ACTION;  // 🔥 restore INSERT endpoint
    }

    // =========================
    // OPEN → ADD SUPPLIER
    // =========================
    openAddBtn.addEventListener("click", () => {
        resetToAddMode();
        modal.classList.remove("hidden");
    });

    // =========================
    // CLOSE MODAL
    // =========================
    function closeModal() {
        resetToAddMode();
        modal.classList.add("hidden");
    }

    closeBtn?.addEventListener("click", closeModal);
    cancelBtn?.addEventListener("click", closeModal);

    // Click outside modal
    modal.addEventListener("click", (e) => {
        if (e.target === modal) {
            closeModal();
        }
    });

    // =========================
    // EDIT SUPPLIER
    // =========================
    document.querySelectorAll(".editSupplierBtn").forEach(btn => {
        btn.addEventListener("click", () => {

            resetToAddMode(); // clear first

            supplierId.value = btn.dataset.id;

            form.supplier_name.value  = btn.dataset.name || "";
            form.supplier_type.value  = btn.dataset.type || "private";
            form.contact_person.value = btn.dataset.contact || "";
            form.contact_number.value = btn.dataset.phone || "";
            form.email.value          = btn.dataset.email || "";
            form.address.value        = btn.dataset.address || "";

            // 🔥 SWITCH TO UPDATE MODE
            form.action = UPDATE_ACTION;

            modal.classList.remove("hidden");
        });
    });

});
