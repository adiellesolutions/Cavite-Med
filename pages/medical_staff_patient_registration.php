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
                        <!--   <button type="button" class="text-xs text-primary hover:text-primary-700">
                            Manage
                        </button>-->
                    </div>
                    <div class="space-y-2" id="favoritesList">
                <div class="text-xs text-text-secondary">Loading favorites...</div>
                </div>
                </div>

                <div class="divider"></div>

                <!-- Recent Patients -->
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-semibold text-text-primary">Recent Patients</h3>
                        <button type="button" id="viewAllRecentBtn" class="text-xs text-primary hover:text-primary-700">
                        View All
                        </button>

                    </div>
                    <div class="space-y-2" id="recentPatientsList">
                    <div class="text-xs text-text-secondary">Loading recent patients...</div>
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
          <div id="ph_initials"
               class="w-20 h-20 rounded-full bg-primary-100 text-primary-700 font-bold flex items-center justify-center text-2xl">
            --
          </div>

          <div>
            <div class="flex items-center gap-3 mb-2">
              <h2 id="ph_name" class="text-2xl font-semibold text-text-primary">Select a patient</h2>

              <span id="ph_status_badge" class="badge badge-secondary">No patient</span>

              <button type="button" id="ph_favorite_btn" class="text-warning hover:text-warning-600 hidden" title="Favorite">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                </svg>
              </button>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
              <div>
                <p class="text-text-secondary">MRN</p>
                <p id="ph_mrn" class="font-medium text-text-primary">--</p>
              </div>
              <div>
                <p class="text-text-secondary">Date of Birth</p>
                <p id="ph_dob_age" class="font-medium text-text-primary">--</p>
              </div>
              <div>
                <p class="text-text-secondary">Gender</p>
                <p id="ph_gender" class="font-medium text-text-primary">--</p>
              </div>
              <div>
                <p class="text-text-secondary">Blood Type</p>
                <p id="ph_blood" class="font-medium text-text-primary">--</p>
              </div>
            </div>
          </div>
        </div>

        <div class="flex gap-2">
        <button type="button" class="btn btn-outline" id="btnEditPatient">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
            Edit
          </button>

       <!--   <button type="button" class="btn btn-primary" id="btnNewVisit">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            New Visit
          </button> -->
        </div>
      </div>

      <!-- Quick Contact Info -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-4 border-t border-border">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-full bg-primary-50 flex items-center justify-center">
            <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
            </svg>
          </div>
          <div>
            <p class="text-xs text-text-secondary">Phone</p>
            <p id="ph_phone" class="text-sm font-medium text-text-primary">--</p>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-full bg-success-50 flex items-center justify-center">
            <svg class="w-5 h-5 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
          </div>
          <div>
            <p class="text-xs text-text-secondary">Email</p>
            <p id="ph_email" class="text-sm font-medium text-text-primary">--</p>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-full bg-warning-50 flex items-center justify-center">
            <svg class="w-5 h-5 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
          </div>
          <div>
            <p class="text-xs text-text-secondary">Address</p>
            <p id="ph_address" class="text-sm font-medium text-text-primary">--</p>
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
        Demographics
      </button>
      <button type="button" class="nav-item pb-3" data-tab="vitals">Vitals</button>
      <button type="button" class="nav-item pb-3" data-tab="insurance">Insurance</button>
      <button type="button" class="nav-item pb-3" data-tab="emergency">Emergency Contacts</button>
      <button type="button" class="nav-item pb-3" data-tab="documents">Documents</button>
    </nav>
  </div>

  <div id="tabContent">
    <!-- Demographics Tab -->
    <div id="demographics-tab" class="tab-content">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <h3 class="text-lg font-semibold text-text-primary mb-4">Personal Information</h3>
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-text-secondary mb-1">Full Name</label>
              <p id="demo_full_name" class="text-base text-text-primary">--</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-text-secondary mb-1">Preferred Name</label>
              <p id="demo_preferred_name" class="text-base text-text-primary">--</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-text-secondary mb-1">Marital Status</label>
              <p id="demo_marital_status" class="text-base text-text-primary">--</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-text-secondary mb-1">Occupation</label>
              <p id="demo_occupation" class="text-base text-text-primary">--</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-text-secondary mb-1">Preferred Language</label>
              <p id="demo_language" class="text-base text-text-primary">--</p>
            </div>
          </div>
        </div>

        <div>
          <h3 class="text-lg font-semibold text-text-primary mb-4">Medical Information</h3>
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-text-secondary mb-1">Allergies</label>
              <div id="med_allergies" class="flex flex-wrap gap-2">
                <span class="text-sm text-text-secondary">--</span>
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-text-secondary mb-1">Chronic Conditions</label>
              <div id="med_conditions" class="flex flex-wrap gap-2">
                <span class="text-sm text-text-secondary">--</span>
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-text-secondary mb-1">Current Medications</label>
              <ul id="med_meds" class="text-sm text-text-primary space-y-1">
                <li class="text-text-secondary">--</li>
              </ul>
            </div>

            <div>
              <label class="block text-sm font-medium text-text-secondary mb-1">Immunization Status</label>
              <p id="med_immunization" class="text-sm text-text-primary">--</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Vitals Tab -->
    <div id="vitals-tab" class="tab-content hidden">
      <div class="card">
        <div class="flex items-center justify-between mb-6 pb-4 border-b border-border">
          <h3 class="text-lg font-semibold text-text-primary">Latest Vital Signs</h3>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div class="vital-sign-card p-4 rounded-base border-2 border-border text-center">
            <p class="text-sm text-text-secondary mb-1">Blood Pressure</p>
            <p id="v_bp" class="text-2xl font-bold text-text-primary">--</p>
          </div>

          <div class="vital-sign-card p-4 rounded-base border-2 border-border text-center">
            <p class="text-sm text-text-secondary mb-1">Heart Rate</p>
            <p id="v_hr" class="text-2xl font-bold text-text-primary">--</p>
            <p class="text-sm text-text-secondary">bpm</p>
          </div>

          <div class="vital-sign-card p-4 rounded-base border-2 border-border text-center">
            <p class="text-sm text-text-secondary mb-1">Temperature</p>
            <p id="v_temp" class="text-2xl font-bold text-text-primary">--</p>
            <p class="text-sm text-text-secondary">°C</p>
          </div>

          <div class="vital-sign-card p-4 rounded-base border-2 border-border text-center">
            <p class="text-sm text-text-secondary mb-1">SpO₂</p>
            <p id="v_spo2" class="text-2xl font-bold text-text-primary">--</p>
            <p class="text-sm text-text-secondary">%</p>
          </div>

          <div class="vital-sign-card p-4 rounded-base border-2 border-border text-center">
            <p class="text-sm text-text-secondary mb-1">Respiratory Rate</p>
            <p id="v_rr" class="text-2xl font-bold text-text-primary">--</p>
            <p class="text-sm text-text-secondary">breaths/min</p>
          </div>

          <div class="vital-sign-card p-4 rounded-base border-2 border-border text-center">
            <p class="text-sm text-text-secondary mb-1">Blood Glucose</p>
            <p id="v_bg" class="text-2xl font-bold text-text-primary">--</p>
            <p class="text-sm text-text-secondary">mg/dL</p>
          </div>

          <div class="vital-sign-card p-4 rounded-base border-2 border-border text-center">
            <p class="text-sm text-text-secondary mb-1">Weight</p>
            <p id="v_weight" class="text-2xl font-bold text-text-primary">--</p>
            <p class="text-sm text-text-secondary">kg</p>
          </div>
        </div>

        <div class="mt-6">
          <label class="block text-sm font-medium text-text-secondary mb-2">Nurse's Notes</label>
          <div class="bg-secondary-50 p-4 rounded-base">
            <p id="v_notes" class="text-sm text-text-primary">--</p>
          </div>
          <p id="v_recorded_at" class="text-xs text-text-tertiary mt-2"></p>
        </div>
      </div>
    </div>

    <!-- Insurance Tab -->
    <div id="insurance-tab" class="tab-content hidden">
      <div id="insurance_content" class="text-sm text-text-secondary">Select a patient to view insurance.</div>
    </div>

    <!-- Emergency Tab -->
    <div id="emergency-tab" class="tab-content hidden">
      <div id="emergency_content" class="text-sm text-text-secondary">Select a patient to view emergency contacts.</div>
    </div>

    <!-- Documents Tab -->
    <div id="documents-tab" class="tab-content hidden">
      <div id="documents_content" class="text-sm text-text-secondary">Select a patient to view documents.</div>
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
                <div id="visitTimeline" class="relative space-y-6">
                <div class="text-sm text-text-secondary">Select a patient to view visit history.</div>
                </div>

            </div>
        </aside>
    </div>

    <div id="recentPatientsModal" class="hidden fixed inset-0 bg-secondary-900 bg-opacity-50 z-modal flex items-center justify-center p-4">
    <div class="card w-full max-w-3xl max-h-[90vh] overflow-y-auto scrollbar-thin animate-slide-in">
        <div class="flex items-center justify-between mb-4">
        <h3 class="text-xl font-semibold text-text-primary">All Recent Patients</h3>
        <button type="button" id="closeRecentPatientsModal" class="text-text-tertiary hover:text-text-primary transition-colors">
            ✕
        </button>
        </div>

        <div class="mb-3 flex items-center gap-2">
        <input id="recentPatientsSearch" type="text" class="input flex-1" placeholder="Search patient name or MRN...">
        <span id="recentPatientsCount" class="text-xs text-text-secondary"></span>
        </div>

        <div id="recentPatientsModalList" class="space-y-2">
        <div class="text-sm text-text-secondary">Loading...</div>
        </div>
    </div>
    </div>

<!-- Edit Patient Modal (same style as New Patient Modal) -->
<div id="editPatientModal"
     class="hidden fixed inset-0 bg-secondary-900 bg-opacity-50 z-modal flex items-center justify-center p-4">
  <div class="card max-w-4xl w-full animate-slide-in max-h-[90vh] overflow-y-auto scrollbar-thin">
    <div class="flex items-center justify-between mb-6">
      <h3 class="text-xl font-semibold text-text-primary">Edit Patient</h3>
      <button type="button" id="btnCloseEditModal"
              class="text-text-tertiary hover:text-text-primary transition-colors">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M6 18L18 6M6 6l12 12"/>
        </svg>
      </button>
    </div>

    <form id="editPatientForm" class="space-y-6">
      <input type="hidden" id="ep_patient_id" name="patient_id" />

      <div class="alert alert-info">
        <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd"
                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                clip-rule="evenodd"/>
        </svg>
        <p class="text-sm">Update patient information then click <b>Save Changes</b>.</p>
      </div>

      <!-- Names -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-text-primary mb-2">First Name *</label>
          <input id="ep_first_name" name="first_name" type="text" class="input" required>
        </div>
        <div>
          <label class="block text-sm font-medium text-text-primary mb-2">Last Name *</label>
          <input id="ep_last_name" name="last_name" type="text" class="input" required>
        </div>

        <div>
          <label class="block text-sm font-medium text-text-primary mb-2">Middle Name</label>
          <input id="ep_middle_name" name="middle_name" type="text" class="input">
        </div>
        <div>
          <label class="block text-sm font-medium text-text-primary mb-2">Preferred Name</label>
          <input id="ep_preferred_name" name="preferred_name" type="text" class="input">
        </div>

        <div>
          <label class="block text-sm font-medium text-text-primary mb-2">Marital Status</label>
          <input id="ep_marital_status" name="marital_status" type="text" class="input">
        </div>
        <div>
          <label class="block text-sm font-medium text-text-primary mb-2">Occupation</label>
          <input id="ep_occupation" name="occupation" type="text" class="input">
        </div>

        <div class="md:col-span-2">
          <label class="block text-sm font-medium text-text-primary mb-2">Preferred Language</label>
          <input id="ep_preferred_language" name="preferred_language" type="text" class="input">
        </div>
      </div>

      <!-- Demographics -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-text-primary mb-2">Date of Birth *</label>
          <input id="ep_date_of_birth" name="date_of_birth" type="date" class="input" required>
        </div>
        <div>
          <label class="block text-sm font-medium text-text-primary mb-2">Gender *</label>
          <select id="ep_gender" name="gender" class="input" required>
            <option value="male">Male</option>
            <option value="female">Female</option>
            <option value="other">Other</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-text-primary mb-2">Phone Number *</label>
          <input id="ep_phone" name="phone" type="tel" class="input" required>
        </div>
        <div>
          <label class="block text-sm font-medium text-text-primary mb-2">Email</label>
          <input id="ep_email" name="email" type="email" class="input">
        </div>

        <div>
          <label class="block text-sm font-medium text-text-primary mb-2">Blood Type</label>
          <select id="ep_blood_type" name="blood_type" class="input">
            <option value="">Select blood type</option>
            <option value="A+">A+</option>
            <option value="A-">A-</option>
            <option value="B+">B+</option>
            <option value="B-">B-</option>
            <option value="AB+">AB+</option>
            <option value="AB-">AB-</option>
            <option value="O+">O+</option>
            <option value="O-">O-</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-text-primary mb-2">Status</label>
          <select id="ep_status" name="status" class="input">
            <option value="active">active</option>
            <option value="inactive">inactive</option>
          </select>
        </div>
      </div>

      <!-- Address -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="md:col-span-2">
          <label class="block text-sm font-medium text-text-primary mb-2">Address *</label>
          <input id="ep_address_line" name="address_line" type="text" class="input" required>
        </div>
        <div>
          <label class="block text-sm font-medium text-text-primary mb-2">City *</label>
          <input id="ep_city" name="city" type="text" class="input" required>
        </div>
        <div>
          <label class="block text-sm font-medium text-text-primary mb-2">State *</label>
          <input id="ep_state" name="state" type="text" class="input" required>
        </div>
        <div>
          <label class="block text-sm font-medium text-text-primary mb-2">ZIP Code *</label>
          <input id="ep_zip_code" name="zip_code" type="text" class="input" required>
        </div>
      </div>

      <div id="editPatientMsg" class="text-sm"></div>

      <div class="flex gap-3">
        <button type="button" id="btnCancelEdit" class="btn btn-outline flex-1">Cancel</button>
        <button type="submit" id="btnSaveEdit" class="btn btn-primary flex-1">Save Changes</button>
      </div>
    </form>
  </div>
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

      <!-- IMPORTANT: message placeholder for JS -->
      <div id="newPatientMsg" class="text-sm"></div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-text-primary mb-2">First Name *</label>
          <input id="np_first_name" name="first_name" type="text" class="input" required>
        </div>

        <div>
          <label class="block text-sm font-medium text-text-primary mb-2">Last Name *</label>
          <input id="np_last_name" name="last_name" type="text" class="input" required>
        </div>

        <div>
          <label class="block text-sm font-medium text-text-primary mb-2">Middle Name</label>
          <input id="np_middle_name" name="middle_name" type="text" class="input">
        </div>

        <div>
          <label class="block text-sm font-medium text-text-primary mb-2">Preferred Name</label>
          <input id="np_preferred_name" name="preferred_name" type="text" class="input">
        </div>

        <div>
          <label class="block text-sm font-medium text-text-primary mb-2">Marital Status</label>
          <input id="np_marital_status" name="marital_status" type="text" class="input">
        </div>

        <div>
          <label class="block text-sm font-medium text-text-primary mb-2">Occupation</label>
          <input id="np_occupation" name="occupation" type="text" class="input">
        </div>

        <div class="md:col-span-2">
          <label class="block text-sm font-medium text-text-primary mb-2">Preferred Language</label>
          <input id="np_preferred_language" name="preferred_language" type="text" class="input">
        </div>

        <div>
          <label class="block text-sm font-medium text-text-primary mb-2">Date of Birth *</label>
          <input id="np_date_of_birth" name="date_of_birth" type="date" class="input" required>
        </div>

        <div>
          <label class="block text-sm font-medium text-text-primary mb-2">Gender *</label>
          <select id="np_gender" name="gender" class="input" required>
            <option value="">Select gender</option>
            <option value="male">Male</option>
            <option value="female">Female</option>
            <option value="other">Other</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-text-primary mb-2">Phone Number *</label>
          <input id="np_phone" name="phone" type="tel" class="input" placeholder="(+63) 987-654-364" required>
        </div>

        <div>
          <label class="block text-sm font-medium text-text-primary mb-2">Email</label>
          <input id="np_email" name="email" type="email" class="input" placeholder="patient@email.com">
        </div>

        <div class="md:col-span-2">
          <label class="block text-sm font-medium text-text-primary mb-2">Address Line *</label>
          <input id="np_address_line" name="address_line" type="text" class="input" placeholder="Street address" required>
        </div>

        <div>
          <label class="block text-sm font-medium text-text-primary mb-2">City *</label>
          <input id="np_city" name="city" type="text" class="input" required>
        </div>

        <div>
          <label class="block text-sm font-medium text-text-primary mb-2">State *</label>
          <input id="np_state" name="state" type="text" class="input" required>
        </div>

        <div>
          <label class="block text-sm font-medium text-text-primary mb-2">ZIP Code *</label>
          <input id="np_zip_code" name="zip_code" type="text" class="input" required>
        </div>

        <div>
          <label class="block text-sm font-medium text-text-primary mb-2">Blood Type</label>
          <select id="np_blood_type" name="blood_type" class="input">
            <option value="">Select blood type</option>
            <option value="A+">A+</option>
            <option value="A-">A-</option>
            <option value="B+">B+</option>
            <option value="B-">B-</option>
            <option value="AB+">AB+</option>
            <option value="AB-">AB-</option>
            <option value="O+">O+</option>
            <option value="O-">O-</option>
          </select>
        </div>
      </div>

      <div class="flex gap-3">
        <button type="button" id="cancelNewPatient" class="btn btn-outline flex-1">Cancel</button>
        <button type="submit" class="btn btn-primary flex-1">Register Patient</button>
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

<script src="../js/medical_staff_new_patient_modal.js"></script>
<script src="../js/medical_staff_edit_patient_modal.js"></script>
<script src="../js/medical_staff_visits_timeline.js"></script>
<script src="../js/medical_staff_patient_details_loader.js"></script>
<script src="../js/medical_staff_recent_patients_modal.js"></script>
<script src="../js/medical_staff_global_search_autocomplete.js"></script>
<script src="../js/medical_staff_advanced_search_modal.js"></script>
<script src="../js/medical_staff_sidebar_lists.js"></script>
<script id="dhws-dataInjector" src="../public/dhws-data-injector.js"></script>


</body>
</html>