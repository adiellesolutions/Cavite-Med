/* ==============================================
   SEARCH + FILTER STATE
============================================== */

let archiveFilters = {
    search: "",
    date: "",
    type: ""
};

/* ==============================================
   INIT
============================================== */
document.addEventListener("DOMContentLoaded", () => {

    const searchInput = document.getElementById("archiveSearch");
    const clearBtn = document.getElementById("clearArchiveSearch");
    const dateFilter = document.getElementById("archiveDateFilter");
    const typeFilter = document.getElementById("archiveTypeFilter");

    /* ===============================
       SEARCH INPUT (AUTO)
    =============================== */
    searchInput.addEventListener("input", e => {

        archiveFilters.search = e.target.value.trim();

        clearBtn.classList.toggle("hidden", !archiveFilters.search);

        resetArchivePage();
        reloadActiveArchiveTab();
        renderActiveArchiveFilters();
    });

    /* ===============================
       CLEAR SEARCH
    =============================== */
    clearBtn.addEventListener("click", () => {

        searchInput.value = "";
        archiveFilters.search = "";
        clearBtn.classList.add("hidden");

        resetArchivePage();
        reloadActiveArchiveTab();
        renderActiveArchiveFilters();
    });

    /* ===============================
       TYPE FILTER
    =============================== */
    typeFilter.addEventListener("change", e => {

        archiveFilters.type = e.target.value;

        resetArchivePage();
        reloadActiveArchiveTab();
        renderActiveArchiveFilters();
    });
});


/* ==============================================
   HELPERS
============================================== */

function resetArchivePage() {
    suppliersPage = 1;
    medicinesPage = 1;
}

function reloadActiveArchiveTab() {

    const activeTab =
        document.querySelector(".archive-tab.active")?.dataset.tab;

    if (activeTab === "suppliers") {
        loadArchivedSuppliers();
    } else {
        loadArchivedMedicines();
    }
}


/* ==============================================
   ACTIVE FILTER CHIPS
============================================== */

function renderActiveArchiveFilters() {

    const container = document.getElementById("archiveActiveFilters");
    container.innerHTML = "";

    Object.entries(archiveFilters).forEach(([key, value]) => {

        if (!value) return;

        const chip = document.createElement("span");
        chip.className =
            "px-3 py-1 rounded-full bg-primary-100 text-primary-700 text-xs flex items-center gap-2";

        chip.innerHTML = `
            ${value}
            <button type="button">✕</button>
        `;

        chip.querySelector("button").addEventListener("click", () => {

            archiveFilters[key] = "";

            if (key === "search")
                document.getElementById("archiveSearch").value = "";

            if (key === "date")
                document.getElementById("archiveDateFilter").value = "";

            if (key === "type")
                document.getElementById("archiveTypeFilter").value = "";

            resetArchivePage();
            reloadActiveArchiveTab();
            renderActiveArchiveFilters();
        });

        container.appendChild(chip);
    });
}
