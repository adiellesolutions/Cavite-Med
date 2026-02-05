<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: system_login_portal.html");
    exit;
}

require "../backend/admin_UM_fetch.php";
require "../backend/admin_UM_pagination.php";
require "../backend/admin_UM_stats.php";

$profile = !empty($user['profile_picture'])
    ? $user['profile_picture']
    : 'uploads/profile/default.png';

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="CAVMED User Management - Administer user accounts, roles, and permissions">
    <title>User Management - CAVMED Admin Portal</title>
    <link rel="stylesheet" href="../css/main.css">
    <script type="module" async src="https://static.rocket.new/rocket-web.js?_cfg=https%3A%2F%2Fcavmedporta6876back.builtwithrocket.new&_be=https%3A%2F%2Fapplication.rocket.new&_v=0.1.10"></script>
    <script type="module" defer src="https://static.rocket.new/rocket-shot.js?v=0.0.1"></script>
    <script defer src="../js/admin_UM_modal.js"></script>
    <script defer src="../js/admin_UM_adduser.js"></script>
    <script defer src="../js/admin_UM_deleteuser.js"></script>
    <script defer src="../js/admin_UM_togglestatus.js"></script>
    <script defer src="../js/admin_UM_resetpassword.js"></script>

</head>
<body class="bg-background min-h-screen">
    <!-- Header Section -->
    <header class="bg-surface border-b border-border shadow-sm sticky top-0 z-sticky">
        <div class="px-6 py-4">
            <div class="flex items-center justify-between">
                <!-- Logo and Title -->
                <div class="flex items-center gap-4">
                    <a href="admin_dashboard.php">
                        <svg class="w-10 h-10" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <rect width="40" height="40" rx="8" fill="#2563EB"/>
                            <path d="M20 10v20M10 20h20" stroke="white" stroke-width="3" stroke-linecap="round"/>
                            <circle cx="20" cy="20" r="6" stroke="white" stroke-width="2" fill="none"/>
                        </svg>
                    </a>
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

        <div class="mb-6">
            <a href="admin_dashboard.php" class="inline-flex items-center gap-2 btn btn-outline text-sm ">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 19l-7-7 7-7"/>
                    </svg>
                    Back to Dashboard
            </a>
        </div>

        <!-- Stats Overview -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-text-secondary mb-1">Total Users</p>
                        <p class="text-2xl font-bold text-text-primary">
                            <?php echo $totalUsers; ?>
                        </p>
                    </div>
                    <div class="w-12 h-12 bg-primary-100 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-text-secondary mb-1">Active Users</p>
                        <p class="text-2xl font-bold text-text-primary">
                            <?php echo $activeUsers; ?>
                        </p>
                    </div>
                    <div class="w-12 h-12 bg-success-100 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

        </div>

        <form method="GET" class="card mb-6">
            <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">

                <!-- Search and Filters -->
                <div class="flex flex-wrap items-center gap-4">

                    <!-- Search -->
                    <div class="relative flex-1 min-w-[300px]">
                        <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-text-secondary"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input
                            type="text"
                            name="search"
                            value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>"
                            placeholder="Search users by name, email, or username..."
                            class="input pl-10 w-full">
                    </div>

                    <!-- Status Filter -->
                    <select name="status" class="input py-2 text-sm">
                        <option value="all">All Status</option>
                        <option value="active"   <?php if(($_GET['status'] ?? '')==='active') echo 'selected'; ?>>Active</option>
                        <option value="inactive" <?php if(($_GET['status'] ?? '')==='inactive') echo 'selected'; ?>>Inactive</option>
                    </select>

                    <!-- Role Filter -->
                    <select name="role" class="input py-2 text-sm">
                        <option value="all">All Roles</option>
                        <option value="admin"         <?php if(($_GET['role'] ?? '')==='admin') echo 'selected'; ?>>Administrator</option>
                        <option value="doctor"        <?php if(($_GET['role'] ?? '')==='doctor') echo 'selected'; ?>>Doctor</option>
                        <option value="medical_staff" <?php if(($_GET['role'] ?? '')==='medical_staff') echo 'selected'; ?>>Medical Staff</option>
                        <option value="encoder"       <?php if(($_GET['role'] ?? '')==='encoder') echo 'selected'; ?>>Encoder</option>
                    </select>
                </div>

                <!-- Action Button -->
                <div class="flex items-center gap-2">
                    <button type="submit" class="btn btn-outline py-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                        </svg>
                        Apply
                    </button>
                    <button type="button" id="addUserBtn" class="btn btn-primary py-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Add User
                    </button>
                </div>
            </div>
        </form>

        <!-- Users Table -->
        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-secondary-50 border-b border-border">
                            <th class="py-3 px-4 text-left">User</th>
                            <th class="py-3 px-4 text-left">Role</th>
                            <th class="py-3 px-4 text-left">Contact</th>
                            <th class="py-3 px-4 text-left">Status</th>
                            <th class="py-3 px-4 text-left">Last Login</th>
                            <th class="py-3 px-4 text-left">Actions</th>
                        </tr>
                    </thead>
                        <tbody class="divide-y divide-border">

                        <?php if ($result->num_rows > 0): ?>
                        <?php while ($user = $result->fetch_assoc()): ?>

                        <tr class="hover:bg-secondary-50">
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-3">

                                    <?php
                                    $profile = !empty($user['profile_picture'])
                                        ? $user['profile_picture']
                                        : 'uploads/profile/default.png';
                                    ?>

                                    <img
                                        src="/HIMS/<?php echo htmlspecialchars($profile); ?>"
                                        class="w-8 h-8 rounded-full object-cover"
                                        alt="User profile">

                                    <div>
                                        <p class="font-medium text-text-primary">
                                            <?php echo htmlspecialchars($user['full_name']); ?>
                                        </p>
                                        <p class="text-sm text-text-secondary">
                                            <?php echo htmlspecialchars($user['username']); ?> •
                                            <?php echo htmlspecialchars($user['position'] ?? ''); ?>
                                        </p>
                                    </div>
                                </div>
                            </td>

                            <td class="py-3 px-4">
                                <span class="badge badge-primary">
                                    <?php echo ucfirst(str_replace('_', ' ', $user['role'])); ?>
                                </span>
                            </td>

                            <td class="py-3 px-4">
                                <p class="text-text-primary">
                                    <?php echo htmlspecialchars($user['email']); ?>
                                </p>
                                <p class="text-sm text-text-secondary">
                                    <?php echo htmlspecialchars($user['contact_number']); ?>
                                </p>
                            </td>

                            <td class="py-3 px-4">
                                <?php if ($user['status'] === 'active'): ?>
                                    <span class="badge badge-success">Active</span>
                                <?php else: ?>
                                    <span class="badge badge-error">Inactive</span>
                                <?php endif; ?>
                            </td>

                            <td class="py-3 px-4">
                                <p class="text-text-primary">
                                    <?php echo $user['last_login']
                                        ? date("M d, Y h:i A", strtotime($user['last_login']))
                                        : 'Never'; ?>
                                </p>
                                <p class="text-sm text-text-secondary">
                                    <?php echo htmlspecialchars($user['clinic']); ?>
                                </p>
                            </td>

                            <td class="py-3 px-4">
                                <div class="flex items-center gap-2">
                                    <button
                                        type="button"
                                        class="btn btn-ghost p-1.5 editUserBtn"
                                        data-id="<?php echo $user['user_id']; ?>"
                                        title="Edit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                    <button
                                        type="button"
                                        class="btn btn-ghost p-1.5 resetPasswordBtn"
                                        data-id="<?php echo $user['user_id']; ?>"
                                        title="Reset Password">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                                        </svg>
                                    </button>
                                    <?php if ($user['status'] === 'active'): ?>
                                        <!-- Deactivate -->
                                        <button
                                            type="button"
                                            class="btn btn-ghost p-1.5 text-error hover:bg-error-50 toggleStatusBtn"
                                            data-id="<?php echo $user['user_id']; ?>"
                                            data-status="inactive"
                                            title="Deactivate">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                        </button>
                                    <?php else: ?>
                                        <!-- Activate -->
                                        <button
                                            type="button"
                                            class="btn btn-ghost p-1.5 text-success hover:bg-success-50 toggleStatusBtn"
                                            data-id="<?php echo $user['user_id']; ?>"
                                            data-status="active"
                                            title="Activate">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M5 13l4 4L19 7"/>
                                            </svg>
                                        </button>
                                    <?php endif; ?>
                                    <button
                                        type="button"
                                        class="btn btn-ghost p-1.5 text-error hover:bg-error-50 deleteUserBtn"
                                        data-id="<?php echo $user['user_id']; ?>"
                                        title="Delete">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                    
                                </div>
                            </td>
                        </tr>

                        <?php endwhile; ?>
                        <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-4 text-text-secondary">
                                No users found.
                            </td>
                        </tr>
                        <?php endif; ?>

                        </tbody>

                </table>
            </div>

            <!-- Table Footer -->
            <div class="border-t border-border p-4">
                <div class="flex flex-col md:flex-row items-center justify-between gap-4">

                    <!-- Showing X–Y of Z -->
                    <div class="text-sm text-text-secondary">
                        Showing
                        <span class="font-medium text-text-primary">
                            <?php echo $start; ?>–<?php echo $end; ?>
                        </span>
                        of
                        <span class="font-medium text-text-primary">
                            <?php echo $totalUsers; ?>
                        </span>
                        users
                    </div>

                    <!-- Pagination Buttons -->
                    <div class="flex items-center gap-2">

                        <!-- Previous -->
                        <a href="?page=<?php echo $i; ?>
                        &search=<?php echo urlencode($_GET['search'] ?? ''); ?>
                        &status=<?php echo $_GET['status'] ?? 'all'; ?>
                        &role=<?php echo $_GET['role'] ?? 'all'; ?>">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 19l-7-7 7-7"/>
                            </svg>
                        </a>

                        <!-- Page Numbers -->
                        <?php
                        $range = 2; // pages before & after current
                        for ($i = max(1, $page - $range); $i <= min($totalPages, $page + $range); $i++):
                        ?>
                            <a href="?page=<?php echo $i; ?>"
                            class="btn <?php echo $i == $page ? 'btn-primary' : 'btn-ghost'; ?> p-2 min-w-[40px]">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>

                        <!-- Next -->
                        <a href="?page=<?php echo $i; ?>
                        &search=<?php echo urlencode($_GET['search'] ?? ''); ?>
                        &status=<?php echo $_GET['status'] ?? 'all'; ?>
                        &role=<?php echo $_GET['role'] ?? 'all'; ?>">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>

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

    <!-- Add User Modal (Hidden by default) -->
    <div id="addUserModal"
        class="fixed inset-0 z-modal hidden items-center justify-center p-4"
        style="background-color: rgba(0,0,0,0.5);">


        <div class="bg-surface rounded-xl shadow-xl max-w-md w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6">

                <!-- Modal Header -->
                <div class="flex items-center justify-between mb-6">
                    <h3 id="userModalTitle" class="text-xl font-semibold text-text-primary">
                        Add New User
                    </h3>
                    <button type="button" id="closeModal" class="btn btn-ghost p-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Form -->
                <form id="addUserForm"
                    class="space-y-4"
                    method="POST"
                    enctype="multipart/form-data">

                    <input type="hidden" name="user_id" id="edit_user_id">


                    <!-- Full Name -->
                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-2">
                            Full Name *
                        </label>
                        <input type="text" name="full_name" required
                            class="input w-full"
                            placeholder="Enter full name">
                    </div>

                    <!-- Username & Email -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-text-primary mb-2">
                                Username *
                            </label>
                            <input type="text" name="username" required
                                class="input w-full"
                                placeholder="Choose username">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-text-primary mb-2">
                                Email
                            </label>
                            <input type="email" name="email"
                                class="input w-full"
                                placeholder="user@cavmed.org">
                        </div>
                    </div>

                    <!-- Role & Position -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-text-primary mb-2">
                                Role *
                            </label>
                            <select id="roleSelect" name="role" required class="input w-full">
                                <option value="">Select role</option>
                                <option value="admin">Administrator</option>
                                <option value="doctor">Doctor</option>
                                <option value="medical_staff">Medical Staff</option>
                                <option value="encoder">Encoder</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-text-primary mb-2">
                                Position
                            </label>
                            <input type="text" name="position"
                                class="input w-full"
                                placeholder="e.g., Cardiologist">
                        </div>
                    </div>


                    <!-- Clinic & Contact Number -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-text-primary mb-2">
                                Clinic
                            </label>
                            <input type="text" name="clinic"
                                class="input w-full"
                                placeholder="Main Clinic">
                        </div>

                        
                        <!-- Contact Number -->
                        <div>
                            <label class="block text-sm font-medium text-text-primary mb-2">
                                Contact Number
                            </label>
                            <input type="tel" name="contact_number"
                                class="input w-full"
                                placeholder="09XXXXXXXXX">
                        </div>
                    </div>

                    <!-- Profile Picture Upload -->
                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-2">
                            Profile Picture
                        </label>
                        <input
                            type="file"
                            name="profile_picture"
                            accept="image/*"
                            class="input w-full">
                        <p class="text-xs text-text-secondary mt-1">
                            Accepted formats: JPG, PNG (max 2MB)
                        </p>
                    </div>

                    <!-- Password Info -->
                    <div class="text-sm text-text-secondary bg-secondary-50 p-3 rounded-lg">
                        Default password will be set to
                        <span class="font-medium text-text-primary">cavmed2025</span>
                        and should be changed on first login.
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-3 pt-6">
                        <button type="button" id="cancelAddUser"
                                class="btn btn-outline flex-1">
                            Cancel
                        </button>
                        <button
                            type="submit"
                            id="submitUserBtn"
                            class="btn btn-primary flex-1">
                            Create User
                        </button>

                    </div>

                </form>
            </div>
        </div>
    </div>

</body>
</html>