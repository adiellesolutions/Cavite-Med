document.addEventListener("DOMContentLoaded", function () {

    const modal = document.getElementById("editDistributionModal");
    const form = document.getElementById("editDistributionForm");

    if (!modal || !form) return;

    document.addEventListener("click", function (e) {

        const btn = e.target.closest(".editDistributionBtn");
        if (!btn) return;

        modal.classList.add("show");

        const id = btn.dataset.id;
        const quantity = btn.dataset.quantity;
        const status = btn.dataset.status;
        const remarks = btn.dataset.remarks || '';
        const centerId = btn.dataset.center;
        const medicineId = btn.dataset.medicine;

        document.getElementById("edit_distribution_id").value = id;
        document.getElementById("edit_quantity").value = quantity;
        document.getElementById("edit_status").value = status;
        document.getElementById("edit_remarks").value = remarks;

        const healthSelect = document.getElementById("edit_health_center");
        const medicineSelect = document.getElementById("edit_medicine");

        // No artificial delay needed if dropdowns already loaded
        healthSelect.value = centerId;
        medicineSelect.value = medicineId;

        // 🔥 Lock fields if cancelled OR returned
        const isLocked = status === "cancelled" || status === "returned";

        healthSelect.disabled = isLocked;
        medicineSelect.disabled = isLocked;
        document.getElementById("edit_quantity").disabled = isLocked;
    });

    /* =====================
       SUBMIT EDIT FORM
    ====================== */
    form.addEventListener("submit", function (e) {

        e.preventDefault();

        const formData = new FormData(form);

        fetch("../backend/encoder_distribution_update.php", {
            method: "POST",
            body: formData
        })
        .then(res => res.text())
        .then(text => {
            console.log("RAW RESPONSE:", text);
            return JSON.parse(text);
        })
        .then(data => {

            if (!data.success) {
                alert(data.message || "Update failed");
                return;
            }

            alert("Distribution updated successfully!");

            modal.classList.remove("show");

            if (typeof loadDistributions === "function") {
                loadDistributions();
            }

            if (typeof loadDistributionStatistics === "function") {
                loadDistributionStatistics();
            }

        })
        .catch(err => {
            console.error("Update error:", err);
            alert("Server error");
        });
    });


    document.getElementById("cancelEditModal")
        ?.addEventListener("click", () => modal.classList.remove("show"));

    document.getElementById("closeEditModal")
        ?.addEventListener("click", () => modal.classList.remove("show"));

});
