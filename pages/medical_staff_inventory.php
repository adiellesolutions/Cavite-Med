<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'medical_staff') {
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
    <meta name="description" content="CAVMED Inventory Management System - Comprehensive pharmaceutical inventory tracking and supply chain management">
    <title>Inventory Management System - CAVMED Portal</title>
    <link rel="stylesheet" href="../css/main.css">
  <script type="module" async src="https://static.rocket.new/rocket-web.js?_cfg=https%3A%2F%2Fcavmedporta6876back.builtwithrocket.new&_be=https%3A%2F%2Fapplication.rocket.new&_v=0.1.10"></script>
  <script type="module" defer src="https://static.rocket.new/rocket-shot.js?v=0.0.1"></script>
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

    <nav class="bg-surface border-b border-border px-6 no-print">        
        <div class="px-6">
            <div class="flex items-center gap-1 overflow-x-auto scrollbar-thin">
                
                <a href="medical_staff_dashboard.html" class="nav-item">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    Dashboard
                </a>

                <a href="medical_staff_prescription.html" class="nav-item">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    <span>Dispensation</span>
                </a>

                <a href="medical_staff_patient_registration.php" class="nav-item">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    <span>Patient</span>
                </a>
                <a href="medical_staff_procedures.php" class="nav-item">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    Procedures
                </a>
                <a href="medical_staff_inventory.php" class="nav-item nav-item-active whitespace-nowrap">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    <span>Inventory List</span>
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

    <!-- Main Content Area -->
    <main class="flex-1 flex overflow-hidden">
        <!-- Left Panel - Inventory Grid (60%) -->
        <div class="flex-1 flex flex-col border-r border-border bg-surface">
            <!-- Toolbar -->
            <div class="border-b border-border p-4 space-y-4">
                <!-- Search & Actions Row -->
                <div class="flex items-center gap-3">
                    <!-- Search Bar -->
                    <div class="flex-1 relative">
                        <input type="text" id="inventorySearch" placeholder="Search by medicine name, category, or supplier..."
                               class="input pl-10 pr-4">
                        <svg class="w-5 h-5 text-text-tertiary absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                </div>

                <!-- Filters Row -->
                <div class="flex items-center gap-3">
                    <!-- Category Filter -->
                    <select class="input w-48" id="categoryFilter">
                        <option value="">All Categories</option>
                        <option value="antibiotics">Antibiotics</option>
                        <option value="analgesics">Analgesics</option>
                        <option value="vitamins">Vitamins & Supplements</option>
                        <option value="cardiovascular">Cardiovascular</option>
                        <option value="respiratory">Respiratory</option>
                        <option value="gastrointestinal">Gastrointestinal</option>
                    </select>

                    <!-- Stock Status Filter -->
                    <select class="input w-40" id="stockStatusFilter">
                        <option value="">All Stock</option>
                        <option value="in-stock">In Stock</option>
                        <option value="low-stock">Low Stock</option>
                        <option value="out-of-stock">Out of Stock</option>
                    </select>

                    <!-- Expiry Filter -->
                    <select class="input w-44" id="expiryFilter">
                        <option value="">All Expiry Dates</option>
                        <option value="expired">Expired</option>
                        <option value="30-days">Expiring in 30 Days</option>
                        <option value="90-days">Expiring in 90 Days</option>
                        <option value="valid">Valid (>90 Days)</option>
                    </select>

                    <!-- Supplier Filter -->
                    <select class="input w-44" id="supplierFilter">
                        <option value="">All Suppliers</option>
                        <option value="medpharma">MedPharma Inc.</option>
                        <option value="healthsupply">HealthSupply Corp.</option>
                        <option value="pharmadist">PharmaDistributors</option>
                        <option value="globalmed">GlobalMed Solutions</option>
                    </select>
                </div>

                <!-- Results Info -->
                <div class="flex items-center justify-between text-sm">
                    <p class="text-text-secondary">
                        Showing <span class="font-medium text-text-primary">1-25</span> of <span class="font-medium text-text-primary">1,247</span> items
                    </p>
                    <div class="flex items-center gap-2">
                        <label class="text-text-secondary">Sort by:</label>
                        <select class="input py-1 text-sm w-40" id="sortBy">
                            <option value="name-asc">Name (A-Z)</option>
                            <option value="name-desc">Name (Z-A)</option>
                            <option value="stock-low">Stock (Low to High)</option>
                            <option value="stock-high">Stock (High to Low)</option>
                            <option value="expiry-soon">Expiry (Soonest First)</option>
                            <option value="recently-added">Recently Added</option>
                        </select>
                    </div>
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
                            <th class="px-4 py-3">Expiry Date</th>
                            <th class="px-4 py-3">Supplier</th>
                            <th class="px-4 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border" id="inventoryTableBody">
                        <!-- Row 1 -->
                        <tr class="hover:bg-secondary-50 cursor-pointer inventory-row" data-item-id="1">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-primary-50 rounded flex items-center justify-center flex-shrink-0">
                                        <svg class="w-6 h-6 text-primary" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1.323l3.954 1.582 1.599-.8a1 1 0 01.894 1.79l-1.233.616 1.738 5.42a1 1 0 01-.285 1.05A3.989 3.989 0 0115 15a3.989 3.989 0 01-2.667-1.019 1 1 0 01-.285-1.05l1.715-5.349L11 6.477V16h2a1 1 0 110 2H7a1 1 0 110-2h2V6.477L6.237 7.582l1.715 5.349a1 1 0 01-.285 1.05A3.989 3.989 0 015 15a3.989 3.989 0 01-2.667-1.019 1 1 0 01-.285-1.05l1.738-5.42-1.233-.617a1 1 0 01.894-1.788l1.599.799L9 4.323V3a1 1 0 011-1z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="font-medium text-text-primary">Amoxicillin 500mg</p>
                                        <p class="text-xs text-text-secondary">Capsule</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="badge badge-primary">Antibiotics</span>
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-text-secondary">500 units</p>
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-text-primary">Mar 15, 2026</p>
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-text-secondary">MedPharma Inc.</p>
                            </td>
                            <td class="px-4 py-3">
                                <span class="badge badge-success">In Stock</span>
                            </td>
                        </tr>

                        <!-- Row 2 -->
                        <tr class="hover:bg-secondary-50 cursor-pointer inventory-row" data-item-id="2">

                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-warning-50 rounded flex items-center justify-center flex-shrink-0">
                                        <svg class="w-6 h-6 text-warning" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1.323l3.954 1.582 1.599-.8a1 1 0 01.894 1.79l-1.233.616 1.738 5.42a1 1 0 01-.285 1.05A3.989 3.989 0 0115 15a3.989 3.989 0 01-2.667-1.019 1 1 0 01-.285-1.05l1.715-5.349L11 6.477V16h2a1 1 0 110 2H7a1 1 0 110-2h2V6.477L6.237 7.582l1.715 5.349a1 1 0 01-.285 1.05A3.989 3.989 0 015 15a3.989 3.989 0 01-2.667-1.019 1 1 0 01-.285-1.05l1.738-5.42-1.233-.617a1 1 0 01.894-1.788l1.599.799L9 4.323V3a1 1 0 011-1z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="font-medium text-text-primary">Paracetamol 500mg</p>
                                        <p class="text-xs text-text-secondary">Tablet</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="badge badge-secondary">Analgesics</span>
                            </td>
                            <td class="px-4 py-3">
                                <p class="font-medium text-warning">350 units</p>
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-text-primary">Jun 20, 2026</p>
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-text-secondary">HealthSupply Corp.</p>
                            </td>
                            <td class="px-4 py-3">
                                <span class="badge badge-warning">Critical</span>
                            </td>
                        </tr>

                        <!-- Row 3 -->
                        <tr class="hover:bg-secondary-50 cursor-pointer inventory-row" data-item-id="3">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-error-50 rounded flex items-center justify-center flex-shrink-0">
                                        <svg class="w-6 h-6 text-error" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1.323l3.954 1.582 1.599-.8a1 1 0 01.894 1.79l-1.233.616 1.738 5.42a1 1 0 01-.285 1.05A3.989 3.989 0 0115 15a3.989 3.989 0 01-2.667-1.019 1 1 0 01-.285-1.05l1.715-5.349L11 6.477V16h2a1 1 0 110 2H7a1 1 0 110-2h2V6.477L6.237 7.582l1.715 5.349a1 1 0 01-.285 1.05A3.989 3.989 0 015 15a3.989 3.989 0 01-2.667-1.019 1 1 0 01-.285-1.05l1.738-5.42-1.233-.617a1 1 0 01.894-1.788l1.599.799L9 4.323V3a1 1 0 011-1z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="font-medium text-text-primary">Ibuprofen 400mg</p>
                                        <p class="text-xs text-text-secondary">Tablet</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="badge badge-secondary">Analgesics</span>
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-text-secondary">400 units</p>
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-error">Jan 10, 2026</p>
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-text-secondary">PharmaDistributors</p>
                            </td>
                            <td class="px-4 py-3">
                                <span class="badge badge-error">Expiring Soon</span>
                            </td>
                        </tr>

                        <!-- Row 4 -->
                        <tr class="hover:bg-secondary-50 cursor-pointer inventory-row" data-item-id="4">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-success-50 rounded flex items-center justify-center flex-shrink-0">
                                        <svg class="w-6 h-6 text-success" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1.323l3.954 1.582 1.599-.8a1 1 0 01.894 1.79l-1.233.616 1.738 5.42a1 1 0 01-.285 1.05A3.989 3.989 0 0115 15a3.989 3.989 0 01-2.667-1.019 1 1 0 01-.285-1.05l1.715-5.349L11 6.477V16h2a1 1 0 110 2H7a1 1 0 110-2h2V6.477L6.237 7.582l1.715 5.349a1 1 0 01-.285 1.05A3.989 3.989 0 015 15a3.989 3.989 0 01-2.667-1.019 1 1 0 01-.285-1.05l1.738-5.42-1.233-.617a1 1 0 01.894-1.788l1.599.799L9 4.323V3a1 1 0 011-1z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="font-medium text-text-primary">Vitamin C 1000mg</p>
                                        <p class="text-xs text-text-secondary">Tablet</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="badge badge-success">Vitamins</span>
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-text-secondary">600 units</p>
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-text-primary">Sep 30, 2026</p>
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-text-secondary">GlobalMed Solutions</p>
                            </td>
                            <td class="px-4 py-3">
                                <span class="badge badge-success">In Stock</span>
                            </td>
                        </tr>

                        <!-- Row 5 -->
                        <tr class="hover:bg-secondary-50 cursor-pointer inventory-row" data-item-id="5">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-primary-50 rounded flex items-center justify-center flex-shrink-0">
                                        <svg class="w-6 h-6 text-primary" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1.323l3.954 1.582 1.599-.8a1 1 0 01.894 1.79l-1.233.616 1.738 5.42a1 1 0 01-.285 1.05A3.989 3.989 0 0115 15a3.989 3.989 0 01-2.667-1.019 1 1 0 01-.285-1.05l1.715-5.349L11 6.477V16h2a1 1 0 110 2H7a1 1 0 110-2h2V6.477L6.237 7.582l1.715 5.349a1 1 0 01-.285 1.05A3.989 3.989 0 015 15a3.989 3.989 0 01-2.667-1.019 1 1 0 01-.285-1.05l1.738-5.42-1.233-.617a1 1 0 01.894-1.788l1.599.799L9 4.323V3a1 1 0 011-1z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="font-medium text-text-primary">Metformin 500mg</p>
                                        <p class="text-xs text-text-secondary">Tablet</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="badge badge-primary">Cardiovascular</span>
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-text-secondary">450 units</p>
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-text-primary">Aug 12, 2026</p>
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-text-secondary">MedPharma Inc.</p>
                            </td>
                            <td class="px-4 py-3">
                                <span class="badge badge-success">In Stock</span>
                            </td>
                        </tr>

                        <!-- Additional rows would continue here... -->
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="border-t border-border p-4 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <label class="text-sm text-text-secondary">Rows per page:</label>
                    <select class="input py-1 text-sm w-20">
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>

                <div class="flex items-center gap-2">
                    <button class="btn btn-ghost p-2" disabled>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </button>
                    <span class="text-sm text-text-secondary">Page 1 of 50</span>
                    <button class="btn btn-ghost p-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Right Panel - Detailed Item View (40%) -->
        <div class="w-2/5 bg-surface flex flex-col" id="detailPanel">
            <!-- Detail Panel Header -->
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
                        <h3 class="text-lg font-semibold text-text-primary">Amoxicillin 500mg</h3>
                        <p class="text-sm text-text-secondary">Capsule • SKU: AMX-500-001</p>
                        <div class="flex items-center gap-2 mt-2">
                            <span class="badge badge-primary">Antibiotics</span>
                            <span class="badge badge-success">In Stock</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Detail Panel Content -->
            <div class="flex-1 overflow-auto scrollbar-thin p-6 space-y-6">

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
                            <p class="text-sm font-medium text-text-primary">MedPharma Inc.</p>
                        </div>
                        <div class="flex items-center justify-between">
                            <p class="text-sm text-text-secondary">Contact Person</p>
                            <p class="text-sm font-medium text-text-primary">John Reyes</p>
                        </div>
                        <div class="flex items-center justify-between">
                            <p class="text-sm text-text-secondary">Phone</p>
                            <p class="text-sm font-medium text-text-primary">(02) 8123-4567</p>
                        </div>
                        <div class="flex items-center justify-between">
                            <p class="text-sm text-text-secondary">Email</p>
                            <p class="text-sm font-medium text-primary">orders@medpharma.ph</p>
                        </div>
                        <div class="flex items-center justify-between">
                            <p class="text-sm text-text-secondary">Lead Time</p>
                            <p class="text-sm font-medium text-text-primary">3-5 business days</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </main>
    
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
</body>
</html>