function loadDistributionDropdowns() {

    console.log("Loading dropdowns...");

    fetch('../backend/encoder_distribution_fetchdropdowns.php')
        .then(response => {
            console.log("Response status:", response.status);
            console.log("Response ok:", response.ok);
            return response.text(); // 👈 get raw text first
        })
        .then(text => {
            console.log("Raw response:", text);

            let data;

            try {
                data = JSON.parse(text);
                console.log("Parsed JSON:", data);
            } catch (e) {
                console.error("JSON parse error:", e);
                return;
            }

            if (!data.success) {
                console.error("Backend returned success = false");
                return;
            }

            const healthSelect = document.getElementById('health_center_id');
            const medicineSelect = document.getElementById('medicine_id');

            if (!healthSelect || !medicineSelect) {
                console.error("Dropdown elements not found in DOM");
                return;
            }

            // Reset
            healthSelect.innerHTML = '<option value="">Select Health Center</option>';
            medicineSelect.innerHTML = '<option value="">Select Medicine</option>';

            console.log("Populating health centers...");
            data.health_centers.forEach(center => {
                healthSelect.innerHTML += `
                    <option value="${center.id}">
                        ${center.center_name}
                    </option>
                `;
            });

            console.log("Populating medicines...");
            data.medicines.forEach(med => {
                medicineSelect.innerHTML += `
                    <option value="${med.id}"
                        data-stock="${med.current_stock}"
                        data-unit="${med.unit_of_measure}">
                        ${med.medicine_name} - Stock: ${med.current_stock} ${med.unit_of_measure}
                    </option>
                `;
            });

            console.log("Dropdown population complete.");
        })
        .catch(error => {
            console.error("Fetch error:", error);
        });
}
