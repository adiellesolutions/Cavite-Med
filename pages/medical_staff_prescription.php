<?php
session_start();
require_once __DIR__ . "/../backend/get_assigned_health_center.php";
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
    <meta name="description" content="Nurse Prescription Management - View and manage medication dispensing">
    <title>Nurse Prescription Management - CAVMED Portal</title>
    <link rel="stylesheet" href="../css/main.css">

</head>
<body class="bg-background min-h-screen flex flex-col">
    <!-- Header Section -->
    <header class="bg-surface border-b border-border py-4 px-6 shadow-sm sticky top-0 z-sticky">
        <div class="max-w-full mx-auto flex items-center justify-between">
            <div class="flex items-center gap-4">
                <!-- Logo -->
                <a href="system_login_portal.php" class="flex items-center gap-3">
                    <svg class="w-10 h-10" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect width="40" height="40" rx="8" fill="#2563EB"/>
                        <path d="M20 10v20M10 20h20" stroke="white" stroke-width="3" stroke-linecap="round"/>
                        <circle cx="20" cy="20" r="6" stroke="white" stroke-width="2" fill="none"/>
                    </svg>
                    <div>
                        <h1 class="text-xl font-semibold text-text-primary">CAVMED Portal</h1>
<p class="text-xs text-text-secondary">
    <?php echo htmlspecialchars($assigned_health_center_name ?: 'Health Center'); ?>
</p>                    </div>
                </a>
            </div>

            <!-- Search Bar - Persistent -->
            <div class="flex-1 max-w-2xl mx-8">
                
            </div>

            <!-- User Info & Actions -->
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

                <a href="medical_staff_prescription.php" class="nav-item nav-item-active whitespace-nowrap">
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
    <div class="max-w-full mx-auto">
      <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">

        <!-- LEFT -->
        <div class="lg:col-span-2 space-y-6">
          <div class="card">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-lg font-semibold text-text-primary">Active Prescriptions</h3>

              <div class="flex items-center gap-3">
                <select id="filterStatus" class="input input-sm">
                  <option value="all">All Status</option>
                  <option value="active">Active</option>
                  <option value="dispensed">Dispensed</option>
                  <option value="cancelled">Cancelled</option>
                </select>

                <button type="button" id="refreshBtn" class="btn btn-outline">Refresh</button>
              </div>
            </div>

            <div id="prescriptionList" class="space-y-3 max-h-[500px] overflow-y-auto"></div>
            <div id="prescriptionListEmpty" class="hidden text-sm text-text-secondary py-6 text-center">
              No prescriptions found.
            </div>
          </div>
        </div>

        <!-- RIGHT -->
        <div class="lg:col-span-3">
          <div class="card">
            <div class="flex items-center justify-between mb-6">
              <h3 class="text-lg font-semibold text-text-primary">Prescription Details</h3>
              <button type="button" id="printPrescriptionBtn" class="btn btn-ghost btn-sm" disabled>
                Print
              </button>
            </div>

            <div id="prescriptionDetails" class="hidden space-y-6"></div>

            <div id="noPrescriptionSelected" class="text-center py-12">
              <p class="text-text-secondary text-lg">No prescription selected</p>
              <p class="text-sm text-text-tertiary mt-2">Select a prescription from the list</p>
            </div>
          </div>

          <div class="card mt-6">
            <h3 class="text-lg font-semibold text-text-primary mb-4">Recent Dispensing Activity</h3>
            <div id="recentDispensing" class="space-y-3"></div>
            <div id="recentDispensingEmpty" class="hidden text-sm text-text-secondary py-4">
              No dispensing activity yet.
            </div>
          </div>
        </div>

      </div>
    </div>
  </main>

  <!-- Modal: remove static MJ / Maria Johnson inside, JS will fill -->
  <div id="dispensingModal" class="hidden fixed inset-0 bg-secondary-900 bg-opacity-50 z-modal flex items-center justify-center p-4">
    <div class="card max-w-md w-full animate-slide-in">
      <div class="flex items-center justify-between mb-6">
        <h3 class="text-xl font-semibold text-text-primary">Confirm Medication Dispensing</h3>
        <button type="button" id="closeDispensingModal" class="text-text-tertiary hover:text-text-primary">✕</button>
      </div>

      <div class="space-y-4">
        <div id="modalSummary" class="bg-secondary-50 rounded-base p-4"></div>

        <div>
          <label for="adminNotes" class="block text-sm font-medium text-text-primary mb-2">
            Administration Notes (Optional)
          </label>
          <textarea id="adminNotes" rows="2" class="input" placeholder="Add notes..."></textarea>
        </div>

        <div class="flex items-center gap-2">
          <input type="checkbox" id="confirmVerification" class="w-4 h-4 text-primary border-border rounded">
          <label for="confirmVerification" class="text-sm text-text-primary">
            I have verified the 5 Rights of Medication Administration
          </label>
        </div>

        <div class="flex gap-3 pt-4 border-t border-border">
          <button type="button" id="cancelDispensingBtn" class="btn btn-outline flex-1">Cancel</button>
          <button type="button" id="confirmDispensingBtn" class="btn btn-primary flex-1">Confirm Dispensing</button>
        </div>
      </div>
    </div>
  </div>

  <script src="../js/medical_staff_prescription.js"></script>
</body>
</html>