document.addEventListener("DOMContentLoaded", function () {
    const searchInput = document.getElementById("inventorySearch");
    const tableRows = document.querySelectorAll("#inventoryTableBody tr");

    searchInput.addEventListener("keyup", function () {
        const query = this.value.toLowerCase().trim();

        tableRows.forEach(row => {
            const medicineName = row.cells[0]?.innerText.toLowerCase() || "";
            const category     = row.cells[1]?.innerText.toLowerCase() || "";
            const supplier     = row.cells[5]?.innerText.toLowerCase() || "";

            const match =
                medicineName.includes(query) ||
                category.includes(query) ||
                supplier.includes(query);

            row.style.display = match ? "" : "none";
        });
    });
});
