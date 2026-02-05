<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
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
    <meta name="description" content="CAVMED Administrative Command Dashboard - Comprehensive system oversight and multi-facility management">
    <title>Administrative Command Dashboard - CAVMED Portal</title>
    <link rel="stylesheet" href="../css/main.css">
  <script type="module" async src="https://static.rocket.new/rocket-web.js?_cfg=https%3A%2F%2Fcavmedporta6876back.builtwithrocket.new&_be=https%3A%2F%2Fapplication.rocket.new&_v=0.1.10"></script>
  <script type="module" defer src="https://static.rocket.new/rocket-shot.js?v=0.0.1"></script>
  </head>
<body class="bg-background min-h-screen">
    <!-- Header Section -->
    <header class="bg-surface border-b border-border shadow-sm sticky top-0 z-sticky">
        <div class="px-6 py-4">
            <div class="flex items-center justify-between">
                <!-- Logo and Title -->
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
        <nav class="border-t border-border bg-secondary-50">
            <div class="px-6">
                <div class="flex items-center gap-1 overflow-x-auto scrollbar-thin">
                    <a href="admin_dashboard.php" class="nav-item nav-item-active whitespace-nowrap">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        Dashboard
                    </a>
                    <a href="admin_analytics_and_reporting.html" class="nav-item whitespace-nowrap">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Analytics
                    </a>
                    <a href="admin_audit_log.html" class="nav-item whitespace-nowrap">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Audit Logs
                    </a>
                    <a href="admin_reports.html" class="nav-item whitespace-nowrap">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Reports
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
    </header>

    <!-- Main Content -->
    <main class="p-6">
        <!-- Advanced Filtering Toolbar -->
        <div class="card mb-6">
            <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                <div class="flex flex-wrap items-center gap-3">
                    <!-- Date Range Selector -->
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-text-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <select class="input py-1.5 text-sm" id="dateRange">
                            <option value="today">Today</option>
                            <option value="week" selected>This Week</option>
                            <option value="month">This Month</option>
                            <option value="quarter">This Quarter</option>
                            <option value="custom">Custom Range</option>
                        </select>
                    </div>

                    <!-- Facility Filter -->
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-text-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                        <select class="input py-1.5 text-sm" id="facilityFilter">
                            <option value="all">All Facilities</option>
                            <option value="main">Main Hospital</option>
                            <option value="north">North Clinic</option>
                            <option value="south">South Clinic</option>
                            <option value="east">East Medical Center</option>
                        </select>
                    </div>

                    <!-- Alert Type Filter -->
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-text-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                        </svg>
                        <select class="input py-1.5 text-sm" id="alertFilter">
                            <option value="all">All Alerts</option>
                            <option value="critical">Critical Only</option>
                            <option value="warning">Warnings</option>
                            <option value="info">Information</option>
                        </select>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="flex items-center gap-2">
                    <button type="button" class="btn btn-primary text-sm py-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Refresh Data
                    </button>
                </div>
            </div>
        </div>

        <!-- Dashboard Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            <!-- Bottom Right: Activity Feed -->
            <div class="card">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-semibold text-text-primary">Recent Activity</h2>
                    <button type="button" class="btn btn-ghost p-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    </button>
                </div>

                <div class="space-y-4 max-h-100 overflow-y-auto scrollbar-thin">
                    <!-- Activity Item 1 -->
                    <div class="flex items-start gap-3 pb-4 border-b border-border">
                        <div class="w-10 h-10 bg-success-100 rounded-full flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm text-text-primary mb-1">
                                <span class="font-semibold">Dr. Sarah Johnson</span> completed patient consultation
                            </p>
                            <p class="text-xs text-text-secondary">Patient ID: PT-2847 • 2 minutes ago</p>
                        </div>
                    </div>

                    <!-- Activity Item 2 -->
                    <div class="flex items-start gap-3 pb-4 border-b border-border">
                        <div class="w-10 h-10 bg-primary-100 rounded-full flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm text-text-primary mb-1">
                                <span class="font-semibold">Pharmacist Mike Chen</span> dispensed prescription
                            </p>
                            <p class="text-xs text-text-secondary">Rx ID: RX-5621 • 8 minutes ago</p>
                        </div>
                    </div>

                    <!-- Activity Item 3 -->
                    <div class="flex items-start gap-3 pb-4 border-b border-border">
                        <div class="w-10 h-10 bg-warning-100 rounded-full flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm text-text-primary mb-1">
                                <span class="font-semibold">System</span> updated medicine inventory
                            </p>
                            <p class="text-xs text-text-secondary">Amoxicillin 500mg stock adjusted • 15 minutes ago</p>
                        </div>
                    </div>

                                        <!-- Activity Item 3 -->
                    <div class="flex items-start gap-3 pb-4 border-b border-border">
                        <div class="w-10 h-10 bg-warning-100 rounded-full flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm text-text-primary mb-1">
                                <span class="font-semibold">System</span> updated medicine inventory
                            </p>
                            <p class="text-xs text-text-secondary">Amoxicillin 500mg stock adjusted • 15 minutes ago</p>
                        </div>
                    </div>

                                        <!-- Activity Item 3 -->
                    <div class="flex items-start gap-3 pb-4 border-b border-border">
                        <div class="w-10 h-10 bg-warning-100 rounded-full flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm text-text-primary mb-1">
                                <span class="font-semibold">System</span> updated medicine inventory
                            </p>
                            <p class="text-xs text-text-secondary">Amoxicillin 300mg stock adjusted • 15 minutes ago</p>
                        </div>
                    </div>

                    <!-- Activity Item 4 -->
                    <div class="flex items-start gap-3 pb-4 border-b border-border">
                        <div class="w-10 h-10 bg-success-100 rounded-full flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm text-text-primary mb-1">
                                <span class="font-semibold">Admin User</span> created new user account
                            </p>
                            <p class="text-xs text-text-secondary">Nurse Emily Rodriguez • 23 minutes ago</p>
                        </div>
                    </div>

                    <!-- Activity Item 5 -->
                    <div class="flex items-start gap-3 pb-4 border-b border-border">
                        <div class="w-10 h-10 bg-primary-100 rounded-full flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm text-text-primary mb-1">
                                <span class="font-semibold">System</span> generated daily report
                            </p>
                            <p class="text-xs text-text-secondary">Patient visits summary • 35 minutes ago</p>
                        </div>
                    </div>

                    <!-- Activity Item 6 -->
                    <div class="flex items-start gap-3 pb-4 border-b border-border">
                        <div class="w-10 h-10 bg-success-100 rounded-full flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm text-text-primary mb-1">
                                <span class="font-semibold">Dr. Michael Brown</span> approved lab results
                            </p>
                            <p class="text-xs text-text-secondary">Patient ID: PT-2891 • 42 minutes ago</p>
                        </div>
                    </div>

                    <!-- Activity Item 7 -->
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 bg-warning-100 rounded-full flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm text-text-primary mb-1">
                                <span class="font-semibold">System</span> detected low stock alert
                            </p>
                            <p class="text-xs text-text-secondary">Ibuprofen 400mg • 1 hour ago</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Right: Critical Alerts -->
            <div class="card">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-semibold text-text-primary">Critical Alerts</h2>
                    <button type="button" class="btn btn-outline text-sm py-1.5">
                        View All
                    </button>
                </div>

                <div class="space-y-3">
                    <!-- Critical Alert 1 -->
                    <div class="border-l-4 border-error bg-error-50 rounded-base p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="badge badge-error">Critical</span>
                                    <span class="text-xs text-text-secondary">2 minutes ago</span>
                                </div>
                                <h3 class="font-semibold text-text-primary mb-1">Medicine Stock Critical</h3>
                                <p class="text-sm text-text-secondary mb-3">Paracetamol 500mg stock below minimum threshold at North Clinic. Only 45 units remaining.</p>
                                <div class="flex gap-2">
                                    <button type="button" class="btn btn-error text-xs py-1">
                                        Reorder Now
                                    </button>
                                    <button type="button" class="btn btn-outline text-xs py-1">
                                        View Details
                                    </button>
                                </div>
                            </div>
                            <button type="button" class="text-text-tertiary hover:text-text-primary transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>



                    
                </div>
            </div>

            <!-- Bottom Left: Quick Access Tiles -->
            <div class="card">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-semibold text-text-primary">Quick Access</h2>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <!-- User Management -->
                    <button type="button"
                            onclick="window.location.href='admin_user_management.php'"
                            class="card-interactive text-left p-4 relative">
                        <div class="absolute top-3 right-3">
                            <span class="badge badge-error">12</span>
                        </div>
                        <div class="w-12 h-12 bg-primary-100 rounded-base flex items-center justify-center mb-3">
                            <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                        </div>
                        <h3 class="font-semibold text-text-primary mb-1">User Management</h3>
                        <p class="text-sm text-text-secondary">Manage accounts & permissions</p>
                    </button>



                    <!-- Report Generation -->
                    <button type="button" class="card-interactive text-left p-4">
                        <div class="w-12 h-12 bg-success-100 rounded-base flex items-center justify-center mb-3">
                            <svg class="w-6 h-6 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <h3 class="font-semibold text-text-primary mb-1">Reports</h3>
                        <p class="text-sm text-text-secondary">View reports</p>
                    </button>

                    <!-- Audit Logs -->
                    <button type="button" class="card-interactive text-left p-4">
                        <div class="w-12 h-12 bg-warning-100 rounded-base flex items-center justify-center mb-3">
                            <svg class="w-6 h-6 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <h3 class="font-semibold text-text-primary mb-1">Audit Logs</h3>
                        <p class="text-sm text-text-secondary">View system activity</p>
                    </button>
                </div>
            </div>

                        <!-- Top Left: Real-time Facility Metrics -->
            <div class="card">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-semibold text-text-primary">Facility Metrics</h2>
                    <span class="badge badge-success">Live</span>
                </div>

                <!-- Metrics Cards -->
                <div class="grid grid-cols-2 gap-4 mb-6">
                    <!-- Patient Census -->
                    <div class="bg-primary-50 rounded-base p-4 border border-primary-200">
                        <div class="flex items-center justify-between mb-2">
                            <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            <span class="text-xs text-success font-medium">+12%</span>
                        </div>
                        <p class="text-2xl font-bold text-primary mb-1">1,847</p>
                        <p class="text-sm text-text-secondary">Active Patients</p>
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