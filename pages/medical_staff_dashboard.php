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

$user_id = $_SESSION['user_id'];

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
                
                <a href="medical_staff_dashboard.php" class="nav-item nav-item-active whitespace-nowrap">
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
                        <p class="text-2xl font-semibold text-text-primary mt-1">12</p>
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
                        <p class="text-2xl font-semibold text-text-primary mt-1">23</p>
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
                        <p class="text-2xl font-semibold text-text-primary mt-1">3</p>
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
                        <p class="text-2xl font-semibold text-text-primary mt-1">47</p>
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

                        <?php foreach ($recent_activity as $activity): ?>

                            <?php
                            $time = date("h:i A", strtotime($activity['activity_time']));
                            ?>

                            <div class="flex items-center gap-3 p-3 bg-secondary-50 rounded-base">
                                <div class="w-8 h-8 rounded-full 
                                    <?= $activity['type'] === 'registered' ? 'bg-accent-100' : 'bg-success-100' ?> 
                                    flex items-center justify-center">

                                    <?php if ($activity['type'] === 'registered'): ?>
                                        <svg class="w-4 h-4 text-accent-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                                        </svg>
                                    <?php else: ?>
                                        <svg class="w-4 h-4 text-success-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    <?php endif; ?>
                                </div>

                                <div class="flex-1">
                                    <p class="text-sm text-text-primary">
                                        <?= htmlspecialchars($activity['name']) ?>
                                        <?= $activity['type'] === 'registered'
                                            ? 'registered as new patient'
                                            : 'vitals recorded' ?>
                                    </p>
                                    <p class="text-xs text-text-secondary"><?= $time ?></p>
                                </div>
                            </div>

                        <?php endforeach; ?>

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
                                <?= $patients_today ?>
                            </p>
                            <p class="text-sm text-text-secondary">Patients Registered</p>
                        </div>

                        <div class="p-4 rounded-base border-2 border-success-100">
                            <p class="text-3xl font-bold text-success-600 mb-1">
                                <?= $vitals_today ?>
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

    <script>
        // Live Time Update
        function updateCurrentTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('en-US', { 
                hour: '2-digit', 
                minute: '2-digit',
                hour12: true 
            });
            document.getElementById('currentTime').textContent = timeString;
            document.getElementById('lastUpdateTime').textContent = timeString;
        }
        
        // Update time immediately and then every minute
        updateCurrentTime();
        setInterval(updateCurrentTime, 60000);

        // Workflow State Management
        let currentStep = 1;
        let activePatientId = 1;
        let patients = {
            1: {
                id: 1,
                firstName: 'John',
                lastName: 'Smith',
                age: 42,
                gender: 'male',
                priority: 'medium',
                visitReason: 'Persistent headache and fever for 3 days',
                vitals: {},
                status: 'registration',
                arrivalTime: '09:30',
                initials: 'JS'
            },
            2: {
                id: 2,
                firstName: 'Maria',
                lastName: 'Johnson',
                age: 35,
                gender: 'female',
                priority: 'high',
                visitReason: 'Chest pain and shortness of breath',
                vitals: {
                    bloodPressure: '150/95',
                    heartRate: '112',
                    temperature: '101.2',
                    spo2: '92',
                    respiratoryRate: '24',
                },
                status: 'vitals',
                arrivalTime: '09:15',
                initials: 'MJ'
            },
            3: {
                id: 3,
                firstName: 'Robert',
                lastName: 'Chen',
                age: 58,
                gender: 'male',
                priority: 'low',
                visitReason: 'Routine check-up',
                vitals: {
                    bloodPressure: '118/76',
                    heartRate: '72',
                    temperature: '98.6',
                    spo2: '98',
                    respiratoryRate: '16',
                },
                status: 'queue',
                arrivalTime: '09:00',
                initials: 'RC'
            }
        };

        // Update Workflow Progress
        function updateWorkflowProgress() {
            const progressBar = document.getElementById('workflowProgress');
            const stepText = document.getElementById('workflowStepText');
            
            progressBar.style.width = `${(currentStep / 2) * 100}%`;
            stepText.textContent = `Step ${currentStep} of 2`;
            
            // Update workflow step UI
            const steps = document.querySelectorAll('.workflow-step');
            steps.forEach((step, index) => {
                step.classList.remove('active', 'completed');
                if (index + 1 === currentStep) {
                    step.classList.add('active');
                } else if (index + 1 < currentStep) {
                    step.classList.add('completed');
                }
            });
            
            // Show/hide forms based on current step
            document.getElementById('registrationForm').classList.toggle('hidden', currentStep !== 1);
            document.getElementById('vitalSignsForm').classList.toggle('hidden', currentStep !== 2);
        }

        // Load Patient Data
        function loadPatient(patientId) {
            const patient = patients[patientId];
            if (!patient) return;
            
            activePatientId = patientId;
            
            // Update active patient card
            const avatar = document.querySelector('#activePatientCard .patient-avatar');
            const name = document.querySelector('#activePatientCard .font-medium');
            const status = document.querySelector('#activePatientCard .text-xs.text-text-secondary');
            const priority = document.querySelector('#activePatientCard .text-warning-600');
            
            avatar.textContent = patient.initials;
            avatar.className = `patient-avatar ${
                patient.priority === 'high' ? 'bg-error-100 text-error-700' :
                patient.priority === 'medium' ? 'bg-warning-100 text-warning-700' :
                'bg-success-100 text-success-700'
            }`;
            
            name.textContent = `${patient.firstName} ${patient.lastName}`;
            status.textContent = `${patient.status === 'registration' ? 'Registration Required' : 
                                  patient.status === 'vitals' ? 'Vital Signs Required' : 
                                  'Ready for Queue'}`;
            
            // Update form fields
            document.getElementById('firstName').value = patient.firstName || '';
            document.getElementById('lastName').value = patient.lastName || '';
            document.getElementById('age').value = patient.age || '';
            document.getElementById('gender').value = patient.gender || '';
            document.getElementById('visitReason').value = patient.visitReason || '';
            
            // Update vital signs monitor
            const vitals = patient.vitals || {};
            document.getElementById('bloodPressure').value = vitals.bloodPressure || '';
            document.getElementById('heartRate').value = vitals.heartRate || '';
            document.getElementById('temperature').value = vitals.temperature || '';
            document.getElementById('spo2').value = vitals.spo2 || '';
            document.getElementById('respiratoryRate').value = vitals.respiratoryRate || '';
            document.getElementById('vitalNotes').value = vitals.notes || '';
            
            // Determine current step based on patient status
            currentStep = patient.status === 'registration' ? 1 :
                         patient.status === 'vitals' ? 2 : 3;
            
            updateWorkflowProgress();
            updateVitalMonitor();
        }

        // Update Vital Signs Monitor Display
        function updateVitalMonitor() {
            const patient = patients[activePatientId];
            const vitals = patient.vitals || {};
            
            const bpElement = document.querySelector('.vital-sign-card:nth-child(1) .text-2xl');
            const hrElement = document.querySelector('.vital-sign-card:nth-child(2) .text-2xl');
            const tempElement = document.querySelector('.vital-sign-card:nth-child(3) .text-2xl');
            const spo2Element = document.querySelector('.vital-sign-card:nth-child(4) .text-2xl');
            
            const bpStatus = document.querySelector('.vital-sign-card:nth-child(1) .text-xs');
            const hrStatus = document.querySelector('.vital-sign-card:nth-child(2) .text-xs');
            const tempStatus = document.querySelector('.vital-sign-card:nth-child(3) .text-xs');
            const spo2Status = document.querySelector('.vital-sign-card:nth-child(4) .text-xs');
            
            const bpCard = document.querySelector('.vital-sign-card:nth-child(1)');
            const hrCard = document.querySelector('.vital-sign-card:nth-child(2)');
            const tempCard = document.querySelector('.vital-sign-card:nth-child(3)');
            const spo2Card = document.querySelector('.vital-sign-card:nth-child(4)');
            
            // Reset all cards
            [bpCard, hrCard, tempCard, spo2Card].forEach(card => {
                card.classList.remove('critical', 'warning');
            });
            
            // Update Blood Pressure
            if (vitals.bloodPressure) {
                bpElement.textContent = vitals.bloodPressure;
                bpElement.classList.remove('text-text-secondary');
                bpElement.classList.add('text-text-primary');
                
                const [systolic, diastolic] = vitals.bloodPressure.split('/').map(Number);
                if (systolic > 140 || diastolic > 90) {
                    bpStatus.textContent = 'High';
                    bpStatus.className = 'text-xs text-error-600';
                    bpCard.classList.add('critical');
                } else if (systolic < 90 || diastolic < 60) {
                    bpStatus.textContent = 'Low';
                    bpStatus.className = 'text-xs text-warning-600';
                    bpCard.classList.add('warning');
                } else {
                    bpStatus.textContent = 'Normal';
                    bpStatus.className = 'text-xs text-success-600';
                }
            } else {
                bpElement.textContent = '--/--';
                bpElement.classList.remove('text-text-primary');
                bpElement.classList.add('text-text-secondary');
                bpStatus.textContent = 'Not Taken';
                bpStatus.className = 'text-xs text-warning-600';
            }
            
            // Update Heart Rate
            if (vitals.heartRate) {
                hrElement.textContent = vitals.heartRate;
                hrElement.classList.remove('text-text-secondary');
                hrElement.classList.add('text-text-primary');
                
                const hr = parseInt(vitals.heartRate);
                if (hr > 100) {
                    hrStatus.textContent = 'High';
                    hrStatus.className = 'text-xs text-error-600';
                    hrCard.classList.add('critical');
                } else if (hr < 60) {
                    hrStatus.textContent = 'Low';
                    hrStatus.className = 'text-xs text-warning-600';
                    hrCard.classList.add('warning');
                } else {
                    hrStatus.textContent = 'Normal';
                    hrStatus.className = 'text-xs text-success-600';
                }
            } else {
                hrElement.textContent = '--';
                hrElement.classList.remove('text-text-primary');
                hrElement.classList.add('text-text-secondary');
                hrStatus.textContent = 'Not Taken';
                hrStatus.className = 'text-xs text-warning-600';
            }
            
            // Update Temperature
            if (vitals.temperature) {
                hrElement.textContent = `${vitals.temperature}°C`;
                hrElement.classList.remove('text-text-secondary');
                hrElement.classList.add('text-text-primary');
                
                const temp = parseFloat(vitals.temperature);
                if (temp > 100.4) {
                    hrStatus.textContent = 'Fever';
                    hrStatus.className = 'text-xs text-error-600';
                    tempCard.classList.add('critical');
                } else if (temp < 96.8) {
                    hrStatus.textContent = 'Low';
                    hrStatus.className = 'text-xs text-warning-600';
                    tempCard.classList.add('warning');
                } else {
                    hrStatus.textContent = 'Normal';
                    hrStatus.className = 'text-xs text-success-600';
                }
            } else {
                tempElement.textContent = '--°C';
                tempElement.classList.remove('text-text-primary');
                tempElement.classList.add('text-text-secondary');
                tempStatus.textContent = 'Not Taken';
                tempStatus.className = 'text-xs text-warning-600';
            }
            
            // Update SpO2
            if (vitals.spo2) {
                spo2Element.textContent = `${vitals.spo2}%`;
                spo2Element.classList.remove('text-text-secondary');
                spo2Element.classList.add('text-text-primary');
                
                const spo2 = parseInt(vitals.spo2);
                if (spo2 < 92) {
                    spo2Status.textContent = 'Critical';
                    spo2Status.className = 'text-xs text-error-600';
                    spo2Card.classList.add('critical');
                } else if (spo2 < 95) {
                    spo2Status.textContent = 'Low';
                    spo2Status.className = 'text-xs text-warning-600';
                    spo2Card.classList.add('warning');
                } else {
                    spo2Status.textContent = 'Normal';
                    spo2Status.className = 'text-xs text-success-600';
                }
            } else {
                spo2Element.textContent = '--%';
                spo2Element.classList.remove('text-text-primary');
                spo2Element.classList.add('text-text-secondary');
                spo2Status.textContent = 'Not Taken';
                spo2Status.className = 'text-xs text-warning-600';
            }
        }

        // Update Queue UI
        function updateQueueUI() {
            const queueContainer = document.getElementById('patientQueue');
            const queueCount = document.getElementById('queueCount');
            
            // Count patients in each status
            let waitingCount = 0;
            let inProgressCount = 0;
            let completedCount = 0;
            
            Object.values(patients).forEach(patient => {
                if (patient.status === 'registration') waitingCount++;
                else if (patient.status === 'vitals') inProgressCount++;
                else if (patient.status === 'queue') completedCount++;
            });
            
                        
            Object.values(patients)
                .sort((a, b) => {
                    // Sort by priority (high first), then arrival time
                    const priorityOrder = { high: 3, medium: 2, low: 1 };
                    if (priorityOrder[b.priority] !== priorityOrder[a.priority]) {
                        return priorityOrder[b.priority] - priorityOrder[a.priority];
                    }
                    return a.arrivalTime.localeCompare(b.arrivalTime);
                })
                .forEach(patient => {
                    const queueItem = document.createElement('div');
                    queueItem.className = `queue-item p-3 rounded-base border border-border ${patient.id === activePatientId ? 'active' : ''}`;
                    queueItem.dataset.patientId = patient.id;
                    
                    const statusText = patient.status === 'registration' ? 'Registration' :
                                     patient.status === 'vitals' ? 'Vital Signs' :
                                     'Ready for Queue';
                    
                    const statusClass = patient.status === 'registration' ? 'status-waiting' :
                                      patient.status === 'vitals' ? 'status-in-progress' :
                                      'status-completed';
                    
                    const priorityClass = `priority-${patient.priority}`;
                    
                    queueItem.innerHTML = `
                        <div class="flex items-center gap-3">
                            <div class="patient-avatar ${
                                patient.priority === 'high' ? 'bg-error-100 text-error-700' :
                                patient.priority === 'medium' ? 'bg-warning-100 text-warning-700' :
                                'bg-success-100 text-success-700'
                            }">
                                ${patient.initials}
                            </div>
                            <div class="flex-1">
                                <p class="font-medium text-text-primary">${patient.firstName} ${patient.lastName}</p>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="text-xs text-text-secondary">${statusText}</span>
                                    <span class="status-indicator ${statusClass}"></span>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-xs text-text-secondary">${patient.arrivalTime}</p>
                                <span class="priority-indicator ${priorityClass}"></span>
                            </div>
                        </div>
                    `;
                    
                    
                    // Add click event
                    queueItem.addEventListener('click', function() {
                        loadPatient(patient.id);
                    });
                });
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            loadPatient(1);
            
            // Next Step Button
            document.getElementById('nextStepBtn').addEventListener('click', function() {
                if (currentStep < 3) {
                    currentStep++;
                    updateWorkflowProgress();
                }
            });
            
            // Skip Patient Button
            document.getElementById('skipPatientBtn').addEventListener('click', function() {
                // Find next patient in queue
                const patientIds = Object.keys(patients).map(Number);
                const currentIndex = patientIds.indexOf(activePatientId);
                const nextPatientId = patientIds[(currentIndex + 1) % patientIds.length];
                
                loadPatient(nextPatientId);
            });
            
            // Save Registration
            document.getElementById('saveRegistrationBtn').addEventListener('click', function() {
                const patient = patients[activePatientId];
                
                patient.firstName = document.getElementById('firstName').value;
                patient.lastName = document.getElementById('lastName').value;
                patient.age = parseInt(document.getElementById('age').value);
                patient.gender = document.getElementById('gender').value;
                patient.visitReason = document.getElementById('visitReason').value;
                patient.initials = patient.firstName[0] + patient.lastName[0];
                patient.status = 'vitals';
                
                // Move to next step
                currentStep = 2;
                updateWorkflowProgress();
                updateQueueUI();
                
                alert('Registration saved successfully!');
            });
            
            // Skip Registration
            document.getElementById('skipRegistrationBtn').addEventListener('click', function() {
                patients[activePatientId].status = 'vitals';
                currentStep = 2;
                updateWorkflowProgress();
                updateQueueUI();
            });
            
            // Save Vital Signs
            document.getElementById('saveVitalsBtn').addEventListener('click', function() {
                const patient = patients[activePatientId];
                
                patient.vitals = {
                    bloodPressure: document.getElementById('bloodPressure').value,
                    heartRate: document.getElementById('heartRate').value,
                    temperature: document.getElementById('temperature').value,
                    spo2: document.getElementById('spo2').value,
                    respiratoryRate: document.getElementById('respiratoryRate').value,
                    notes: document.getElementById('vitalNotes').value
                };
                
                patient.status = 'queue';
                
                // Move to next step
                currentStep = 2;
                updateWorkflowProgress();
                updateVitalMonitor();
                
                alert('Vital signs saved successfully!');
                
                // Check for critical values
                const hr = parseInt(patient.vitals.heartRate);
                const spo2 = parseInt(patient.vitals.spo2);
                
                if (hr > 120 || spo2 < 92) {
                    alert('CRITICAL ALERT: Patient has critical vital signs! Please review immediately.');
                }
            });
            
            
            
            // Auto-fill Normal Values
            document.getElementById('autoFillBtn').addEventListener('click', function() {
                document.getElementById('bloodPressure').value = '120/80';
                document.getElementById('heartRate').value = '72';
                document.getElementById('temperature').value = '98.6';
                document.getElementById('spo2').value = '98';
                document.getElementById('respiratoryRate').value = '16';
                document.getElementById('vitalNotes').value = 'Patient appears stable, all vital signs within normal range';
                
                updateVitalMonitor();
                alert('Normal vital signs values auto-filled.');
            });
            
            // Start New Session
            document.getElementById('startNewSessionBtn').addEventListener('click', function() {
                document.getElementById('newPatientModal').classList.remove('hidden');
            });
            
            // Close New Patient Modal
            document.getElementById('closeNewPatientModal').addEventListener('click', function() {
                document.getElementById('newPatientModal').classList.add('hidden');
            });
            
            document.getElementById('cancelNewPatientBtn').addEventListener('click', function() {
                document.getElementById('newPatientModal').classList.add('hidden');
            });
            
            // Add New Patient to Queue
            document.getElementById('addToQueueBtn').addEventListener('click', function() {
                const firstName = document.getElementById('newFirstName').value;
                const lastName = document.getElementById('newLastName').value;
                const age = document.getElementById('newAge').value;
                const gender = document.getElementById('newGender').value;
                const visitReason = document.getElementById('newVisitReason').value;
                const priority = document.getElementById('newPriority').value;
                
                if (!firstName || !lastName || !age || !gender || !visitReason) {
                    alert('Please fill in all required fields marked with *');
                    return;
                }
                
                const newId = Object.keys(patients).length + 1;
                const arrivalTime = new Date().toLocaleTimeString('en-US', { 
                    hour: '2-digit', 
                    minute: '2-digit' 
                });
                
                patients[newId] = {
                    id: newId,
                    firstName,
                    lastName,
                    age: parseInt(age),
                    gender,
                    priority,
                    visitReason,
                    vitals: {},
                    status: 'registration',
                    arrivalTime,
                    initials: firstName[0] + lastName[0]
                };
                
                // Clear form
                document.getElementById('newFirstName').value = '';
                document.getElementById('newLastName').value = '';
                document.getElementById('newAge').value = '';
                document.getElementById('newGender').value = '';
                document.getElementById('newVisitReason').value = '';
                document.getElementById('newPriority').value = 'medium';
                
                // Close modal and load new patient
                document.getElementById('newPatientModal').classList.add('hidden');
                loadPatient(newId);
                
                alert(`New patient ${firstName} ${lastName} added to queue!`);
            });
            
            // Emergency Alert System
            const emergencyAlertBtn = document.getElementById('emergencyAlertBtn');
            const emergencyModal = document.getElementById('emergencyModal');
            const cancelEmergencyBtn = document.getElementById('cancelEmergencyBtn');
            const triggerEmergencyBtn = document.getElementById('triggerEmergencyBtn');

            emergencyAlertBtn.addEventListener('click', function() {
                emergencyModal.classList.remove('hidden');
            });

            cancelEmergencyBtn.addEventListener('click', function() {
                emergencyModal.classList.add('hidden');
            });

            triggerEmergencyBtn.addEventListener('click', function() {
                const type = document.getElementById('emergencyType').value;
                const location = document.getElementById('emergencyLocation').value;
                
                if (!type || !location) {
                    alert('Please select emergency type and enter location');
                    return;
                }
                
                // In a real app, this would trigger emergency protocols
                console.log('Emergency triggered:', { type, location });
                
                alert(`EMERGENCY ALERT: ${type} at ${location}. Emergency services have been notified.`);
                
                // Reset and close modal
                document.getElementById('emergencyType').value = '';
                document.getElementById('emergencyLocation').value = '';
                emergencyModal.classList.add('hidden');
            });

            emergencyModal.addEventListener('click', function(e) {
                if (e.target === emergencyModal) {
                    emergencyModal.classList.add('hidden');
                }
            });

            document.getElementById('newPatientModal').addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.add('hidden');
                }
            });

            // Update queue count initially
            updateQueueUI();
        });

        // Simulate real-time updates
        function simulateRealTimeUpdates() {
            // Randomly update arrival times for demo purposes
            const now = new Date();
            const timeString = now.toLocaleTimeString('en-US', { 
                hour: '2-digit', 
                minute: '2-digit' 
            });
            
            document.getElementById('currentTime').textContent = timeString;
            document.getElementById('lastUpdateTime').textContent = timeString;
        }
        
        // Update every 30 seconds
        setInterval(simulateRealTimeUpdates, 30000);
    </script>
</body>
</html>