document.getElementById("disposalForm").addEventListener("submit", e => {
    e.preventDefault();

    const payload = {
        medicine_id: document.getElementById("medicineSelect").value,
        batch_number: document.getElementById("batchNumber").value,
        expiry_date: new Date().toISOString().slice(0,10), // or store separately
        quantity: document.getElementById("disposalQuantity").value,
        total_value: document.getElementById("disposalValue").value,
        disposal_method: document.getElementById("disposalMethod").value,
        disposal_date: document.getElementById("disposalDate").value
    };

    fetch("../backend/encoder_disposal_add.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            alert("Disposal record saved");
            modal.classList.add("hidden");
            document.getElementById("disposalForm").reset();
            loadDisposalRecords(); // reload table
        } else {
            alert("Failed to save record");
        }
    });
});
