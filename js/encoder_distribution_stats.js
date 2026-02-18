document.addEventListener("DOMContentLoaded", function () {
    loadDistributionStats();
});

function loadDistributionStats() {

    fetch("../backend/encoder_distribution_stats.php")
        .then(res => res.json())
        .then(data => {

            if (!data.success) return;

            document.getElementById("Distributed").textContent =
                data.distributed || 0;

            document.getElementById("pendingCount").textContent =
                data.pending || 0;

            document.getElementById("cancelledCount").textContent =
                data.cancelled || 0;

            document.getElementById("returnedCount").textContent =
                data.returned || 0;
        })
        .catch(err => console.error("Stats fetch error:", err));
}
