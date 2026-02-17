class ArchiveTabManager {
    constructor() {
        this.tabs = document.querySelectorAll('.archive-tab');
        this.contents = {
            suppliers: document.getElementById('suppliersTabContent'),
            medicines: document.getElementById('medicinesTabContent'),
        };
        this.currentTab = 'suppliers';
    }

    init() {
        this.tabs.forEach(tab => {
            tab.addEventListener('click', (e) => this.switchTab(e));
        });

        this.updateTypeFilterOptions();
    }

    switchTab(event) {

        const tab = event.currentTarget;
        const tabName = tab.dataset.tab;

        if (this.currentTab === tabName) return;

        this.currentTab = tabName;

        /* ===============================
        🔥 RESET EVERYTHING FIRST
        =============================== */
        this.resetFilters();

        /* ===============================
        UPDATE TAB STYLES
        =============================== */
        this.tabs.forEach(t => {
            t.classList.remove('active', 'border-primary', 'text-primary');
            t.classList.add('border-transparent', 'text-text-secondary');
        });

        tab.classList.add('active', 'border-primary', 'text-primary');
        tab.classList.remove('border-transparent', 'text-text-secondary');

        /* ===============================
        SWITCH CONTENT
        =============================== */
        Object.values(this.contents).forEach(content => {
            if (content) content.classList.add('hidden');
        });

        if (this.contents[tabName]) {
            this.contents[tabName].classList.remove('hidden');
        }

        /* ===============================
        UPDATE TYPE DROPDOWN
        =============================== */
        this.updateTypeFilterOptions();

        /* ===============================
        RESET PAGE TO 1 + FORCE RELOAD
        =============================== */
        if (tabName === 'suppliers') {
            if (typeof suppliersPage !== "undefined") suppliersPage = 1;
            if (typeof loadArchivedSuppliers === "function") loadArchivedSuppliers();
        }

        if (tabName === 'medicines') {
            if (typeof medicinesPage !== "undefined") medicinesPage = 1;
            if (typeof loadArchivedMedicines === "function") loadArchivedMedicines();
        }

        /* ===============================
        🔥 REFRESH STATS HERE
        =============================== */
        if (typeof loadArchiveStats === "function") {
            loadArchiveStats();
        }

        /* ===============================
        NOTIFY OTHER MODULES
        =============================== */
        document.dispatchEvent(new CustomEvent('tabChanged', {
            detail: { tab: tabName }
        }));
    }


    /* ==============================================
       🔥 RESET FILTER INPUTS
    ============================================== */
    resetFilters() {

        const searchInput = document.getElementById('archiveSearch');
        const dateFilter  = document.getElementById('archiveDateFilter');
        const typeFilter  = document.getElementById('archiveTypeFilter');
        const activeFiltersContainer = document.getElementById('archiveActiveFilters');
        const clearBtn = document.getElementById('clearArchiveSearch');

        /* Reset inputs */
        if (searchInput) searchInput.value = '';
        if (dateFilter) dateFilter.value = '';
        if (typeFilter) typeFilter.value = '';

        /* Reset global filter state */
        if (typeof archiveFilters !== "undefined") {
            archiveFilters.search = '';
            archiveFilters.date = '';
            archiveFilters.type = '';
        }

        /* 🔥 Clear filter chips */
        if (activeFiltersContainer) {
            activeFiltersContainer.innerHTML = '';
        }

        /* Hide clear button */
        if (clearBtn) {
            clearBtn.classList.add('hidden');
        }
    }


    updateTypeFilterOptions() {

        const typeFilter = document.getElementById('archiveTypeFilter');
        if (!typeFilter) return;

        let options = '<option value="">All Types</option>';

        switch (this.currentTab) {

            case 'suppliers':
                options += `
                    <option value="private">Private</option>
                    <option value="donation">Donation</option>
                    <option value="government">Government</option>
                `;
                break;

            case 'medicines':
                options += `
                    <option value="branded">Branded</option>
                    <option value="generic">Generic</option>
                `;
                break;
        }

        typeFilter.innerHTML = options;
        typeFilter.value = ''; // ensure reset
    }

    resetPagination() {

        if (this.currentTab === 'suppliers') {
            if (typeof suppliersPage !== "undefined") suppliersPage = 1;
            if (typeof loadArchivedSuppliers === "function") loadArchivedSuppliers();
        }

        if (this.currentTab === 'medicines') {
            if (typeof medicinesPage !== "undefined") medicinesPage = 1;
            if (typeof loadArchivedMedicines === "function") loadArchivedMedicines();
        }
    }

    getCurrentFilters() {
        return {
            tab: this.currentTab,
            search: document.getElementById('archiveSearch')?.value || '',
            date: document.getElementById('archiveDateFilter')?.value || '',
            type: document.getElementById('archiveTypeFilter')?.value || ''
        };
    }
}

/* ==============================================
   INIT
============================================== */
const archiveTabs = new ArchiveTabManager();

document.addEventListener('DOMContentLoaded', () => {
    archiveTabs.init();
});
