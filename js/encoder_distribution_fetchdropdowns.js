document.addEventListener("DOMContentLoaded", function () {
    loadDistributionDropdowns();
});

function loadDistributionDropdowns() {

    fetch('../backend/encoder_distribution_fetchdropdowns.php')
        .then(res => res.json())
        .then(data => {

            if (!data.success) return;

            const createHealth = document.getElementById('health_center_id');
            const editHealth = document.getElementById('edit_health_center');
            const filterHealth = document.getElementById('healthCenterFilter');

            const createMedicine = document.getElementById('medicine_id');
            const editMedicine = document.getElementById('edit_medicine');

            /* ======================
               RESET DROPDOWNS
            ======================= */

            if (createHealth)
                createHealth.innerHTML = '<option value="">Select Health Center</option>';

            if (editHealth)
                editHealth.innerHTML = '<option value="">Select Health Center</option>';

            if (filterHealth)
                filterHealth.innerHTML = '<option value="">All Health Centers</option>';

            if (createMedicine)
                createMedicine.innerHTML = '<option value="">Select Medicine</option>';

            if (editMedicine)
                editMedicine.innerHTML = '<option value="">Select Medicine</option>';

            /* ======================
               HEALTH CENTERS
            ======================= */

            data.health_centers.forEach(center => {

                const option = `
                    <option value="${center.id}">
                        ${center.center_name}
                    </option>
                `;

                if (createHealth) createHealth.innerHTML += option;
                if (editHealth) editHealth.innerHTML += option;
                if (filterHealth) filterHealth.innerHTML += option;
            });

            /* ======================
               MEDICINES
            ======================= */

            data.medicines.forEach(med => {

                const option = `
                    <option 
                        value="${med.id}"
                        data-barcode="${med.barcode}"
                    >
                        ${med.medicine_name}
                    </option>
                `;

                if (createMedicine) createMedicine.innerHTML += option;
                if (editMedicine) editMedicine.innerHTML += option;
            });

        })
        .catch(err => console.error("Dropdown load error:", err));
}
