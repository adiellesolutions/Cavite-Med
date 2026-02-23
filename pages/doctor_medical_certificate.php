<?php
session_start();
require_once __DIR__ . "/../backend/get_assigned_health_center.php";

// ✅ require login
if (!isset($_SESSION['user_id'])) {
    header("Location: system_login_portal.html");
    exit;
}

// ✅ optional: require doctor role
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
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="Medical Certificate - Generate and manage medical certificates" />
    <title>Medical Certificate - CAVMED Portal</title>

    <link rel="stylesheet" href="../css/main.css" />

    <style>
        .certificate-preview {
            background: white;
            border: 2px solid #1e293b;
            padding: 2rem;
            font-family: 'Times New Roman', serif;
            min-height: 500px;
            position: relative;
        }
        .certificate-header {
            text-align: center;
            border-bottom: 2px solid #1e293b;
            padding-bottom: 1.5rem;
            margin-bottom: 2rem;
        }
        .certificate-body {
            line-height: 1.8;
            font-size: 16px;
        }
        .certificate-footer {
            margin-top: 4rem;
            border-top: 1px solid #666;
            padding-top: 1.5rem;
        }
        .stamp-placeholder {
            width: 150px;
            height: 150px;
            border: 2px dashed #cbd5e1;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #94a3b8;
            font-style: italic;
            margin: 0 auto;
        }
        .signature-line {
            width: 300px;
            border-bottom: 1px solid #1e293b;
            margin: 1rem auto;
        }
        .template-card {
            transition: all 0.2s ease;
            cursor: pointer;
        }
        .template-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }
        .template-card.active {
            border-color: #059669;
            background-color: #f0fdf4;
        }
        .duration-display {
            background-color: #f1f5f9;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-weight: 500;
        }
        .watermark {
            position: absolute;
            opacity: 0.1;
            font-size: 120px;
            font-weight: bold;
            color: #059669;
            transform: rotate(-63deg);
            top: 40%;
            left: -11%;
            white-space: nowrap;
            pointer-events: none;
        }
    </style>
</head>

<body class="bg-background min-h-screen flex flex-col">
    <!-- Header Section -->
    <header class="bg-surface border-b border-border py-4 px-6 shadow-sm sticky top-0 z-sticky">
        <div class="max-w-full mx-auto flex items-center justify-between">
            <div class="flex items-center gap-4">
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

            <!-- User Profile -->
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-3">
                    <div class="text-right hidden md:block">
                        <p class="text-sm font-medium text-text-primary">
                            <?php echo htmlspecialchars($_SESSION['name']); ?>
                        </p>
                        <p class="text-xs text-text-secondary">
                            <?php echo ucfirst(str_replace('_', ' ', $_SESSION['role'])); ?>
                        </p>
                    </div>

                    <img
                        src="/HIMS/<?php echo $_SESSION['profile_picture'] ?: 'uploads/profile/default.png'; ?>"
                        alt="User profile picture"
                        class="w-10 h-10 rounded-full object-cover border-2 border-primary"
                        onerror="this.src='/HIMS/uploads/profile/default.png'; this.onerror=null;"
                    />
                </div>
            </div>
        </div>
    </header>

    <!-- Navigation Tabs -->
    <nav class="bg-surface border-b border-border px-6 no-print">
        <div class="px-6">
            <div class="flex items-center gap-1 overflow-x-auto scrollbar-thin">
                <a href="doctor_prescription.php" class="nav-item">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    <span>e-Prescription</span>
                </a>

                <a href="doctor_medical_certificate.php" class="nav-item nav-item-active whitespace-nowrap">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span>Medical Certificates</span>
                </a>

                <a href="doctor_patient_records.php" class="nav-item">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
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
            <div class="flex items-center justify-between mb-6 pb-6 border-b border-border">
                <div></div>
                <div class="flex items-center gap-3">
                   <!-- <button type="button" id="viewHistoryBtn" class="btn btn-outline">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>History</span>
                    </button>

                     ✅ removed inline onclick, JS will handle 
                    <button type="button" id="printCertificateBtn" class="btn btn-primary">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                        </svg>
                        <span>Print Certificate</span>
                    </button>-->
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
                <!-- Left Panel -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Patient Selection -->
                    <div class="card">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-text-primary">Patient Information</h3>
                            <button type="button" id="selectPatientBtn" class="btn btn-outline btn-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                                <span>Select Patient</span>
                            </button>
                        </div>

                        <!-- ✅ made dynamic fields (optional), JS can fill these if you want later -->
                      <div id="selectedPatientInfo" class="hidden">
  <div class="flex items-start gap-4 p-4 bg-secondary-50 rounded-base">
    <div id="selectedPatientInitials"
         class="w-16 h-16 rounded-full bg-primary-100 text-primary-700 flex items-center justify-center font-semibold text-xl flex-shrink-0">
      --
    </div>

    <div class="flex-1">
      <h4 id="selectedPatientName" class="font-semibold text-text-primary">--</h4>
      <p class="text-sm text-text-secondary">Patient ID: <span id="selectedPatientMrn">--</span></p>

      <div class="flex items-center gap-4 mt-2 text-sm">
        <span class="text-text-secondary">Age: <span id="selectedPatientAge">--</span></span>
        <span class="text-text-secondary">Gender: <span id="selectedPatientGender">--</span></span>
        <span class="text-text-secondary">Blood Type: <span id="selectedPatientBlood">--</span></span>
      </div>
    </div>
  </div>
</div>


                        <div id="noPatientSelected" class="text-center py-8">
                            <svg class="w-16 h-16 mx-auto text-text-tertiary mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            <p class="text-text-secondary">No patient selected</p>
                            <p class="text-sm text-text-tertiary mt-1">Select a patient to create certificate</p>
                        </div>
                    </div>

                    <!-- Certificate Template Selection -->
                    <div class="card">
                        <h3 class="text-lg font-semibold text-text-primary mb-4">Certificate Template</h3>

                        <!-- ✅ IMPORTANT: add data-template attributes (JS reads these) -->
                        <div class="grid grid-cols-2 gap-3 mb-4">
                            <button type="button"
                                    class="template-card p-4 rounded-base border border-border hover:bg-secondary-50"
                                    data-template="medical_leave">
                                <div class="flex items-center justify-between mb-2">
                                    <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <p class="font-medium text-text-primary">Medical Leave</p>
                                <p class="text-xs text-text-secondary mt-1">For work/school absence</p>
                            </button>

                            <button type="button"
                                    class="template-card p-4 rounded-base border border-border hover:bg-secondary-50"
                                    data-template="fitness_certificate">
                                <div class="flex items-center justify-between mb-2">
                                    <svg class="w-6 h-6 text-warning-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <p class="font-medium text-text-primary">Fitness Certificate</p>
                                <p class="text-xs text-text-secondary mt-1">Return to work/school</p>
                            </button>

                            <button type="button"
                                    class="template-card p-4 rounded-base border border-border hover:bg-secondary-50"
                                    data-template="travel_clearance">
                                <div class="flex items-center justify-between mb-2">
                                    <svg class="w-6 h-6 text-success-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <p class="font-medium text-text-primary">Travel Clearance</p>
                                <p class="text-xs text-text-secondary mt-1">Medical clearance for travel</p>
                            </button>

                            <button type="button"
                                    class="template-card p-4 rounded-base border border-border hover:bg-secondary-50"
                                    data-template="sports_participation">
                                <div class="flex items-center justify-between mb-2">
                                    <svg class="w-6 h-6 text-accent-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <p class="font-medium text-text-primary">Sports Participation</p>
                                <p class="text-xs text-text-secondary mt-1">Medical clearance for sports</p>
                            </button>
                        </div>
                    </div>

                    <!-- Certificate Details Form -->
                  

                    <!-- Recent Certificates (dynamic) -->
<div class="card">
  <div class="flex items-center justify-between mb-4">
    <h3 class="text-lg font-semibold text-text-primary">Recent Certificates</h3>
    <button type="button" id="btnRefreshCertificates" class="btn btn-ghost btn-sm">
      refresh
    </button>
  </div>

  <!-- loading / empty states -->
  <div id="recentCertificatesStatus" class="text-sm text-text-secondary">
    select a patient to load recent certificates.
  </div>

  <!-- list -->
  <div id="recentCertificatesList" class="space-y-2 hidden"></div>
</div>

                </div>

                <!-- Right Panel -->
                <div class="lg:col-span-3">
                    <div class="card">
                    <h3 class="text-lg font-semibold text-text-primary mb-4">Certificate Details</h3>

<form id="certificateForm" class="space-y-4">
    <!-- ✅ required hidden inputs for JS -->
    <input type="hidden" id="selectedPatientId" value="" />
    <input type="hidden" id="selectedTemplateType" value="">

    <div>
        <label for="diagnosis" class="block text-sm font-medium text-text-primary mb-2">
            Diagnosis/Condition *
        </label>
        <select id="diagnosis" required class="input">
            <option value="">Select diagnosis...</option>
            <option value="acute-upper-respiratory">Acute Upper Respiratory Infection</option>
            <option value="influenza-like">Influenza-like Illness</option>
            <option value="gastroenteritis">Acute Gastroenteritis</option>
            <option value="migraine">Migraine Headache</option>
            <option value="back-pain">Acute Lower Back Pain</option>
            <option value="sprain-strain">Musculoskeletal Sprain/Strain</option>
            <option value="post-operative">Post-operative Recovery</option>
            <option value="stress-anxiety">Stress/Anxiety Disorder</option>
            <option value="other">Other (specify in notes)</option>
        </select>
    </div>

    <div>
        <label class="block text-sm font-medium text-text-primary mb-2">Leave Period *</label>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-xs text-text-secondary mb-1">From Date</label>
                <input type="date" id="fromDate" required class="input" />
            </div>
            <div>
                <label class="block text-xs text-text-secondary mb-1">To Date</label>
                <input type="date" id="toDate" required class="input" />
            </div>
        </div>
        <div class="mt-2 text-center">
            <span class="duration-display" id="durationDisplay">1 day</span>
        </div>
    </div>

    <div>
        <label for="restrictionLevel" class="block text-sm font-medium text-text-primary mb-2">
            Restriction Level
        </label>
        <select id="restrictionLevel" class="input">
            <option value="complete-rest">Complete Rest</option>
            <option value="modified-duties" selected>Modified Duties</option>
            <option value="light-duty">Light Duty Only</option>
            <option value="no-restriction">No Restrictions</option>
        </select>
    </div>

    <div>
        <label for="additionalInstructions" class="block text-sm font-medium text-text-primary mb-2">
            Additional Instructions
        </label>
        <textarea id="additionalInstructions" rows="3" class="input"
            placeholder="e.g., Patient advised to avoid strenuous activity, maintain hydration..."></textarea>
    </div>

    <div>
        <label for="followUpDate" class="block text-sm font-medium text-text-primary mb-2">
            Follow-up Date (Optional)
        </label>
        <input type="date" id="followUpDate" class="input" />
    </div>

    <div>
        <label class="block text-sm font-medium text-text-primary mb-2">Signature Options</label>
        <div class="space-y-2">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" id="includeDigitalStamp" checked
                    class="w-4 h-4 text-primary border-border rounded focus:ring-2 focus:ring-primary-500" />
                <span class="text-sm text-text-primary">Include Digital Stamp</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" id="includeQRCode" checked
                    class="w-4 h-4 text-primary border-border rounded focus:ring-2 focus:ring-primary-500" />
                <span class="text-sm text-text-primary">Include Verification QR Code</span>
            </label>
        </div>
    </div>

    <div class="flex gap-3 pt-4 border-t border-border">
        <button type="submit" class="btn btn-primary flex-1">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span>Generate Certificate</span>
        </button>
    </div>
    
</form>
                    </div>

                    <div class="your-box" style="margin-top:20px;"></div>

                   <!--      <div class="card">
                         <h3 class="text-lg font-semibold text-text-primary mb-4">Certificate Information</h3>
                              
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-text-secondary mb-1">Certificate Number</p>
                                <p class="font-medium text-text-primary" id="infoCertificateNo">--</p>
                            </div>
                            <div>
                                <p class="text-sm text-text-secondary mb-1">Status</p>
                                <span class="badge badge-success" id="infoStatus">Active</span>
                            </div>
                           <div>
                                <p class="text-sm text-text-secondary mb-1">Date Created</p>
                                <p class="font-medium text-text-primary" id="infoCreatedAt">--</p>
                            </div>
                            <div>
                                <p class="text-sm text-text-secondary mb-1">Valid Until</p>
                                <p class="font-medium text-text-primary" id="infoValidUntil">--</p>
                            </div>
                        </div>

                        <div class="mt-4 pt-4 border-t border-border">
                            <p class="text-sm text-text-secondary mb-2">Verification Details</p>
                            <div class="bg-secondary-50 p-3 rounded-base">
                                <p class="text-sm text-text-primary">
                                    Verification Code: <code class="font-mono" id="infoVerifyCode">--</code>
                                </p>
                                <p class="text-xs text-text-secondary mt-1">Scan QR code or enter code at verify page</p>
                            </div>
                        </div>-->
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
                        <path fill-rule="evenodd"
                              d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                              clip-rule="evenodd"/>
                    </svg>
                    <span class="text-sm text-text-secondary">HIPAA Compliant</span>
                </div>
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-success" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                              d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                              clip-rule="evenodd"/>
                    </svg>
                    <span class="text-sm text-text-secondary">Digitally Signed</span>
                </div>
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-success" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                              d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                              clip-rule="evenodd"/>
                    </svg>
                    <span class="text-sm text-text-secondary">Legal Validity</span>
                </div>
            </div>
            <div class="text-sm text-text-secondary text-center md:text-right">
                <p>© 2025 CAVMED Portal. Medical Certificate</p>
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
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="mb-4">
                <input type="text" id="patientSearchInput" placeholder="Search by name or patient ID..." class="input" />
            </div>

            <!-- ✅ JS will render buttons here -->
            <div id="patientResults" class="space-y-2"></div>

            <p id="patientEmptyState" class="hidden text-center text-text-secondary text-sm py-6">
                no patients found
            </p>
        </div>
    </div>

    <!-- ✅ IMPORTANT: load your separated JS file -->
    <script src="../js/doctor_medical_certificate.js" defer></script>
</body>
</html>
