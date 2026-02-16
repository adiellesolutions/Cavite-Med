function loadArchiveStats() {

    fetch("../backend/encoder_archive_stats.php")
        .then(res => res.json())
        .then(res => {

            document.getElementById("totalArchivedSuppliers").textContent =
                res.suppliers;

            document.getElementById("totalArchivedMedicines").textContent =
                res.medicines;

            document.getElementById("totalArchiveValue").textContent =
                "₱" + parseFloat(res.total_value).toFixed(2);
        })
        .catch(err => {
            console.error("Failed to load archive stats:", err);
        });
}
