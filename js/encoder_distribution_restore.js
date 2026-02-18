document.addEventListener("click", function (e) {

    const btn = e.target.closest(".restoreDistributionBtn");
    if (!btn) return;

    const distributionId = btn.dataset.id;

    if (!confirm("Restore this distribution? Stock will be returned.")) {
        return;
    }

    const formData = new FormData();
    formData.append("distribution_id", distributionId);

    fetch("../backend/encoder_distribution_restore.php", {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then(data => {

        if (!data.success) {
            alert(data.message || "Failed to restore");
            return;
        }

        alert("Distribution restored successfully!");

        if (typeof loadDistributions === "function") {
            loadDistributions();
        }

    })
    .catch(err => {
        console.error("Restore error:", err);
        alert("Server error");
    });
});
