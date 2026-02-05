document.addEventListener("DOMContentLoaded", () => {
    const panel = document.getElementById("detailPanel");
    const closeBtn = document.getElementById("closeDetailPanel");

    document.querySelectorAll(".inventory-row").forEach(row => {
        row.addEventListener("click", () => {
            const stock = parseInt(row.dataset.stock, 10) || 0;
            const price = parseFloat(row.dataset.price) || 0;

            // ===== Header =====
            document.getElementById("detailName").textContent = row.dataset.name;
            document.getElementById("detailMeta").textContent =
                `${row.dataset.type} • ${row.dataset.unit}`;

            // ===== Badges =====
            document.getElementById("detailCategory").textContent = row.dataset.category;
            document.getElementById("detailStatus").textContent =
                row.dataset.status.replace("_", " ").toUpperCase();

            // ===== Stock Info =====
            document.getElementById("detailStock").textContent =
                `${stock} units`;
            document.getElementById("detailReorder").textContent =
                `${row.dataset.reorder} units`;
            document.getElementById("detailPrice").textContent =
                `₱${price.toFixed(2)}`;

            // ✅ Total Value (NEW)
            const totalValue = stock * price;
            document.getElementById("detailTotalValue").textContent =
                `₱${totalValue.toLocaleString(undefined, { minimumFractionDigits: 2 })}`;

            // ===== Supplier & Dates =====

            document.getElementById("detailManufacturing").textContent =
                new Date(row.dataset.manufacturing).toLocaleDateString();

            document.getElementById("detailExpiry").textContent =
                new Date(row.dataset.expiry).toLocaleDateString();

                        // ===== Supplier Details =====
            document.getElementById("detailSupplier").textContent =
                row.dataset.supplier || "—";

            document.getElementById("detailContact").textContent =
                row.dataset.contact || "—";

            document.getElementById("detailPhone").textContent =
                row.dataset.phone || "—";

            document.getElementById("detailEmail").textContent =
                row.dataset.email || "—";

            document.getElementById("detailType").textContent =
                row.dataset.suppliertype || "—";


            // ===== Show Panel =====
            panel.classList.remove("hidden");
        });
    });

    // ===== Close Panel =====
    closeBtn.addEventListener("click", () => {
        panel.classList.add("hidden");
    });
});
