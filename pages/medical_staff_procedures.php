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
    <meta name="description" content="Nurse Vital Signs & Patient Queue System - CAVMED Portal">
    <title>Nurse Workflow - CAVMED Portal</title>
    <link rel="stylesheet" href="../css/main.css">
    <style>
        .workflow-step {
            border-left: 4px solid transparent;
            transition: all 0.3s ease;
        }
        
        .workflow-step.active {
            border-left-color: #059669;
            background-color: #f0fdf4;
        }
        
        .workflow-step.completed {
            border-left-color: #10b981;
        }
        
        .patient-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 14px;
        }
        
        .queue-item {
            transition: all 0.2s ease;
            cursor: pointer;
        }
        
        .queue-item:hover {
            background-color: #f8fafc;
            transform: translateX(4px);
        }
        
        .queue-item.active {
            background-color: #ecfdf5;
            border-left: 4px solid #10b981;
        }
        
        .vital-sign-input {
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 0.75rem;
            width: 100%;
            font-size: 1rem;
            transition: all 0.2s ease;
        }
        
        .vital-sign-input:focus {
            outline: none;
            border-color: #10b981;
            box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.1);
        }
        
        .vital-sign-card {
            transition: all 0.3s ease;
        }
        
        .vital-sign-card.critical {
            animation: pulse 2s infinite;
            border-color: #ef4444;
        }
        
        .vital-sign-card.warning {
            border-color: #f59e0b;
        }
        
        @keyframes pulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4); }
            50% { box-shadow: 0 0 0 6px rgba(239, 68, 68, 0); }
        }
        
        .status-indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 4px;
        }
        
        .status-waiting { background-color: #f59e0b; }
        .status-in-progress { background-color: #3b82f6; }
        .status-completed { background-color: #10b981; }
        .status-critical { background-color: #ef4444; animation: pulse 2s infinite; }
        
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .badge-waiting { background-color: #fef3c7; color: #92400e; }
        .badge-in-progress { background-color: #dbeafe; color: #1e40af; }
        .badge-completed { background-color: #d1fae5; color: #065f46; }
        .badge-critical { background-color: #fee2e2; color: #991b1b; }
        
        .priority-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 6px;
        }
        
        .priority-high { background-color: #ef4444; }
        .priority-medium { background-color: #f59e0b; }
        .priority-low { background-color: #10b981; }
        
        .modal-backdrop {
            background-color: rgba(0, 0, 0, 0.5);
        }
        
        .fade-in {
            animation: fadeIn 0.2s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .workflow-progress {
            height: 4px;
            background-color: #e2e8f0;
            border-radius: 2px;
            overflow: hidden;
        }
        
        .workflow-progress-bar {
            height: 100%;
            background-color: #10b981;
            transition: width 0.3s ease;
        }
    </style>
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

                <a href="medical_staff_patient_registration.php" class="nav-item">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    <span>Patient</span>
                </a>
                <a href="medical_staff_procedures.php" class="nav-item nav-item-active whitespace-nowrap">
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
            <!-- Dashboard Header -->
            <div class="flex items-center justify-between mb-6">
                <div>
                </div>
                <div class="flex items-center gap-3">
                <!--    <div class="workflow-progress w-48">
                        <div id="workflowProgress" class="workflow-progress-bar" style="width: 33%"></div>
                    </div>-->
                    <span id="workflowStepText" class="text-sm text-text-secondary"> </span>
                </div> 
            </div>

            <!-- Main Workflow Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                <!-- Left Column: Active Patients & Queue -->
                <div class="lg:col-span-1">
                   <!-- Active Session -->
<div class="card mb-6">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h3 class="text-lg font-semibold text-text-primary">Active Patient</h3>
            <p class="text-xs text-text-secondary">
                Today’s Visit Queue:
                <span id="queueCount" class="font-semibold text-text-primary">0</span>
            </p>
        </div>

        <button type="button" id="startNewSessionBtn" class="btn btn-success btn-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            <span>New Patient</span>
        </button>
    </div>

    <!-- ACTIVE PATIENT CARD (DYNAMIC) -->
    <div id="activePatientCard" class="bg-secondary-50 p-4 rounded-base mb-4 border border-border">
        <div class="flex items-center gap-3 mb-3">
            <div id="activePatientAvatar" class="patient-avatar bg-secondary-200 text-text-secondary">
                --
            </div>

            <div class="flex-1">
                <p id="activePatientName" class="font-medium text-text-primary">No Active Patient</p>
                <p id="activePatientStatusText" class="text-xs text-text-secondary">
                    Select a patient from the queue.
                </p>
            </div>

            <span id="activePatientBadge" class="badge badge-waiting hidden">Waiting</span>
        </div>

        <div class="grid grid-cols-2 gap-3">
            <div>
                <p class="text-xs text-text-secondary">Age</p>
                <p id="activePatientAge" class="text-sm font-medium text-text-primary">--</p>
            </div>

            <div>
                <p class="text-xs text-text-secondary">Gender</p>
                <p id="activePatientGender" class="text-sm font-medium text-text-primary">--</p>
            </div>

            <div>
                <p class="text-xs text-text-secondary">Arrival Time</p>
                <p id="activePatientArrival" class="text-sm font-medium text-text-primary">--</p>
            </div>

            <div>
                <p class="text-xs text-text-secondary">Visit Type</p>
                <p id="activePatientVisitType" class="text-sm font-medium text-text-primary">--</p>
            </div>
        </div>
    </div>

   <!-- ACTION BUTTONS -->
<div class="flex gap-2 mb-4">


  <!-- next step: starts workflow + opens vitals -->
  <button type="button" id="nextStepBtn" class="btn btn-primary flex-1" disabled>
    next step →
  </button>
</div>

<!-- QUEUE LIST (DYNAMIC POPULATE) -->
<div>
  <h4 class="text-sm font-semibold text-text-primary mb-2">Queue</h4>

  <div id="patientQueue" class="space-y-2">
    <div class="bg-secondary-50 rounded-base p-4 border border-border">
      <p class="text-sm text-text-secondary text-center">no patients in queue</p>
    </div>
  </div>

<!-- FOR CONSULTATION QUEUE -->
<div class="mt-6">
  <h4 class="text-sm font-semibold text-text-primary mb-2">For Consultation</h4>

  <div id="consultationQueue" class="space-y-2">
    <div class="bg-secondary-50 rounded-base p-4 border border-border">
      <p class="text-sm text-text-secondary text-center">no patients for consultation</p>
    </div>
  </div>
</div>

</div>
</div>

</div>


<!-- =========================
     WORKFLOW: VITAL SIGNS STEP
     (shown when "next step" is clicked)
========================= -->
<div class="lg:col-span-2 space-y-6">
<div id="patientWorkflowCard" class="card">

  <div class="flex items-center justify-between mb-4">
    <div>
      <h3 class="text-lg font-semibold text-text-primary">Patient Workflow</h3>
      <p class="text-xs text-text-secondary">
        Current Patient:
        <span id="workflowPatientName" class="font-semibold text-text-primary">--</span>
        <span class="mx-2 text-text-tertiary">•</span>
        Status:
        <span id="workflowPatientStatus" class="badge badge-waiting">Waiting</span>
      </p>
    </div>

    <div class="flex gap-2">
      
    </div>
  </div>

  <div class="space-y-4">

    <!-- step 1: start session (auto when next step) -->
    <div class="workflow-step p-4 rounded-base border border-border">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="w-8 h-8 rounded-full bg-primary-100 text-primary-700 flex items-center justify-center font-semibold">
            1
          </div>
          <div>
            <h4 class="font-medium text-text-primary">Start Visit</h4>
            <p class="text-xs text-text-secondary">Set patient to in progress.</p>
          </div>
        </div>

        <span id="step1Badge" class="badge badge-waiting">Pending</span>
      </div>

      <div id="step1Meta" class="hidden mt-3 text-xs text-text-secondary">
        <span>Visit ID:</span>
        <span id="workflowVisitId" class="font-semibold text-text-primary">--</span>
        <span class="mx-2 text-text-tertiary">•</span>
        <span>Started:</span>
        <span id="workflowStartTime" class="font-semibold text-text-primary">--</span>
      </div>
    </div>

    <!-- step 2: vital signs -->
    <div class="workflow-step p-4 rounded-base border border-border">
      <div class="flex items-center justify-between mb-3">
        <div class="flex items-center gap-3">
          <div class="w-8 h-8 rounded-full bg-secondary-200 text-text-secondary flex items-center justify-center font-semibold">
            2
          </div>
          <div>
            <h4 class="font-medium text-text-primary">Vital Signs Collection</h4>
            <p class="text-xs text-text-secondary">Record Vitals for the Active Patient</p>
          </div>
        </div>

        <span id="step2Badge" class="badge badge-waiting">Pending</span>
      </div>

      <!-- hidden until next step clicked -->
      <div id="vitalSignsForm" class="hidden space-y-4">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div>
            <label class="block text-sm font-medium text-text-primary mb-2">Blood Pressure</label>
            <input type="text" id="bloodPressure" class="vital-sign-input" placeholder="120/80">
          </div>

          <div>
            <label class="block text-sm font-medium text-text-primary mb-2">Heart Rate</label>
            <input type="number" id="heartRate" class="vital-sign-input" placeholder="0" min="0">
          </div>

          <div>
            <label class="block text-sm font-medium text-text-primary mb-2">Temperature (°c)</label>
            <input type="number" id="temperature" class="vital-sign-input" placeholder="36.8" step="0.1">
          </div>

          <div>
            <label class="block text-sm font-medium text-text-primary mb-2">spo2 (%)</label>
            <input type="number" id="spo2" class="vital-sign-input" placeholder="98" min="0" max="100">
          </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div>
            <label class="block text-sm font-medium text-text-primary mb-2">Blood Glucose (mg/dl)</label>
            <input type="number" id="bloodGlucose" class="vital-sign-input" placeholder="90" min="0">
          </div>

          <div>
            <label class="block text-sm font-medium text-text-primary mb-2">Weight (kg)</label>
            <input type="number" id="weightKg" class="vital-sign-input" placeholder="0" step="0.1" min="0">
          </div>

          <div>
            <label class="block text-sm font-medium text-text-primary mb-2">Respiratory Rate</label>
            <input type="number" id="respiratoryRate" class="vital-sign-input" placeholder="16" min="0">
          </div>

          <div>
            <label class="block text-sm font-medium text-text-primary mb-2">Height (cm)</label>
            <input type="number" id="heightCm" class="vital-sign-input" placeholder="0" step="0.1" min="0">
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-text-primary mb-2">Notes</label>
          <textarea id="vitalNotes" class="vital-sign-input" rows="2" placeholder="additional observations..."></textarea>
        </div>

        <div class="flex gap-3 pt-4 border-t border-border">
          <!-- will POST vitals + keep status in_progress -->
          <button type="button" id="saveVitalsBtn" class="btn btn-primary">
            Save Vital Signs
          </button>

         
        </div>

        <!-- inline message area -->
        <div id="vitalsMsg" class="hidden text-xs mt-2"></div>
      </div>
    </div>

<!-- hidden fields for JS (store active visit/patient) -->
<input type="hidden" id="activeVisitId" value="">
<input type="hidden" id="activePatientId" value="">

           
        </div>
    </main>
<!-- New Visit Modal (Add to Queue) -->
<div id="newVisitModal" class="hidden fixed inset-0 modal-backdrop z-modal flex items-center justify-center p-4">
  <div class="card max-w-md w-full fade-in max-h-[90vh] overflow-y-auto">
    <div class="flex items-center justify-between mb-6">
      <h3 class="text-xl font-semibold text-text-primary">New Visit / Add to Queue</h3>
      <button type="button" id="closeNewVisitModal" class="text-text-tertiary hover:text-text-primary transition-colors">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
      </button>
    </div>

    <div class="space-y-4">
<!-- Select Existing Patient -->
<div>
  <label class="block text-sm font-medium text-text-primary mb-2">
    Select Patient *
  </label>

  <!-- Search Input -->
  <div class="flex gap-2">
    <input 
      type="text" 
      id="visitPatientSearch" 
      class="vital-sign-input w-full"
      placeholder="Search patient name / MRN..."
      autocomplete="off"
    />
  </div>

  <!-- Hidden field to store REAL patient_id -->
  <input type="hidden" id="visitPatientId" value="">

  <!-- Patient search results dropdown -->
  <select 
    id="patientList" 
    class="vital-sign-input mt-2 w-full"
    size="5"
    style="display: none;">
  </select>

  <!-- Display selected patient -->
  <p class="text-sm text-text-secondary mt-2">
    Selected:
    <span id="visitSelectedPatientName" class="font-medium text-text-primary">
      None
    </span>
  </p>
</div>



      <!-- Visit Type -->
      <div>
        <label class="block text-sm font-medium text-text-primary mb-2">
          Visit Type *
        </label>
        <select id="visitType" class="vital-sign-input">
          <option value="" selected disabled>Select visit type</option>
          <option value="consultation">Consultation</option>
          <option value="follow_up">Follow-up</option>
          <option value="check_up">Check-up</option>
          <option value="emergency">Emergency</option>
          <option value="other">Other</option>
        </select>
      </div>

      <div>
        <label class="block text-sm font-medium text-text-primary mb-2">
          Reason for Visit / Chief Complaint *
        </label>
        <textarea id="visitReason" class="vital-sign-input" rows="2" placeholder="Brief description..."></textarea>
      </div>

      <div>
        <label class="block text-sm font-medium text-text-primary mb-2">
          Priority Assessment *
        </label>
        <select id="visitPriority" class="vital-sign-input">
          <option value="low">Low - Non-urgent</option>
          <option value="medium" selected>Medium - Semi-urgent</option>
          <option value="high">High - Urgent</option>
        </select>
      </div>

      <!-- Buttons -->
      <div class="flex gap-3 pt-4 border-t border-border">
        <button type="button" id="cancelNewVisitBtn" class="btn btn-outline flex-1">
          Cancel
        </button>
        <button type="button" id="addVisitToQueueBtn" class="btn btn-primary flex-1">
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

   

<script src="../js/medical_staff_new_visit_modal.js"></script>
<script src="../js/medical_staff_visit_queue.js"></script>

</body>
</html>