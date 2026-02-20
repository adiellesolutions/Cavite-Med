<?php
// admin_audit_log.php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: system_login_portal.html");
    exit;
}

if (!empty($_SESSION['force_change_password'])) {
    header("Location: force_change_password.php");
    exit;
}

// Database connection
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'cavitemed';

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get current user info
$user_id = $_SESSION['user_id'];
$user_query = "SELECT * FROM users WHERE user_id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 50;
$offset = ($page - 1) * $limit;

// Date filters
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-d', strtotime('-30 days'));
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');
$action_filter = isset($_GET['action']) ? $_GET['action'] : 'all';
$user_filter = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

// Build WHERE clause for filters - FIXED: Different tables have different column names
$params = [];
$types = "";

// Get total counts for pagination - FIXED: Handle each table separately with correct column names
$total_records = 0;

// Count inventory transactions
$inventory_count_query = "SELECT COUNT(*) as total FROM inventory_transactions WHERE 1=1";
$inventory_params = [];
$inventory_types = "";

if (!empty($date_from) && !empty($date_to)) {
    $inventory_count_query .= " AND DATE(created_at) BETWEEN ? AND ?";
    $inventory_params[] = $date_from;
    $inventory_params[] = $date_to;
    $inventory_types .= "ss";
}

if ($user_filter > 0) {
    $inventory_count_query .= " AND performed_by = ?";
    $inventory_params[] = $user_filter;
    $inventory_types .= "i";
}

$stmt = $conn->prepare($inventory_count_query);
if (!empty($inventory_params)) {
    $stmt->bind_param($inventory_types, ...$inventory_params);
}
$stmt->execute();
$total_records += $stmt->get_result()->fetch_assoc()['total'];

// Count distribution records
$distribution_count_query = "SELECT COUNT(*) as total FROM distribution WHERE 1=1";
$distribution_params = [];
$distribution_types = "";

if (!empty($date_from) && !empty($date_to)) {
    $distribution_count_query .= " AND DATE(created_at) BETWEEN ? AND ?";
    $distribution_params[] = $date_from;
    $distribution_params[] = $date_to;
    $distribution_types .= "ss";
}

if ($user_filter > 0) {
    $distribution_count_query .= " AND created_by = ?";
    $distribution_params[] = $user_filter;
    $distribution_types .= "i";
}

$stmt = $conn->prepare($distribution_count_query);
if (!empty($distribution_params)) {
    $stmt->bind_param($distribution_types, ...$distribution_params);
}
$stmt->execute();
$total_records += $stmt->get_result()->fetch_assoc()['total'];

// Count disposal records
$disposal_count_query = "SELECT COUNT(*) as total FROM disposal_records WHERE 1=1";
$disposal_params = [];
$disposal_types = "";

if (!empty($date_from) && !empty($date_to)) {
    $disposal_count_query .= " AND DATE(created_at) BETWEEN ? AND ?";
    $disposal_params[] = $date_from;
    $disposal_params[] = $date_to;
    $disposal_types .= "ss";
}

if ($user_filter > 0) {
    $disposal_count_query .= " AND created_by = ?";
    $disposal_params[] = $user_filter;
    $disposal_types .= "i";
}

$stmt = $conn->prepare($disposal_count_query);
if (!empty($disposal_params)) {
    $stmt->bind_param($disposal_types, ...$disposal_params);
}
$stmt->execute();
$total_records += $stmt->get_result()->fetch_assoc()['total'];

// Count stock returns
$returns_count_query = "SELECT COUNT(*) as total FROM stock_returns WHERE 1=1";
$returns_params = [];
$returns_types = "";

if (!empty($date_from) && !empty($date_to)) {
    $returns_count_query .= " AND DATE(created_at) BETWEEN ? AND ?";
    $returns_params[] = $date_from;
    $returns_params[] = $date_to;
    $returns_types .= "ss";
}

if ($user_filter > 0) {
    $returns_count_query .= " AND returned_by = ?";
    $returns_params[] = $user_filter;
    $returns_types .= "i";
}

$stmt = $conn->prepare($returns_count_query);
if (!empty($returns_params)) {
    $stmt->bind_param($returns_types, ...$returns_params);
}
$stmt->execute();
$total_records += $stmt->get_result()->fetch_assoc()['total'];

$total_pages = ceil($total_records / $limit);

// Fetch combined audit data from all tables
$audit_logs = [];

// 1. Get inventory transactions - FIXED: Proper WHERE clause
$inventory_query = "
    SELECT 
        'inventory' as source,
        it.id,
        it.transaction_type as action,
        it.quantity,
        it.remarks as description,
        it.performed_by as user_id,
        it.created_at,
        u.full_name as user_name,
        u.role as user_role,
        u.profile_picture,
        m.medicine_name,
        m.batch_number,
        CONCAT('Medicine: ', m.medicine_name, ' (Batch: ', m.batch_number, ') - ', 
               it.transaction_type, ' ', it.quantity, ' units') as full_description
    FROM inventory_transactions it
    LEFT JOIN users u ON it.performed_by = u.user_id
    LEFT JOIN medicine m ON it.medicine_id = m.id
    WHERE 1=1
";

$inventory_params = [];
$inventory_types = "";

if (!empty($date_from) && !empty($date_to)) {
    $inventory_query .= " AND DATE(it.created_at) BETWEEN ? AND ?";
    $inventory_params[] = $date_from;
    $inventory_params[] = $date_to;
    $inventory_types .= "ss";
}

if ($user_filter > 0) {
    $inventory_query .= " AND it.performed_by = ?";
    $inventory_params[] = $user_filter;
    $inventory_types .= "i";
}

$inventory_query .= " ORDER BY it.created_at DESC LIMIT ? OFFSET ?";
$inventory_params[] = $limit;
$inventory_params[] = $offset;
$inventory_types .= "ii";

$stmt = $conn->prepare($inventory_query);
if (!empty($inventory_params)) {
    $stmt->bind_param($inventory_types, ...$inventory_params);
}
$stmt->execute();
$inventory_logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$audit_logs = array_merge($audit_logs, $inventory_logs);

// 2. Get distribution records - FIXED: Proper WHERE clause
$distribution_query = "
    SELECT 
        'distribution' as source,
        d.id,
        d.status as action,
        d.quantity,
        d.remarks as description,
        d.created_by as user_id,
        d.created_at,
        u.full_name as user_name,
        u.role as user_role,
        u.profile_picture,
        m.medicine_name,
        m.batch_number,
        hc.center_name,
        CONCAT('Distributed ', d.quantity, ' units of ', m.medicine_name, 
               ' to ', hc.center_name, ' (Status: ', d.status, ')') as full_description
    FROM distribution d
    LEFT JOIN users u ON d.created_by = u.user_id
    LEFT JOIN medicine m ON d.medicine_id = m.id
    LEFT JOIN health_centers hc ON d.health_center_id = hc.id
    WHERE 1=1
";

$distribution_params = [];
$distribution_types = "";

if (!empty($date_from) && !empty($date_to)) {
    $distribution_query .= " AND DATE(d.created_at) BETWEEN ? AND ?";
    $distribution_params[] = $date_from;
    $distribution_params[] = $date_to;
    $distribution_types .= "ss";
}

if ($user_filter > 0) {
    $distribution_query .= " AND d.created_by = ?";
    $distribution_params[] = $user_filter;
    $distribution_types .= "i";
}

$distribution_query .= " ORDER BY d.created_at DESC LIMIT ? OFFSET ?";
$distribution_params[] = $limit;
$distribution_params[] = $offset;
$distribution_types .= "ii";

$stmt = $conn->prepare($distribution_query);
if (!empty($distribution_params)) {
    $stmt->bind_param($distribution_types, ...$distribution_params);
}
$stmt->execute();
$distribution_logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$audit_logs = array_merge($audit_logs, $distribution_logs);

// 3. Get disposal records - FIXED: Proper WHERE clause
$disposal_query = "
    SELECT 
        'disposal' as source,
        dr.id,
        dr.disposal_method as action,
        dr.quantity,
        dr.notes as description,
        dr.created_by as user_id,
        dr.created_at,
        u.full_name as user_name,
        u.role as user_role,
        u.profile_picture,
        m.medicine_name,
        dr.batch_number,
        CONCAT('Disposed ', dr.quantity, ' units of ', m.medicine_name, 
               ' (Batch: ', dr.batch_number, ') via ', dr.disposal_method) as full_description
    FROM disposal_records dr
    LEFT JOIN users u ON dr.created_by = u.user_id
    LEFT JOIN medicine m ON dr.medicine_id = m.id
    WHERE 1=1
";

$disposal_params = [];
$disposal_types = "";

if (!empty($date_from) && !empty($date_to)) {
    $disposal_query .= " AND DATE(dr.created_at) BETWEEN ? AND ?";
    $disposal_params[] = $date_from;
    $disposal_params[] = $date_to;
    $disposal_types .= "ss";
}

if ($user_filter > 0) {
    $disposal_query .= " AND dr.created_by = ?";
    $disposal_params[] = $user_filter;
    $disposal_types .= "i";
}

$disposal_query .= " ORDER BY dr.created_at DESC LIMIT ? OFFSET ?";
$disposal_params[] = $limit;
$disposal_params[] = $offset;
$disposal_types .= "ii";

$stmt = $conn->prepare($disposal_query);
if (!empty($disposal_params)) {
    $stmt->bind_param($disposal_types, ...$disposal_params);
}
$stmt->execute();
$disposal_logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$audit_logs = array_merge($audit_logs, $disposal_logs);

// 4. Get stock returns - FIXED: Proper WHERE clause
$returns_query = "
    SELECT 
        'return' as source,
        sr.id,
        'return' as action,
        sr.quantity,
        sr.reason as description,
        sr.returned_by as user_id,
        sr.created_at,
        u.full_name as user_name,
        u.role as user_role,
        u.profile_picture,
        m.medicine_name,
        m.batch_number,
        hc.center_name,
        CONCAT('Returned ', sr.quantity, ' units of ', m.medicine_name, 
               ' from ', hc.center_name, ' - Reason: ', sr.reason) as full_description
    FROM stock_returns sr
    LEFT JOIN users u ON sr.returned_by = u.user_id
    LEFT JOIN medicine m ON sr.medicine_id = m.id
    LEFT JOIN health_centers hc ON sr.health_center_id = hc.id
    WHERE 1=1
";

$returns_params = [];
$returns_types = "";

if (!empty($date_from) && !empty($date_to)) {
    $returns_query .= " AND DATE(sr.created_at) BETWEEN ? AND ?";
    $returns_params[] = $date_from;
    $returns_params[] = $date_to;
    $returns_types .= "ss";
}

if ($user_filter > 0) {
    $returns_query .= " AND sr.returned_by = ?";
    $returns_params[] = $user_filter;
    $returns_types .= "i";
}

$returns_query .= " ORDER BY sr.created_at DESC LIMIT ? OFFSET ?";
$returns_params[] = $limit;
$returns_params[] = $offset;
$returns_types .= "ii";

$stmt = $conn->prepare($returns_query);
if (!empty($returns_params)) {
    $stmt->bind_param($returns_types, ...$returns_params);
}
$stmt->execute();
$returns_logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$audit_logs = array_merge($audit_logs, $returns_logs);

// 5. Get user login activity - FIXED: Proper WHERE clause
if (!empty($date_from) && !empty($date_to)) {
    $user_activity_query = "
        SELECT 
            'user' as source,
            u.user_id as id,
            'login' as action,
            NULL as quantity,
            CONCAT(u.role, ' login activity') as description,
            u.user_id as user_id,
            u.last_login as created_at,
            u.full_name as user_name,
            u.role as user_role,
            u.profile_picture,
            NULL as medicine_name,
            NULL as batch_number,
            NULL as center_name,
            CONCAT(u.full_name, ' logged in at ', DATE_FORMAT(u.last_login, '%Y-%m-%d %H:%i:%s')) as full_description
        FROM users u
        WHERE u.last_login IS NOT NULL 
        AND DATE(u.last_login) BETWEEN ? AND ?
        ORDER BY u.last_login DESC
        LIMIT ? OFFSET ?
    ";
    
    $stmt = $conn->prepare($user_activity_query);
    $user_params = [$date_from, $date_to, $limit, $offset];
    $stmt->bind_param("ssii", ...$user_params);
    $stmt->execute();
    $user_logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $audit_logs = array_merge($audit_logs, $user_logs);
}

// Sort all logs by created_at
usort($audit_logs, function($a, $b) {
    if ($a['created_at'] === null) return 1;
    if ($b['created_at'] === null) return -1;
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

// Apply action filter if specified
if ($action_filter !== 'all') {
    $audit_logs = array_filter($audit_logs, function($log) use ($action_filter) {
        return $log['action'] === $action_filter || $log['source'] === $action_filter;
    });
}

// Get unique users for filter dropdown
$users_query = "SELECT user_id, full_name, username, role FROM users WHERE status = 'active' ORDER BY full_name";
$users_result = $conn->query($users_query);
$users = $users_result->fetch_all(MYSQLI_ASSOC);

// Get summary statistics
$stats = [
    'total_inventory' => $conn->query("SELECT COUNT(*) as count FROM inventory_transactions WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetch_assoc()['count'],
    'total_distributions' => $conn->query("SELECT COUNT(*) as count FROM distribution WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetch_assoc()['count'],
    'total_disposals' => $conn->query("SELECT COUNT(*) as count FROM disposal_records WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetch_assoc()['count'],
    'total_returns' => $conn->query("SELECT COUNT(*) as count FROM stock_returns WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetch_assoc()['count'],
];

// Slice for current page
$audit_logs = array_slice($audit_logs, 0, $limit);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Logs - CAVMED Portal</title>
    <link rel="stylesheet" href="../css/main.css">
</head>
<body class="bg-background min-h-screen flex flex-col">

    <!-- Header -->
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
                    <a href="admin_dashboard.php" class="nav-item whitespace-nowrap">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        Dashboard
                    </a>
                    <a href="admin_analytics_and_reporting.php" class="nav-item whitespace-nowrap">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Analytics and Reports
                    </a>
                    <a href="admin_audit_log.php" class="nav-item nav-item-active whitespace-nowrap">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Audit Logs
                    </a>
                    <a href="admin_user_management.php" class="nav-item whitespace-nowrap">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        User Management
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
    <main class="flex-1 w-full mx-auto px-6 py-8">
    

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <div class="card hover:shadow-lg transition-shadow">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-primary-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-primary-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-text-secondary">Inventory Actions</p>
                        <p class="text-2xl font-bold text-text-primary"><?php echo number_format($stats['total_inventory']); ?></p>
                    </div>
                </div>
            </div>

            <div class="card hover:shadow-lg transition-shadow">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-success-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-success-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-text-secondary">Distributions</p>
                        <p class="text-2xl font-bold text-text-primary"><?php echo number_format($stats['total_distributions']); ?></p>
                    </div>
                </div>
            </div>

            <div class="card hover:shadow-lg transition-shadow">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-warning-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-warning-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-text-secondary">Disposals</p>
                        <p class="text-2xl font-bold text-text-primary"><?php echo number_format($stats['total_disposals']); ?></p>
                    </div>
                </div>
            </div>

            <div class="card hover:shadow-lg transition-shadow">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-error-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-error-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 15v-1a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3m9 14v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-text-secondary">Returns</p>
                        <p class="text-2xl font-bold text-text-primary"><?php echo number_format($stats['total_returns']); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="card mb-8">
            <form method="GET" action="">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-2">Date From</label>
                        <input type="date" 
                               name="date_from" 
                               value="<?php echo htmlspecialchars($date_from); ?>"
                               class="input w-full">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-2">Date To</label>
                        <input type="date" 
                               name="date_to" 
                               value="<?php echo htmlspecialchars($date_to); ?>"
                               class="input w-full">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-2">Action Type</label>
                        <select name="action" class="input w-full">
                            <option value="all">All Actions</option>
                            <option value="inventory" <?php echo $action_filter === 'inventory' ? 'selected' : ''; ?>>Inventory</option>
                            <option value="distribution" <?php echo $action_filter === 'distribution' ? 'selected' : ''; ?>>Distribution</option>
                            <option value="disposal" <?php echo $action_filter === 'disposal' ? 'selected' : ''; ?>>Disposal</option>
                            <option value="return" <?php echo $action_filter === 'return' ? 'selected' : ''; ?>>Returns</option>
                            <option value="add" <?php echo $action_filter === 'add' ? 'selected' : ''; ?>>Add Stock</option>
                            <option value="deduct" <?php echo $action_filter === 'deduct' ? 'selected' : ''; ?>>Deduct Stock</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-2"></label>

                    </div>
                    <div class="flex items-end gap-2">
                        <button type="submit" class="btn btn-primary flex-1">Apply</button>
                        <a href="admin_audit_log.php" class="btn btn-outline flex-1">Clear</a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Audit Log Table -->
        <div class="card overflow-hidden">
            <div class="px-6 py-4 border-b border-border flex items-center justify-between">
                <h3 class="font-semibold text-text-primary">System Activity Logs</h3>
                <span class="text-sm text-text-secondary">
                    Showing <?php echo count($audit_logs); ?> of <?php echo number_format($total_records); ?> records
                </span>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-secondary-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Timestamp</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">User</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Action Type</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Description</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Medicine</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-text-secondary uppercase tracking-wider">Quantity</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <?php if (empty($audit_logs)): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-text-secondary">
                                    <svg class="w-12 h-12 mx-auto text-text-tertiary mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    <p>No audit logs found</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($audit_logs as $log): ?>
                                <tr class="hover:bg-secondary-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-text-primary">
                                            <?php echo date('M d, Y', strtotime($log['created_at'])); ?>
                                        </div>
                                        <div class="text-xs text-text-secondary">
                                            <?php echo date('h:i:s A', strtotime($log['created_at'])); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <img src="/HIMS/<?php echo $log['profile_picture'] ?? 'uploads/profile/default.png'; ?>" 
                                                 alt="" 
                                                 class="w-8 h-8 rounded-full object-cover"
                                                 onerror="this.src='/HIMS/uploads/profile/default.png';">
                                            <div>
                                                <div class="text-sm font-medium text-text-primary">
                                                    <?php echo htmlspecialchars($log['user_name'] ?? 'System'); ?>
                                                </div>
                                                <div class="text-xs text-text-secondary">
                                                    <?php echo ucfirst($log['user_role'] ?? 'N/A'); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php
                                        $badgeClass = match($log['source']) {
                                            'inventory' => 'badge-primary',
                                            'distribution' => 'badge-success',
                                            'disposal' => 'badge-warning',
                                            'return' => 'badge-error',
                                            default => 'badge-secondary'
                                        };
                                        ?>
                                        <span class="badge <?php echo $badgeClass; ?>">
                                            <?php echo ucfirst($log['action']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-text-primary max-w-xs">
                                            <?php echo htmlspecialchars($log['full_description'] ?? $log['description']); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php if (!empty($log['medicine_name'])): ?>
                                            <div class="text-sm text-text-primary">
                                                <?php echo htmlspecialchars($log['medicine_name']); ?>
                                            </div>
                                            <?php if (!empty($log['batch_number'])): ?>
                                                <div class="text-xs text-text-secondary">
                                                    Batch: <?php echo htmlspecialchars($log['batch_number']); ?>
                                                </div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-text-secondary">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php if (!empty($log['quantity'])): ?>
                                            <span class="text-sm font-semibold text-text-primary">
                                                <?php echo number_format($log['quantity']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-text-secondary">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="px-6 py-4 border-t border-border flex justify-center gap-2">
                    <?php if ($page > 1): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" 
                           class="px-3 py-1 border border-border rounded text-text-primary hover:bg-secondary-50 transition-colors">
                            &laquo;
                        </a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                           class="px-3 py-1 border border-border rounded <?php echo $i === $page ? 'bg-primary text-white border-primary' : 'text-text-primary hover:bg-secondary-50'; ?> transition-colors">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" 
                           class="px-3 py-1 border border-border rounded text-text-primary hover:bg-secondary-50 transition-colors">
                            &raquo;
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

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
<?php $conn->close(); ?>