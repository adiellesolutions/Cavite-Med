let currentPage = 1;
const limit = 8;

document.addEventListener("DOMContentLoaded", () => {
    loadDisposalRecords();

    document.getElementById("prevPageBtn").addEventListener("click", () => {
        if (currentPage > 1) {
            currentPage--;
            loadDisposalRecords();
        }
    });

    document.getElementById("nextPageBtn").addEventListener("click", () => {
        currentPage++;
        loadDisposalRecords();
    });
});

/* =========================================================
   FETCH + RENDER
========================================================= */
function loadDisposalRecords() {
    const params = new URLSearchParams({
        page: currentPage,
        limit,
        search: disposalFilters.search,
        status: disposalFilters.status,
        category: disposalFilters.category,
        disposal_method: disposalFilters.disposal_method
    });

    fetch(`../backend/encoder_disposal_fetch.php?page=${currentPage}&limit=${limit}`)
        .then(res => res.json())
        .then(res => {
            const data = res.data;
            const total = res.total;

            const tbody = document.getElementById("disposalRecordsTable");
            tbody.innerHTML = "";

            if (!data.length) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="9" class="text-center py-6 text-text-secondary">
                            No disposal records found
                        </td>
                    </tr>
                `;
                updatePagination(0, 0, 0);
                return;
            }

            data.forEach(record => {
                const row = document.createElement("tr");
                row.className = "border-b border-border text-sm";

                row.innerHTML = `
                    <td class="py-3">${record.medicine_name}</td>
                    <td class="py-3">${record.batch_number}</td>
                    <td class="py-3">
                        <svg class="barcode" data-barcode="${record.barcode}"></svg>
                    </td>
                    <td class="py-3">${formatDate(record.expiry_date)}</td>
                    <td class="py-3">${record.quantity}</td>
                    <td class="py-3">₱${parseFloat(record.total_value).toFixed(2)}</td>
                    <td class="py-3 capitalize">${record.disposal_method}</td>
                    <td class="py-3">${formatDate(record.disposal_date)}</td>
                    <td class="py-3">
                        <button
                            type="button"
                            class="editItemBtn"
                            data-id="${record.id}"
                            data-medicine-id="${record.medicine_id}"
                            data-medicine-name="${record.medicine_name}"
                            data-barcode="${record.barcode}"
                            data-batch="${record.batch_number}"
                            data-expiry="${record.expiry_date}"
                            data-quantity="${record.quantity}"
                            data-value="${record.total_value}"
                            data-method="${record.disposal_method}"
                            data-date="${record.disposal_date}"
                            data-notes="${record.notes}"

                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 5H6a2 2 0 00-2 2v11a2
                                        2 0 002 2h11a2 2 0 002-2v-5
                                        m-1.414-9.414a2 2 0 112.828
                                        2.828L11.828 15H9v-2.828
                                        l8.586-8.586z"/>
                            </svg>
                        </button>
                    </td>
                `;

                tbody.appendChild(row);
            });

            renderBarcodes();
            updatePagination(total, data.length, currentPage);
        })
        .catch(err => {
            console.error("Failed to load disposal records:", err);
        });
}

/* =========================================================
   BARCODE
========================================================= */
function renderBarcodes() {
    document.querySelectorAll(".barcode").forEach(svg => {
        const value = svg.dataset.barcode;
        if (!value) return;

        JsBarcode(svg, value, {
            format: "CODE128",
            width: 1.5,
            height: 60,
            displayValue: true,
            fontSize: 12,
            margin: 10
        });
    });
}

/* =========================================================
   PAGINATION
========================================================= */
function updatePagination(totalItems, pageCount, page) {
    const totalPages = Math.ceil(totalItems / limit);
    const pageNumbersContainer = document.getElementById("pageNumbers");

    const start = totalItems === 0 ? 0 : (page - 1) * limit + 1;
    const end = Math.min(page * limit, totalItems);

    document.getElementById("startIndex").textContent = start;
    document.getElementById("endIndex").textContent = end;
    document.getElementById("totalItems").textContent = totalItems;

    document.getElementById("prevPageBtn").disabled = page <= 1;
    document.getElementById("nextPageBtn").disabled = page >= totalPages;

    pageNumbersContainer.innerHTML = "";

    for (let i = 1; i <= totalPages; i++) {
        const btn = document.createElement("button");
        btn.textContent = i;
        btn.className = "btn btn-outline btn-sm w-8 h-8 p-0" + (i === page ? " active" : "");
        btn.onclick = () => {
            currentPage = i;
            loadDisposalRecords();
        };
        pageNumbersContainer.appendChild(btn);
    }
}

function formatDate(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleDateString("en-PH", {
        year: "numeric",
        month: "short",
        day: "2-digit"
    });
}
