/* =========================================================
   FILTER STATE
========================================================= */
let disposalFilters = {
    search: "",
    status: "",
    category: "",
    disposal_method: ""
};

/* =========================================================
   INIT
========================================================= */
document.addEventListener("DOMContentLoaded", () => {
    const searchInput = document.getElementById("medicineSearch");
    const clearBtn = document.getElementById("clearSearch");

    searchInput.addEventListener("input", e => {
        disposalFilters.search = e.target.value.trim();
        clearBtn.classList.toggle("hidden", !disposalFilters.search);
        currentPage = 1;
        loadDisposalRecords();
        renderActiveFilters();
    });

    clearBtn.addEventListener("click", () => {
        searchInput.value = "";
        disposalFilters.search = "";
        clearBtn.classList.add("hidden");
        currentPage = 1;
        loadDisposalRecords();
        renderActiveFilters();
    });

    document.getElementById("statusFilter")
        .addEventListener("change", e => {
            disposalFilters.status = e.target.value;
            currentPage = 1;
            loadDisposalRecords();
            renderActiveFilters();
        });

    document.getElementById("categoryFilter")
        .addEventListener("change", e => {
            disposalFilters.category = e.target.value;
            currentPage = 1;
            loadDisposalRecords();
            renderActiveFilters();
        });

    document.getElementById("disposalFilter")
        .addEventListener("change", e => {
            disposalFilters.disposal_method = e.target.value;
            currentPage = 1;
            loadDisposalRecords();
            renderActiveFilters();
        });
});

function renderActiveFilters() {
    const container = document.getElementById("activeFilters");
    container.innerHTML = "";

    Object.entries(disposalFilters).forEach(([key, value]) => {
        if (!value) return;

        const chip = document.createElement("span");
        chip.className =
            "px-3 py-1 rounded-full bg-primary-100 text-primary-700 text-xs flex items-center gap-1";

        chip.innerHTML = `
            ${value}
            <button type="button" class="ml-1">✕</button>
        `;

        chip.querySelector("button").addEventListener("click", () => {
            disposalFilters[key] = "";
            document.getElementById(getFilterInputId(key)).value = "";
            currentPage = 1;
            loadDisposalRecords();
            renderActiveFilters();
        });

        container.appendChild(chip);
    });
}

function getFilterInputId(key) {
    return {
        search: "medicineSearch",
        status: "statusFilter",
        category: "categoryFilter",
        disposal_method: "disposalFilter"
    }[key];
}
