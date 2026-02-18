function loadHealthCenters() {

    fetch("../backend/encoder_distribution_healthcenterfetch.php")
        .then(res => res.json())
        .then(data => {

            if (!data.success) return;

            const tbody = document.getElementById("healthCentersTable");
            if (!tbody) return;

            tbody.innerHTML = "";

            if (data.data.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6" class="px-4 py-4 text-center text-text-secondary">
                            No health centers found
                        </td>
                    </tr>
                `;
                return;
            }

            data.data.forEach(center => {

                tbody.innerHTML += `
                    <tr class="hover:bg-secondary-50">
                        <td class="px-4 py-3 font-medium">
                            ${center.center_name}
                        </td>
                        <td class="px-4 py-3">
                            ${center.center_type.replace(/_/g,' ')}
                        </td>
                        <td class="px-4 py-3">
                            ${center.contact_person ?? '-'}
                        </td>
                        <td class="px-4 py-3">
                            ${center.contact_number ?? '-'}
                        </td>
                        <td class="px-4 py-3">
                            ${center.address ?? '-'}
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-4">
                                <!-- EDIT -->
                                <button
                                    type="button" class="text-text-secondary hover:text-primary transition-colors editHealthCenterBtn"
                                    data-id="${center.id}"
                                    data-name="${center.center_name}"
                                    data-type="${center.center_type}"
                                    data-contact="${center.contact_person ?? ''}"
                                    data-phone="${center.contact_number ?? ''}"
                                    data-address="${center.address ?? ''}"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M11 5H6a2 2 0 00-2 2v11a2
                                                2 0 002 2h11a2 2 0 002-2v-5
                                                m-1.414-9.414a2 2 0 112.828
                                                2.828L11.828 15H9v-2.828
                                                l8.586-8.586z"/>
                                    </svg>
                                </button>

                                <button
                                    type="button" class="text-text-secondary hover:text-primary transition-colors archiveHealthCenterBtn"
                                    data-id="${center.id}"

                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0
                                                0116.138 21H7.862a2 2 0
                                                01-1.995-1.858L5 7m5
                                                4v6m4-6v6m1-10V4
                                                a1 1 0 00-1-1h-4
                                                a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            });

        })
        .catch(err => console.error("Health center fetch error:", err));
}
