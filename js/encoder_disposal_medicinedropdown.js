/* =========================================================
   MEDICINE DROPDOWN LOGIC
========================================================= */
document.addEventListener("DOMContentLoaded", () => {
    const medicineSelect = document.getElementById("medicineSelect");
    const qtyInput = document.getElementById("disposalQuantity");

    if (!medicineSelect || !qtyInput) return;

    medicineSelect.addEventListener("change", handleMedicineChange);
    qtyInput.addEventListener("input", calculateDisposalValue);
});

/* =========================================================
   LOAD MEDICINES (CALL WHEN MODAL OPENS)
========================================================= */
function loadDisposalMedicines() {
    const select = document.getElementById("medicineSelect");
    if (!select) return;

    fetch("../backend/encoder_disposal_medicinedropdown.php")
        .then(res => res.json())
        .then(data => {
            select.innerHTML = `<option value="">Select Medicine</option>`;

            data.forEach(med => {
                const opt = document.createElement("option");
                opt.value = med.id;
                opt.textContent = `${med.medicine_name} (${med.batch_number})`;

                opt.dataset.batch = med.batch_number;
                opt.dataset.expiry = med.expiry_date;
                opt.dataset.price = med.unit_price;
                opt.dataset.barcode = med.barcode;

                select.appendChild(opt);
            });
        })
        .catch(err => console.error("Failed to load medicines:", err));
}

/* =========================================================
   HANDLERS
========================================================= */
function handleMedicineChange(e) {
    const opt = e.target.selectedOptions[0];
    if (!opt) return;

    const batchInput = document.getElementById("batchNumber");
    if (batchInput) {
        batchInput.value = opt.dataset.batch || "";
    }

        // ✅ Expiry date (AUTO POPULATE)
    const expiryInput = document.getElementById("expiryDate");
    if (expiryInput) {
        expiryInput.value = opt.dataset.expiry || "";
    }

    calculateDisposalValue();
}

/* =========================================================
   VALUE CALCULATION
========================================================= */
function calculateDisposalValue() {
    const select = document.getElementById("medicineSelect");
    const qtyInput = document.getElementById("disposalQuantity");
    const valueInput = document.getElementById("disposalValue");

    if (!select || !qtyInput || !valueInput) return;

    const opt = select.selectedOptions[0];
    const qty = parseInt(qtyInput.value, 10);

    if (!opt || !opt.dataset.price || !qty || qty <= 0) {
        valueInput.value = "";
        return;
    }

    const unitPrice = parseFloat(opt.dataset.price);
    valueInput.value = (unitPrice * qty).toFixed(2);
}
