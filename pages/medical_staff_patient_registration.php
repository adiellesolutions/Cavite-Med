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
    <meta name="description" content="Patient Records Management Portal - Secure Electronic Medical Records System">
    <title>Patient Records Management - CAVMED Portal</title>
    <link rel="stylesheet" href="../css/main.css">
  <script type="module" async src="https://static.rocket.new/rocket-web.js?_cfg=https%3A%2F%2Fcavmedporta6876back.builtwithrocket.new&_be=https%3A%2F%2Fapplication.rocket.new&_v=0.1.10"></script>
  <script type="module" defer src="https://static.rocket.new/rocket-shot.js?v=0.0.1"></script>
  </head>
<body class="bg-background min-h-screen flex flex-col">
    <!-- Header Section -->
    <header class="bg-surface border-b border-border py-4 px-6 shadow-sm sticky top-0 z-fixed">
        <div class="max-w-full mx-auto flex items-center justify-between">
            <div class="flex items-center gap-4">
                <!-- Logo -->
                <svg class="w-10 h-10" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect width="40" height="40" rx="8" fill="#2563EB"/>
                    <path d="M20 10v20M10 20h20" stroke="white" stroke-width="3" stroke-linecap="round"/>
                    <circle cx="20" cy="20" r="6" stroke="white" stroke-width="2" fill="none"/>
                </svg>
                <div>
                    <h1 class="text-xl font-semibold text-text-primary">CAVMED Portal</h1>
                    <p class="text-xs text-text-secondary">Patient Records Management</p>
                </div>
            </div>

            <!-- Advanced Search Bar -->
            <div class="flex-1 max-w-2xl mx-8">
                <div class="relative">
                    <input type="text" 
                           id="globalSearch" 
                           placeholder="Search by patient name, ID, phone, or MRN..."
                           class="input pl-10 pr-32 w-full"
                           autocomplete="off">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                        <svg class="w-5 h-5 text-text-tertiary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <div class="absolute inset-y-0 right-0 flex items-center pr-2 gap-2">
                        <button type="button" id="advancedSearchBtn" class="btn btn-ghost px-3 py-1 text-sm">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                            </svg>
                            Filters
                        </button>
                        <kbd class="hidden md:inline-block px-2 py-1 text-xs font-mono bg-secondary-100 border border-border rounded">Ctrl+K</kbd>
                    </div>
                </div>

                <!-- Search Autocomplete Dropdown (Hidden by default) -->
                <div id="searchAutocomplete" class="hidden absolute mt-2 w-full max-w-2xl bg-surface border border-border rounded-base shadow-lg z-dropdown max-h-96 overflow-y-auto scrollbar-thin">
                    <div class="p-2">
                        <div class="text-xs text-text-secondary px-3 py-2 font-medium">Recent Searches</div>
                        <div id="autocompleteResults" class="space-y-1">
                            <!-- Autocomplete results will be inserted here -->
                        </div>
                    </div>
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
                
                <a href="medical_staff_dashboard.php" class="nav-item">
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

                <a href="medical_staff_patient_registration.php" class="nav-item nav-item-active whitespace-nowrap">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    <span>Patient</span>
                </a>

                <a href="medical_staff_inventory.html" class="nav-item">
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
    <div class="flex-1 flex overflow-hidden">
        <!-- Left Sidebar - Recent & Favorites -->
        <aside class="w-80 bg-surface border-r border-border overflow-y-auto scrollbar-thin">
            <div class="p-4 space-y-4">
                <!-- Quick Actions -->

                <div class="space-y-2">

                    <button type="button" id="newPatientBtn" class="btn btn-primary w-full">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        New Patient Record
                    </button>

                </div>

                <!-- Favorites Section -->
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-semibold text-text-primary">Favorites</h3>
                        <button type="button" class="text-xs text-primary hover:text-primary-700">
                            Manage
                        </button>
                    </div>
                    <div class="space-y-2" id="favoritesList">
                        <!-- Favorite Patient Cards -->
                        <div class="card p-3 card-interactive cursor-pointer" data-patient-id="FAV001">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2">
                                        <h4 class="text-sm font-medium text-text-primary">Emily Rodriguez</h4>
                                        <svg class="w-4 h-4 text-warning" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                    </div>
                                    <p class="text-xs text-text-secondary mt-1">MRN: 2024-001234</p>
                                    <div class="flex items-center gap-2 mt-2">
                                        <span class="badge badge-success">Active</span>
                                        <span class="text-xs text-text-tertiary">Last visit: Dec 5, 2025</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card p-3 card-interactive cursor-pointer" data-patient-id="FAV002">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2">
                                        <h4 class="text-sm font-medium text-text-primary">Michael Chen</h4>
                                        <svg class="w-4 h-4 text-warning" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                    </div>
                                    <p class="text-xs text-text-secondary mt-1">MRN: 2024-001189</p>
                                    <div class="flex items-center gap-2 mt-2">
                                        <span class="badge badge-warning">Follow-up</span>
                                        <span class="text-xs text-text-tertiary">Last visit: Dec 1, 2025</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="divider"></div>

                <!-- Recent Patients -->
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-semibold text-text-primary">Recent Patients</h3>
                        <button type="button" class="text-xs text-primary hover:text-primary-700">
                            View All
                        </button>
                    </div>
                    <div class="space-y-2" id="recentPatientsList">
                        <!-- Recent Patient Cards -->
                        <div class="card p-3 card-hover cursor-pointer" data-patient-id="REC001">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-primary-100 text-primary-700 font-semibold flex items-center justify-center text-sm">
                                    JD
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="text-sm font-medium text-text-primary truncate">Jennifer Davis</h4>
                                    <p class="text-xs text-text-secondary">MRN: 2024-002456</p>
                                </div>
                                <span class="text-xs text-text-tertiary">2h ago</span>
                            </div>
                        </div>

                        <div class="card p-3 card-hover cursor-pointer" data-patient-id="REC002">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-success-100 text-success-700 font-semibold flex items-center justify-center text-sm">
                                    RW
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="text-sm font-medium text-text-primary truncate">Robert Williams</h4>
                                    <p class="text-xs text-text-secondary">MRN: 2024-002398</p>
                                </div>
                                <span class="text-xs text-text-tertiary">4h ago</span>
                            </div>
                        </div>

                        <div class="card p-3 card-hover cursor-pointer" data-patient-id="REC003">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-warning-100 text-warning-700 font-semibold flex items-center justify-center text-sm">
                                    LM
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="text-sm font-medium text-text-primary truncate">Lisa Martinez</h4>
                                    <p class="text-xs text-text-secondary">MRN: 2024-002301</p>
                                </div>
                                <span class="text-xs text-text-tertiary">1d ago</span>
                            </div>
                        </div>

                        <div class="card p-3 card-hover cursor-pointer" data-patient-id="REC004">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-error-100 text-error-700 font-semibold flex items-center justify-center text-sm">
                                    DT
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="text-sm font-medium text-text-primary truncate">David Thompson</h4>
                                    <p class="text-xs text-text-secondary">MRN: 2024-002287</p>
                                </div>
                                <span class="text-xs text-text-tertiary">2d ago</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Center Panel - Patient Details -->
        <main class="flex-1 overflow-y-auto scrollbar-thin bg-background">
            <div class="max-w-5xl mx-auto p-6">
                <!-- Patient Header Card -->
                <div class="card mb-6" id="patientHeaderCard">
                    <div class="flex items-start justify-between mb-6">
                        <div class="flex items-start gap-4">
                            <div class="w-20 h-20 rounded-full bg-primary-100 text-primary-700 font-bold flex items-center justify-center text-2xl">
                                ER
                            </div>
                            <div>
                                <div class="flex items-center gap-3 mb-2">
                                    <h2 class="text-2xl font-semibold text-text-primary">Emily Rodriguez</h2>
                                    <span class="badge badge-success">Active Patient</span>
                                    <button type="button" class="text-warning hover:text-warning-600">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                    </button>
                                </div>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                    <div>
                                        <p class="text-text-secondary">MRN</p>
                                        <p class="font-medium text-text-primary">2024-001234</p>
                                    </div>
                                    <div>
                                        <p class="text-text-secondary">Date of Birth</p>
                                        <p class="font-medium text-text-primary">Mar 15, 1985 (40y)</p>
                                    </div>
                                    <div>
                                        <p class="text-text-secondary">Gender</p>
                                        <p class="font-medium text-text-primary">Female</p>
                                    </div>
                                    <div>
                                        <p class="text-text-secondary">Blood Type</p>
                                        <p class="font-medium text-text-primary">O+</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <button type="button" class="btn btn-outline">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                                Edit
                            </button>
                            <button type="button" class="btn btn-primary">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                New Visit
                            </button>
                        </div>
                    </div>

                    <!-- Quick Contact Info -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-4 border-t border-border">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-primary-50 flex items-center justify-center">
                                <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs text-text-secondary">Phone</p>
                                <p class="text-sm font-medium text-text-primary">(+63) 987-654-364</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-success-50 flex items-center justify-center">
                                <svg class="w-5 h-5 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs text-text-secondary">Email</p>
                                <p class="text-sm font-medium text-text-primary">emily.r@email.com</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-warning-50 flex items-center justify-center">
                                <svg class="w-5 h-5 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs text-text-secondary">Address</p>
                                <p class="text-sm font-medium text-text-primary">123 Main St, City, ST 12345</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabbed Content Area -->
                <div class="card">
                    <!-- Tab Navigation -->
                    <div class="border-b border-border mb-6">
                        <nav class="flex gap-6 overflow-x-auto scrollbar-thin" role="tablist">
                            <button type="button" class="nav-item nav-item-active border-b-2 border-primary pb-3" data-tab="demographics">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                Demographics
                            </button>
                            <button type="button" class="nav-item pb-3" data-tab="vitals">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                Vitals
                            </button>
                            <button type="button" class="nav-item pb-3" data-tab="insurance">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                Insurance
                            </button>
                            <button type="button" class="nav-item pb-3" data-tab="emergency">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                Emergency Contacts
                            </button>
                            <button type="button" class="nav-item pb-3" data-tab="documents">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                </svg>
                                Documents
                            </button>
                        </nav>
                    </div>

                    <!-- Tab Content -->
                    <div id="tabContent">
                        <!-- Demographics Tab -->
                        <div id="demographics-tab" class="tab-content">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <h3 class="text-lg font-semibold text-text-primary mb-4">Personal Information</h3>
                                    <div class="space-y-4">
                                        <div>
                                            <label class="block text-sm font-medium text-text-secondary mb-1">Full Name</label>
                                            <p class="text-base text-text-primary">Emily Maria Rodriguez</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-text-secondary mb-1">Preferred Name</label>
                                            <p class="text-base text-text-primary">Emily</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-text-secondary mb-1">Social Security Number</label>
                                            <p class="text-base text-text-primary font-mono">***-**-4567</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-text-secondary mb-1">Marital Status</label>
                                            <p class="text-base text-text-primary">Married</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-text-secondary mb-1">Occupation</label>
                                            <p class="text-base text-text-primary">Software Engineer</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-text-secondary mb-1">Preferred Language</label>
                                            <p class="text-base text-text-primary">English, Spanish</p>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <h3 class="text-lg font-semibold text-text-primary mb-4">Medical Information</h3>
                                    <div class="space-y-4">
                                        <div>
                                            <label class="block text-sm font-medium text-text-secondary mb-1">Allergies</label>
                                            <div class="flex flex-wrap gap-2">
                                                <span class="badge badge-error">Penicillin</span>
                                                <span class="badge badge-error">Shellfish</span>
                                            </div>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-text-secondary mb-1">Chronic Conditions</label>
                                            <div class="flex flex-wrap gap-2">
                                                <span class="badge badge-warning">Type 2 Diabetes</span>
                                                <span class="badge badge-warning">Hypertension</span>
                                            </div>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-text-secondary mb-1">Current Medications</label>
                                            <ul class="text-sm text-text-primary space-y-1">
                                                <li>• Metformin 500mg - Twice daily</li>
                                                <li>• Lisinopril 10mg - Once daily</li>
                                                <li>• Atorvastatin 20mg - Once daily</li>
                                            </ul>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-text-secondary mb-1">Immunization Status</label>
                                            <div class="flex items-center gap-2">
                                                <span class="status-dot status-online"></span>
                                                <span class="text-sm text-text-primary">Up to date</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                                <button type="button" class="btn btn-outline w-full mt-6">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                    </svg>
                                    Edit Personal Information
                                </button>
                        </div>
                        

                        <!-- Insurance Tab -->
                        <div id="insurance-tab" class="tab-content hidden">
                            <div class="space-y-6">
                                <!-- Primary Insurance -->
                                <div class="border border-border rounded-base p-4">
                                    <div class="flex items-center justify-between mb-4">
                                        <h3 class="text-lg font-semibold text-text-primary">Primary Insurance</h3>
                                        <div class="flex items-center gap-2">
                                            <span class="status-dot status-online"></span>
                                            <span class="text-sm text-success">Verified</span>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-text-secondary mb-1">Insurance Provider</label>
                                            <p class="text-base text-text-primary">Blue Cross Blue Shield</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-text-secondary mb-1">Policy Number</label>
                                            <p class="text-base text-text-primary font-mono">BCBS-123456789</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-text-secondary mb-1">Group Number</label>
                                            <p class="text-base text-text-primary font-mono">GRP-987654</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-text-secondary mb-1">Effective Date</label>
                                            <p class="text-base text-text-primary">Jan 1, 2025</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-text-secondary mb-1">Subscriber Name</label>
                                            <p class="text-base text-text-primary">Emily Rodriguez</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-text-secondary mb-1">Relationship</label>
                                            <p class="text-base text-text-primary">Self</p>
                                        </div>
                                    </div>
                                    <div class="mt-4 pt-4 border-t border-border">
                                        <button type="button" class="btn btn-outline">
                                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            Verify Coverage
                                        </button>
                                    </div>
                                </div>

                                <!-- Secondary Insurance -->
                                <div class="border border-border rounded-base p-4 opacity-50">
                                    <div class="flex items-center justify-between mb-4">
                                        <h3 class="text-lg font-semibold text-text-primary">Secondary Insurance</h3>
                                        <span class="badge badge-secondary">Not Provided</span>
                                    </div>
                                    <button type="button" class="btn btn-outline">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                        </svg>
                                        Add Secondary Insurance
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Emergency Contacts Tab -->
                        <div id="emergency-tab" class="tab-content hidden">
                            <div class="space-y-4">
                                <!-- Emergency Contact 1 -->
                                <div class="border border-border rounded-base p-4">
                                    <div class="flex items-start justify-between mb-4">
                                        <div>
                                            <h3 class="text-lg font-semibold text-text-primary">Carlos Rodriguez</h3>
                                            <p class="text-sm text-text-secondary">Spouse</p>
                                        </div>
                                        <span class="badge badge-primary">Primary Contact</span>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-text-secondary mb-1">Phone</label>
                                            <p class="text-base text-text-primary">(+63) 987-654-364</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-text-secondary mb-1">Email</label>
                                            <p class="text-base text-text-primary">carlos.r@email.com</p>
                                        </div>
                                        <div class="md:col-span-2">
                                            <label class="block text-sm font-medium text-text-secondary mb-1">Address</label>
                                            <p class="text-base text-text-primary">123 Main St, City, ST 12345</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Emergency Contact 2 -->
                                <div class="border border-border rounded-base p-4">
                                    <div class="flex items-start justify-between mb-4">
                                        <div>
                                            <h3 class="text-lg font-semibold text-text-primary">Maria Rodriguez</h3>
                                            <p class="text-sm text-text-secondary">Mother</p>
                                        </div>
                                        <span class="badge badge-secondary">Secondary Contact</span>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-text-secondary mb-1">Phone</label>
                                            <p class="text-base text-text-primary">(+63) 987-654-364</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-text-secondary mb-1">Email</label>
                                            <p class="text-base text-text-primary">maria.r@email.com</p>
                                        </div>
                                        <div class="md:col-span-2">
                                            <label class="block text-sm font-medium text-text-secondary mb-1">Address</label>
                                            <p class="text-base text-text-primary">456 Oak Ave, City, ST 12346</p>
                                        </div>
                                    </div>
                                </div>

                                <button type="button" class="btn btn-outline w-full">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                    </svg>
                                    Add Emergency Contact
                                </button>
                            </div>
                        </div>


                        <!-- Documents Tab -->
                        <div id="documents-tab" class="tab-content hidden">
                            <div class="mb-4 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <button type="button" class="btn btn-primary">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                        </svg>
                                        Upload Document
                                    </button>
                                    <button type="button" class="btn btn-outline">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                                        </svg>
                                        Filter
                                    </button>
                                </div>
                                <div class="text-sm text-text-secondary">
                                    12 documents
                                </div>
                            </div>

                            <div class="space-y-3">
                                <!-- Document Item 1 -->
                                <div class="border border-border rounded-base p-4 hover:bg-secondary-50 transition-colors cursor-pointer">
                                    <div class="flex items-center gap-4">
                                        <div class="w-12 h-12 rounded-base bg-error-50 flex items-center justify-center">
                                            <svg class="w-6 h-6 text-error" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                            </svg>
                                        </div>
                                        <div class="flex-1">
                                            <h4 class="text-base font-medium text-text-primary">Lab Results - Complete Blood Count</h4>
                                            <div class="flex items-center gap-4 mt-1 text-sm text-text-secondary">
                                                <span>PDF • 245 KB</span>
                                                <span>Dec 5, 2025</span>
                                                <span>Dr. Sarah Johnson</span>
                                            </div>
                                        </div>
                                        <div class="flex gap-2">
                                            <button type="button" class="btn btn-ghost p-2">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                </svg>
                                            </button>
                                            <button type="button" class="btn btn-ghost p-2">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Document Item 2 -->
                                <div class="border border-border rounded-base p-4 hover:bg-secondary-50 transition-colors cursor-pointer">
                                    <div class="flex items-center gap-4">
                                        <div class="w-12 h-12 rounded-base bg-primary-50 flex items-center justify-center">
                                            <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                            </svg>
                                        </div>
                                        <div class="flex-1">
                                            <h4 class="text-base font-medium text-text-primary">Prescription - Metformin Refill</h4>
                                            <div class="flex items-center gap-4 mt-1 text-sm text-text-secondary">
                                                <span>PDF • 128 KB</span>
                                                <span>Dec 1, 2025</span>
                                                <span>Dr. Michael Patel</span>
                                            </div>
                                        </div>
                                        <div class="flex gap-2">
                                            <button type="button" class="btn btn-ghost p-2">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                </svg>
                                            </button>
                                            <button type="button" class="btn btn-ghost p-2">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Document Item 3 -->
                                <div class="border border-border rounded-base p-4 hover:bg-secondary-50 transition-colors cursor-pointer">
                                    <div class="flex items-center gap-4">
                                        <div class="w-12 h-12 rounded-base bg-success-50 flex items-center justify-center">
                                            <svg class="w-6 h-6 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                        </div>
                                        <div class="flex-1">
                                            <h4 class="text-base font-medium text-text-primary">X-Ray - Chest PA View</h4>
                                            <div class="flex items-center gap-4 mt-1 text-sm text-text-secondary">
                                                <span>DICOM • 1.2 MB</span>
                                                <span>Nov 28, 2025</span>
                                                <span>Radiology Dept</span>
                                            </div>
                                        </div>
                                        <div class="flex gap-2">
                                            <button type="button" class="btn btn-ghost p-2">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                </svg>
                                            </button>
                                            <button type="button" class="btn btn-ghost p-2">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>



                        <!-- Vital Signs Tab -->
                        <div id="vitals-tab" class="tab-content hidden">
                            <!-- Current Vital Signs -->
                            <div class="lg:col-span-2">
                                <div class="card">
                                    <div class="flex items-center justify-between mb-6 pb-4 border-b border-border">
                                        <h3 class="text-lg font-semibold text-text-primary">Current Vital Signs</h3>
                                        <button type="button" class="btn btn-primary btn-sm">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                            </svg>
                                            Record New Vitals
                                        </button>
                                    </div>
                                    
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                        <div class="vital-sign-card p-4 rounded-base border-2 border-border text-center">
                                            <p class="text-sm text-text-secondary mb-1">Blood Pressure</p>
                                            <p class="text-2xl font-bold text-text-primary">128/84</p>
                                            <div class="mt-2">
                                                <span class="badge badge-success">Normal</span>
                                            </div>
                                        </div>
                                        
                                        <div class="vital-sign-card p-4 rounded-base border-2 border-border text-center">
                                            <p class="text-sm text-text-secondary mb-1">Heart Rate</p>
                                            <p class="text-2xl font-bold text-text-primary">78</p>
                                            <p class="text-sm text-text-secondary">bpm</p>
                                            <div class="mt-2">
                                                <span class="badge badge-success">Normal</span>
                                            </div>
                                        </div>
                                        
                                        <div class="vital-sign-card p-4 rounded-base border-2 border-border text-center">
                                            <p class="text-sm text-text-secondary mb-1">Temperature</p>
                                            <p class="text-2xl font-bold text-text-primary">36.6</p>
                                            <p class="text-sm text-text-secondary">°C</p>
                                            <div class="mt-2">
                                                <span class="badge badge-success">Normal</span>
                                            </div>
                                        </div>
                                        
                                        <div class="vital-sign-card p-4 rounded-base border-2 border-border text-center">
                                            <p class="text-sm text-text-secondary mb-1">SpO₂</p>
                                            <p class="text-2xl font-bold text-text-primary">98</p>
                                            <p class="text-sm text-text-secondary">%</p>
                                            <div class="mt-2">
                                                <span class="badge badge-success">Normal</span>
                                            </div>
                                        </div>
                                        
                                        <div class="vital-sign-card p-4 rounded-base border-2 border-border text-center">
                                            <p class="text-sm text-text-secondary mb-1">Respiratory Rate</p>
                                            <p class="text-2xl font-bold text-text-primary">16</p>
                                            <p class="text-sm text-text-secondary">breaths/min</p>
                                            <div class="mt-2">
                                                <span class="badge badge-success">Normal</span>
                                            </div>
                                        </div>
                                        
                                        
                                        <div class="vital-sign-card p-4 rounded-base border-2 border-border text-center">
                                            <p class="text-sm text-text-secondary mb-1">Blood Glucose</p>
                                            <p class="text-2xl font-bold text-text-primary">142</p>
                                            <p class="text-sm text-text-secondary">mg/dL</p>
                                            <div class="mt-2">
                                                <span class="badge badge-warning">Elevated</span>
                                            </div>
                                        </div>
                                        
                                        <div class="vital-sign-card p-4 rounded-base border-2 border-border text-center">
                                            <p class="text-sm text-text-secondary mb-1">Weight</p>
                                            <p class="text-2xl font-bold text-text-primary">68.2</p>
                                            <p class="text-sm text-text-secondary">kg</p>
                                            <div class="mt-2">
                                                <span class="badge badge-success">Stable</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-6">
                                        <label class="block text-sm font-medium text-text-secondary mb-2">Nurse's Notes</label>
                                        <div class="bg-secondary-50 p-4 rounded-base">
                                            <p class="text-sm text-text-primary">Patient resting comfortably. Blood glucose slightly elevated but within expected range post-meal. Reports mild headache. Skin warm and dry, mucous membranes moist.</p>
                                        </div>
                                    </div>

                                    <button type="button" class="btn btn-outline w-full mt-4">
                                        View All History
                                    </button>
                                </div>
                            </div>
                        </div>


                    </div>
                </div>
            </div>
        </main>

        <!-- Right Panel - Visit History Timeline -->
        <aside class="w-96 bg-surface border-l border-border overflow-y-auto scrollbar-thin">
            <div class="p-4">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-text-primary">Visit History</h3>
                    <button type="button" class="btn btn-ghost p-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                        </svg>
                    </button>
                </div>

                <!-- Timeline -->
                <div class="relative space-y-6">
                    <!-- Timeline Line -->
                    <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-border"></div>

                    <!-- Visit Entry 1 -->
                    <div class="relative pl-10">
                        <div class="absolute left-0 w-8 h-8 rounded-full bg-primary text-white flex items-center justify-center text-xs font-semibold">
                            1
                        </div>
                        <div class="card p-4">
                            <div class="flex items-start justify-between mb-2">
                                <div>
                                    <h4 class="text-sm font-semibold text-text-primary">Routine Checkup</h4>
                                    <p class="text-xs text-text-secondary">Dec 5, 2025 • 10:30 AM</p>
                                </div>
                                <span class="badge badge-success">Completed</span>
                            </div>
                            <p class="text-sm text-text-secondary mb-3">Dr. Sarah Johnson</p>
                            <div class="space-y-2 text-sm">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-text-tertiary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                    </svg>
                                    <span class="text-text-primary">Blood pressure check</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-text-tertiary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                    </svg>
                                    <span class="text-text-primary">Lab work ordered</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-text-tertiary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                    </svg>
                                    <span class="text-text-primary">Prescription refilled</span>
                                </div>
                            </div>
                            <button type="button" class="text-xs text-primary hover:text-primary-700 mt-3">
                                View Details →
                            </button>
                        </div>
                    </div>

                    <!-- Visit Entry 2 -->
                    <div class="relative pl-10">
                        <div class="absolute left-0 w-8 h-8 rounded-full bg-success text-white flex items-center justify-center text-xs font-semibold">
                            2
                        </div>
                        <div class="card p-4">
                            <div class="flex items-start justify-between mb-2">
                                <div>
                                    <h4 class="text-sm font-semibold text-text-primary">Endocrinology Consult</h4>
                                    <p class="text-xs text-text-secondary">Dec 1, 2025 • 2:00 PM</p>
                                </div>
                                <span class="badge badge-success">Completed</span>
                            </div>
                            <p class="text-sm text-text-secondary mb-3">Dr. Michael Patel</p>
                            <div class="space-y-2 text-sm">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-text-tertiary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                    </svg>
                                    <span class="text-text-primary">Diabetes management review</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-text-tertiary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                    </svg>
                                    <span class="text-text-primary">HbA1c test ordered</span>
                                </div>
                            </div>
                            <button type="button" class="text-xs text-primary hover:text-primary-700 mt-3">
                                View Details →
                            </button>
                        </div>
                    </div>

                    <!-- Visit Entry 3 -->
                    <div class="relative pl-10">
                        <div class="absolute left-0 w-8 h-8 rounded-full bg-warning text-white flex items-center justify-center text-xs font-semibold">
                            3
                        </div>
                        <div class="card p-4">
                            <div class="flex items-start justify-between mb-2">
                                <div>
                                    <h4 class="text-sm font-semibold text-text-primary">Cardiology Follow-up</h4>
                                    <p class="text-xs text-text-secondary">Nov 28, 2025 • 11:00 AM</p>
                                </div>
                                <span class="badge badge-success">Completed</span>
                            </div>
                            <p class="text-sm text-text-secondary mb-3">Dr. Lisa Chen</p>
                            <div class="space-y-2 text-sm">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-text-tertiary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                    </svg>
                                    <span class="text-text-primary">ECG performed</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-text-tertiary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                    </svg>
                                    <span class="text-text-primary">Medication adjusted</span>
                                </div>
                            </div>
                            <button type="button" class="text-xs text-primary hover:text-primary-700 mt-3">
                                View Details →
                            </button>
                        </div>
                    </div>

                    <!-- Upcoming Appointment -->
                    <div class="relative pl-10">
                        <div class="absolute left-0 w-8 h-8 rounded-full bg-secondary-300 text-white flex items-center justify-center text-xs font-semibold">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="card p-4 border-2 border-primary">
                            <div class="flex items-start justify-between mb-2">
                                <div>
                                    <h4 class="text-sm font-semibold text-text-primary">Annual Physical</h4>
                                    <p class="text-xs text-text-secondary">Dec 15, 2025 • 9:00 AM</p>
                                </div>
                                <span class="badge badge-primary">Scheduled</span>
                            </div>
                            <p class="text-sm text-text-secondary mb-3">Dr. Sarah Johnson</p>
                            <div class="flex gap-2">
                                <button type="button" class="btn btn-outline text-xs py-1 px-3">
                                    Reschedule
                                </button>
                                <button type="button" class="btn btn-primary text-xs py-1 px-3">
                                    Confirm
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </aside>
    </div>

        <!-- Advanced Search Modal -->
    <div id="advancedSearchModal" class="hidden fixed inset-0 bg-secondary-900 bg-opacity-50 z-modal flex items-center justify-center p-4">
    <div class="card max-w-3xl w-full animate-slide-in max-h-[90vh] overflow-y-auto scrollbar-thin">
        <div class="flex items-center justify-between mb-6">
        <h3 class="text-xl font-semibold text-text-primary">Advanced Search</h3>
        <button type="button" id="closeAdvancedSearch" class="text-text-tertiary hover:text-text-primary transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
        </div>

        <form id="advancedSearchForm" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
            <label class="block text-sm font-medium text-text-primary mb-2">Patient Name</label>
            <input type="text" id="adv_name" name="name" class="input">
            </div>

            <div>
            <label class="block text-sm font-medium text-text-primary mb-2">Medical Record Number</label>
            <input type="text" id="adv_mrn" name="mrn" class="input">
            </div>

            <div>
            <label class="block text-sm font-medium text-text-primary mb-2">Date of Birth</label>
            <input type="date" id="adv_dob" name="dob" class="input">
            </div>

            <div>
            <label class="block text-sm font-medium text-text-primary mb-2">Phone Number</label>
            <input type="tel" id="adv_phone" name="phone" class="input">
            </div>

            <div>
            <label class="block text-sm font-medium text-text-primary mb-2">Diagnosis Code</label>
            <input type="text" id="adv_diagnosis_code" name="diagnosis_code" class="input">
            </div>

            <div>
            <label class="block text-sm font-medium text-text-primary mb-2">Treating Physician</label>
            <select id="adv_doctor_id" name="doctor_id" class="input">
                <option value="">All Doctors</option>
            </select>
            </div>

            <div>
            <label class="block text-sm font-medium text-text-primary mb-2">Visit Date Range</label>
            <div class="flex gap-2">
                <input type="date" id="adv_visit_from" name="visit_from" class="input flex-1">
                <input type="date" id="adv_visit_to" name="visit_to" class="input flex-1">
            </div>
            </div>

            <div>
            <label class="block text-sm font-medium text-text-primary mb-2">Care Location</label>
            <select id="adv_health_center_id" name="health_center_id" class="input">
                <option value="">All Locations</option>
            </select>
            </div>
        </div>

        <!-- Results -->
        <div class="border border-border rounded-base p-3">
            <div class="flex items-center justify-between mb-2">
            <div class="text-sm font-semibold text-text-primary">Results</div>
            <div id="advResultsCount" class="text-xs text-text-secondary">No results</div>
            </div>

            <div id="advResults" class="space-y-2 max-h-64 overflow-y-auto scrollbar-thin">
            <div class="text-sm text-text-secondary">Search results will appear here.</div>
            </div>
        </div>

        <div class="flex gap-3">
            <button type="button" id="resetAdvancedSearch" class="btn btn-outline flex-1">
            Reset Filters
            </button>
            <button type="submit" id="submitAdvancedSearch" class="btn btn-primary flex-1">
            Search Patients
            </button>
        </div>
        </form>
    </div>
    </div>


    <!-- New Patient Modal -->
    <div id="newPatientModal" class="hidden fixed inset-0 bg-secondary-900 bg-opacity-50 z-modal flex items-center justify-center p-4">
        <div class="card max-w-4xl w-full animate-slide-in max-h-[90vh] overflow-y-auto scrollbar-thin">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-semibold text-text-primary">New Patient Registration</h3>
                <button type="button" id="closeNewPatient" class="text-text-tertiary hover:text-text-primary transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form id="newPatientForm" class="space-y-6">
                <div class="alert alert-info">
                    <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                    <p class="text-sm">Complete all required fields marked with an asterisk (*)</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-2">First Name *</label>
                        <input type="text" class="input" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-2">Last Name *</label>
                        <input type="text" class="input" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-2">Date of Birth *</label>
                        <input type="date" class="input" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-2">Gender *</label>
                        <select class="input" required>
                            <option value="">Select gender</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-2">Phone Number *</label>
                        <input type="tel" class="input" placeholder="(+63) 987-654-364" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-2">Email</label>
                        <input type="email" class="input" placeholder="patient@email.com">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-text-primary mb-2">Address *</label>
                        <input type="text" class="input" placeholder="Street address" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-2">City *</label>
                        <input type="text" class="input" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-2">State *</label>
                        <input type="text" class="input" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-2">ZIP Code *</label>
                        <input type="text" class="input" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-2">Blood Type</label>
                        <select class="input">
                            <option value="">Select blood type</option>
                            <option value="a+">A+</option>
                            <option value="a-">A-</option>
                            <option value="b+">B+</option>
                            <option value="b-">B-</option>
                            <option value="ab+">AB+</option>
                            <option value="ab-">AB-</option>
                            <option value="o+">O+</option>
                            <option value="o-">O-</option>
                        </select>
                    </div>
                </div>

                <div class="flex gap-3">
                    <button type="button" id="cancelNewPatient" class="btn btn-outline flex-1">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary flex-1">
                        Register Patient
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Bottom Navigation Bar (Mobile) -->
    <nav class="lg:hidden fixed bottom-0 left-0 right-0 bg-surface border-t border-border z-fixed">
        <div class="flex items-center justify-around py-2">
            <a href="administrative_command_dashboard.html" class="flex flex-col items-center gap-1 px-3 py-2 text-text-secondary hover:text-primary transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                <span class="text-xs">Dashboard</span>
            </a>
            <a href="patient_records_management_portal.html" class="flex flex-col items-center gap-1 px-3 py-2 text-primary transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                <span class="text-xs font-medium">Patients</span>
            </a>
            <a href="electronic_prescription_management_system.html" class="flex flex-col items-center gap-1 px-3 py-2 text-text-secondary hover:text-primary transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <span class="text-xs">Prescriptions</span>
            </a>
            <a href="inventory_management_system.html" class="flex flex-col items-center gap-1 px-3 py-2 text-text-secondary hover:text-primary transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
                <span class="text-xs">Inventory</span>
            </a>
            <a href="analytics_and_reporting_dashboard.html" class="flex flex-col items-center gap-1 px-3 py-2 text-text-secondary hover:text-primary transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                <span class="text-xs">Reports</span>
            </a>
        </div>
    </nav>

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

    <script>
        // Tab Navigation
        const tabButtons = document.querySelectorAll('[data-tab]');
        const tabContents = document.querySelectorAll('.tab-content');

        tabButtons.forEach(button => {
            button.addEventListener('click', () => {
                const targetTab = button.getAttribute('data-tab');
                
                // Remove active state from all tabs
                tabButtons.forEach(btn => {
                    btn.classList.remove('nav-item-active', 'border-primary');
                    btn.classList.add('border-transparent');
                });
                
                // Add active state to clicked tab
                button.classList.add('nav-item-active', 'border-primary');
                button.classList.remove('border-transparent');
                
                // Hide all tab contents
                tabContents.forEach(content => {
                    content.classList.add('hidden');
                });
                
                // Show target tab content
                document.getElementById(`${targetTab}-tab`).classList.remove('hidden');
            });
        });

        

        
        // New Patient Modal
        const newPatientBtn = document.getElementById('newPatientBtn');
        const newPatientModal = document.getElementById('newPatientModal');
        const closeNewPatient = document.getElementById('closeNewPatient');
        const cancelNewPatient = document.getElementById('cancelNewPatient');
        const newPatientForm = document.getElementById('newPatientForm');

        newPatientBtn.addEventListener('click', () => {
            newPatientModal.classList.remove('hidden');
        });

        closeNewPatient.addEventListener('click', () => {
            newPatientModal.classList.add('hidden');
        });

        cancelNewPatient.addEventListener('click', () => {
            newPatientModal.classList.add('hidden');
        });

        newPatientForm.addEventListener('submit', (e) => {
            e.preventDefault();
            alert('New patient registration would be processed here');
            newPatientModal.classList.add('hidden');
            newPatientForm.reset();
        });

        // Close modal on outside click
        newPatientModal.addEventListener('click', (e) => {
            if (e.target === newPatientModal) {
                newPatientModal.classList.add('hidden');
            }
        });

        // Patient Card Click Handlers
        document.querySelectorAll('[data-patient-id]').forEach(card => {
            card.addEventListener('click', function() {
                const patientId = this.getAttribute('data-patient-id');
                console.log('Loading patient:', patientId);
                // In production, this would load the patient data
            });
        });

        document.addEventListener("keydown", (e) => {
        if (e.key === "Escape") {
            const adv = document.getElementById("advancedSearchModal");
            const newP = document.getElementById("newPatientModal");
            if (adv) adv.classList.add("hidden");
            if (newP) newP.classList.add("hidden");
        }
        });


        // Session timeout warning
        let sessionTimeout;
        function resetSessionTimeout() {
            clearTimeout(sessionTimeout);
            sessionTimeout = setTimeout(() => {
                alert('Your session is about to expire due to inactivity. Please save your work.');
            }, 1800000); // 30 minutes
        }

        document.addEventListener('mousemove', resetSessionTimeout);
        document.addEventListener('keypress', resetSessionTimeout);
        resetSessionTimeout();

        // Audit logging simulation
        function logAccess(action, patientId) {
            console.log(`[AUDIT] ${new Date().toISOString()} - User accessed ${action} for patient ${patientId}`);
            // In production, this would send to audit logging system
        }

        // Initialize tooltips
        const tooltipElements = document.querySelectorAll('[data-tooltip]');
        tooltipElements.forEach(element => {
            element.addEventListener('mouseenter', function() {
                const tooltip = document.createElement('div');
                tooltip.className = 'tooltip tooltip-visible';
                tooltip.textContent = this.getAttribute('data-tooltip');
                document.body.appendChild(tooltip);
                
                const rect = this.getBoundingClientRect();
                tooltip.style.top = `${rect.top - tooltip.offsetHeight - 5}px`;
                tooltip.style.left = `${rect.left + (rect.width - tooltip.offsetWidth) / 2}px`;
            });
            
            element.addEventListener('mouseleave', function() {
                document.querySelectorAll('.tooltip').forEach(t => t.remove());
            });
        });
    </script>
<script src="../js/medical_staff_global_search_autocomplete.js"></script>
<script src="../js/medical_staff_advanced_search_modal.js"></script>
<script id="dhws-dataInjector" src="../public/dhws-data-injector.js"></script>

</body>
</html>