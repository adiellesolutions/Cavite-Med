document.addEventListener("DOMContentLoaded", () => {

    /* ======================
       EDIT SUPPLIER
    ====================== */
    document.querySelectorAll(".editSupplierBtn").forEach(btn => {
        btn.addEventListener("click", () => {

            const modal = document.getElementById("addSupplierModal");
            modal.classList.remove("hidden");

            // Fill form
            document.querySelector("[name='supplier_name']").value = btn.dataset.name;
            document.querySelector("[name='supplier_type']").value = btn.dataset.type;
            document.querySelector("[name='contact_person']").value = btn.dataset.contact;
            document.querySelector("[name='contact_number']").value = btn.dataset.phone;
            document.querySelector("[name='email']").value = btn.dataset.email;
            document.querySelector("[name='address']").value = btn.dataset.address;

            // Set ID
            document.getElementById("editSupplierId").value = btn.dataset.id;

            // Change form action
            document.querySelector("#addSupplierModal form")
                .action = "../backend/encoder_inventory_supplier_update.php";
        });
    });

    /* ======================
       DELETE SUPPLIER
    ====================== */
    document.querySelectorAll(".deleteSupplierBtn").forEach(btn => {
        btn.addEventListener("click", () => {

            const supplierId = btn.dataset.id;
            const supplierName = btn.dataset.name;

            if (!confirm(`Delete supplier "${supplierName}"? This cannot be undone.`)) {
                return;
            }

            fetch("../backend/encoder_inventory_supplier_delete.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `id=${supplierId}`
            })
            .then(res => res.text())
            .then(() => location.reload());
        });
    });

});
