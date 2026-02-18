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
    <meta name="description" content="Distribution Management - CAVMED Pharmacy Management">
    <title>Distribution Management - CAVMED Portal</title>
    <link rel="stylesheet" href="../css/main.css">
    
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script defer src="../js/encoder_distribution_modal.js"></script>
    <script defer src="../js/encoder_distribution_fetchdropdowns.js"></script>
    <script defer src="../js/encoder_distribution_submit.js"></script>
    <script defer src="../js/encoder_distribution_fetch.js"></script>
    <script src="../js/encoder_distribution_restore.js"></script>
    <script src="../js/encoder_distribution_update.js"></script>



    <style>
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }

        .modal-overlay.show {
            display: flex;
        }

        .modal-container {
            background: white;
            border-radius: 12px;
            width: 100%;
            max-height: 90vh;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        }

    </style>
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

                <a href="encoder_archive.php" class="nav-item"  >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                    </svg>
                    <span>Archive</span>
                </a>

                <a href="encoder_distribution.php" class="nav-item nav-item-active whitespace-nowrap"> 
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
                <!-- Total Distributions -->
                <div class="card stats-card hover-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-text-secondary">Total Distributions</p>
                            <p class="text-2xl font-semibold text-text-primary mt-1" id="totalDistributions">0</p>
                        </div>
                        <div class="w-12 h-12 rounded-lg bg-primary-100 flex items-center justify-center">
                            <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                        </div>
                    </div>
                </div>
                

                <!-- Distributed -->
                <div class="card stats-card hover-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-text-secondary">Distributed</p>
                            <p class="text-2xl font-semibold text-text-primary mt-1" id="distributedCount">0</p>
                        </div>
                        <div class="w-12 h-12 rounded-lg bg-success-100 flex items-center justify-center">
                            <svg class="w-6 h-6 text-success-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Total Items Distributed -->
                <div class="card stats-card hover-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-text-secondary">Total Items Distributed</p>
                            <p class="text-2xl font-semibold text-text-primary mt-1" id="totalItemsDistributed">0</p>
                        </div>
                        <div class="w-12 h-12 rounded-lg bg-secondary-100 flex items-center justify-center">
                            <svg class="w-6 h-6 text-secondary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add Distribution Button -->
            <div class="mb-6 no-print flex gap-3">

                <button type="button" id="openDistributionModal" class="btn btn-primary">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Distribute Medicine
                </button>

                <button type="button" id="viewHealthCenterBtn" class="btn btn-primary">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <!-- Building -->
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 21V7l8-4 8 4v14M9 21v-6h6v6" />
                        
                        <!-- Medical Cross -->
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4m-2-2h4" />
                    </svg>
                    Health Center
                </button>

            </div>

            <!-- Distribution Modal -->
            <div id="distributionModal" class="modal-overlay">
                <div class="modal-container max-w-3xl w-full">


                    <!-- Header -->
                    <div class="modal-header flex items-center justify-between px-6 py-4 border-b border-border">
                        <h3 class="text-lg font-semibold text-text-primary">
                            Create New Distribution
                        </h3>
                        <button type="button" id="closeDistributionModal"
                            class="text-text-tertiary hover:text-text-primary">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="flex-1 overflow-auto px-6 py-6">

                        <form id="distributionForm" method="POST" action="../backend/encoder_distribution_add.php">

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                                <!-- Health Center -->
                                <div>
                                    <label class="block text-sm font-medium text-text-primary mb-2">
                                        Health Center <span class="text-error">*</span>
                                    </label>
                                    <select id="health_center_id" name="health_center_id"
                                        required class="input w-full">
                                        <option value="">Select Health Center</option>
                                    </select>
                                </div>

                                <!-- Medicine -->
                                <div>
                                    <label class="block text-sm font-medium text-text-primary mb-2">
                                        Medicine <span class="text-error">*</span>
                                    </label>
                                    <select id="medicine_id" name="medicine_id"
                                        required class="input w-full"
                                        onchange="updateMedicineDetails()">
                                        <option value="">Select Medicine</option>
                                    </select>
                                </div>


                                <!-- Quantity -->
                                <div>
                                    <label class="block text-sm font-medium text-text-primary mb-2">
                                        Quantity <span class="text-error">*</span>
                                    </label>
                                    <input type="number"
                                        id="quantity"
                                        name="quantity"
                                        required
                                        min="1"
                                        class="input w-full"
                                        placeholder="Enter quantity">
                                </div>

                                <!-- Remarks -->
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-text-primary mb-2">
                                        Remarks
                                    </label>
                                    <textarea name="remarks"
                                        rows="3"
                                        class="input w-full"
                                        placeholder="Enter notes..."></textarea>
                                </div>

                            </div>

                            <!-- Footer -->
                            <div class="flex justify-end gap-3 mt-6 pt-6 border-t border-border">
                                <button type="button"
                                    id="cancelDistributionModal"
                                    class="btn btn-outline">
                                    Cancel
                                </button>

                                <button type="submit"
                                    name="action"
                                    value="create"
                                    class="btn btn-primary">
                                    Create Distribution
                                </button>
                            </div>

                        </form>

                    </div>

                </div>
            </div>


            <!-- Filters and Search Section -->
            <div class="card mb-6 no-print">
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                    <div class="flex-1">
                        <div class="relative">
                            <input type="text" id="distributionSearch" 
                                placeholder="Search by health center, medicine, or remarks..."
                                class="input pl-10 pr-10 w-full">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                <svg class="w-5 h-5 text-text-tertiary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                            <button type="button" id="clearDistributionSearch" class="hidden absolute inset-y-0 right-0 flex items-center pr-3 text-text-tertiary hover:text-text-primary">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-3 flex-wrap">
                        <select id="statusFilter" class="input w-40">
                            <option value="">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="distributed">Distributed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>

                        <select id="healthCenterFilter" class="input w-48">
                            <option value="">All Health Centers</option>
                            <?php foreach ($healthCenters as $center): ?>
                                <option value="<?php echo $center['id']; ?>">
                                    <?php echo htmlspecialchars($center['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Active Filters -->
                <div id="distributionActiveFilters" class="flex flex-wrap gap-2 mt-4">
                    <!-- Filters will be added here dynamically -->
                </div>
            </div>

            <!-- Distributions Table -->
            <div class="card">

                <!-- Pagination -->
                <div class="flex items-center justify-between mt-6 pt-6 mb-6 pb-6 border-t border-border no-print">
                    <div class="text-sm text-text-secondary">
                        Showing <span id="startIndex">1</span> to <span id="endIndex">10</span> of <span id="totalItems">0</span> entries
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" id="prevPage" class="btn btn-outline btn-sm" disabled>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                            Previous
                        </button>
                        <div class="flex items-center gap-1" id="pageNumbers"></div>
                        <button type="button" id="nextPage" class="btn btn-outline btn-sm">
                            Next
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-text-primary">Distribution Records</h3>
                    <span class="text-sm text-text-secondary" id="distributionsCount">0 records</span>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-sm table-auto">
                        <thead>
                            <tr class="text-left text-sm text-text-secondary border-b border-border">
                                <th class="pb-3 font-medium">ID</th>
                                <th class="pb-3 font-medium">Health Center</th>
                                <th class="pb-3 font-medium">Medicine</th>
                                <th class="pb-3 font-medium">Quantity</th>
                                <th class="pb-3 font-medium">Status</th>
                                <th class="pb-3 font-medium">Remarks</th>
                                <th class="pb-3 font-medium">Created By</th>
                                <th class="pb-3 font-medium">Date</th>
                                <th class="pb-3 font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="distributionsTable">
                            <!-- Distributions data will be populated here -->
                        </tbody>
                    </table>
                </div>

            </div>

            <!-- No Results Message -->
            <div id="noDistributionResults" class="hidden text-center py-12">
                <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-secondary-100 flex items-center justify-center">
                    <svg class="w-8 h-8 text-text-tertiary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-text-primary mb-2">No distribution records found</h3>
                <p class="text-text-secondary">Try adjusting your search or filters</p>
            </div>
        </div>
    </main>

    <!-- Update Status Modal -->
    <div id="updateStatusModal" class="hidden fixed inset-0 bg-secondary-900 bg-opacity-50 z-modal flex items-center justify-center p-4">
        <div class="card max-w-md w-full">
            <div class="text-center mb-6">
                <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-primary-100 flex items-center justify-center">
                    <svg class="w-8 h-8 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-text-primary mb-2">Update Distribution Status</h3>
                <p class="text-text-secondary" id="updateStatusMessage">Change the status of this distribution</p>
            </div>

            <form id="updateStatusForm" method="POST" action="../backend/process_distribution.php">
                <input type="hidden" id="updateDistributionId" name="distribution_id">
                <input type="hidden" name="action" value="update_status">
                
                <div class="mb-4">
                    <label for="newStatus" class="block text-sm font-medium text-text-primary mb-2">
                        New Status
                    </label>
                    <select id="newStatus" name="status" class="input w-full" required>
                        <option value="pending">Pending</option>
                        <option value="distributed">Distributed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label for="updateRemarks" class="block text-sm font-medium text-text-primary mb-2">
                        Remarks (Optional)
                    </label>
                    <textarea id="updateRemarks" name="remarks" rows="2" 
                              class="input w-full" placeholder="Add remarks for status change..."></textarea>
                </div>

                <div class="flex gap-3">
                    <button type="button" id="cancelUpdate" class="btn btn-outline flex-1">Cancel</button>
                    <button type="submit" class="btn btn-primary flex-1">Update Status</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteConfirmModal" class="hidden fixed inset-0 bg-secondary-900 bg-opacity-50 z-modal flex items-center justify-center p-4">
        <div class="card max-w-md w-full">
            <div class="text-center mb-6">
                <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-error-100 flex items-center justify-center">
                    <svg class="w-8 h-8 text-error-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-text-primary mb-2">Confirm Delete</h3>
                <p class="text-text-secondary" id="deleteConfirmMessage">Are you sure you want to delete this distribution record?</p>
            </div>

            <form method="POST" action="../backend/process_distribution.php">
                <input type="hidden" id="deleteDistributionId" name="distribution_id">
                <input type="hidden" name="action" value="delete">
                
                <div class="flex gap-3">
                    <button type="button" id="cancelDelete" class="btn btn-outline flex-1">Cancel</button>
                    <button type="submit" class="btn btn-error flex-1">Yes, Delete</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Distribution Modal -->
    <div id="editDistributionModal" class="modal-overlay hidden">
        <div class="modal-container max-w-2xl w-full">

            <div class="flex items-center justify-between px-6 py-4 border-b">
                <h3 class="text-lg font-semibold">Edit Distribution</h3>
                <button id="closeEditModal">✕</button>
            </div>

            <form id="editDistributionForm" class="p-6">

                <input type="hidden" name="distribution_id" id="edit_distribution_id">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    <!-- Health Center -->
                    <div>
                        <label class="block text-sm font-medium mb-2">
                            Health Center
                        </label>
                        <select name="health_center_id"
                                id="edit_health_center"
                                class="input w-full">
                        </select>
                    </div>

                    <!-- Medicine -->
                    <div>
                        <label class="block text-sm font-medium mb-2">
                            Medicine
                        </label>
                        <select name="medicine_id"
                                id="edit_medicine"
                                class="input w-full">
                        </select>
                    </div>

                    <!-- Quantity -->
                    <div>
                        <label class="block text-sm font-medium mb-2">
                            Quantity
                        </label>
                        <input type="number"
                            name="quantity"
                            id="edit_quantity"
                            class="input w-full">
                    </div>

                    <!-- Status -->
                    <div>
                        <label class="block text-sm font-medium mb-2">
                            Status
                        </label>
                        <select name="status"
                                id="edit_status"
                                class="input w-full">
                            <option value="pending">Pending</option>
                            <option value="distributed">Distributed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>

                    <!-- Remarks (Full Width) -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium mb-2">
                            Remarks
                        </label>
                        <textarea name="remarks"
                                id="edit_remarks"
                                rows="3"
                                class="input w-full">
                        </textarea>
                    </div>

                </div>

                <!-- Buttons -->
                <div class="flex justify-end gap-3 mt-6 pt-6 border-t">
                    <button type="button"
                            id="cancelEditModal"
                            class="btn btn-outline">
                        Cancel
                    </button>

                    <button type="submit"
                            class="btn btn-primary">
                        Save Changes
                    </button>
                </div>

            </form>


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