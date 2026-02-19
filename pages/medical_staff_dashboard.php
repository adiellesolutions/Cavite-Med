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


require_once "../backend/db/cavitemed_db.php";


// Get health center of logged-in staff
$stmt = $conn->prepare("SELECT health_center_id FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($health_center_id);
$stmt->fetch();
$stmt->close();

/* ===========================
   TODAY'S STATISTICS
=========================== */

// Patients Registered Today (same health center)
$stmt = $conn->prepare("
    SELECT COUNT(*) 
    FROM patients 
    WHERE DATE(created_at) = CURDATE()
");
$stmt->execute();
$stmt->bind_result($patients_today);
$stmt->fetch();
$stmt->close();

// Vitals Taken Today (same health center staff only)
$stmt = $conn->prepare("
    SELECT COUNT(*) 
    FROM patient_vitals pv
    JOIN users u ON pv.recorded_by = u.user_id
    WHERE DATE(pv.recorded_at) = CURDATE()
    AND u.health_center_id = ?
");
$stmt->bind_param("i", $health_center_id);
$stmt->execute();
$stmt->bind_result($vitals_today);
$stmt->fetch();
$stmt->close();

/* ===========================
   RECENT ACTIVITY
=========================== */

$recent_activity = [];

$query = "
(
    SELECT 
        CONCAT(p.first_name, ' ', p.last_name) AS name,
        'registered' AS type,
        p.created_at AS activity_time
    FROM patients p
    ORDER BY p.created_at DESC
    LIMIT 3
)

UNION ALL

(
    SELECT 
        CONCAT(p.first_name, ' ', p.last_name) AS name,
        'vitals' AS type,
        pv.recorded_at AS activity_time
    FROM patient_vitals pv
    JOIN patients p ON pv.patient_id = p.patient_id
    JOIN users u ON pv.recorded_by = u.user_id
    WHERE u.health_center_id = ?
    ORDER BY pv.recorded_at DESC
    LIMIT 3
)

ORDER BY activity_time DESC
LIMIT 5
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $health_center_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $recent_activity[] = $row;
}

$stmt->close();



?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Nurse Vital Signs & Patient Queue System - CAVMED Portal">
    <title>Nurse Workflow - CAVMED Portal</title>
    <link rel="stylesheet" href="../css/main.css">

</head>
<body class="bg-background min-h-screen flex flex-col">
    <!-- Header Section -->
    <header class="bg-surface border-b border-border py-4 px-6 shadow-sm sticky top-0 z-sticky">
        <div class="max-w-full mx-auto flex items-center justify-between">
            <div class="flex items-center gap-4">
                <!-- Logo -->
                <a href="system_login_portal.html" class="flex items-center gap-3">
                    <svg class="w-10 h-10" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect width="40" height="40" rx="8" fill="#2563EB"/>
                        <path d="M20 10v20M10 20h20" stroke="white" stroke-width="3" stroke-linecap="round"/>
                        <circle cx="20" cy="20" r="6" stroke="white" stroke-width="2" fill="none"/>
                    </svg>
                    <div>
                        <h1 class="text-xl font-semibold text-text-primary">CAVMED Portal</h1>
                    </div>
                </a>
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
  src="/CAVITE-MED/<?php echo $_SESSION['profile_picture'] ?: 'uploads/profile/default.png'; ?>"
  alt="User profile picture"
  class="w-10 h-10 rounded-full object-cover border-2 border-primary"
  onerror="this.src='/CAVITE-MED/uploads/profile/default.png'; this.onerror=null;">

                    </div>
                </div>
            </div>
        </div>

    </header>


    <nav class="bg-surface border-b border-border px-6 no-print">        
        <div class="px-6">
            <div class="flex items-center gap-1 overflow-x-auto scrollbar-thin">
                
                <a href="medical_staff_dashboard.php" class="nav-item nav-item-active whitespace-nowrap">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    Dashboard
                </a>

                <a href="medical_staff_prescription.php" class="nav-item">
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
                <a href="medical_staff_inventory.php" class="nav-item">
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

    
    

    <!-- Main Content -->
    <main class="flex-1 px-6 py-6">


                <!-- Quick Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-text-secondary">Pending Dispensing</p>
                        <p id="statPendingDispensing" class="text-2xl font-semibold text-text-primary mt-1">0</p>
                    </div>
                    <div class="w-12 h-12 rounded-lg bg-warning-100 flex items-center justify-center">
                        <svg class="w-6 h-6 text-warning-600" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-text-secondary">Completed Today</p>
                        <p id="statCompletedToday" class="text-2xl font-semibold text-text-primary mt-1">0</p>
                    </div>
                    <div class="w-12 h-12 rounded-lg bg-success-100 flex items-center justify-center">
                        <svg class="w-6 h-6 text-success-600" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-text-secondary">Requiring Attention</p>
                        <p id="statRequiringAttention" class="text-2xl font-semibold text-text-primary mt-1">0</p>
                    </div>
                    <div class="w-12 h-12 rounded-lg bg-error-100 flex items-center justify-center">
                        <svg class="w-6 h-6 text-error-600" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.998-.833-2.732 0L4.346 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                        </svg>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-text-secondary">Total Patients</p>
                        <p id="statTotalPatients" class="text-2xl font-semibold text-text-primary mt-1">0</p>
                    </div>
                    <div class="w-12 h-12 rounded-lg bg-primary-100 flex items-center justify-center">
                        <svg class="w-6 h-6 text-primary-600" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <div class="max-w-full mx-auto">
            <!-- Dashboard Header -->
            <div class="flex items-center justify-between mb-6">
                <div>
                </div>
                <div class="flex items-center gap-3">
                    <div class="workflow-progress w-48">
                        <div id="workflowProgress" class="workflow-progress-bar" style="width: 33%"></div>
                    </div>
                </div>
            </div>

        
            <!-- Statistics & Recent Activity -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <div class="card lg:col-span-2">
                    <h3 class="text-lg font-semibold text-text-primary mb-4">Recent Activity</h3>
                    <div class="space-y-3">

                    <div id="recentActivityList" class="space-y-3"></div>
<p id="recentActivityEmpty" class="text-sm text-text-secondary hidden">No recent activity.</p>
<p id="dashboardError" class="text-sm text-error-600 hidden"></p>


                        <?php if (empty($recent_activity)): ?>
                            <p class="text-sm text-text-secondary">No recent activity.</p>
                        <?php endif; ?>

                    </div>
                </div>

                <div class="card">
                    <h3 class="text-lg font-semibold text-text-primary mb-4">Today's Statistics</h3>
                    <div class="grid grid-cols-2 gap-3">

                        <div class="p-4 rounded-base border-2 border-primary-100">
                            <p class="text-3xl font-bold text-primary-600 mb-1">
                            <p id="statPatientsToday" class="text-3xl font-bold text-primary-600 mb-1">0</p>
                            </p>
                            <p class="text-sm text-text-secondary">Patients Registered</p>
                        </div>

                        <div class="p-4 rounded-base border-2 border-success-100">
                            <p class="text-3xl font-bold text-success-600 mb-1">
                            <p id="statVitalsToday" class="text-3xl font-bold text-success-600 mb-1">0</p>
                            </p>
                            <p class="text-sm text-text-secondary">Vitals Taken</p>
                        </div>

                    </div>
                </div>
       
                
            </div>

        </div>
    </main>

    <!-- New Patient Modal -->
    <div id="newPatientModal" class="hidden fixed inset-0 modal-backdrop z-modal flex items-center justify-center p-4">
        <div class="card max-w-md w-full fade-in max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-semibold text-text-primary">New Patient Registration</h3>
                <button type="button" id="closeNewPatientModal" class="text-text-tertiary hover:text-text-primary transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-2">
                            First Name *
                        </label>
                        <input type="text" id="newFirstName" class="vital-sign-input" placeholder="First name">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-2">
                            Last Name *
                        </label>
                        <input type="text" id="newLastName" class="vital-sign-input" placeholder="Last name">
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-2">
                            Age *
                        </label>
                        <input type="number" id="newAge" class="vital-sign-input" placeholder="Age">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-2">
                            Gender *
                        </label>
                        <select id="newGender" class="vital-sign-input">
                            <option value="">Select gender</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-text-primary mb-2">
                        Reason for Visit *
                    </label>
                    <textarea id="newVisitReason" class="vital-sign-input" rows="2" placeholder="Brief description"></textarea>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-text-primary mb-2">
                        Initial Priority Assessment *
                    </label>
                    <select id="newPriority" class="vital-sign-input">
                        <option value="low">Low - Non-urgent</option>
                        <option value="medium" selected>Medium - Semi-urgent</option>
                        <option value="high">High - Urgent</option>
                    </select>
                </div>
                
                <div class="flex gap-3 pt-4 border-t border-border">
                    <button type="button" id="cancelNewPatientBtn" class="btn btn-outline flex-1">
                        Cancel
                    </button>
                    <button type="button" id="addToQueueBtn" class="btn btn-primary flex-1">
                        Add to Queue
                    </button>
                </div>
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

    <script src="../js/medical_staff_dashboard.js"></script>

</body>
</html>