let currentPage = 1;
let rowsPerPage = 25;

function loadInventory() {
    fetch(`../backend/encoder_inventory_fetch.php?page=${currentPage}&limit=${rowsPerPage}`)
        .then(res => res.text())
        .then(html => {
            document.getElementById("inventoryTableBody").innerHTML = html;
        });

    fetch(`../backend/encoder_inventory_pagination.php?page=${currentPage}&limit=${rowsPerPage}`)
        .then(res => res.json())
        .then(data => {
            document.getElementById("pageInfo").textContent =
                `Page ${data.page} of ${data.totalPages}`;

            document.getElementById("prevBtn").disabled = !data.hasPrev;
            document.getElementById("nextBtn").disabled = !data.hasNext;
        });
}

function changePage(direction) {
    currentPage += direction;
    if (currentPage < 1) currentPage = 1;
    loadInventory();
}

function changeLimit(value) {
    rowsPerPage = parseInt(value);
    currentPage = 1;
    loadInventory();
}

// Initial load
document.addEventListener("DOMContentLoaded", () => {
    document.getElementById("rowsPerPage").value = rowsPerPage;
    loadInventory();
});
