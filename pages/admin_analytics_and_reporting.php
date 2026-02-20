<?php
// admin_analytics.php
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

// Date range filter
$date_range = isset($_GET['date_range']) ? $_GET['date_range'] : '30';
$end_date = date('Y-m-d');
$start_date = date('Y-m-d', strtotime("-{$date_range} days"));

// Get inventory summary
$inventory_summary_query = "
    SELECT 
        COUNT(DISTINCT m.id) as total_medicines,
        SUM(m.current_stock) as total_stock,
        SUM(CASE WHEN m.status = 'in_stock' THEN 1 ELSE 0 END) as in_stock_count,
        SUM(CASE WHEN m.status = 'low_stock' THEN 1 ELSE 0 END) as low_stock_count,
        SUM(CASE WHEN m.status = 'out_of_stock' THEN 1 ELSE 0 END) as out_of_stock_count,
        SUM(CASE WHEN m.status = 'expired' THEN 1 ELSE 0 END) as expired_count,
        SUM(CASE WHEN m.expiry_date <= DATE_ADD(NOW(), INTERVAL 30 DAY) AND m.expiry_date > NOW() THEN 1 ELSE 0 END) as expiring_soon,
        SUM(m.current_stock * m.unit_price) as total_value
    FROM medicine m
    WHERE m.is_archived = 0
";
$inventory_summary = $conn->query($inventory_summary_query)->fetch_assoc();

// Get top medicines by stock value
$top_medicines_query = "
    SELECT 
        m.medicine_name,
        m.category,
        m.current_stock,
        m.unit_price,
        (m.current_stock * m.unit_price) as total_value,
        m.status,
        s.supplier_name
    FROM medicine m
    LEFT JOIN suppliers s ON m.supplier_id = s.id
    WHERE m.is_archived = 0
    ORDER BY total_value DESC
    LIMIT 10
";
$top_medicines = $conn->query($top_medicines_query)->fetch_all(MYSQLI_ASSOC);

// Get distribution by health center
$distribution_by_center_query = "
    SELECT 
        hc.center_name,
        hc.center_type,
        COUNT(DISTINCT d.id) as total_distributions,
        SUM(d.quantity) as total_quantity,
        COUNT(DISTINCT d.medicine_id) as unique_medicines
    FROM distribution d
    JOIN health_centers hc ON d.health_center_id = hc.id
    WHERE d.created_at >= ? AND d.created_at <= ?
    AND d.status = 'distributed'
    GROUP BY hc.id, hc.center_name, hc.center_type
    ORDER BY total_quantity DESC
    LIMIT 10
";
$stmt = $conn->prepare($distribution_by_center_query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$distribution_by_center = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get medicine categories distribution
$categories_query = "
    SELECT 
        category,
        COUNT(*) as medicine_count,
        SUM(current_stock) as total_stock,
        SUM(current_stock * unit_price) as total_value
    FROM medicine
    WHERE is_archived = 0
    GROUP BY category
    ORDER BY total_value DESC
";
$categories = $conn->query($categories_query)->fetch_all(MYSQLI_ASSOC);

// Get funding sources breakdown
$funding_sources_query = "
    SELECT 
        funding_source,
        COUNT(*) as medicine_count,
        SUM(current_stock) as total_stock,
        SUM(current_stock * unit_price) as total_value
    FROM medicine
    WHERE is_archived = 0
    GROUP BY funding_source
";
$funding_sources = $conn->query($funding_sources_query)->fetch_all(MYSQLI_ASSOC);

// Get expiring medicines
$expiring_query = "
    SELECT 
        m.medicine_name,
        m.batch_number,
        m.expiry_date,
        m.current_stock,
        DATEDIFF(m.expiry_date, NOW()) as days_until_expiry,
        s.supplier_name
    FROM medicine m
    LEFT JOIN suppliers s ON m.supplier_id = s.id
    WHERE m.is_archived = 0 
    AND m.expiry_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 60 DAY)
    ORDER BY m.expiry_date ASC
    LIMIT 10
";
$expiring_medicines = $conn->query($expiring_query)->fetch_all(MYSQLI_ASSOC);

// Get user activity summary
$user_activity = [];

// Get inventory transactions by user
$inventory_users = $conn->query("
    SELECT 
        performed_by as user_id,
        COUNT(*) as inv_count
    FROM inventory_transactions 
    WHERE created_at BETWEEN '$start_date' AND '$end_date'
    GROUP BY performed_by
");

while($row = $inventory_users->fetch_assoc()) {
    $user_activity[$row['user_id']]['inventory'] = $row['inv_count'];
}

// Get distributions by user
$dist_users = $conn->query("
    SELECT 
        created_by as user_id,
        COUNT(*) as dist_count
    FROM distribution 
    WHERE created_at BETWEEN '$start_date' AND '$end_date'
    GROUP BY created_by
");

while($row = $dist_users->fetch_assoc()) {
    $user_activity[$row['user_id']]['distribution'] = $row['dist_count'];
}

// Get disposals by user
$disp_users = $conn->query("
    SELECT 
        created_by as user_id,
        COUNT(*) as disp_count
    FROM disposal_records 
    WHERE created_at BETWEEN '$start_date' AND '$end_date'
    GROUP BY created_by
");

while($row = $disp_users->fetch_assoc()) {
    $user_activity[$row['user_id']]['disposal'] = $row['disp_count'];
}

// Get returns by user
$ret_users = $conn->query("
    SELECT 
        returned_by as user_id,
        COUNT(*) as ret_count
    FROM stock_returns 
    WHERE created_at BETWEEN '$start_date' AND '$end_date'
    GROUP BY returned_by
");

while($row = $ret_users->fetch_assoc()) {
    $user_activity[$row['user_id']]['return'] = $row['ret_count'];
}

// Get user details for active users
$active_users = [];
if (!empty($user_activity)) {
    $user_ids = implode(',', array_keys($user_activity));
    $user_details = $conn->query("
        SELECT user_id, full_name, role 
        FROM users 
        WHERE user_id IN ($user_ids) AND status = 'active'
    ");
    
    while($user = $user_details->fetch_assoc()) {
        $active_users[] = [
            'full_name' => $user['full_name'],
            'role' => $user['role'],
            'inventory_transactions' => $user_activity[$user['user_id']]['inventory'] ?? 0,
            'distributions' => $user_activity[$user['user_id']]['distribution'] ?? 0,
            'disposals' => $user_activity[$user['user_id']]['disposal'] ?? 0,
            'returns' => $user_activity[$user['user_id']]['return'] ?? 0
        ];
    }
    
    // Sort by total activity
    usort($active_users, function($a, $b) {
        $total_a = $a['inventory_transactions'] + $a['distributions'] + $a['disposals'] + $a['returns'];
        $total_b = $b['inventory_transactions'] + $b['distributions'] + $b['disposals'] + $b['returns'];
        return $total_b - $total_a;
    });
    
    $active_users = array_slice($active_users, 0, 10);
}

// Get supplier performance
$supplier_query = "
    SELECT 
        s.supplier_name,
        s.supplier_type,
        COUNT(DISTINCT m.id) as medicines_supplied,
        COALESCE(SUM(m.current_stock), 0) as total_stock,
        COALESCE(SUM(m.current_stock * m.unit_price), 0) as total_value
    FROM suppliers s
    LEFT JOIN medicine m ON s.id = m.supplier_id AND m.is_archived = 0
    WHERE s.is_archived = 0
    GROUP BY s.id, s.supplier_name, s.supplier_type
    ORDER BY total_value DESC
    LIMIT 10
";
$suppliers = $conn->query($supplier_query)->fetch_all(MYSQLI_ASSOC);

// Get inventory alerts
$alerts = [
    'low_stock' => $conn->query("SELECT COUNT(*) as count FROM medicine WHERE status = 'low_stock' AND is_archived = 0")->fetch_assoc()['count'],
    'out_of_stock' => $conn->query("SELECT COUNT(*) as count FROM medicine WHERE status = 'out_of_stock' AND is_archived = 0")->fetch_assoc()['count'],
    'expired' => $conn->query("SELECT COUNT(*) as count FROM medicine WHERE status = 'expired' AND is_archived = 0")->fetch_assoc()['count'],
    'expiring_30' => $conn->query("SELECT COUNT(*) as count FROM medicine WHERE expiry_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 30 DAY) AND is_archived = 0")->fetch_assoc()['count'],
];

// Get stock movement summary
$stock_movement_query = "
    SELECT 
        COALESCE(SUM(CASE WHEN transaction_type = 'add' THEN quantity ELSE 0 END), 0) as total_added,
        COALESCE(SUM(CASE WHEN transaction_type = 'deduct' THEN quantity ELSE 0 END), 0) as total_deducted,
        COALESCE(SUM(CASE WHEN transaction_type = 'distribute' THEN quantity ELSE 0 END), 0) as total_distributed,
        COALESCE(SUM(CASE WHEN transaction_type = 'dispose' THEN quantity ELSE 0 END), 0) as total_disposed
    FROM inventory_transactions
    WHERE created_at >= ? AND created_at <= ?
";
$stmt = $conn->prepare($stock_movement_query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$stock_movement = $stmt->get_result()->fetch_assoc();

// Get medicine type distribution
$medicine_type_query = "
    SELECT 
        medicine_type,
        COUNT(*) as count,
        SUM(current_stock) as total_stock
    FROM medicine
    WHERE is_archived = 0
    GROUP BY medicine_type
";
$medicine_types = $conn->query($medicine_type_query)->fetch_all(MYSQLI_ASSOC);

// Get recent transactions
$recent_transactions_query = "
    SELECT 
        it.created_at,
        it.transaction_type,
        it.quantity,
        it.remarks,
        m.medicine_name,
        u.full_name as performed_by_name
    FROM inventory_transactions it
    JOIN medicine m ON it.medicine_id = m.id
    JOIN users u ON it.performed_by = u.user_id
    ORDER BY it.created_at DESC
    LIMIT 10
";
$recent_transactions = $conn->query($recent_transactions_query)->fetch_all(MYSQLI_ASSOC);

// Get health centers summary
$health_centers_query = "
    SELECT 
        center_type,
        COUNT(*) as count
    FROM health_centers
    WHERE is_archived = 0
    GROUP BY center_type
";
$health_centers = $conn->query($health_centers_query)->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - CAVMED Portal</title>
    <link rel="stylesheet" href="../css/main.css">
    <style>
        .stat-value {
            font-size: 1.875rem;
            line-height: 2.25rem;
            font-weight: 700;
            color: var(--color-text-primary);
        }
        .stat-label {
            font-size: 0.875rem;
            line-height: 1.25rem;
            color: var(--color-text-secondary);
        }
        .trend-up { color: var(--color-success); }
        .trend-down { color: var(--color-error); }
        .metric-card {
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .metric-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        .mini-stat {
            padding: 1rem;
            background: var(--color-secondary-50);
            border-radius: var(--radius-base);
            text-align: center;
        }
        .mini-stat-value {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--color-primary-700);
        }
        .mini-stat-label {
            font-size: 0.75rem;
            color: var(--color-text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.05em;
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
            background: var(--color-primary);
            border-radius: 4px;
            transition: width 0.3s ease;
        }
        .insight-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        .insight-badge-success { background: var(--color-success-100); color: var(--color-success-700); }
        .insight-badge-warning { background: var(--color-warning-100); color: var(--color-warning-700); }
        .insight-badge-error { background: var(--color-error-100); color: var(--color-error-700); }
        .summary-card {
            background: var(--color-surface);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            box-shadow: var(--shadow-base);
        }
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
    </style>
</head>
<body class="bg-background min-h-screen flex flex-col">

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

        <!-- Alert Cards -->
        <?php if (array_sum($alerts) > 0): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <?php if ($alerts['low_stock'] > 0): ?>
            <div class="bg-warning-50 border border-warning-200 rounded-lg p-4 flex items-center gap-3">
                <div class="w-10 h-10 bg-warning-100 rounded-full flex items-center justify-center">
                    <svg class="w-5 h-5 text-warning-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-warning-700 font-medium">Low Stock Alert</p>
                    <p class="text-xl font-bold text-warning-800"><?php echo $alerts['low_stock']; ?> items</p>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($alerts['expiring_30'] > 0): ?>
            <div class="bg-warning-50 border border-warning-200 rounded-lg p-4 flex items-center gap-3">
                <div class="w-10 h-10 bg-warning-100 rounded-full flex items-center justify-center">
                    <svg class="w-5 h-5 text-warning-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-warning-700 font-medium">Expiring in 30 days</p>
                    <p class="text-xl font-bold text-warning-800"><?php echo $alerts['expiring_30']; ?> items</p>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($alerts['out_of_stock'] > 0): ?>
            <div class="bg-error-50 border border-error-200 rounded-lg p-4 flex items-center gap-3">
                <div class="w-10 h-10 bg-error-100 rounded-full flex items-center justify-center">
                    <svg class="w-5 h-5 text-error-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-error-700 font-medium">Out of Stock</p>
                    <p class="text-xl font-bold text-error-800"><?php echo $alerts['out_of_stock']; ?> items</p>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($alerts['expired'] > 0): ?>
            <div class="bg-error-50 border border-error-200 rounded-lg p-4 flex items-center gap-3">
                <div class="w-10 h-10 bg-error-100 rounded-full flex items-center justify-center">
                    <svg class="w-5 h-5 text-error-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-error-700 font-medium">Expired Items</p>
                    <p class="text-xl font-bold text-error-800"><?php echo $alerts['expired']; ?> items</p>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <!-- Summary Cards Row -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <div class="summary-card">
                <h4 class="font-semibold text-text-primary mb-3">Medicine Types</h4>
                <?php foreach ($medicine_types as $type): ?>
                <div class="flex justify-between py-2 border-b border-border last:border-0">
                    <span class="text-text-secondary capitalize"><?php echo $type['medicine_type'] ?? 'N/A'; ?></span>
                    <span class="font-medium"><?php echo $type['count'] ?? 0; ?> items</span>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="summary-card">
                <h4 class="font-semibold text-text-primary mb-3">Health Centers</h4>
                <?php foreach ($health_centers as $center): ?>
                <div class="flex justify-between py-2 border-b border-border last:border-0">
                    <span class="text-text-secondary capitalize"><?php echo str_replace('_', ' ', $center['center_type'] ?? 'N/A'); ?></span>
                    <span class="font-medium"><?php echo $center['count'] ?? 0; ?></span>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="summary-card">
                <h4 class="font-semibold text-text-primary mb-3">Funding Sources</h4>
                <?php foreach ($funding_sources as $source): ?>
                <div class="flex justify-between py-2 border-b border-border last:border-0">
                    <span class="text-text-secondary capitalize"><?php echo str_replace('_', ' ', $source['funding_source'] ?? 'N/A'); ?></span>
                    <span class="font-medium">₱<?php echo number_format($source['total_value'] ?? 0, 0); ?></span>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="summary-card">
                <h4 class="font-semibold text-text-primary mb-3">Categories</h4>
                <?php foreach (array_slice($categories, 0, 5) as $cat): ?>
                <div class="flex justify-between py-2 border-b border-border last:border-0">
                    <span class="text-text-secondary"><?php echo htmlspecialchars($cat['category'] ?? 'N/A'); ?></span>
                    <span class="font-medium">₱<?php echo number_format($cat['total_value'] ?? 0, 0); ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Key Metrics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="card metric-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-text-secondary">Total Medicines</p>
                        <p class="stat-value"><?php echo number_format($inventory_summary['total_medicines'] ?? 0); ?></p>
                    </div>
                    <div class="w-12 h-12 bg-primary-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-primary-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                    </div>
                </div>
                <div class="mt-4 flex gap-4">
                    <div class="mini-stat flex-1">
                        <div class="mini-stat-value"><?php echo $inventory_summary['in_stock_count'] ?? 0; ?></div>
                        <div class="mini-stat-label">In Stock</div>
                    </div>
                    <div class="mini-stat flex-1">
                        <div class="mini-stat-value"><?php echo $inventory_summary['low_stock_count'] ?? 0; ?></div>
                        <div class="mini-stat-label">Low Stock</div>
                    </div>
                </div>
            </div>

            <div class="card metric-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-text-secondary">Total Stock Value</p>
                        <p class="stat-value">₱<?php echo number_format($inventory_summary['total_value'] ?? 0, 0); ?></p>
                    </div>
                    <div class="w-12 h-12 bg-success-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-success-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-text-secondary">Total Units</span>
                        <span class="font-medium"><?php echo number_format($inventory_summary['total_stock'] ?? 0); ?></span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo min(100, (($inventory_summary['total_stock'] ?? 0) / 10000) * 100); ?>%"></div>
                    </div>
                </div>
            </div>
        </div>



        <!-- Top Medicines Table -->
        <div class="card mb-8">
            <div class="px-6 py-4 border-b border-border flex items-center justify-between">
                <h3 class="font-semibold text-text-primary">Top 10 Medicines by Value</h3>
                <span class="text-sm text-text-secondary">Current stock value</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-secondary-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-text-secondary uppercase">Medicine</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-text-secondary uppercase">Category</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-text-secondary uppercase">Supplier</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-text-secondary uppercase">Stock</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-text-secondary uppercase">Unit Price</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-text-secondary uppercase">Total Value</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-text-secondary uppercase">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <?php foreach ($top_medicines as $medicine): ?>
                        <tr class="hover:bg-secondary-50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-text-primary"><?php echo htmlspecialchars($medicine['medicine_name'] ?? 'N/A'); ?></div>
                            </td>
                            <td class="px-6 py-4 text-sm text-text-secondary"><?php echo htmlspecialchars($medicine['category'] ?? 'N/A'); ?></td>
                            <td class="px-6 py-4 text-sm text-text-secondary"><?php echo htmlspecialchars($medicine['supplier_name'] ?? 'N/A'); ?></td>
                            <td class="px-6 py-4 text-right text-sm font-medium"><?php echo number_format($medicine['current_stock'] ?? 0); ?></td>
                            <td class="px-6 py-4 text-right text-sm">₱<?php echo number_format($medicine['unit_price'] ?? 0, 2); ?></td>
                            <td class="px-6 py-4 text-right text-sm font-semibold text-primary-700">₱<?php echo number_format($medicine['total_value'] ?? 0, 2); ?></td>
                            <td class="px-6 py-4 text-center">
                                <?php
                                $statusClass = match($medicine['status'] ?? '') {
                                    'in_stock' => 'badge-success',
                                    'low_stock' => 'badge-warning',
                                    'out_of_stock', 'expired' => 'badge-error',
                                    default => 'badge-secondary'
                                };
                                ?>
                                <span class="badge <?php echo $statusClass; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $medicine['status'] ?? 'N/A')); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>


        <!-- Bottom Grid - Suppliers and Expiring -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Top Suppliers -->
            <div class="card">
                <h3 class="text-lg font-semibold text-text-primary mb-4">Top Suppliers by Value</h3>
                <div class="space-y-4">
                    <?php foreach ($suppliers as $supplier): ?>
                    <div class="flex items-center justify-between p-3 bg-secondary-50 rounded-lg">
                        <div>
                            <p class="font-medium"><?php echo htmlspecialchars($supplier['supplier_name'] ?? 'N/A'); ?></p>
                            <p class="text-xs text-text-secondary capitalize"><?php echo $supplier['supplier_type'] ?? 'N/A'; ?></p>
                        </div>
                        <div class="text-right">
                            <p class="font-semibold text-primary-700">₱<?php echo number_format($supplier['total_value'] ?? 0, 0); ?></p>
                            <p class="text-xs text-text-secondary"><?php echo $supplier['medicines_supplied'] ?? 0; ?> items</p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Expiring Soon -->
            <div class="card">
                <h3 class="text-lg font-semibold text-text-primary mb-4">Expiring Soon (Next 60 Days)</h3>
                <div class="space-y-3">
                    <?php foreach ($expiring_medicines as $exp): ?>
                    <div class="flex items-center justify-between p-3 <?php echo ($exp['days_until_expiry'] ?? 0) <= 30 ? 'bg-error-50' : 'bg-warning-50'; ?> rounded-lg">
                        <div>
                            <p class="font-medium"><?php echo htmlspecialchars($exp['medicine_name'] ?? 'N/A'); ?></p>
                            <p class="text-xs text-text-secondary">Batch: <?php echo htmlspecialchars($exp['batch_number'] ?? 'N/A'); ?></p>
                        </div>
                        <div class="text-right">
                            <p class="font-semibold <?php echo ($exp['days_until_expiry'] ?? 0) <= 30 ? 'text-error-700' : 'text-warning-700'; ?>">
                                <?php echo $exp['days_until_expiry'] ?? 0; ?> days
                            </p>
                            <p class="text-xs text-text-secondary"><?php echo number_format($exp['current_stock'] ?? 0); ?> units</p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Recent Transactions -->
        <div class="card">
            <div class="px-6 py-4 border-b border-border">
                <h3 class="font-semibold text-text-primary">Recent Transactions</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-secondary-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-text-secondary uppercase">Date/Time</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-text-secondary uppercase">Medicine</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-text-secondary uppercase">Type</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-text-secondary uppercase">Quantity</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-text-secondary uppercase">Performed By</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-text-secondary uppercase">Remarks</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <?php foreach ($recent_transactions as $trans): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm"><?php echo date('M d, Y', strtotime($trans['created_at'])); ?></div>
                                <div class="text-xs text-text-secondary"><?php echo date('h:i A', strtotime($trans['created_at'])); ?></div>
                            </td>
                            <td class="px-6 py-4 text-sm"><?php echo htmlspecialchars($trans['medicine_name'] ?? 'N/A'); ?></td>
                            <td class="px-6 py-4">
                                <?php
                                $typeClass = match($trans['transaction_type']) {
                                    'add' => 'badge-success',
                                    'deduct', 'dispose' => 'badge-error',
                                    'distribute' => 'badge-warning',
                                    default => 'badge-secondary'
                                };
                                ?>
                                <span class="badge <?php echo $typeClass; ?>">
                                    <?php echo ucfirst($trans['transaction_type'] ?? 'N/A'); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right font-medium"><?php echo number_format($trans['quantity'] ?? 0); ?></td>
                            <td class="px-6 py-4 text-sm"><?php echo htmlspecialchars($trans['performed_by_name'] ?? 'N/A'); ?></td>
                            <td class="px-6 py-4 text-sm text-text-secondary"><?php echo htmlspecialchars($trans['remarks'] ?? '-'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
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