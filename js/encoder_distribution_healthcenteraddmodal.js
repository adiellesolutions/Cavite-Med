document.addEventListener("DOMContentLoaded", function () {

    const modal = document.getElementById("addHealthCenterModal");
    const openBtn = document.getElementById("addNewHealthCenterFromModal");
    const closeBtn = document.getElementById("closeAddHealthCenterModal");
    const cancelBtn = document.getElementById("cancelAddHealthCenter");
    const form = document.getElementById("addHealthCenterForm");
    const modalTitle = document.getElementById("healthCenterModalTitle");
    const editIdField = document.getElementById("editHealthCenterId");

    if (!modal || !form) return;

    /* =========================
       OPEN ADD MODE
    ========================= */
    openBtn?.addEventListener("click", function () {

        form.reset();
        editIdField.value = "";

        modalTitle.textContent = "Add Health Center";

        modal.classList.add("show");
    });

    /* =========================
       CLOSE MODAL
    ========================= */
    function closeModal() {
        modal.classList.remove("show");
        form.reset();
        editIdField.value = "";
        modalTitle.textContent = "Add Health Center";
    }

    closeBtn?.addEventListener("click", closeModal);
    cancelBtn?.addEventListener("click", closeModal);

    /* =========================
       EDIT MODE
    ========================= */
    document.addEventListener("click", function(e){

        const btn = e.target.closest(".editHealthCenterBtn");
        if(!btn) return;

        modalTitle.textContent = "Edit Health Center";

        modal.classList.add("show");

        editIdField.value = btn.dataset.id;

        form.center_name.value = btn.dataset.name;
        form.center_type.value = btn.dataset.type;
        form.contact_person.value = btn.dataset.contact;
        form.contact_number.value = btn.dataset.phone;
        form.address.value = btn.dataset.address;
    });

    /* =========================
       SUBMIT FORM
    ========================= */
    form.addEventListener("submit", function (e) {

        e.preventDefault();

        const formData = new FormData(form);

        fetch("../backend/encoder_distribution_healthcenteradd.php", {
            method: "POST",
            body: formData
        })
        .then(res => res.json())
        .then(data => {

            if (!data.success) {
                alert(data.message || "Save failed");
                return;
            }

            alert("Health Center saved successfully!");

            closeModal();

            if (typeof loadHealthCenters === "function") {
                loadHealthCenters();
            }

            if (typeof loadDistributionDropdowns === "function") {
                loadDistributionDropdowns();
            }

        })
        .catch(err => {
            console.error("Error:", err);
            alert("Server error");
        });

    });

});
