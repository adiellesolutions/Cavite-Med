<?php
session_start();
require_once __DIR__ . "/../backend/get_assigned_health_center.php";

// ✅ require login
if (!isset($_SESSION['user_id'])) {
    header("Location: system_login_portal.html");
    exit;
}

// ✅ optional: require doctor role (edit if your role name is different)
if (isset($_SESSION['role']) && $_SESSION['role'] !== 'doctor') {
    header("Location: system_login_portal.html");
    exit;
}

// ✅ optional: force password change
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
    <meta name="description" content="Electronic Prescription Management - Digital prescribing platform with drug databases and interaction checking">
    <title>Electronic Prescription Management - CAVMED Portal</title>
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
<p class="text-xs text-text-secondary">
    <?php echo htmlspecialchars($assigned_health_center_name ?: 'Health Center'); ?>
</p>                      </div>
                </a>
            </div>

            <!-- Search Bar - Persistent -->
            <div class="flex-1 max-w-2xl mx-8">
               
                <!-- Autocomplete Dropdown -->
                <div id="globalSearchResults" class="hidden absolute mt-2 w-full max-w-2xl bg-surface border border-border rounded-base shadow-lg max-h-96 overflow-y-auto z-dropdown">
                    <!-- Results populated by JavaScript -->
                </div>
            </div>

            <!-- User Info & Actions -->
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
    </header>

    <!-- Navigation Tabs -->
    <nav class="bg-surface border-b border-border px-6 no-print">        
        <div class="px-6">
            <div class="flex items-center gap-1 overflow-x-auto scrollbar-thin">

                <a href="doctor_prescription.php" class="nav-item nav-item-active whitespace-nowrap">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    <span>e-Prescription</span>
                </a>

                <a href="doctor_medical_certificate.php" class="nav-item">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span>Medical Certificates</span>
                </a>

                <a href="doctor_patient_records.php" class="nav-item">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    <span>Patient Records</span>
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
            <!-- Page Header -->
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-2xl font-semibold text-text-primary">Electronic Prescription Management</h2>
                    <p class="text-text-secondary mt-1">Create and manage digital prescriptions with clinical decision support</p>
                </div>
                <div class="flex items-center gap-3">
                   
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                
            </div>

            <!-- Dual Panel Layout -->
            <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
                <!-- Left Panel - Prescription Creation (40%) -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Patient Selection -->
                    <div class="card">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-text-primary">Patient Information</h3>
                            <button type="button" id="selectPatientBtn" class="btn btn-outline btn-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                                <span>Select Patient</span>
                            </button>
                        </div>

                        <div id="selectedPatientInfo" class="hidden">
  <div class="flex items-start gap-4 p-4 bg-secondary-50 rounded-base">
    <div id="patientInitials"
      class="w-16 h-16 rounded-full bg-primary-100 text-primary-700 flex items-center justify-center font-semibold text-xl flex-shrink-0">
      PT
    </div>

    <div class="flex-1">
      <h4 id="patientFullName" class="font-semibold text-text-primary">---</h4>
      <p id="patientMeta" class="text-sm text-text-secondary">Patient ID: ---</p>

      <div class="flex items-center gap-4 mt-2 text-sm">
        <span class="text-text-secondary">Age: <b id="patientAge">--</b></span>
        <span class="text-text-secondary">Gender: <b id="patientGender">--</b></span>
        <span class="text-text-secondary">Blood Type: <b id="patientBloodType">--</b></span>
      </div>
    </div>
  </div>
</div>


                        <div id="noPatientSelected" class="text-center py-8">
                            <svg class="w-16 h-16 mx-auto text-text-tertiary mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            <p class="text-text-secondary">No patient selected</p>
                            <p class="text-sm text-text-tertiary mt-1">Select a patient to create prescription</p>
                        </div>
                    </div>

              <!-- Clinical Decision Support Alerts -->
              <div id="clinicalAlerts" class="hidden space-y-3">
  <div id="alertsContainer" class="space-y-3"></div>


                    </div>

                    <!-- Favorites List 
                    <div class="card">
                        <h3 class="text-lg font-semibold text-text-primary mb-4">Frequently Prescribed</h3>
                        <div class="space-y-2">
                            <button type="button" class="w-full text-left p-3 rounded-base hover:bg-secondary-50 transition-colors border border-border">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="font-medium text-text-primary">Amoxicillin 500mg</p>
                                        <p class="text-sm text-text-secondary">TID × 7 days</p>
                                    </div>
                                    <svg class="w-5 h-5 text-warning-500" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                                    </svg>
                                </div>
                            </button>
                            <button type="button" class="w-full text-left p-3 rounded-base hover:bg-secondary-50 transition-colors border border-border">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="font-medium text-text-primary">Metformin 850mg</p>
                                        <p class="text-sm text-text-secondary">BID × 30 days</p>
                                    </div>
                                    <svg class="w-5 h-5 text-warning-500" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                                    </svg>
                                </div>
                            </button>
                            <button type="button" class="w-full text-left p-3 rounded-base hover:bg-secondary-50 transition-colors border border-border">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="font-medium text-text-primary">Lisinopril 10mg</p>
                                        <p class="text-sm text-text-secondary">Once daily × 30 days</p>
                                    </div>
                                    <svg class="w-5 h-5 text-warning-500" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                                    </svg>
                                </div>
                            </button>
                        </div>
                    </div>-->
                </div>

                <!-- Right Panel - Medication Information & Clinical Support (60%) -->
                <div class="lg:col-span-3">
                    

                    <!-- Medication Information Card -->
                    <div class="card">
                    <h3 class="text-lg font-semibold text-text-primary mb-4">Prescription Details</h3>
                        
                        <form id="prescriptionForm" class="space-y-4">
                            <!-- Medication Selection -->
                           <div>
  <label for="medicationSearch" class="block text-sm font-medium text-text-primary mb-2">
    Medication *
  </label>

  <div class="relative">
    <input
      type="text"
      id="medicationSearch"
      required
      autocomplete="off"
      placeholder="Search medicine..."
      class="input w-full"
    />

    <!-- dropdown results -->
    <div
  id="medicineResults"
  class="mt-1 bg-white border border-border rounded-base shadow-lg max-h-60 overflow-y-auto hidden"
></div>
    <!-- hidden selected id -->
    <input type="hidden" id="selectedMedicineId" required />
  </div>
</div>

                            <!-- Dosage Calculator -->
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="dosageAmount" class="block text-sm font-medium text-text-primary mb-2">
                                        Dosage Amount *
                                    </label>
                                    <input type="number" id="dosageAmount" required class="input" placeholder="e.g., 500">
                                </div>
                                <div>
                                    <label for="dosageUnit" class="block text-sm font-medium text-text-primary mb-2">
                                        Unit *
                                    </label>
                                    <select id="dosageUnit" required class="input">
                                        <option value="mg">mg</option>
                                        <option value="mcg">mcg</option>
                                        <option value="g">g</option>
                                        <option value="ml">ml</option>
                                        <option value="units">units</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Frequency Templates -->
                            <div>
                                <label for="frequencyTemplate" class="block text-sm font-medium text-text-primary mb-2">
                                    Medication Intake *
                                </label>
                                <select id="frequencyTemplate" required class="input">
                                    <option value="">Select medication intake...</option>
                                    <option value="once-daily">Once daily</option>
                                    <option value="twice-daily">Twice daily (BID)</option>
                                    <option value="three-times">Three times daily (TID)</option>
                                    <option value="four-times">Four times daily (QID)</option>
                                    <option value="every-6h">Every 6 hours</option>
                                    <option value="every-8h">Every 8 hours</option>
                                    <option value="every-12h">Every 12 hours</option>
                                    <option value="as-needed">As needed (PRN)</option>
                                    <option value="bedtime">At bedtime (HS)</option>
                                </select>
                            </div>

                            <!-- Duration Presets -->
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="durationAmount" class="block text-sm font-medium text-text-primary mb-2">
                                        Duration *
                                    </label>
                                    <input type="number" id="durationAmount" required class="input" placeholder="e.g., 7">
                                </div>
                                <div>
                                    <label for="durationUnit" class="block text-sm font-medium text-text-primary mb-2">
                                        Period *
                                    </label>
                                    <select id="durationUnit" required class="input">
                                        <option value="days">Days</option>
                                        <option value="weeks">Weeks</option>
                                        <option value="months">Months</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Route of Administration -->
                            <div>
                                <label for="routeAdmin" class="block text-sm font-medium text-text-primary mb-2">
                                    Route of Administration *
                                </label>
                                <select id="routeAdmin" required class="input">
                                    <option value="">Select route...</option>
                                    <option value="oral">Oral (PO)</option>
                                    <option value="sublingual">Sublingual (SL)</option>
                                    <option value="topical">Topical</option>
                                    <option value="inhalation">Inhalation</option>
                                    <option value="injection-im">Injection - Intramuscular (IM)</option>
                                    <option value="injection-iv">Injection - Intravenous (IV)</option>
                                    <option value="injection-sc">Injection - Subcutaneous (SC)</option>
                                    <option value="rectal">Rectal</option>
                                    <option value="ophthalmic">Ophthalmic</option>
                                    <option value="otic">Otic</option>
                                </select>
                            </div>

                            <!-- Special Instructions -->
                            <div>
                                <label for="specialInstructions" class="block text-sm font-medium text-text-primary mb-2">
                                    Special Instructions
                                </label>
                                <textarea id="specialInstructions" rows="3" class="input" 
                                          placeholder="e.g., Take with food, Avoid alcohol, etc."></textarea>
                            </div>

                            <!-- Substitution -->
                            <div>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" id="allowSubstitution" checked
                                           class="w-4 h-4 text-primary border-border rounded focus:ring-2 focus:ring-primary-500">
                                    <span class="text-sm text-text-primary">Allow generic substitution</span>
                                </label>
                            </div>
<!-- Added Medicines List -->
<div class="border border-border rounded-base p-3">
  <div class="flex items-center justify-between mb-2">
    <p class="font-medium text-text-primary">Medicines Added</p>
    <span id="itemsCount" class="text-sm text-text-secondary">0 item(s)</span>
  </div>

  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="text-text-secondary">
        <tr class="border-b border-border">
          <th class="py-2 text-left">Medicine</th>
          <th class="py-2 text-left">Dosage</th>
          <th class="py-2 text-left">Intake</th>
          <th class="py-2 text-left">Duration</th>
          <th class="py-2 text-left">Route</th>
          <th class="py-2 text-right">Action</th>
        </tr>
      </thead>
      <tbody id="itemsTbody">
        <tr>
          <td colspan="6" class="py-3 text-center text-text-tertiary">
            no medicine added yet
          </td>
        </tr>
      </tbody>
    </table>
  </div>

  <div class="flex gap-2 mt-3">
    <button type="button" id="addItemBtn" class="btn btn-outline flex-1">
      + Add Medicine
    </button>
    <button type="button" id="clearItemsBtn" class="btn btn-outline">
      Clear
    </button>
  </div>
</div>

                            <!-- Action Buttons -->
                            <div class="flex gap-3 pt-4 border-t border-border">
                                <button type="submit" class="btn btn-primary flex-1">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <span>Create Prescription</span>
                                </button>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-surface border-t border-border py-4 px-6 no-print">
        <div class="max-w-full mx-auto flex flex-col md:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-4 flex-wrap justify-center">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-success" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <span class="text-sm text-text-secondary">HIPAA Compliant</span>
                </div>
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-success" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <span class="text-sm text-text-secondary">DEA Certified</span>
                </div>
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-success" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <span class="text-sm text-text-secondary">FDA Approved Database</span>
                </div>
            </div>
            <div class="text-sm text-text-secondary text-center md:text-right">
                <p>© 2025 CAVMED Portal. All Rights Reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Patient Selection Modal -->
<div id="patientSelectionModal" class="hidden fixed inset-0 bg-secondary-900 bg-opacity-50 z-modal flex items-center justify-center p-4">
  <div class="card max-w-2xl w-full animate-slide-in max-h-[90vh] overflow-y-auto">
    <div class="flex items-center justify-between mb-6">
      <h3 class="text-xl font-semibold text-text-primary">Select Patient</h3>
      <button type="button" id="closePatientModal" class="text-text-tertiary hover:text-text-primary transition-colors">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
      </button>
    </div>

    <div class="mb-4">
      <input type="text" id="patientSearchInput" placeholder="Search by name or patient ID..." class="input">
    </div>

    <!-- ✅ IMPORTANT: dynamic list container -->
    <div id="patientList" class="space-y-2">
      <div class="p-3 text-text-secondary">loading...</div>
    </div>
  </div>
</div>


    <script src="../js/doctor_prescription.js"></script>
    <script src="../js/doctor_prescription_alerts.js"></script>

</body>
</html>