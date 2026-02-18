let allDistributions = [];
let currentPage = 1;
const rowsPerPage = 10;

function loadDistributions() {

    fetch('../backend/encoder_distribution_fetch.php')
        .then(res => res.json())
        .then(data => {

            if (!data.success) return;

            allDistributions = data.data;
            currentPage = 1;

            renderTable();
            renderPagination();

        })
        .catch(err => console.error("Fetch error:", err));
}

function renderTable() {

    const tbody = document.getElementById('distributionsTable');
    const countDisplay = document.getElementById('distributionsCount');

    tbody.innerHTML = '';

    if (allDistributions.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="9" class="text-center py-6 text-text-secondary">
                    No records found
                </td>
            </tr>
        `;
        countDisplay.textContent = "0 records";
        return;
    }

    const start = (currentPage - 1) * rowsPerPage;
    const end = start + rowsPerPage;
    const paginatedData = allDistributions.slice(start, end);

    paginatedData.forEach(row => {

        const formattedDate = new Date(row.created_at)
            .toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });

        const statusBadge =
            row.status === 'distributed'
                ? `<span class="px-2 py-1 text-xs font-semibold rounded bg-success-100 text-success-700">Distributed</span>`
                : row.status === 'pending'
                ? `<span class="px-2 py-1 text-xs font-semibold rounded bg-warning-100 text-warning-700">Pending</span>`
                : `<span class="px-2 py-1 text-xs font-semibold rounded bg-error-100 text-error-700">Cancelled</span>`;

        tbody.innerHTML += `
            <tr class="border-b border-border hover:bg-secondary-50 transition">
                <td class="px-4 py-3 text-sm font-medium">${row.id}</td>
                <td class="px-4 py-3 text-sm">${row.center_name}</td>
                <td class="px-4 py-3 text-sm">${row.medicine_name}</td>
                <td class="px-4 py-3 text-sm font-medium">${row.quantity}</td>
                <td class="px-4 py-3 text-sm">${statusBadge}</td>
                <td class="px-4 py-3 text-sm text-text-secondary">
                    ${row.remarks ? row.remarks : '-'}
                </td>
                <td class="px-4 py-3 text-sm">${row.full_name}</td>
                <td class="px-4 py-3 text-sm">${formattedDate}</td>
                <td class="px-4 py-3 text-sm cursor-pointer">
                   <div class="flex items-center gap-6">

                            <button 
                                type="button"
                                class="editDistributionBtn"
                                data-id="${row.id}"
                                data-center="${row.health_center_id}"
                                data-medicine="${row.medicine_id}"
                                data-quantity="${row.quantity}"
                                data-status="${row.status}"
                                data-remarks="${row.remarks || ''}"
                            >



                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2
                                            2 0 002 2h11a2 2 0 002-2v-5
                                            m-1.414-9.414a2 2 0 112.828
                                            2.828L11.828 15H9v-2.828
                                            l8.586-8.586z"></path>
                                </svg>
                            </button>

                    ${
                        row.status === 'distributed'
                        ? `
                            <button
                                type="button"
                                class="restoreDistributionBtn"
                                data-id="${row.id}"
                                data-medicine="${row.medicine_id}"
                                data-quantity="${row.quantity}"
                                title="Restore Distribution"
                            >
                                <svg class="w-5 h-5 transform group-hover:rotate-12 transition-transform duration-300" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <!-- Restore with circular arrow -->
                                    <path d="M4 4V9H4.58152M4.58152 9C5.76853 6.06817 8.64262 4 12 4C16.4183 4 20 7.58172 20 12C20 16.4183 16.4183 20 12 20C7.58172 20 4 16.4183 4 12C4 10.8748 4.19128 9.79455 4.53672 8.79279M4.58152 9H9" 
                                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M15 12L12 15L9 12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M12 9V15" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                </svg>
                            </button>
                        `
                        : ''
                    }
                    </div>
                </td>
            </tr>
        `;
    });

    countDisplay.textContent = `${allDistributions.length} records`;

    document.getElementById('startIndex').textContent = start + 1;
    document.getElementById('endIndex').textContent =
        Math.min(end, allDistributions.length);
    document.getElementById('totalItems').textContent =
        allDistributions.length;
}


function renderPagination() {

    const totalPages = Math.ceil(allDistributions.length / rowsPerPage);
    const pageNumbers = document.getElementById('pageNumbers');
    const prevBtn = document.getElementById('prevPage');
    const nextBtn = document.getElementById('nextPage');

    pageNumbers.innerHTML = '';

    for (let i = 1; i <= totalPages; i++) {

        const btn = document.createElement('button');
        btn.textContent = i;
        btn.className = `
            px-3 py-1 rounded border
            ${i === currentPage ? 'bg-primary text-white' : 'btn btn-outline btn-sm'}
        `;

        btn.addEventListener('click', () => {
            currentPage = i;
            renderTable();
            renderPagination();
        });

        pageNumbers.appendChild(btn);
    }

    prevBtn.disabled = currentPage === 1;
    nextBtn.disabled = currentPage === totalPages || totalPages === 0;

    prevBtn.onclick = () => {
        if (currentPage > 1) {
            currentPage--;
            renderTable();
            renderPagination();
        }
    };

    nextBtn.onclick = () => {
        if (currentPage < totalPages) {
            currentPage++;
            renderTable();
            renderPagination();
        }
    };
}

document.addEventListener("DOMContentLoaded", function () {
    loadDistributions();
});
