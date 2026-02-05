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

require "../backend/encoder_inventory_pagination.php";
require "../backend/encoder_inventory_fetch.php";
require "../backend/encoder_inventory_summary.php";
require "../backend/encoder_inventory_fetchsupplier.php";

$start = $offset + 1;
$end   = min($offset + $limit, $totalRecords);


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="CAVMED Inventory Management System - Comprehensive pharmaceutical inventory tracking and supply chain management">
    <title>Inventory Management System - CAVMED Portal</title>
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="../css/encoder_inventory.css">

  <script type="module" async src="https://static.rocket.new/rocket-web.js?_cfg=https%3A%2F%2Fcavmedporta6876back.builtwithrocket.new&_be=https%3A%2F%2Fapplication.rocket.new&_v=0.1.10"></script>
  <script type="module" defer src="https://static.rocket.new/rocket-shot.js?v=0.0.1"></script>
  <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
  <script defer src="../js/encoder_inventory_modal.js"></script>
  <script defer src="../js/encoder_inventory_search.js"></script>
  <script defer src="../js/encoder_inventory_sort.js"></script>
  <script defer src="../js/encoder_inventory_filter.js"></script>
  <script defer src="../js/encoder_inventory_rightpanel.js"></script>
  <script defer src="../js/encoder_inventory_supplier.js"></script>
  <script defer src="../js/encoder_inventory_suppliermodal.js"></script>
  <script defer src="../js/encoder_inventory_supplieractions.js"></script>
  <script defer src="../js/encoder_inventory_actions.js"></script>
  

     
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

        <!-- Navigation Tabs -->
        <nav class="bg-surface border-b border-border px-6 no-print">        
            <div class="px-6">
                <div class="flex items-center gap-1 overflow-x-auto scrollbar-thin">

                    <a href="encoder_inventory.php" class="nav-item nav-item-active whitespace-nowrap">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        <span>Inventory List</span>
                    </a>

                    <a href="encoder_dispose.php" class="nav-item">
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

        <!-- Inventory Summary Bar -->
        <div class="bg-secondary-50 border-t border-border px-6 py-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-8">
                    <!-- Total Items -->
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-primary" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"/>
                        </svg>
                        <div>
                            <p class="text-xs text-text-secondary">Total Items</p>
                            <p class="text-lg font-semibold text-text-primary">
                                <?= number_format($totalItems); ?>
                            </p>
                        </div>
                    </div>

                    <!-- Low Stock Alerts -->
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-warning" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        <div>
                            <p class="text-xs text-text-secondary">Critical</p>
                            <p class="text-lg font-semibold text-warning">
                                <?= number_format($criticalItems); ?>
                            </p>
                        </div>
                    </div>

                    <!-- Expiry Warnings -->
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-error" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                        <div>
                            <p class="text-xs text-text-secondary">Expiring Soon</p>
                            <p class="text-lg font-semibold text-error">
                                <?= number_format($expiringSoon); ?>
                            </p>
                        </div>
                    </div>

                    <!-- Total Value -->
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-success" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/>
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"/>
                        </svg>
                        <div>
                            <p class="text-xs text-text-secondary">Total Value</p>
                            <p class="text-lg font-semibold text-success">
                                ₱<?= number_format($totalValue, 2); ?>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Last Updated -->
                <div class="text-right">
                    <p class="text-xs text-text-secondary">Last Updated</p>
                    <p class="text-sm font-medium text-text-primary">
                        <?= $lastUpdated ? date("M d, Y h:i A", strtotime($lastUpdated)) : "N/A"; ?>
                    </p>
                </div>
            </div>
        </div>
    </header>


    <!-- Main Content Area -->
    <main class="flex h-full overflow-hidden">
        <!-- Left Panel - Inventory Grid (60%) -->
        <div class="flex-1 min-w-0 flex flex-col border-r border-border bg-surface">
            <!-- Toolbar -->
            <div class="border-b border-border p-4 space-y-4">
                <!-- Search & Actions Row -->
                <div class="flex items-center gap-3">
                    <!-- Search Bar -->
                    <div class="flex-1 relative">
                        <input type="text" id="inventorySearch" placeholder="Search by medicine name, category, or supplier..."
                               class="input pl-10 pr-4">
                    </div>

                    <!-- Quick Actions -->
                    <button class="btn btn-primary" id="addNewItemBtn">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        Add New Item
                    </button>
                    
                    <button class="btn btn-primary" id="addNewSupplierBtn">
                        Suppliers
                    </button>
                </div>

                <div class="flex items-center gap-3">
                    <!-- Category -->
                    <select id="filterCategory"  class="input w-48">
                        <option value="">All Categories</option>
                        <option value="antibiotics">Antibiotics</option>
                        <option value="analgesics">Analgesics</option>
                        <option value="vitamins">Vitamins</option>
                        <option value="cardiovascular">Cardiovascular</option>
                        <option value="respiratory">Respiratory</option>
                        <option value="gastrointestinal">Gastrointestinal</option>
                    </select>

                    <!-- Stock Status -->
                    <select id="filterStock" class="input w-40">
                        <option value="">All Stock</option>
                        <option value="in_stock">In Stock</option>
                        <option value="low_stock">Low Stock</option>
                        <option value="out_of_stock">Out of Stock</option>
                    </select>

                    <!-- Expiry -->
                    <select id="filterExpiry" class="input w-44">
                        <option value="">All Expiry</option>
                        <option value="expired">Expired</option>
                        <option value="30">Expiring in 30 Days</option>
                        <option value="90">Expiring in 90 Days</option>
                        <option value="valid">Valid (>90 Days)</option>
                    </select>

                    <!-- Submit -->
                    <button id="resetFilters" class="btn btn-primary">
                        Reset
                    </button>
                </div>
                <!-- Results Info -->
                <div class="flex items-center justify-between text-sm">
                    <p class="text-text-secondary">
                        Showing
                        <span class="font-medium text-text-primary">
                            <?= $start ?>–<?= $end ?>
                        </span>
                        of
                        <span class="font-medium text-text-primary">
                            <?= number_format($totalRecords) ?>
                        </span>
                        items
                    </p>

                    <div class="flex items-center gap-2">
                        <label class="text-text-secondary">Sort by:</label>

                        <select id="sortBy" class="input py-1 text-sm w-40">
                            <option value="name-asc">Name (A–Z)</option>
                            <option value="name-desc">Name (Z–A)</option>
                            <option value="stock-low">Stock (Low → High)</option>
                            <option value="stock-high">Stock (High → Low)</option>
                            <option value="expiry-soon">Expiry (Soonest)</option>
                            <option value="recently-added">Recently Added</option>
                        </select>
                    </div>


                    <!-- Preserve pagination -->
                    <input type="hidden" name="page" value="1">
                    <input type="hidden" name="limit" value="<?= $limit ?>">
                </div>
            </div>

            <!-- Inventory Table -->
            <div class="flex-1 overflow-auto scrollbar-thin">
                <table class="w-full">
                    <thead class="bg-secondary-50 sticky top-0 z-10">
                        <tr class="text-left text-xs font-medium text-text-secondary uppercase tracking-wider">
                            <th class="px-4 py-3">Medicine Name</th>
                            <th class="px-4 py-3">Category</th>
                            <th class="px-4 py-3">Current Stock</th>
                            <th class="px-4 py-3">Reorder Point</th>
                            <th class="px-4 py-3">Manufacturing Date</th>
                            <th class="px-4 py-3">Expiry Date</th>
                            <th class="px-4 py-3">Supplier</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border" id="inventoryTableBody">

                    <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>

                    <?php
                        // Status badge styles
                        $statusClass = match ($row['status']) {
                            'in_stock' => 'badge-success',
                            'low_stock' => 'badge-warning',
                            'out_of_stock' => 'badge-error',
                            'expired' => 'badge-error',
                            default => 'badge-secondary'
                        };

                        // Stock warning color
                        $stockClass = ($row['current_stock'] <= $row['reorder_point'])
                            ? 'text-warning'
                            : 'text-text-primary';
                    ?>

                    <tr class="hover:bg-secondary-50 cursor-pointer inventory-row"
                        data-id="<?= $row['id']; ?>"
                        data-supplier-id="<?= $row['supplier_id']; ?>"
                        data-name="<?= htmlspecialchars($row['medicine_name']); ?>"
                        data-type="<?= ucfirst($row['medicine_type']); ?>"
                        data-unit="<?= htmlspecialchars($row['unit_of_measure']); ?>"
                        data-category="<?= htmlspecialchars($row['category']); ?>"
                        data-status="<?= $row['status']; ?>"
                        data-stock="<?= $row['current_stock']; ?>"
                        data-reorder="<?= $row['reorder_point']; ?>"
                        data-fundingsource="<?= $row['funding_source']; ?>"
                        data-price="<?= $row['unit_price']; ?>"
                        data-manufacturing="<?= $row['manufacturing_date']; ?>"
                        data-expiry="<?= $row['expiry_date']; ?>"     
                        data-supplier="<?= htmlspecialchars($row['supplier_name']); ?>"
                        data-contact="<?= htmlspecialchars($row['contact_person']); ?>"
                        data-barcode="<?= htmlspecialchars($row['barcode']); ?>"
                        data-batch="<?= htmlspecialchars($row['batch_number']); ?>"
                        data-notes="<?= htmlspecialchars($row['notes']); ?>"
                        data-phone="<?= htmlspecialchars($row['contact_number']); ?>"
                        data-suppliertype="<?= htmlspecialchars($row['supplier_type']); ?>"
                        data-email="<?= htmlspecialchars($row['email']); ?>">



                        <!-- Medicine Name -->
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-primary-50 rounded flex items-center justify-center flex-shrink-0">
                                    <!-- ICON (unchanged) -->
                                    <svg class="w-6 h-6 text-primary" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M10 2a1 1 0 011 1v1.323l3.954 1.582
                                            1.599-.8a1 1 0 01.894 1.79l-1.233.616
                                            1.738 5.42a1 1 0 01-.285 1.05A3.989
                                            3.989 0 0115 15a3.989 3.989 0
                                            01-2.667-1.019 1 1 0
                                            01-.285-1.05l1.715-5.349
                                            L11 6.477V16h2a1 1 0
                                            110 2H7a1 1 0
                                            110-2h2V6.477L6.237
                                            7.582l1.715 5.349a1
                                            1 0 01-.285 1.05A3.989
                                            3.989 0 015 15a3.989
                                            3.989 0 01-2.667-1.019
                                            1 1 0 01-.285-1.05l1.738-5.42
                                            -1.233-.617a1 1 0 01.894-1.788
                                            l1.599.799L9 4.323V3a1 1 0 011-1z"
                                            clip-rule="evenodd"/>
                                    </svg>
                                </div>

                                <div>
                                    
                                    <!-- Medicine Name -->
                                    <p class="font-medium text-text-primary">
                                        <?= htmlspecialchars($row['medicine_name']); ?>
                                    </p>

                                    <!-- Type + Unit -->
                                    <p class="text-xs text-text-secondary">
                                        <?= ucfirst($row['medicine_type']); ?> • <?= htmlspecialchars($row['unit_of_measure']); ?>
                                    </p>

                                    <!-- ACTUAL BARCODE -->
                                    <svg class="barcode mt-2"
                                        jsbarcode-format="CODE128"
                                        jsbarcode-value="<?= preg_replace('/[^\x20-\x7E]/', '', $row['barcode']); ?>"
                                        jsbarcode-width="2.5"
                                        jsbarcode-height="60"
                                        jsbarcode-margin="10"
                                        jsbarcode-displayValue="true"
                                        jsbarcode-fontSize="12">
                                    </svg>


                                </div>

                            </div>
                        </td>

                        <!-- Category -->
                        <td class="px-4 py-3">
                            <span class="badge badge-secondary">
                                <?= htmlspecialchars($row['category']); ?>
                            </span>
                        </td>

                        <!-- Current Stock -->
                        <td class="px-4 py-3">
                            <p class="font-medium <?= $stockClass; ?>">
                                <?= number_format($row['current_stock']); ?> units
                            </p>
                        </td>

                        <!-- Reorder Point -->
                        <td class="px-4 py-3">
                            <p class="text-text-secondary">
                                <?= number_format($row['reorder_point']); ?> units
                            </p>
                        </td>

                        <!-- Manufacturing Date -->
                        <td class="px-4 py-3">
                            <p class="text-text-primary">
                                <?= date("M d, Y", strtotime($row['manufacturing_date'])); ?>
                            </p>
                        </td>

                        <!-- Expiry Date -->
                        <td class="px-4 py-3">
                            <p class="<?= ($row['status'] === 'expired') ? 'text-error' : 'text-text-primary'; ?>">
                                <?= date("M d, Y", strtotime($row['expiry_date'])); ?>
                            </p>
                        </td>

                        <!-- Supplier -->
                        <td class="px-4 py-3">
                            <p class="text-text-secondary">
                                <?= htmlspecialchars($row['supplier_name']); ?>
                            </p>
                        </td>

                        <!-- Status -->
                        <td class="px-4 py-3">
                            <span class="badge <?= $statusClass; ?>">
                                <?= ucwords(str_replace('_', ' ', $row['status'])); ?>
                            </span>
                        </td>

                        <!-- Actions -->
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-4">
                                <button type="button" class="text-text-secondary hover:text-primary transition-colors editItemBtn">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M11 5H6a2 2 0 00-2 2v11a2
                                                2 0 002 2h11a2 2 0 002-2v-5
                                                m-1.414-9.414a2 2 0 112.828
                                                2.828L11.828 15H9v-2.828
                                                l8.586-8.586z"/>
                                    </svg>
                                </button>

                                <button type="button" class="text-text-secondary hover:text-primary transition-colors deleteItemBtn" data-id="<?= $row['id']; ?>" data-name="<?= htmlspecialchars($row['medicine_name']); ?>">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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

                    <?php endwhile; ?>
                    <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center py-6 text-text-secondary">
                            No medicines found.
                        </td>
                    </tr>
                    <?php endif; ?>

                    </tbody>

                </table>
            </div>


        <div class="border-t border-border p-4 flex items-center justify-between">

            <!-- Rows per page -->
            <div class="flex items-center gap-2">
                <label class="text-sm text-text-secondary">Rows per page:</label>
                <form method="get">
                    <select name="limit" class="input py-1 text-sm w-20"
                            onchange="this.form.submit()">
                        <?php foreach ([25,50,100] as $l): ?>
                            <option value="<?= $l ?>" <?= ($limit == $l) ? 'selected' : '' ?>>
                                <?= $l ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="hidden" name="page" value="1">
                </form>
            </div>

            <!-- Page controls -->
            <div class="flex items-center gap-2">

                <a href="?page=<?= max(1, $page-1) ?>&limit=<?= $limit ?>"
                class="btn btn-ghost p-2 <?= ($page <= 1) ? 'pointer-events-none opacity-50' : '' ?>">
                    ‹
                </a>

                <span class="text-sm text-text-secondary">
                    Page <?= $page ?> of <?= $totalPages ?>
                </span>

                <a href="?page=<?= min($totalPages, $page+1) ?>&limit=<?= $limit ?>"
                class="btn btn-ghost p-2 <?= ($page >= $totalPages) ? 'pointer-events-none opacity-50' : '' ?>">
                    ›
                </a>

            </div>
        </div>
    </div>

        <!-- Right Panel - Detailed Item View (40%) -->
    <div id="detailPanel" class="w-2/5 bg-surface flex flex-col border-l border-border hidden transition-transform duration-300 ease-in-out">
            <div class="border-b border-border p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-semibold text-text-primary">Item Details</h2>
                    <button class="text-text-secondary hover:text-text-primary transition-colors" id="closeDetailPanel">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Item Header -->
                <div class="flex items-start gap-4">
                    <div class="w-16 h-16 bg-primary-50 rounded-lg flex items-center justify-center flex-shrink-0">
                        <svg class="w-10 h-10 text-primary" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1.323l3.954 1.582 1.599-.8a1 1 0 01.894 1.79l-1.233.616 1.738 5.42a1 1 0 01-.285 1.05A3.989 3.989 0 0115 15a3.989 3.989 0 01-2.667-1.019 1 1 0 01-.285-1.05l1.715-5.349L11 6.477V16h2a1 1 0 110 2H7a1 1 0 110-2h2V6.477L6.237 7.582l1.715 5.349a1 1 0 01-.285 1.05A3.989 3.989 0 015 15a3.989 3.989 0 01-2.667-1.019 1 1 0 01-.285-1.05l1.738-5.42-1.233-.617a1 1 0 01.894-1.788l1.599.799L9 4.323V3a1 1 0 011-1z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 id="detailName" class="text-lg font-semibold text-text-primary"></h3>
                        <p id="detailMeta" class="text-sm text-text-secondary"></p>
                        <div class="flex items-center gap-2 mt-2">
                            <span id="detailCategory" class="badge badge-primary"></span>
                            <span id="detailStatus" class="badge badge-success"></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Detail Panel Content -->
            <div class="flex-1 overflow-auto scrollbar-thin p-6 space-y-6">
                <!-- Stock Information -->
                <div class="card">
                    <h4 class="text-sm font-semibold text-text-primary mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-primary" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"/>
                        </svg>
                        Stock Information
                    </h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs text-text-secondary mb-1">Current Stock</p>
                            <p id="detailStock" class="text-lg font-semibold text-text-primary"></p>
                        </div>
                        <div>
                            <p class="text-xs text-text-secondary mb-1">Reorder Point</p>
                            <p id="detailReorder" class="text-lg font-semibold text-text-primary"></p>
                        </div>
                        <div>
                            <p class="text-xs text-text-secondary mb-1">Unit Price</p>
                            <p id="detailPrice" class="text-lg font-semibold text-text-primary"></p>
                        </div>
                        <div>
                            <p class="text-xs text-text-secondary mb-1">Total Value</p>
                            <p id="detailTotalValue" class="text-lg font-semibold text-success"></p>
                        </div>
                        <div>
                            <p class="text-xs text-text-secondary mb-1">Manufacturing Date</p>
                            <p id="detailManufacturing" class="text-lg font-semibold text-success"></p>
                        </div>
                        <div>
                            <p class="text-xs text-text-secondary mb-1">Expiry Date</p>
                            <p id="detailExpiry" class="text-lg font-semibold text-success"></p>
                        </div>
                    </div>
                </div>

                <!-- Supplier Information -->
                <div class="card">
                    <h4 class="text-sm font-semibold text-text-primary mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-primary" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                        </svg>
                        Supplier Details
                    </h4>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <p class="text-sm text-text-secondary">Supplier Name</p>
                            <p id="detailSupplier" class="text-sm font-medium text-text-primary"></p>
                        </div>
                        <div class="flex items-center justify-between">
                            <p class="text-sm text-text-secondary">Contact Person</p>
                            <p id="detailContact" class="text-sm font-medium text-text-primary"></p>
                        </div>
                        <div class="flex items-center justify-between">
                            <p class="text-sm text-text-secondary">Phone</p>
                            <p id="detailPhone" class="text-sm font-medium text-text-primary"></p>
                        </div>
                        <div class="flex items-center justify-between">
                            <p class="text-sm text-text-secondary">Email</p>
                            <p id="detailEmail" class="text-sm font-medium text-primary"></p>
                        </div>
                        <div class="flex items-center justify-between">
                            <p class="text-sm text-text-secondary">Supplier Type</p>
                            <p id="detailType" class="text-sm font-medium text-text-primary"></p>
                        </div>
                    </div>
                </div>

                <!-- Transaction History -->
                <div class="card">
                    <h4 class="text-sm font-semibold text-text-primary mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-primary" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                        </svg>
                        Recent Transactions
                    </h4>
                    <div class="space-y-3">
                        <div class="flex items-start gap-3 p-3 bg-success-50 rounded-base">
                            <svg class="w-5 h-5 text-success flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-text-primary">Stock Added</p>
                                <p class="text-xs text-text-secondary">+500 units • Dec 5, 2025 2:30 PM</p>
                                <p class="text-xs text-text-secondary mt-1">By: Maria Santos</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3 p-3 bg-secondary-50 rounded-base">
                            <svg class="w-5 h-5 text-text-secondary flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-text-primary">Stock Dispensed</p>
                                <p class="text-xs text-text-secondary">-150 units • Dec 4, 2025 10:15 AM</p>
                                <p class="text-xs text-text-secondary mt-1">By: Pharmacy Dept.</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3 p-3 bg-success-50 rounded-base">
                            <svg class="w-5 h-5 text-success flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-text-primary">Delivery Received</p>
                                <p class="text-xs text-text-secondary">+1,000 units • Dec 1, 2025 9:00 AM</p>
                                <p class="text-xs text-text-secondary mt-1">By: Maria Santos</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card">
                    <h4 class="text-sm font-semibold text-text-primary mb-4">Quick Actions</h4>
                    <div class="grid grid-cols-2 gap-3">
                        <button class="btn btn-outline text-sm">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            Add Stock
                        </button>
                        <button class="btn btn-outline text-sm">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                            </svg>
                            Deduct Stock
                        </button>
                        <button class="btn btn-outline text-sm">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            Edit Details
                        </button>
                        <button class="btn btn-outline text-sm">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Generate Report
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Floating Action Button for Quick Add -->
    <button class="fixed bottom-8 right-8 w-14 h-14 bg-primary text-white rounded-full shadow-lg hover:shadow-xl hover:bg-primary-700 transition-all flex items-center justify-center z-fixed" id="quickAddFab">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
        </svg>
    </button>

    <!-- Navigation Sidebar (Hidden by default, toggle with hamburger) -->
    <nav class="fixed left-0 top-0 h-full w-64 bg-surface border-r border-border shadow-lg transform -translate-x-full transition-transform duration-300 z-modal" id="navSidebar">
        <div class="p-6">
            <div class="flex items-center justify-between mb-8">
                <h2 class="text-lg font-semibold text-text-primary">Navigation</h2>
                <button class="text-text-secondary hover:text-text-primary transition-colors" id="closeSidebar">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="space-y-2">
                 <a href="encoder_inventory.php" class="nav-item nav-item-active whitespace-nowrap">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    <span>Inventory List</span>
                </a>

                <a href="encoder_dispose.php" class="nav-item">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span>Inventory Dispose</span>
                </a>

                <div class="divider"></div>

                <a href="system_login_portal.html" class="nav-item text-error">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    Logout
                </a>
            </div>
        </div>
    </nav>

    <!-- Sidebar Overlay -->
    <div class="fixed inset-0 bg-secondary-900 bg-opacity-50 z-modal-backdrop hidden" id="sidebarOverlay"></div>

    <!-- Hamburger Menu Button (Mobile) -->
    <button class="fixed top-4 left-4 p-2 bg-surface rounded-base shadow-md hover:shadow-lg transition-shadow z-fixed md:hidden" id="hamburgerBtn">
        <svg class="w-6 h-6 text-text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
        </svg>
    </button>

    <!-- Add New Item Modal -->
    <div id="addItemModal" class="modal-overlay hidden">
        <div id="addMedicineModal"class="modal-container max-w-4xl w-full max-h-[90vh] flex flex-col">

            <div class="modal-header flex-shrink-0">
                <h2 id="addItemModalTitle" class="text-lg font-semibold text-text-primary">
                    Add New Medicine
                </h2>
                <button id="closeModal" class="text-text-tertiary hover:text-text-primary">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form id="addItemForm"
                method="post"
                action="../backend/encoder_inventory_add.php"
                class="modal-content space-y-4 overflow-y-auto px-6 py-4 flex-1">

                <input type="hidden" name="medicine_id" id="editMedicineId">

                <div class="grid grid-cols-2 gap-3">
                    

                    <!-- Medicine Name -->
                    <div>
                        <label class="block text-sm font-medium text-text-secondary mb-1">
                            Medicine Name
                        </label>
                        <input type="text" name="medicine_name" class="input w-full" required>
                    </div>

                    <!-- Category -->
                    <div>
                        <label class="block text-sm font-medium text-text-secondary mb-1">
                            Category
                        </label>
                        <input type="text" name="category"
                            class="input w-full" required>
                    </div>
                </div>

                <!-- Medicine Type + Funding Source -->
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-text-secondary mb-1">
                            Medicine Type
                        </label>
                        <select name="medicine_type" class="input w-full" required>
                            <option value="">Select</option>
                            <option value="generic">Generic</option>
                            <option value="branded">Branded</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-text-secondary mb-1">
                            Funding Source
                        </label>
                        <select name="funding_source" class="input w-full" required>
                            <option value="">Select</option>
                            <option value="donation">Donation</option>
                            <option value="city_health">City Health</option>
                            <option value="mixed">Mixed</option>
                        </select>
                    </div>
                </div>

                <!-- Unit of Measure + Unit Price -->
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-text-secondary mb-1">
                            Unit of Measure
                        </label>
                        <select name="unit_of_measure" class="input w-full" required>
                            <option value="">Select</option>
                            <option value="tablet">Tablet</option>
                            <option value="capsule">Capsule</option>
                            <option value="bottle">Bottle</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-text-secondary mb-1">
                            Unit Price (₱)
                        </label>
                        <input type="number" name="unit_price"
                            class="input w-full" step="0.01" min="0" required>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-text-secondary mb-1">
                        Batch Number
                    </label>
                    <input type="text" name="batch_number"
                        class="input w-full" required>
                </div>
                    
                <!-- Batch + Manufacturing -->
                <div class="grid grid-cols-2 gap-3">
 

                    <div>
                        <label class="block text-sm font-medium text-text-secondary mb-1">
                            Manufacturing Date
                        </label>
                        <input type="date" name="manufacturing_date"
                            class="input w-full" required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-text-secondary mb-1">
                            Expiry Date
                        </label>
                        <input type="date" name="expiry_date"
                            class="input w-full" required>
                    </div>

                </div>


                <!-- Stock -->
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-text-secondary mb-1">
                            Initial Stock
                        </label>
                        <input type="number" name="current_stock"
                            class="input w-full" min="0" value="0" required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-text-secondary mb-1">
                            Reorder Point
                        </label>
                        <input type="number" name="reorder_point"
                            class="input w-full" min="0" value="0" required>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">

                    <!-- Supplier (DB DROPDOWN) -->
                    <div>
                        <label class="block text-sm font-medium text-text-secondary mb-1">
                            Supplier
                        </label>
                        <select name="supplier_id" class="input w-full" required>
                            <option value="">Select supplier</option>
                            <?php while ($s = $suppliersResult->fetch_assoc()): ?>
                                <option value="<?= $s['id']; ?>">
                                    <?= htmlspecialchars($s['supplier_name']); ?>
                                    (<?= ucfirst($s['supplier_type']); ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <!-- Barcode -->
                    <div>
                        <label class="block text-sm font-medium text-text-secondary mb-1">
                            Barcode
                        </label>
                        <input type="text" name="barcode" class="input w-full" required>
                    </div>

                </div>

                <!-- Notes -->
                <div>
                    <label class="block text-sm font-medium text-text-secondary mb-1">
                        Notes
                    </label>
                    <textarea name="notes"
                            class="input w-full h-20"></textarea>
                </div>

                <!-- Actions -->
                <div class="flex justify-end gap-2 pt-4">
                    <button type="button" id="cancelAddItem" class="btn btn-ghost">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        Save Item
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Supplier List Modal -->
    <div id="supplierModal" class="modal-overlay hidden">
        <div class="modal-container supplier-modal flex flex-col">

            <!-- Header -->
            <div class="modal-header flex items-center justify-between px-6 py-4 border-b border-border">
                <h2 class="text-lg font-semibold text-text-primary">Suppliers</h2>
                <button id="closeSupplierModal" class="text-text-tertiary hover:text-text-primary text-xl">
                    ✕
                </button>
            </div>

            <!-- Content -->
            <div class="flex-1 overflow-auto px-6 py-4">
                <table class="w-full min-w-[1000px] border border-border rounded">
                    <thead class="bg-secondary-50 sticky top-0 z-10">
                        <tr class="text-xs uppercase text-text-secondary">
                            <th class="px-4 py-3 text-left">Supplier Name</th>
                            <th class="px-4 py-3 text-left">Type</th>
                            <th class="px-4 py-3 text-left">Contact Person</th>
                            <th class="px-4 py-3 text-left">Phone</th>
                            <th class="px-4 py-3 text-left">Email</th>
                            <th class="px-4 py-3 text-left">Address</th>
                            <th class="px-4 py-3 text-left">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">

                    <?php
                    mysqli_data_seek($suppliersResult, 0);
                    while ($s = $suppliersResult->fetch_assoc()):
                    ?>
                        <tr class="hover:bg-secondary-50">
                            <td class="px-4 py-3 font-medium">
                                <?= htmlspecialchars($s['supplier_name']) ?>
                            </td>
                            <td class="px-4 py-3">
                                <?= ucfirst($s['supplier_type']) ?>
                            </td>
                            <td class="px-4 py-3">
                                <?= htmlspecialchars($s['contact_person'] ?? '-') ?>
                            </td>
                            <td class="px-4 py-3">
                                <?= htmlspecialchars($s['contact_number'] ?? '-') ?>
                            </td>
                            <td class="px-4 py-3 text-primary">
                                <?= htmlspecialchars($s['email'] ?? '-') ?>
                            </td>
                            <td class="px-4 py-3 text-primary">
                                <?= htmlspecialchars($s['address'] ?? '-') ?>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-4">

                                    <!-- EDIT -->
                                    <button
                                        type="button" class="text-text-secondary hover:text-primary transition-colors editSupplierBtn"
                                        data-id="<?= $s['id'] ?>"
                                        data-name="<?= htmlspecialchars($s['supplier_name']) ?>"
                                        data-type="<?= $s['supplier_type'] ?>"
                                        data-contact="<?= htmlspecialchars($s['contact_person'] ?? '') ?>"
                                        data-phone="<?= htmlspecialchars($s['contact_number'] ?? '') ?>"
                                        data-email="<?= htmlspecialchars($s['email'] ?? '') ?>"
                                        data-address="<?= htmlspecialchars($s['address'] ?? '') ?>"
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

                                    <!-- DELETE -->
                                    <button
                                        type="button" class="text-text-secondary hover:text-primary transition-colors deleteSupplierBtn"
                                        data-id="<?= $s['id'] ?>"
                                        data-name="<?= htmlspecialchars($s['supplier_name']) ?>"
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
                    <?php endwhile; ?>

                    </tbody>
                </table>
            </div>

            <!-- Footer -->
            <div class="border-t border-border px-6 py-4 flex items-center justify-between">

                <button
                    class="btn btn-ghost"
                    id="closeSupplierModalBottom">
                    Close
                </button>

                <button
                    class="btn btn-primary"
                    id="addNewSupplierFromModal">
                    + Add New Supplier
                </button>
            </div>

        </div>
    </div>


         <!-- Add Supplier Modal -->
        <div id="addSupplierModal" class="modal-overlay hidden">
            <div id="addSupplierModalContent" class="modal-container max-w-md w-full flex flex-col">

                <!-- Header -->
                <div class="modal-header flex items-center justify-between px-4 py-3 border-b border-border">
                    <h2 class="text-base font-semibold text-text-primary">
                        Add New Supplier
                    </h2>
                    <button id="closeAddSupplierModal" class="text-lg text-text-secondary">
                        ✕
                    </button>
                </div>

                <!-- Form -->
                <form 
                    id="addSupplierForm"
                    class="px-4 py-3 space-y-3"
                    method="post"
                    action="../backend/encoder_inventory_supplier_add.php"
                >

                    <div class="grid grid-cols-2 gap-4">

                        <input type="hidden" name="supplier_id" id="editSupplierId">


                        <div>
                            <label class="text-xs text-text-secondary">Supplier Name</label>
                            <input name="supplier_name" class="input w-full h-9" required>
                        </div>

                        <div>
                            <label class="text-xs text-text-secondary">Supplier Type</label>
                            <select name="supplier_type" class="input w-full h-9" required>
                                <option value="private">Private</option>
                                <option value="donation">Donation</option>
                                <option value="government">Government</option>
                            </select>
                        </div>

                        <div>
                            <label class="text-xs text-text-secondary">Contact Person</label>
                            <input name="contact_person" class="input w-full h-9">
                        </div>

                        <div>
                            <label class="text-xs text-text-secondary">Contact Number</label>
                            <input name="contact_number" class="input w-full h-9">
                        </div>

                    </div>

                    <div>
                        <label class="text-xs text-text-secondary">Email</label>
                        <input type="email" name="email" class="input w-full h-9">
                    </div>

                    <div>
                        <label class="text-xs text-text-secondary">Address</label>
                        <textarea
                            name="address"
                            class="input w-full h-20 resize-none"
                        ></textarea>
                    </div>

                    <!-- Actions -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <button
                            type="button"
                            class="btn btn-ghost btn-sm"
                            id="cancelAddSupplier"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            class="btn btn-primary btn-sm"
                        >
                            Save
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

<script id="dhws-dataInjector" src="../public/dhws-data-injector.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    JsBarcode(".barcode").init();
});
</script>

</body>
</html>