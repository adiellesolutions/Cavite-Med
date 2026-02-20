<?php
// admin_dashboard.php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: system_login_portal.html");
    exit;
}

if (!empty($_SESSION['force_change_password'])) {
    header("Location: force_change_password.php");
    exit;
}

date_default_timezone_set('Asia/Manila');


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

// Get current date for greeting
$current_hour = date('H');
if ($current_hour < 12) {
    $greeting = "Good Morning";
} elseif ($current_hour < 18) {
    $greeting = "Good Afternoon";
} else {
    $greeting = "Good Evening";
}

// Get summary statistics
$summary = [];

// Total medicines count
$medicines_query = "SELECT COUNT(*) as total FROM medicine WHERE is_archived = 0 OR is_archived IS NULL";
$result = $conn->query($medicines_query);
$summary['total_medicines'] = $result ? $result->fetch_assoc()['total'] : 0;

// Total health centers
$centers_query = "SELECT COUNT(*) as total FROM health_centers WHERE is_archived = 0 OR is_archived IS NULL";
$result = $conn->query($centers_query);
$summary['total_centers'] = $result ? $result->fetch_assoc()['total'] : 0;

// Total users
$users_query = "SELECT COUNT(*) as total FROM users WHERE status = 'active' OR status IS NULL";
$result = $conn->query($users_query);
$summary['total_users'] = $result ? $result->fetch_assoc()['total'] : 0;

// Total suppliers
$suppliers_query = "SELECT COUNT(*) as total FROM suppliers WHERE is_archived = 0 OR is_archived IS NULL";
$result = $conn->query($suppliers_query);
$summary['total_suppliers'] = $result ? $result->fetch_assoc()['total'] : 0;

// Get inventory status counts
$inventory_status = [];

$status_query = "SELECT 
    COALESCE(SUM(CASE WHEN status = 'in_stock' THEN 1 ELSE 0 END), 0) as in_stock,
    COALESCE(SUM(CASE WHEN status = 'low_stock' THEN 1 ELSE 0 END), 0) as low_stock,
    COALESCE(SUM(CASE WHEN status = 'out_of_stock' THEN 1 ELSE 0 END), 0) as out_of_stock,
    COALESCE(SUM(CASE WHEN status = 'expired' THEN 1 ELSE 0 END), 0) as expired
FROM medicine WHERE is_archived = 0 OR is_archived IS NULL";
$result = $conn->query($status_query);
$inventory_status = $result ? $result->fetch_assoc() : ['in_stock' => 0, 'low_stock' => 0, 'out_of_stock' => 0, 'expired' => 0];

// Get recent activities (last 10) - FIXED: Added table aliases for created_at
$recent_activities = [];

// Inventory transactions - FIXED: Added alias for created_at
$inv_activity_query = "
    SELECT 
        'inventory' as type,
        CONCAT(UCASE(LEFT(it.transaction_type, 1)), SUBSTRING(it.transaction_type, 2)) as action,
        it.quantity,
        it.created_at,
        CONCAT(u.full_name, ' ', 
            CASE 
                WHEN it.transaction_type = 'add' THEN CONCAT('added ', it.quantity, ' units')
                WHEN it.transaction_type = 'deduct' THEN CONCAT('deducted ', it.quantity, ' units')
                WHEN it.transaction_type = 'dispose' THEN CONCAT('disposed ', it.quantity, ' units')
                WHEN it.transaction_type = 'distribute' THEN CONCAT('distributed ', it.quantity, ' units')
                ELSE it.transaction_type
            END, ' of ', COALESCE(m.medicine_name, 'medicine')) as description
    FROM inventory_transactions it
    LEFT JOIN users u ON it.performed_by = u.user_id
    LEFT JOIN medicine m ON it.medicine_id = m.id
    ORDER BY it.created_at DESC
    LIMIT 5
";
$result = $conn->query($inv_activity_query);
if ($result) {
    while($row = $result->fetch_assoc()) {
        $recent_activities[] = $row;
    }
}

// Distribution activities - FIXED: Added alias for created_at
$dist_activity_query = "
    SELECT 
        'distribution' as type,
        'Distribution' as action,
        d.quantity,
        d.created_at,
        CONCAT(u.full_name, ' distributed ', d.quantity, ' units to ', COALESCE(hc.center_name, 'health center')) as description
    FROM distribution d
    LEFT JOIN users u ON d.created_by = u.user_id
    LEFT JOIN health_centers hc ON d.health_center_id = hc.id
    WHERE d.status = 'distributed'
    ORDER BY d.created_at DESC
    LIMIT 5
";
$result = $conn->query($dist_activity_query);
if ($result) {
    while($row = $result->fetch_assoc()) {
        $recent_activities[] = $row;
    }
}

// Sort activities by date
usort($recent_activities, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});
$recent_activities = array_slice($recent_activities, 0, 10);

// Get low stock alerts
$low_stock_query = "
    SELECT 
        m.medicine_name,
        m.current_stock,
        m.reorder_point,
        m.category,
        COALESCE(s.supplier_name, 'No Supplier') as supplier_name
    FROM medicine m
    LEFT JOIN suppliers s ON m.supplier_id = s.id
    WHERE m.status = 'low_stock' AND (m.is_archived = 0 OR m.is_archived IS NULL)
    ORDER BY m.current_stock ASC
    LIMIT 5
";
$low_stock_result = $conn->query($low_stock_query);
$low_stock_items = $low_stock_result ? $low_stock_result->fetch_all(MYSQLI_ASSOC) : [];

// Get expiring soon items
$expiring_query = "
    SELECT 
        m.medicine_name,
        m.batch_number,
        m.expiry_date,
        m.current_stock,
        DATEDIFF(m.expiry_date, NOW()) as days_left
    FROM medicine m
    WHERE m.expiry_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 30 DAY)
    AND (m.is_archived = 0 OR m.is_archived IS NULL)
    ORDER BY m.expiry_date ASC
    LIMIT 5
";
$expiring_result = $conn->query($expiring_query);
$expiring_items = $expiring_result ? $expiring_result->fetch_all(MYSQLI_ASSOC) : [];

// Get pending distributions
$pending_dist_query = "
    SELECT 
        d.id,
        d.quantity,
        d.created_at,
        COALESCE(m.medicine_name, 'Unknown Medicine') as medicine_name,
        COALESCE(hc.center_name, 'Unknown Center') as center_name,
        COALESCE(u.full_name, 'Unknown User') as requested_by
    FROM distribution d
    LEFT JOIN medicine m ON d.medicine_id = m.id
    LEFT JOIN health_centers hc ON d.health_center_id = hc.id
    LEFT JOIN users u ON d.created_by = u.user_id
    WHERE d.status = 'pending'
    ORDER BY d.created_at ASC
    LIMIT 5
";
$pending_result = $conn->query($pending_dist_query);
$pending_distributions = $pending_result ? $pending_result->fetch_all(MYSQLI_ASSOC) : [];

// Get today's statistics
$today = date('Y-m-d');
$today_stats = [];

// Today's inventory transactions
$today_inv_query = "SELECT COUNT(*) as count FROM inventory_transactions WHERE DATE(created_at) = ?";
$stmt = $conn->prepare($today_inv_query);
if ($stmt) {
    $stmt->bind_param("s", $today);
    $stmt->execute();
    $today_stats['transactions'] = $stmt->get_result()->fetch_assoc()['count'];
    $stmt->close();
} else {
    $today_stats['transactions'] = 0;
}

// Today's distributions
$today_dist_query = "SELECT COUNT(*) as count FROM distribution WHERE DATE(created_at) = ? AND status = 'distributed'";
$stmt = $conn->prepare($today_dist_query);
if ($stmt) {
    $stmt->bind_param("s", $today);
    $stmt->execute();
    $today_stats['distributions'] = $stmt->get_result()->fetch_assoc()['count'];
    $stmt->close();
} else {
    $today_stats['distributions'] = 0;
}

// Get top medicines by stock value
$top_medicines_query = "
    SELECT 
        m.medicine_name,
        m.current_stock,
        (m.current_stock * m.unit_price) as total_value,
        m.status
    FROM medicine m
    WHERE (m.is_archived = 0 OR m.is_archived IS NULL)
    ORDER BY total_value DESC
    LIMIT 5
";
$top_result = $conn->query($top_medicines_query);
$top_medicines = $top_result ? $top_result->fetch_all(MYSQLI_ASSOC) : [];

// Get total stock value
$value_query = "SELECT COALESCE(SUM(current_stock * unit_price), 0) as total FROM medicine WHERE is_archived = 0 OR is_archived IS NULL";
$result = $conn->query($value_query);
$total_value = $result ? $result->fetch_assoc()['total'] : 0;

// Get recent distributions count for the period
$recent_dist_count = count($recent_activities);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - CAVMED Portal</title>
    <link rel="stylesheet" href="../css/main.css">
    <style>
        .greeting-section {
            background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-700) 100%);
            color: white;
            padding: 2rem;
            border-radius: var(--radius-lg);
            margin-bottom: 2rem;
        }
        .stat-card {
            background: var(--color-surface);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            box-shadow: var(--shadow-base);
            border: 1px solid var(--color-border);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--color-primary), var(--color-primary-400));
        }
        .stat-icon {
            width: 3rem;
            height: 3rem;
            border-radius: var(--radius-base);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
        }
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--color-text-primary);
            line-height: 1.2;
        }
        .stat-label {
            font-size: 0.875rem;
            color: var(--color-text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        .status-good { background: var(--color-success-100); color: var(--color-success-700); }
        .status-warning { background: var(--color-warning-100); color: var(--color-warning-700); }
        .status-critical { background: var(--color-error-100); color: var(--color-error-700); }
        .activity-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: 1rem;
            border-bottom: 1px solid var(--color-border);
            transition: background-color 0.2s;
        }
        .activity-item:hover {
            background-color: var(--color-secondary-50);
        }
        .activity-item:last-child {
            border-bottom: none;
        }
        .activity-icon {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: var(--radius-base);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .activity-content {
            flex: 1;
        }
        .activity-time {
            font-size: 0.75rem;
            color: var(--color-text-tertiary);
        }
        .quick-action-card {
            background: var(--color-secondary-50);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            text-align: center;
            transition: all 0.2s;
            border: 1px solid transparent;
            text-decoration: none;
            display: block;
        }
        .quick-action-card:hover {
            background: var(--color-surface);
            border-color: var(--color-primary);
            transform: scale(1.02);
            box-shadow: var(--shadow-md);
        }
        .progress-bar {
            width: 100%;
            height: 8px;
            background: var(--color-secondary-200);
            border-radius: 4px;
            overflow: hidden;
        }
        .progress-fill {
            height: 100%;
            border-radius: 4px;
            transition: width 0.3s ease;
        }
        .inventory-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-top: 1rem;
        }
        .inventory-stat {
            text-align: center;
            padding: 1rem;
            background: var(--color-secondary-50);
            border-radius: var(--radius-base);
        }
        .welcome-text {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        .date-display {
            font-size: 1.25rem;
            opacity: 0.9;
        }
    </style>
</head>
<body class="bg-background min-h-screen flex flex-col">

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
                    <a href="admin_analytics_and_reporting.php" class="nav-item whitespace-nowrap">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Analytics and Reports
                    </a>
                    <a href="admin_audit_log.php" class="nav-item whitespace-nowrap">
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
        
        <!-- Greeting Section -->
        <div class="greeting-section">
            <div class="flex items-center justify-between">
                <div>
                    <div class="welcome-text"><?php echo $greeting; ?>, <?php echo htmlspecialchars($user['full_name'] ?? 'Admin'); ?>!</div>
                    <div class="text-primary-100">Welcome to your dashboard. Here's what's happening with your inventory today.</div>
                </div>
                <div class="text-right date-display">
                    <div class="font-bold"><?php echo date('F j, Y'); ?></div>
                    <div class="text-primary-100"><?php echo date('l'); ?></div>
                </div>
            </div>
        </div>

        <!-- Quick Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="stat-card">
                <div class="stat-icon" style="background: var(--color-primary-50);">
                    <svg class="w-6 h-6 text-primary-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                </div>
                <div class="stat-value"><?php echo number_format($summary['total_medicines']); ?></div>
                <div class="stat-label">Total Medicines</div>
                <div class="mt-3 flex gap-2 text-sm">
                    <span class="text-success-600"><?php echo $inventory_status['in_stock']; ?> in stock</span>
                    <span class="text-warning-600"><?php echo $inventory_status['low_stock']; ?> low</span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: var(--color-success-50);">
                    <svg class="w-6 h-6 text-success-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="stat-value">₱<?php echo number_format($total_value, 0); ?></div>
                <div class="stat-label">Total Stock Value</div>
                <div class="mt-3 text-sm text-text-secondary">
                    Across all medicines
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: var(--color-warning-50);">
                    <svg class="w-6 h-6 text-warning-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <div class="stat-value"><?php echo number_format($summary['total_centers']); ?></div>
                <div class="stat-label">Health Centers</div>
                <div class="mt-3 text-sm text-text-secondary">
                    <?php echo number_format($summary['total_users']); ?> active users
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: var(--color-error-50);">
                    <svg class="w-6 h-6 text-error-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
                <div class="stat-value"><?php echo number_format($summary['total_suppliers']); ?></div>
                <div class="stat-label">Suppliers</div>
                <div class="mt-3 text-sm text-text-secondary">
                    Partner organizations
                </div>
            </div>
        </div>

        <!-- Today's Activity -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div class="card">
                <div class="px-6 py-4 border-b border-border">
                    <h3 class="text-lg font-semibold text-text-primary flex items-center gap-2">
                        <span class="w-1 h-6 bg-primary rounded-full"></span>
                        Today's Activity
                    </h3>
                </div>
                <div class="p-6">
                    <div class="inventory-grid">
                        <div class="inventory-stat">
                            <div class="text-2xl font-bold text-primary-700"><?php echo $today_stats['transactions']; ?></div>
                            <div class="text-xs text-text-secondary mt-1">Transactions</div>
                        </div>
                        <div class="inventory-stat">
                            <div class="text-2xl font-bold text-success-700"><?php echo $today_stats['distributions']; ?></div>
                            <div class="text-xs text-text-secondary mt-1">Distributions</div>
                        </div>
                        <div class="inventory-stat">
                            <div class="text-2xl font-bold text-warning-700"><?php echo count($pending_distributions); ?></div>
                            <div class="text-xs text-text-secondary mt-1">Pending</div>
                        </div>
                        <div class="inventory-stat">
                            <div class="text-2xl font-bold text-error-700"><?php echo count($expiring_items); ?></div>
                            <div class="text-xs text-text-secondary mt-1">Expiring</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="px-6 py-4 border-b border-border">
                    <h3 class="text-lg font-semibold text-text-primary flex items-center gap-2">
                        <span class="w-1 h-6 bg-success rounded-full"></span>
                        Inventory Status
                    </h3>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-text-secondary">In Stock</span>
                                <span class="font-medium"><?php echo $inventory_status['in_stock']; ?> items</span>
                            </div>
                            <div class="progress-bar">
                                <?php $percentage = $summary['total_medicines'] > 0 ? ($inventory_status['in_stock'] / $summary['total_medicines']) * 100 : 0; ?>
                                <div class="progress-fill" style="width: <?php echo $percentage; ?>%; background: var(--color-success);"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-text-secondary">Low Stock</span>
                                <span class="font-medium"><?php echo $inventory_status['low_stock']; ?> items</span>
                            </div>
                            <div class="progress-bar">
                                <?php $percentage = $summary['total_medicines'] > 0 ? ($inventory_status['low_stock'] / $summary['total_medicines']) * 100 : 0; ?>
                                <div class="progress-fill" style="width: <?php echo $percentage; ?>%; background: var(--color-warning);"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-text-secondary">Out of Stock</span>
                                <span class="font-medium"><?php echo $inventory_status['out_of_stock']; ?> items</span>
                            </div>
                            <div class="progress-bar">
                                <?php $percentage = $summary['total_medicines'] > 0 ? ($inventory_status['out_of_stock'] / $summary['total_medicines']) * 100 : 0; ?>
                                <div class="progress-fill" style="width: <?php echo $percentage; ?>%; background: var(--color-error);"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-text-secondary">Expired</span>
                                <span class="font-medium"><?php echo $inventory_status['expired']; ?> items</span>
                            </div>
                            <div class="progress-bar">
                                <?php $percentage = $summary['total_medicines'] > 0 ? ($inventory_status['expired'] / $summary['total_medicines']) * 100 : 0; ?>
                                <div class="progress-fill" style="width: <?php echo $percentage; ?>%; background: var(--color-secondary-400);"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alerts and Quick Actions -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <!-- Low Stock Alerts -->
            <div class="card">
                <div class="px-6 py-4 border-b border-border">
                    <h3 class="text-lg font-semibold text-text-primary flex items-center gap-2">
                        <span class="w-1 h-6 bg-warning rounded-full"></span>
                        Low Stock Alerts
                        <?php if (count($low_stock_items) > 0): ?>
                            <span class="ml-auto status-badge status-warning"><?php echo count($low_stock_items); ?> items</span>
                        <?php endif; ?>
                    </h3>
                </div>
                <div class="divide-y divide-border max-h-96 overflow-y-auto">
                    <?php if (!empty($low_stock_items)): ?>
                        <?php foreach ($low_stock_items as $item): ?>
                        <div class="p-4 hover:bg-secondary-50 transition-colors">
                            <div class="flex justify-between items-start mb-2">
                                <h4 class="font-medium text-text-primary"><?php echo htmlspecialchars($item['medicine_name']); ?></h4>
                                <span class="status-badge status-warning"><?php echo $item['current_stock']; ?> left</span>
                            </div>
                            <p class="text-xs text-text-secondary mb-1">Category: <?php echo htmlspecialchars($item['category']); ?></p>
                            <p class="text-xs text-text-secondary mb-1">Supplier: <?php echo htmlspecialchars($item['supplier_name']); ?></p>
                            <p class="text-xs font-medium text-warning-700 mt-2">Reorder at: <?php echo $item['reorder_point']; ?> units</p>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="p-8 text-center text-text-secondary">
                            <svg class="w-16 h-16 mx-auto mb-4 text-success-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <p class="text-lg">All good!</p>
                            <p class="text-sm mt-1">No low stock items</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Expiring Soon -->
            <div class="card">
                <div class="px-6 py-4 border-b border-border">
                    <h3 class="text-lg font-semibold text-text-primary flex items-center gap-2">
                        <span class="w-1 h-6 bg-error rounded-full"></span>
                        Expiring Soon
                        <?php if (count($expiring_items) > 0): ?>
                            <span class="ml-auto status-badge status-critical"><?php echo count($expiring_items); ?> items</span>
                        <?php endif; ?>
                    </h3>
                </div>
                <div class="divide-y divide-border max-h-96 overflow-y-auto">
                    <?php if (!empty($expiring_items)): ?>
                        <?php foreach ($expiring_items as $item): ?>
                        <div class="p-4 hover:bg-secondary-50 transition-colors">
                            <div class="flex justify-between items-start mb-2">
                                <h4 class="font-medium text-text-primary"><?php echo htmlspecialchars($item['medicine_name']); ?></h4>
                                <span class="status-badge <?php echo ($item['days_left'] ?? 0) <= 7 ? 'status-critical' : 'status-warning'; ?>">
                                    <?php echo $item['days_left'] ?? 0; ?> days
                                </span>
                            </div>
                            <p class="text-xs text-text-secondary mb-1">Batch: <?php echo htmlspecialchars($item['batch_number']); ?></p>
                            <p class="text-xs text-text-secondary mb-1">Expires: <?php echo date('M d, Y', strtotime($item['expiry_date'])); ?></p>
                            <p class="text-xs font-medium mt-2">Stock: <?php echo number_format($item['current_stock']); ?> units</p>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="p-8 text-center text-text-secondary">
                            <svg class="w-16 h-16 mx-auto mb-4 text-success-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <p class="text-lg">All good!</p>
                            <p class="text-sm mt-1">No items expiring soon</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Pending Distributions -->
            <div class="card">
                <div class="px-6 py-4 border-b border-border">
                    <h3 class="text-lg font-semibold text-text-primary flex items-center gap-2">
                        <span class="w-1 h-6 bg-primary rounded-full"></span>
                        Pending Distributions
                        <?php if (count($pending_distributions) > 0): ?>
                            <span class="ml-auto status-badge status-warning"><?php echo count($pending_distributions); ?> pending</span>
                        <?php endif; ?>
                    </h3>
                </div>
                <div class="divide-y divide-border max-h-96 overflow-y-auto">
                    <?php if (!empty($pending_distributions)): ?>
                        <?php foreach ($pending_distributions as $pending): ?>
                        <div class="p-4 hover:bg-secondary-50 transition-colors">
                            <div class="flex justify-between items-start mb-2">
                                <h4 class="font-medium text-text-primary"><?php echo htmlspecialchars($pending['medicine_name']); ?></h4>
                                <span class="font-semibold text-primary-700"><?php echo $pending['quantity']; ?> units</span>
                            </div>
                            <p class="text-xs text-text-secondary mb-1">To: <?php echo htmlspecialchars($pending['center_name']); ?></p>
                            <p class="text-xs text-text-secondary mb-1">Requested by: <?php echo htmlspecialchars($pending['requested_by']); ?></p>
                            <p class="text-xs text-text-secondary mt-2">Date: <?php echo date('M d, Y', strtotime($pending['created_at'])); ?></p>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="p-8 text-center text-text-secondary">
                            <svg class="w-16 h-16 mx-auto mb-4 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            <p class="text-lg">All clear!</p>
                            <p class="text-sm mt-1">No pending distributions</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Activity and Top Medicines -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Recent Activity -->
            <div class="card">
                <div class="px-6 py-4 border-b border-border">
                    <h3 class="text-lg font-semibold text-text-primary flex items-center gap-2">
                        <span class="w-1 h-6 bg-primary rounded-full"></span>
                        Recent Activity
                    </h3>
                </div>
                <div class="divide-y divide-border max-h-96 overflow-y-auto">
                    <?php if (!empty($recent_activities)): ?>
                        <?php foreach ($recent_activities as $activity): ?>
                        <div class="activity-item">
                            <div class="activity-icon" style="background: var(--color-<?php 
                                echo $activity['type'] == 'inventory' ? 'primary' : 'success'; 
                            ?>-50);">
                                <svg class="w-5 h-5 text-<?php 
                                    echo $activity['type'] == 'inventory' ? 'primary' : 'success'; 
                                ?>-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <?php if ($activity['type'] == 'inventory'): ?>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                    <?php else: ?>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                    <?php endif; ?>
                                </svg>
                            </div>
                            <div class="activity-content">
                                <p class="text-sm"><?php echo htmlspecialchars($activity['description']); ?></p>
                                <p class="activity-time mt-1"><?php echo date('M d, Y h:i A', strtotime($activity['created_at'])); ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="p-8 text-center text-text-secondary">
                            <svg class="w-16 h-16 mx-auto mb-4 text-text-tertiary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <p class="text-lg">No recent activity</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Top Medicines by Value -->
            <div class="card">
                <div class="px-6 py-4 border-b border-border">
                    <h3 class="text-lg font-semibold text-text-primary flex items-center gap-2">
                        <span class="w-1 h-6 bg-success rounded-full"></span>
                        Top Medicines by Value
                    </h3>
                </div>
                <div class="divide-y divide-border max-h-96 overflow-y-auto">
                    <?php if (!empty($top_medicines)): ?>
                        <?php foreach ($top_medicines as $medicine): ?>
                        <div class="p-4 hover:bg-secondary-50 transition-colors">
                            <div class="flex justify-between items-start mb-2">
                                <h4 class="font-medium text-text-primary"><?php echo htmlspecialchars($medicine['medicine_name']); ?></h4>
                                <span class="font-semibold text-primary-700">₱<?php echo number_format($medicine['total_value'], 2); ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-xs text-text-secondary">Stock: <?php echo number_format($medicine['current_stock']); ?> units</span>
                                <?php
                                $statusClass = match($medicine['status'] ?? '') {
                                    'in_stock' => 'status-good',
                                    'low_stock' => 'status-warning',
                                    'out_of_stock', 'expired' => 'status-critical',
                                    default => 'status-good'
                                };
                                $statusText = match($medicine['status'] ?? '') {
                                    'in_stock' => 'In Stock',
                                    'low_stock' => 'Low Stock',
                                    'out_of_stock' => 'Out of Stock',
                                    'expired' => 'Expired',
                                    default => 'Unknown'
                                };
                                ?>
                                <span class="status-badge <?php echo $statusClass; ?>">
                                    <?php echo $statusText; ?>
                                </span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="p-8 text-center text-text-secondary">
                            <svg class="w-16 h-16 mx-auto mb-4 text-text-tertiary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                            </svg>
                            <p class="text-lg">No medicine data</p>
                        </div>
                    <?php endif; ?>
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

</body>
</html>
<?php $conn->close(); ?>