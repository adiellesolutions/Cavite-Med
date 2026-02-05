document.addEventListener("DOMContentLoaded", () => {
    loadDisposeStats();
});

function loadDisposeStats() {
    fetch("../backend/encoder_disposal_stats.php")
        .then(res => res.json())
        .then(data => {
            if (data.error) {
                console.error(data.error);
                return;
            }

            document.getElementById("totalExpiredCount").textContent =
                data.totalExpired;

            document.getElementById("expiringSoonCount").textContent =
                data.expiringSoon;
        })
        .catch(err => {
            console.error("Stats load failed:", err);
        });
}
