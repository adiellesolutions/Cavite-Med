class ArchiveTabManager {
    constructor() {
        this.tabs = document.querySelectorAll('.archive-tab');
        this.contents = {
            suppliers: document.getElementById('suppliersTabContent'),
            medicines: document.getElementById('medicinesTabContent'),
        };
        this.currentTab = 'suppliers';
        this.filters = {
            date: '',
            type: '',
            search: ''
        };
    }

    init() {
        this.tabs.forEach(tab => {
            tab.addEventListener('click', (e) => this.switchTab(e));
        });

        // Update type filter options based on active tab
        this.updateTypeFilterOptions();
    }

    switchTab(event) {
        const tab = event.currentTarget;
        const tabName = tab.dataset.tab;

        // Update active state
        this.tabs.forEach(t => {
            t.classList.remove('active', 'border-primary', 'text-primary');
            t.classList.add('border-transparent', 'text-text-secondary');
        });
        
        tab.classList.add('active', 'border-primary', 'text-primary');
        tab.classList.remove('border-transparent', 'text-text-secondary');

        // Hide all content
        Object.values(this.contents).forEach(content => {
            if (content) content.classList.add('hidden');
        });

        // Show selected content
        if (this.contents[tabName]) {
            this.contents[tabName].classList.remove('hidden');
        }

        this.currentTab = tabName;
        
        // Update type filter options for new tab
        this.updateTypeFilterOptions();
        
        // Reset and trigger search for new tab
        this.resetPagination();
        
        // Dispatch event for other modules
        document.dispatchEvent(new CustomEvent('tabChanged', { 
            detail: { tab: tabName } 
        }));
    }

    updateTypeFilterOptions() {
        const typeFilter = document.getElementById('archiveTypeFilter');
        if (!typeFilter) return;

        let options = '<option value="">All Types</option>';

        switch(this.currentTab) {
            case 'suppliers':
                options += `
                    <option value="distributor">Distributor</option>
                    <option value="manufacturer">Manufacturer</option>
                    <option value="wholesaler">Wholesaler</option>
                    <option value="retailer">Retailer</option>
                `;
                break;
            case 'medicines':
                options += `
                    <option value="branded">Branded</option>
                    <option value="generic">Generic</option>
                    <option value="prescription">Prescription</option>
                    <option value="otc">Over-the-Counter</option>
                `;
                break;
        }

        typeFilter.innerHTML = options;
    }

    resetPagination() {
        // Reset page numbers for current tab
        const pageEvents = {
            suppliers: 'resetSuppliersPagination',
            medicines: 'resetMedicinesPagination',
        };

        document.dispatchEvent(new CustomEvent(pageEvents[this.currentTab]));
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

// Initialize tab manager
const archiveTabs = new ArchiveTabManager();

document.addEventListener('DOMContentLoaded', () => {
    archiveTabs.init();
});