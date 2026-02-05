document.addEventListener("DOMContentLoaded", function () {
    const sortSelect = document.getElementById("sortBy");
    const tbody = document.getElementById("inventoryTableBody");

    sortSelect.addEventListener("change", function () {
        const rows = Array.from(tbody.querySelectorAll("tr"));
        const sortType = this.value;

        rows.sort((a, b) => {
            const nameA = a.cells[0].innerText.toLowerCase();
            const nameB = b.cells[0].innerText.toLowerCase();

            const stockA = parseInt(a.cells[2].innerText.replace(/\D/g, "")) || 0;
            const stockB = parseInt(b.cells[2].innerText.replace(/\D/g, "")) || 0;

            const expiryA = new Date(a.cells[4].innerText);
            const expiryB = new Date(b.cells[4].innerText);

            const idA = parseInt(a.dataset.itemId || 0);
            const idB = parseInt(b.dataset.itemId || 0);

            switch (sortType) {
                case "name-asc":
                    return nameA.localeCompare(nameB);

                case "name-desc":
                    return nameB.localeCompare(nameA);

                case "stock-low":
                    return stockA - stockB;

                case "stock-high":
                    return stockB - stockA;

                case "expiry-soon":
                    return expiryA - expiryB;

                case "recently-added":
                    return idB - idA; // assumes higher ID = newer
            }
        });

        // Re-append sorted rows
        rows.forEach(row => tbody.appendChild(row));
    });
});