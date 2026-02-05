document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("disposalForm");
    if (!form) return;

    form.addEventListener("submit", handleDisposalSubmit);
});

/* =========================================================
   SUBMIT HANDLER
========================================================= */
function handleDisposalSubmit(e) {
    e.preventDefault();

    const payload = {
        id: disposalMode === "edit"
            ? document.getElementById("editDisposalId").value
            : null,

        medicine_id: document.getElementById("medicineSelect").value,
        batch_number: document.getElementById("batchNumber").value,
        quantity: document.getElementById("disposalQuantity").value,
        expiry_date: document.getElementById("expiryDate").value,
        total_value: document.getElementById("disposalValue").value,
        disposal_method: document.getElementById("disposalMethod").value,
        disposal_date: document.getElementById("disposalDate").value,
        notes: document.getElementById("disposalNotes").value
    };

    const endpoint =
        disposalMode === "edit"
            ? "../backend/encoder_disposal_update.php"
            : "../backend/encoder_disposal_add.php";

    console.log("MODE:", disposalMode);
    console.log("PAYLOAD:", payload);

    fetch(endpoint, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload)
    })
        .then(res => res.json())
        .then(res => {
            if (!res.success) {
                alert(res.message || "Operation failed");
                return;
            }

            closeDisposalModal();
            loadDisposalRecords();
        })
        .catch(err => {
            console.error("Save failed:", err);
            alert("Request failed. Check console.");
        });
}
