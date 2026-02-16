document.addEventListener("click", function (e) {
    const btn = e.target.closest(".restoreItemBtn");
    if (!btn) return;

    const id = btn.dataset.id;

    if (!id) {
        console.error("Missing disposal ID");
        return;
    }

    if (!confirm("Are you sure you want to restore this disposal record?")) {
        return;
    }

    restoreDisposalRecord(id);
});

function restoreDisposalRecord(id) {
    fetch("../backend/encoder_disposal_restore.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({ id })
    })
    .then(res => res.json())
    .then(response => {
        if (!response.success) {
            alert(response.message || "Failed to restore record");
            return;
        }

        // Reload table
        if (typeof loadDisposalRecords === "function") {
            loadDisposalRecords();
        }
    })
    .catch(err => {
        console.error("Restore error:", err);
        alert("Something went wrong while restoring.");
    });
}
