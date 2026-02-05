function loadDisposalMedicines() {
    fetch("../backend/encoder_disposal_medicinedropdown.php")
        .then(res => res.json())
        .then(data => {
            const select = document.getElementById("medicineSelect");
            select.innerHTML = `<option value="">Select Medicine</option>`;

            data.forEach(med => {
                const opt = document.createElement("option");
                opt.value = med.id;
                opt.textContent = `${med.medicine_name} (${med.batch_number})`;
                opt.dataset.batch = med.batch_number;
                opt.dataset.expiry = med.expiry_date;
                select.appendChild(opt);
            });
        });
}
