document.addEventListener('DOMContentLoaded', function () {
    let currentVisitId = null;
  
    function safeJson(response) {
      if (!response.ok) throw new Error(`HTTP ${response.status}`);
      return response.json();
    }
  
    function setNoActivePatient() {
      document.getElementById('activePatientName').textContent = 'no active patient';
      document.getElementById('activePatientAvatar').textContent = '--';
      document.getElementById('activePatientAge').textContent = '--';
      document.getElementById('activePatientGender').textContent = '--';
      document.getElementById('activePatientArrival').textContent = '--';
      document.getElementById('activePatientVisitType').textContent = '--';
      document.getElementById('activePatientStatusText').textContent = 'select a patient from the queue';
  
      document.getElementById('activePatientBadge').classList.add('hidden');
  
      document.getElementById('skipPatientBtn').disabled = true;
      document.getElementById('nextStepBtn').disabled = true;
  
      currentVisitId = null;
    }
  
    function fetchActivePatient() {
      fetch('/CAVITE-MED/backend/medical_staff_get_visit_queue.php?action=getActivePatient')
        .then(safeJson)
        .then(data => {
          if (data.ok && data.data && data.data.length > 0) {
            const p = data.data[0];
  
            document.getElementById('activePatientName').textContent = `${p.first_name} ${p.last_name}`;
            document.getElementById('activePatientAvatar').textContent = `${p.first_name?.[0] ?? ''}${p.last_name?.[0] ?? ''}`;
            document.getElementById('activePatientAge').textContent = p.age ?? '--';
            document.getElementById('activePatientGender').textContent = p.gender ?? '--';
            document.getElementById('activePatientArrival').textContent =
              p.visit_datetime ? new Date(p.visit_datetime).toLocaleTimeString() : '--';
            document.getElementById('activePatientVisitType').textContent = p.visit_type ?? '--';
            document.getElementById('activePatientStatusText').textContent = p.status ?? '';
  
            // badge
            const badge = document.getElementById('activePatientBadge');
            badge.textContent = p.status ?? 'waiting';
            badge.classList.remove('hidden');
  
            document.getElementById('skipPatientBtn').disabled = false;
            document.getElementById('nextStepBtn').disabled = false;
  
            currentVisitId = p.visit_id;
          } else {
            setNoActivePatient();
          }
        })
        .catch(err => {
          console.error('Error fetching active patient:', err);
          setNoActivePatient();
        });
    }
  
    function fetchQueueList() {
      fetch('/CAVITE-MED/backend/medical_staff_get_visit_queue.php')
        .then(safeJson)
        .then(data => {
          const queueContainer = document.getElementById('patientQueue');
          const countEl = document.getElementById('queueCount');
  
          if (data.ok && data.data && data.data.length > 0) {
            countEl.textContent = data.data.length;
  
            queueContainer.innerHTML = '';
  
            data.data.forEach(patient => {
              const item = document.createElement('div');
              item.classList.add('bg-secondary-50', 'rounded-base', 'p-4', 'border', 'border-border');
  
              item.innerHTML = `
                <div class="flex items-center gap-3">
                  <div class="patient-avatar bg-primary-100 text-primary-700">
                    ${(patient.first_name?.[0] ?? '')}${(patient.last_name?.[0] ?? '')}
                  </div>
                  <div class="flex-1">
                    <p class="font-medium text-text-primary">${patient.first_name} ${patient.last_name}</p>
                    <p class="text-xs text-text-secondary">${patient.visit_type ?? ''}</p>
                  </div>
                </div>
              `;
  
              queueContainer.appendChild(item);
            });
          } else {
            countEl.textContent = '0';
            queueContainer.innerHTML = `
              <div class="bg-secondary-50 rounded-base p-4 border border-border">
                <p class="text-sm text-text-secondary text-center">no patients in queue</p>
              </div>
            `;
          }
        })
        .catch(err => console.error('Error fetching queue list:', err));
    }
  
    fetchActivePatient();
    fetchQueueList();
  
    document.getElementById('skipPatientBtn').addEventListener('click', function () {
      alert('Skipping current patient...');
    });
  
    document.getElementById('nextStepBtn').addEventListener('click', function () {
      alert('Moving patient to next step...');
    });
  });
  