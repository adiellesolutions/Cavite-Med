document.addEventListener("DOMContentLoaded", () => {

    const modal       = document.getElementById("addItemModal");
    const form        = document.getElementById("addItemForm");
    const editIdInput = document.getElementById("editMedicineId");

    const titleEl     = document.getElementById("addItemModalTitle");
    const cancelBtn   = document.getElementById("cancelAddItem");
    const closeBtn    = document.getElementById("closeModal");
    const addBtn      = document.getElementById("addNewItemBtn");

    if (!modal || !form) return;

    // =========================
    // HARD RESET (EXIT EDIT MODE)
    // =========================
    function resetForm() {
        form.reset();
        if (editIdInput) editIdInput.value = "";
        if (titleEl) titleEl.textContent = "Add New Medicine";

        // 🔥 ALWAYS point to ONE endpoint
        form.action = "../backend/encoder_inventory_add.php";
    }

    // =========================
    // OPEN ADD MODE
    // =========================
    addBtn?.addEventListener("click", () => {
        resetForm();
        modal.classList.remove("hidden");
    });

    // =========================
    // EDIT MODE
    // =========================
    document.querySelectorAll(".editItemBtn").forEach(btn => {
        btn.addEventListener("click", (e) => {
            e.stopPropagation(); // prevent row click

            resetForm(); // kill add state

            const row = btn.closest("tr");
            if (!row) return;

            // 🔁 enable UPDATE mode
            editIdInput.value = row.dataset.id || "";
            if (titleEl) titleEl.textContent = "Update Medicine";

            // 🔥 SAME endpoint
            form.action = "../backend/encoder_inventory_update.php";

            // Fill form
            form.medicine_name.value      = row.dataset.name || "";
            form.medicine_type.value      = (row.dataset.type || "").toLowerCase();
            form.category.value           = row.dataset.category || "";
            form.unit_of_measure.value    = row.dataset.unit || "";
            form.current_stock.value      = row.dataset.stock || 0;
            form.reorder_point.value      = row.dataset.reorder || 0;
            form.unit_price.value         = row.dataset.price || 0;
            form.manufacturing_date.value = row.dataset.manufacturing || "";
            form.expiry_date.value        = row.dataset.expiry || "";
            form.barcode.value            = row.dataset.barcode || "";
            form.batch_number.value       = row.dataset.batch || "";
            form.notes.value              = row.dataset.notes || "";

            // dropdowns
            form.supplier_id.value    = row.dataset.supplierId || "";
            form.funding_source.value = row.dataset.fundingsource || "";

            modal.classList.remove("hidden");
        });
    });

    // =========================
    // DELETE ITEM
    // =========================
    document.querySelectorAll(".deleteItemBtn").forEach(btn => {
        btn.addEventListener("click", (e) => {
            e.stopPropagation(); // ✅ PREVENT detail panel


            const id   = btn.dataset.id;
            const name = btn.dataset.name;

            if (!confirm(`Delete "${name}"? This cannot be undone.`)) return;

            fetch("../backend/encoder_inventory_delete.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `id=${encodeURIComponent(id)}`
            })
            .then(res => res.text())
            .then(() => location.reload())
            .catch(() => alert("Delete failed"));
        });
    });

    // =========================
    // CLOSE MODAL = EXIT ALL STATES
    // =========================
    function closeModal() {
        modal.classList.add("hidden");
        resetForm();
    }

    cancelBtn?.addEventListener("click", closeModal);
    closeBtn?.addEventListener("click", closeModal);

    modal.addEventListener("click", (e) => {
        if (e.target === modal) {
            closeModal();
        }
    });

});
