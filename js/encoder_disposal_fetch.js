document.addEventListener("DOMContentLoaded", () => {
    loadDisposalRecords();
});

function loadDisposalRecords() {
    fetch("../backend/encoder_disposal_fetch.php")
        .then(res => res.json())
        .then(data => {
            const tbody = document.getElementById("disposalRecordsTable");
            tbody.innerHTML = "";

            if (!data.length) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="8" class="text-center py-6 text-text-secondary">
                            No disposal records found
                        </td>
                    </tr>
                `;
                return;
            }

            data.forEach(record => {
                const row = document.createElement("tr");
                row.classList.add("border-b", "border-border", "text-sm");

                row.innerHTML = `
                    <td class="py-3">${record.medicine_name}</td>
                    <td class="py-3">${record.batch_number}</td>
                    <td class="py-3">${record.barcode}</td>
                    <td class="py-3">${formatDate(record.expiry_date)}</td>
                    <td class="py-3">${record.quantity}</td>
                    <td class="py-3">₱${parseFloat(record.total_value).toFixed(2)}</td>
                    <td class="py-3 capitalize">${record.disposal_method}</td>
                    <td class="py-3">${formatDate(record.disposal_date)}</td>
                    <td class="py-3">
                        <button 
                            class="btn btn-outline btn-sm"
                            onclick="deleteDisposalRecord(${record.id})">
                            Delete
                        </button>
                    </td>
                `;

                tbody.appendChild(row);
            });
        })
        .catch(err => {
            console.error("Failed to load disposal records:", err);
        });
}

function formatDate(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleDateString("en-PH", {
        year: "numeric",
        month: "short",
        day: "2-digit"
    });
}