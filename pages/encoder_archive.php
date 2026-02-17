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
    <meta name="description" content="Archive Management - Suppliers and Medicines - CAVMED Pharmacy Management">
    <title>Archive Management - CAVMED Portal</title>
    <link rel="stylesheet" href="../css/main.css">

    <!-- JavaScript Modules -->
    <script defer src="../js/encoder_archive_tabs.js"></script>
    <script defer src="../js/encoder_archive_fetch.js"></script>
    <script defer src="../js/encoder_archive_restore.js"></script>
    <script defer src="../js/encoder_archive_search.js"></script>
    <script defer src="../js/encoder_archive_stats.js"></script>

    
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

                <a href="encoder_dispose.php" class="nav-item">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span>Dispose</span>
                </a>

                <a href="encoder_archive.php" class="nav-item nav-item-active whitespace-nowrap">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                    </svg>
                    <span>Archive</span>
                </a>

                <a href="encoder_distribution.php" class="nav-item"> 
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <!-- Box -->
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 7l9-4 9 4-9 4-9-4z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 7v10l9 4 9-4V7"/>

                        <!-- Outgoing arrow (distribution) -->
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M16 13h5m0 0l-2-2m2 2l-2 2"/>
                    </svg>
                    <span>Distribution</span> 
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
            <!-- Statistics Overview -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <!-- Total Archived Suppliers -->
                <div class="card stats-card hover-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-text-secondary">Archived Suppliers</p>
                            <p class="text-2xl font-semibold text-text-primary mt-1" id="totalArchivedSuppliers">0</p>
                        </div>
                        <div class="w-12 h-12 rounded-lg bg-secondary-100 flex items-center justify-center">
                            <svg class="w-6 h-6 text-secondary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                        </div>
                    </div>
                </div>
                
                <!-- Total Archived Medicines -->
                <div class="card stats-card hover-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-text-secondary">Archived Medicines</p>
                            <p class="text-2xl font-semibold text-text-primary mt-1" id="totalArchivedMedicines">0</p>
                        </div>
                        <div class="w-12 h-12 rounded-lg bg-secondary-100 flex items-center justify-center">
                            <svg class="w-6 h-6 text-secondary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                
                <!-- Total Archive Value -->
                <div class="card stats-card hover-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-text-secondary">Total Archive Value</p>
                            <p class="text-2xl font-semibold text-text-primary mt-1" id="totalArchiveValue">₱0.00</p>
                        </div>
                        <div class="w-12 h-12 rounded-lg bg-success-100 flex items-center justify-center">
                            <svg class="w-6 h-6 text-success-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Archive Tabs -->
            <div class="mb-6">
                <div class="border-b border-border">
                    <nav class="flex gap-6" id="archiveTabs">
                        <button type="button" class="archive-tab active py-2 px-1 border-b-2 border-primary text-primary font-medium text-sm" data-tab="suppliers">
                            Suppliers Archive
                        </button>
                        <button type="button" class="archive-tab py-2 px-1 border-b-2 border-transparent text-text-secondary hover:text-text-primary font-medium text-sm" data-tab="medicines">
                            Medicines Archive
                        </button>
                    </nav>
                </div>
            </div>

            <!-- Filters and Search Section -->
            <div class="card mb-6 no-print">
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                    <div class="flex-1">
                        <div class="relative">
                            <input type="text" id="archiveSearch" 
                                placeholder="Search by name, code, manufacturer, or reason..."
                                class="input pl-10 pr-10 w-full">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                <svg class="w-5 h-5 text-text-tertiary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                            <button type="button" id="clearArchiveSearch" class="hidden absolute inset-y-0 right-0 flex items-center pr-3 text-text-tertiary hover:text-text-primary">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-3 flex-wrap">
                        
                        <select id="archiveTypeFilter" class="input w-40" data-tab-specific>
                            <!-- Options change based on tab -->
                        </select>
                        
                    </div>
                </div>

                <!-- Active Filters -->
                <div id="archiveActiveFilters" class="flex flex-wrap gap-2 mt-4">
                    <!-- Filters will be added here dynamically -->
                </div>
            </div>

            <!-- Suppliers Archive Table -->
            <div id="suppliersTabContent" class="tab-content">
                <div class="card">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-semibold text-text-primary">Archived Suppliers</h3>
                        <span class="text-sm text-text-secondary" id="suppliersCount">0 records</span>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="text-left text-sm text-text-secondary border-b border-border">
                                    <th class="pb-3 font-medium">Supplier ID</th>
                                    <th class="pb-3 font-medium">Suppler Name</th>
                                    <th class="pb-3 font-medium">Suppler Type</th>
                                    <th class="pb-3 font-medium">Contact Person</th>
                                    <th class="pb-3 font-medium">Contact Number</th>
                                    <th class="pb-3 font-medium">Email</th>
                                    <th class="pb-3 font-medium">Address</th>
                                    <th class="pb-3 font-medium">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="suppliersArchiveTable">
                                <!-- Suppliers data will be populated here -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination for Suppliers -->
                    <div class="flex items-center justify-between mt-6 pt-6 border-t border-border no-print">
                        <div class="text-sm text-text-secondary">
                            Showing <span id="suppliersStartIndex">1</span> to <span id="suppliersEndIndex">10</span> of <span id="suppliersTotalItems">0</span> entries
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" id="suppliersPrevPage" class="btn btn-outline btn-sm" disabled>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                </svg>
                                Previous
                            </button>
                            <div class="flex items-center gap-1" id="suppliersPageNumbers"></div>
                            <button type="button" id="suppliersNextPage" class="btn btn-outline btn-sm">
                                Next
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Medicines Archive Table -->
            <div id="medicinesTabContent" class="tab-content hidden">
                <div class="card">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-semibold text-text-primary">Archived Medicines</h3>
                        <span class="text-sm text-text-secondary" id="medicinesCount">0 records</span>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="text-left text-sm text-text-secondary border-b border-border">
                                    <th class="pb-3 font-medium">Barcode</th>
                                    <th class="pb-3 font-medium">Medicine Name</th>
                                    <th class="pb-3 font-medium">Type</th>
                                    <th class="pb-3 font-medium">Category</th>
                                    <th class="pb-3 font-medium">Batch No.</th>
                                    <th class="pb-3 font-medium">Expiry Date</th>
                                    <th class="pb-3 font-medium">Stock</th>
                                    <th class="pb-3 font-medium">Unit Price</th>
                                    <th class="pb-3 font-medium">Status</th>
                                    <th class="pb-3 font-medium">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="medicinesArchiveTable">
                                <!-- Medicines data will be populated here -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination for Medicines -->
                    <div class="flex items-center justify-between mt-6 pt-6 border-t border-border no-print">
                        <div class="text-sm text-text-secondary">
                            Showing <span id="medicinesStartIndex">1</span> to <span id="medicinesEndIndex">10</span> of <span id="medicinesTotalItems">0</span> entries
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" id="medicinesPrevPage" class="btn btn-outline btn-sm" disabled>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                </svg>
                                Previous
                            </button>
                            <div class="flex items-center gap-1" id="medicinesPageNumbers"></div>
                            <button type="button" id="medicinesNextPage" class="btn btn-outline btn-sm">
                                Next
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- No Results Message -->
            <div id="noArchiveResults" class="hidden text-center py-12">
                <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-secondary-100 flex items-center justify-center">
                    <svg class="w-8 h-8 text-text-tertiary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-text-primary mb-2">No archive records found</h3>
                <p class="text-text-secondary">Try adjusting your search or filters</p>
            </div>
        </div>
    </main>

    <!-- Restore Confirmation Modal -->
    <div id="restoreConfirmModal" class="hidden fixed inset-0 bg-secondary-900 bg-opacity-50 z-modal flex items-center justify-center p-4">
        <div class="card max-w-md w-full">
            <div class="text-center mb-6">
                <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-success-100 flex items-center justify-center">
                    <svg class="w-8 h-8 text-success-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-text-primary mb-2">Confirm Restore</h3>
                <p class="text-text-secondary" id="restoreConfirmMessage">Are you sure you want to restore this item?</p>
            </div>

            <div class="flex gap-3">
                <button type="button" id="cancelRestore" class="btn btn-outline flex-1">Cancel</button>
                <button type="button" id="confirmRestore" class="btn btn-primary flex-1">Yes, Restore</button>
            </div>
        </div>
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