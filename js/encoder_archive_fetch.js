/* ==============================================
   STATE
============================================== */
let suppliersPage = 1;
let medicinesPage = 1;
const archiveLimit = 10;

/* ==============================================
   INIT
============================================== */
document.addEventListener("DOMContentLoaded", () => {
    loadArchivedSuppliers();
    loadArchivedMedicines();

    /* Suppliers Pagination */
    document.getElementById("suppliersPrevPage")
        ?.addEventListener("click", () => {
            if (suppliersPage > 1) {
                suppliersPage--;
                loadArchivedSuppliers();
            }
        });

    document.getElementById("suppliersNextPage")
        ?.addEventListener("click", () => {
            const total = parseInt(
                document.getElementById("suppliersTotalItems").textContent
            ) || 0;

            const totalPages = Math.ceil(total / archiveLimit);

            if (suppliersPage < totalPages) {
                suppliersPage++;
                loadArchivedSuppliers();
            }
        });

    /* Medicines Pagination */
    document.getElementById("medicinesPrevPage")
        ?.addEventListener("click", () => {
            if (medicinesPage > 1) {
                medicinesPage--;
                loadArchivedMedicines();
            }
        });

    document.getElementById("medicinesNextPage")
        ?.addEventListener("click", () => {
            const total = parseInt(
                document.getElementById("medicinesTotalItems").textContent
            ) || 0;

            const totalPages = Math.ceil(total / archiveLimit);

            if (medicinesPage < totalPages) {
                medicinesPage++;
                loadArchivedMedicines();
            }
        });
});


/* ==============================================
   LOAD ARCHIVED SUPPLIERS
============================================== */
function loadArchivedSuppliers() {

        const params = new URLSearchParams({
            page: suppliersPage,
            limit: archiveLimit,
            search: archiveFilters.search,
            date: archiveFilters.date,
            type: archiveFilters.type
        });

        fetch(`../backend/encoder_archive_fetchsuppliers.php?${params}`)

        .then(res => res.json())
        .then(res => {

            const tbody = document.getElementById("suppliersArchiveTable");
            tbody.innerHTML = "";

            if (!res.data || !res.data.length) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="8" class="py-4 text-center text-text-secondary">
                            No archived suppliers
                        </td>
                    </tr>
                `;
                updateSuppliersPagination(0);
                document.getElementById("totalArchivedSuppliers").textContent = 0;
                return;
            }

            res.data.forEach(s => {

                const row = document.createElement("tr");

                row.innerHTML = `
                    <td class="py-2">${s.id}</td>
                    <td class="py-2">${s.supplier_name}</td>
                    <td class="py-2">${s.supplier_type}</td>
                    <td class="py-2">${s.contact_person || "-"}</td>
                    <td class="py-2">${s.contact_number || "-"}</td>
                    <td class="py-2">${s.email || "-"}</td>
                    <td class="py-2">${s.address || "-"}</td>
                    <td class="py-2">

                            <button
                                type="button"
                                class="restoreSupplierBtn"
                                data-id="${s.id}"
                                title="Restore Supplier"
                            >
                                <svg class="w-5 h-5 transform group-hover:rotate-12 transition-transform duration-300" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <!-- Restore with circular arrow -->
                                    <path d="M4 4V9H4.58152M4.58152 9C5.76853 6.06817 8.64262 4 12 4C16.4183 4 20 7.58172 20 12C20 16.4183 16.4183 20 12 20C7.58172 20 4 16.4183 4 12C4 10.8748 4.19128 9.79455 4.53672 8.79279M4.58152 9H9" 
                                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M15 12L12 15L9 12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M12 9V15" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                </svg>
                            </button>
                        
                    </td>
                `;

                tbody.appendChild(row);
            });

            document.getElementById("totalArchivedSuppliers").textContent = res.total;
            document.getElementById("suppliersCount").textContent =
                `${res.total} records`;

            updateSuppliersPagination(res.total);
        })
        .catch(err => {
            console.error("Failed to load archived suppliers:", err);
        });
}


/* ==============================================
   SUPPLIERS PAGINATION
============================================== */
function updateSuppliersPagination(total) {

    const totalPages = Math.ceil(total / archiveLimit);

    document.getElementById("suppliersTotalItems").textContent = total;

    document.getElementById("suppliersStartIndex").textContent =
        total === 0 ? 0 : (suppliersPage - 1) * archiveLimit + 1;

    document.getElementById("suppliersEndIndex").textContent =
        Math.min(suppliersPage * archiveLimit, total);

    document.getElementById("suppliersPrevPage").disabled =
        suppliersPage <= 1;

    document.getElementById("suppliersNextPage").disabled =
        suppliersPage >= totalPages;
}



/* ==============================================
   LOAD ARCHIVED MEDICINES
============================================== */
function loadArchivedMedicines() {

        const params = new URLSearchParams({
            page: medicinesPage,
            limit: archiveLimit,
            search: archiveFilters.search,
            date: archiveFilters.date,
            type: archiveFilters.type
        });

        fetch(`../backend/encoder_archive_fetchmedicine.php?${params}`)

        .then(res => res.json())
        .then(res => {

            const tbody = document.getElementById("medicinesArchiveTable");
            tbody.innerHTML = "";

            if (!res.data || !res.data.length) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="10" class="py-4 text-center text-text-secondary">
                            No archived medicines
                        </td>
                    </tr>
                `;
                updateMedicinesPagination(0);
                document.getElementById("totalArchivedMedicines").textContent = 0;
                document.getElementById("totalArchiveValue").textContent = "₱0.00";
                return;
            }

            res.data.forEach(m => {

                const row = document.createElement("tr");

                row.innerHTML = `
                    <td class="py-2">${m.barcode}</td>
                    <td class="py-2">${m.medicine_name}</td>
                    <td class="py-2">${m.medicine_type}</td>
                    <td class="py-2">${m.category}</td>
                    <td class="py-2">${m.batch_number}</td>
                    <td class="py-2">${formatDate(m.expiry_date)}</td>
                    <td class="py-2">${m.current_stock}</td>
                    <td class="py-2">₱${parseFloat(m.unit_price).toFixed(2)}</td>
                    <td class="py-2">${m.status}</td>
                    <td class="py-2">

                            <button
                                type="button"
                                class="restoreMedicineBtn"
                                data-id="${m.id}"
                                title="Restore Medicine"
                            >
                                <svg class="w-5 h-5 transform group-hover:rotate-12 transition-transform duration-300" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <!-- Restore with circular arrow -->
                                    <path d="M4 4V9H4.58152M4.58152 9C5.76853 6.06817 8.64262 4 12 4C16.4183 4 20 7.58172 20 12C20 16.4183 16.4183 20 12 20C7.58172 20 4 16.4183 4 12C4 10.8748 4.19128 9.79455 4.53672 8.79279M4.58152 9H9" 
                                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M15 12L12 15L9 12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M12 9V15" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                </svg>
                            </button>

                    </td>
                `;

                tbody.appendChild(row);
            });

            document.getElementById("totalArchivedMedicines").textContent =
                res.total;

            document.getElementById("medicinesCount").textContent =
                `${res.total} records`;

            document.getElementById("totalArchiveValue").textContent =
                "₱" + parseFloat(res.total_value || 0).toFixed(2);

            updateMedicinesPagination(res.total);
        })
        .catch(err => {
            console.error("Failed to load archived medicines:", err);
        });
}


/* ==============================================
   MEDICINES PAGINATION
============================================== */
function updateMedicinesPagination(total) {

    const totalPages = Math.ceil(total / archiveLimit);

    document.getElementById("medicinesTotalItems").textContent = total;

    document.getElementById("medicinesStartIndex").textContent =
        total === 0 ? 0 : (medicinesPage - 1) * archiveLimit + 1;

    document.getElementById("medicinesEndIndex").textContent =
        Math.min(medicinesPage * archiveLimit, total);

    document.getElementById("medicinesPrevPage").disabled =
        medicinesPage <= 1;

    document.getElementById("medicinesNextPage").disabled =
        medicinesPage >= totalPages;
}


/* ==============================================
   DATE FORMATTER
============================================== */
function formatDate(dateStr) {
    if (!dateStr) return "-";

    const date = new Date(dateStr);

    return date.toLocaleDateString("en-PH", {
        year: "numeric",
        month: "short",
        day: "2-digit"
    });
}
