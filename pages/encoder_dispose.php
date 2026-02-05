<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'encoder') {
    header("Location: system_login_portal.html");
    exit;
}

if (!empty($_SESSION['force_change_password'])) {
    header("Location: force_change_password.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Expired Medicine Disposal - CAVMED Pharmacy Management">
    <title>Expired Medicine Dispose - CAVMED Portal</title>
    <link rel="stylesheet" href="../css/encoder_dispose.css">
    <link rel="stylesheet" href="../css/main.css">

    <script defer src="../js/encoder_disposal.js"></script>
    <script defer src="../js/encoder_disposal_stats.js"></script>
    <script defer src="../js/encoder_disposal_fetch.js"></script>
    <script defer src="../js/encoder_disposal_modal.js"></script>
    <script defer src="../js/encoder_disposal_medicinedropdown.js"></script>
    <script defer src="../js/encoder_disposal_add.js"></script>
    <script defer src="../js/barcode_scanstate.js"></script>
    <script defer src="../js/encoder_disposal_form.js"></script>
    <script defer src="../js/encoder_disposal_filters.js"></script>

    
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-background min-h-screen flex flex-col">
    <!-- Header Section -->
    <header class="bg-surface border-b border-border shadow-sm sticky top-0 z-fixed">
        <div class="flex items-center justify-between px-6 py-4">
            <!-- Logo & Title -->
            <div class="flex items-center gap-4">
                <svg class="w-10 h-10" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect width="40" height="40" rx="8" fill="#2563EB"/>
                    <path d="M20 10v20M10 20h20" stroke="white" stroke-width="3" stroke-linecap="round"/>
                    <circle cx="20" cy="20" r="6" stroke="white" stroke-width="2" fill="none"/>
                </svg>
                <div>
                    <h1 class="text-xl font-semibold text-text-primary">CAVMED Portal</h1>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <!-- User Profile -->
                <div class="flex items-center gap-4">   
                    <div class="flex items-center gap-3">

                        <!-- User Name & Role -->
                        <div class="text-right hidden md:block">
                            <p class="text-sm font-medium text-text-primary">
                                <?php echo htmlspecialchars($_SESSION['name']); ?>
                            </p>
                            <p class="text-xs text-text-secondary">
                                <?php echo ucfirst(str_replace('_', ' ', $_SESSION['role'])); ?>
                            </p>
                        </div>

                        <!-- Profile Picture -->
                        <img
                            src="/HIMS/<?php echo $_SESSION['profile_picture'] ?: 'uploads/profile/default.png'; ?>"
                            alt="User profile picture"
                            class="w-10 h-10 rounded-full object-cover border-2 border-primary"
                            onerror="this.src='/HIMS/uploads/profile/default.png'; this.onerror=null;">
                    </div>
                </div>
            </div>
        </div>

    </header>
    
        <!-- Navigation Tabs -->
    <nav class="bg-surface border-b border-border px-6 no-print">        
        <div class="px-6">
            <div class="flex items-center gap-1 overflow-x-auto scrollbar-thin">

                <a href="encoder_inventory.php" class="nav-item">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    <span>Inventory List</span>
                </a>

                <a href="encoder_dispose.php" class="nav-item nav-item-active whitespace-nowrap">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span>Inventory Dispose</span>
                </a>

                <a href="../backend/system_logout.php" class="nav-item whitespace-nowrap ml-auto">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    Logout
                </a>


            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="flex-1 px-6 py-6">
        <div class="max-w-full mx-auto">
            <!-- Page Header -->
            <div class="flex items-center justify-between mb-6 pb-6 border-b border-border">
                <div>
                    <h2 class="text-2xl font-semibold text-text-primary"></h2>
                    <p class="text-text-secondary"></p>
                </div>
                <div class="flex items-center gap-3 mb-3 pb-3">
                    <button type="button" id="printReportBtn" class="btn btn-outline no-print">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                        </svg>
                        <span>Print Report</span>
                    </button>
                </div>
            </div>

            <!-- Statistics Overview -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <!-- Total Expired -->
                <div class="card stats-card hover-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-text-secondary">Total Expired</p>
                            <p class="text-2xl font-semibold text-text-primary mt-1" id="totalExpiredCount">156</p>
                        </div>
                        <div class="w-12 h-12 rounded-lg bg-error-100 flex items-center justify-center">
                            <svg class="w-6 h-6 text-error-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.998-.833-2.732 0L4.346 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-2">
                        <p class="text-xs text-text-secondary">Across all categories</p>
                    </div>
                </div>
                
                <!-- Expiring Soon -->
                <div class="card stats-card hover-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-text-secondary">Expiring Soon</p>
                            <p class="text-2xl font-semibold text-warning-600 mt-1" id="expiringSoonCount">42</p>
                        </div>
                        <div class="w-12 h-12 rounded-lg bg-warning-100 flex items-center justify-center">
                            <svg class="w-6 h-6 text-warning-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-2">
                        <p class="text-xs text-text-secondary">Within 30 days</p>
                    </div>
                </div>
                
                <!-- Action Required -->
                <div class="card stats-card hover-card action-required">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-text-secondary">Action Required</p>
                            <p class="text-2xl font-semibold text-error-600 mt-1" id="actionRequiredCount">23</p>
                        </div>
                        <div class="w-12 h-12 rounded-lg bg-error-100 flex items-center justify-center">
                            <svg class="w-6 h-6 text-error-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-2">
                        <p class="text-xs text-error-600 font-medium">Pending disposal</p>
                    </div>
                </div>
            </div>

            <!-- Filters & Search -->
            <div class="card no-print">
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                    <div class="flex-1">
                        <div class="relative">
                            <input type="text" id="medicineSearch" 
                                   placeholder="Search by medicine name, batch number, manufacturer..."
                                   class="input pl-10 pr-10 w-full">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                <svg class="w-5 h-5 text-text-tertiary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                            <button type="button" id="clearSearch" class="hidden absolute inset-y-0 right-0 flex items-center pr-3 text-text-tertiary hover:text-text-primary">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-3">
                        <select id="statusFilter" class="input w-40">
                            <option value="">All Status</option>
                            <option value="expired">Expired</option>
                            <option value="expiring-soon">Expiring Soon</option>
                            <option value="disposed">Disposed</option>
                            <option value="returned">Returned</option>
                        </select>
                        
                        <select id="categoryFilter" class="input w-40">
                            <option value="">All Categories</option>
                            <option value="antibiotics">Antibiotics</option>
                            <option value="analgesics">Analgesics</option>
                            <option value="cardiovascular">Cardiovascular</option>
                            <option value="diabetes">Diabetes</option>
                            <option value="respiratory">Respiratory</option>
                            <option value="other">Other</option>
                        </select>
                        
                        <select id="disposalFilter" class="input w-40">
                            <option value="">Disposal Method</option>
                            <option value="incinerated">Incinerated</option>
                            <option value="returned">Returned</option>
                            <option value="destroyed">Destroyed</option>
                            <option value="donated">Donated</option>
                            <option value="pending">Pending</option>
                        </select>
                    </div>
                </div>
                
                <!-- Active Filters -->
                <div id="activeFilters" class="flex flex-wrap gap-2 mt-4">
                    <!-- Filters will be added here dynamically -->
                </div>
            </div>

            <!-- Main Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Left Column - Expired Medicine List -->
                <div class="lg:col-span-2">

                    <!-- Medicine Cards Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4" id="medicineCardsContainer">
                        <!-- Medicine cards will be populated here -->
                    </div>

                    <!-- No Results Message -->
                    <div id="noResultsMessage" class="hidden text-center py-12">
                        <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-secondary-100 flex items-center justify-center">
                            <svg class="w-8 h-8 text-text-tertiary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-text-primary mb-2">No expired medicines found</h3>
                        <p class="text-text-secondary">Try adjusting your search or filters</p>
                    </div>

                    <!-- Pagination -->
                    <div class="flex items-center justify-between mt-6 pt-6 border-t border-border no-print">
                        <div class="text-sm text-text-secondary">
                            Showing <span id="startIndex">1</span> to <span id="endIndex">8</span> of <span id="totalItems">156</span> entries
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" id="prevPageBtn" class="btn btn-outline btn-sm" disabled>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                </svg>
                                Previous
                            </button>
                            <div class="flex items-center gap-1" id="pageNumbers">
                                <!-- page buttons generated dynamically -->
                            </div>
                            <button type="button" id="nextPageBtn" class="btn btn-outline btn-sm">
                                Next
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Disposal Records Table -->
            <div class="card mt-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-text-primary">Disposal Records</h3>
                    <button type="button" id="addNewRecordBtn" class="btn btn-primary btn-sm">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Add Record
                    </button>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-left text-sm text-text-secondary border-b border-border">
                                <th class="pb-3 font-medium">Medicine</th>
                                <th class="pb-3 font-medium">Batch No.</th>
                                <th class="pb-3 font-medium">Barcode</th>
                                <th class="pb-3 font-medium">Expiry Date</th>
                                <th class="pb-3 font-medium">Quantity</th>
                                <th class="pb-3 font-medium">Value</th>
                                <th class="pb-3 font-medium">Disposal Method</th>
                                <th class="pb-3 font-medium">Disposal Date</th>
                                <th class="pb-3 font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="disposalRecordsTable">

                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Modal for Medicine Details -->
            <div id="medicineDetailsModal" class="hidden fixed inset-0 bg-secondary-900 bg-opacity-50 z-modal flex items-center justify-center p-4">
                <div class="card max-w-4xl w-full max-h-[90vh] overflow-y-auto">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-xl font-semibold text-text-primary">Medicine Details</h3>
                        <button type="button" id="closeMedicineModal" class="text-text-tertiary hover:text-text-primary transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <div id="medicineDetailsContent">
                        <!-- Medicine details will be populated here -->
                    </div>
                </div>
            </div>

            <!-- Modal for Add Disposal -->
            <div id="addDisposalModal" class="hidden fixed inset-0 bg-secondary-900 bg-opacity-50 z-modal flex items-center justify-center p-4">
                <div class="card max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                    <div class="flex items-center justify-between mb-6">
                        <h3 id="disposalModalTitle"
                            class="text-xl font-semibold text-text-primary">
                            Add Disposal Record
                        </h3>
                        <button type="button" id="closeDisposalModal" class="text-text-tertiary hover:text-text-primary transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <form id="disposalForm" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-text-secondary mb-1">Medicine</label>
                                <select id="medicineSelect" class="input w-full" required>
                                    <option value="">Select Medicine</option>
                                    <!-- Options will be populated dynamically -->
                                </select>
                            </div>

                            <input type="hidden" id="editDisposalId">
                            <input type="hidden" id="batchNumber" class="input w-full" readonly>
                            <input type="hidden" id="disposalValue" class="input w-full" step="0.01" min="0" readonly>
                            <input type="text" id="barcodeInput" autocomplete="off" class="absolute opacity-0 pointer-events-none" aria-hidden="true">
                            <input type="hidden" id="expiryDate" class="input w-full" readonly>

                            <div>
                                <label class="block text-sm font-medium text-text-secondary mb-1">Quantity</label>
                                <input type="number" id="disposalQuantity" class="input w-full" min="1" required>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-text-secondary mb-1">Disposal Method</label>
                                <select id="disposalMethod" class="input w-full" required>
                                    <option value="">Select Method</option>
                                    <option value="incinerated">Incinerated</option>
                                    <option value="returned">Returned to Manufacturer</option>
                                    <option value="destroyed">Chemically Destroyed</option>
                                    <option value="donated">Donated</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-text-secondary mb-1">Disposal Date</label>
                                <input type="date" id="disposalDate" class="input w-full" required>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-text-secondary mb-1">Notes</label>
                                <textarea id="disposalNotes" class="input w-full h-24" placeholder="Additional notes about the disposal process..."></textarea>
                            </div>
                        </div>
                        
                        <div class="flex gap-3 pt-6 border-t border-border">
                            <button type="button" id="cancelDisposal" class="btn btn-outline flex-1">Cancel</button>
                            <button type="submit" class="btn btn-primary flex-1">Save Disposal Record</button>
                            <button type="button"
                                id="barcodeModeBtn"
                                class="btn btn-outline btn-sm"
                                title="Barcode mode">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 5v14M7 5v14M11 5v14M15 5v14M19 5v14"/>
                            </svg>
                        </button>

                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>


        <!-- Scan State UI -->
    <div id="scanStateUI"
        class="hidden mt-3 flex items-center gap-3 px-4 py-3 rounded-lg border border-primary bg-primary/5">

        <!-- Pulse Indicator -->
        <span class="relative flex h-3 w-3">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-primary opacity-75"></span>
            <span class="relative inline-flex rounded-full h-3 w-3 bg-primary"></span>
        </span>

        <!-- Text -->
        <div class="flex-1">
            <p class="text-sm font-medium text-text-primary">
                Barcode Scan Mode Active
            </p>
            <p class="text-xs text-text-secondary">
                Scan a medicine barcode now
            </p>
        </div>

        <!-- Exit hint -->
        <span class="text-xs text-text-tertiary">
            Press <kbd class="px-1 border rounded">Esc</kbd> to exit
        </span>
    </div>


    <!-- Footer -->
    <footer class="bg-surface border-t border-border py-6 px-6 mt-auto">
        <div class="max-w-7xl mx-auto">
            <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                <!-- Compliance Certifications -->
                <div class="flex items-center gap-4 flex-wrap justify-center">
                </div>

                <!-- Copyright -->
                <div class="text-sm text-text-secondary text-center md:text-right">
                    <p>© 2025 CAVMED Portal. All Rights Reserved.</p>
                </div>
            </div>
        </div>
    </footer>


</body>
</html>