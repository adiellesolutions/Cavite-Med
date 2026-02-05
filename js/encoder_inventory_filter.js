document.addEventListener("DOMContentLoaded", () => {
    const rows = document.querySelectorAll(".inventory-row");

    const category = document.getElementById("filterCategory");
    const stock = document.getElementById("filterStock");
    const expiry = document.getElementById("filterExpiry");
    const resetBtn = document.getElementById("resetFilters");

    function applyFilters() {
        const catVal = category.value;
        const stockVal = stock.value;
        const expiryVal = expiry.value;

        const today = new Date();

        rows.forEach(row => {
            let show = true;

            const rowCategory = row.dataset.category;
            const rowStatus = row.dataset.status;
            const rowExpiry = new Date(row.dataset.expiry);

            // Category
            if (catVal && rowCategory !== catVal) {
                show = false;
            }

            // Stock status
            if (stockVal && rowStatus !== stockVal) {
                show = false;
            }

            // Expiry logic
            if (expiryVal) {
                const diffDays = Math.ceil(
                    (rowExpiry - today) / (1000 * 60 * 60 * 24)
                );

                if (expiryVal === "expired" && diffDays >= 0) show = false;
                if (expiryVal === "valid" && diffDays <= 90) show = false;
                if (expiryVal === "30" && (diffDays < 0 || diffDays > 30)) show = false;
                if (expiryVal === "90" && (diffDays < 0 || diffDays > 90)) show = false;
            }

            row.style.display = show ? "" : "none";
        });
    }

    category.addEventListener("change", applyFilters);
    stock.addEventListener("change", applyFilters);
    expiry.addEventListener("change", applyFilters);

    resetBtn.addEventListener("click", () => {
        category.value = "";
        stock.value = "";
        expiry.value = "";
        rows.forEach(row => row.style.display = "");
    });
});
