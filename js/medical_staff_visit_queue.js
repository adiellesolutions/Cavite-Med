document.addEventListener('DOMContentLoaded', function () {

  let currentVisitId = null;
  let currentPatientId = null;

  const $ = (id) => document.getElementById(id);

  /* =============================
     SAFE JSON (shows real errors)
  ============================== */
  function safeJson(response) {
    return response.text().then(text => {
      let data;
      try { data = JSON.parse(text); }
      catch (e) { throw new Error("Invalid JSON from server: " + text); }

      if (!response.ok) {
        throw new Error(data.error || `HTTP ${response.status}`);
      }
      return data;
    });
  }

  function setBadgeText(el, text) {
    if (!el) return;
    el.textContent = text;
    el.classList.remove('hidden');
  }

  /* =============================
     RESET UI
  ============================== */
  function setNoActivePatient() {
    $('activePatientName').textContent = 'no active patient';
    $('activePatientAvatar').textContent = '--';
    $('activePatientAge').textContent = '--';
    $('activePatientGender').textContent = '--';
    $('activePatientArrival').textContent = '--';
    $('activePatientVisitType').textContent = '--';
    $('activePatientStatusText').textContent = 'select a patient from the queue';

    $('activePatientBadge').classList.add('hidden');
    $('nextStepBtn').disabled = true;

    currentVisitId = null;
    currentPatientId = null;

    if ($('activeVisitId')) $('activeVisitId').value = '';
    if ($('activePatientId')) $('activePatientId').value = '';

    // hide vitals form if open
    if ($('vitalSignsForm')) $('vitalSignsForm').classList.add('hidden');
  }

  /* =============================
     FETCH ACTIVE PATIENT
  ============================== */
  function fetchActivePatient() {
    fetch('/CAVITE-MED/backend/medical_staff_get_visit_queue.php?action=getActivePatient')
      .then(safeJson)
      .then(data => {

        if (data.ok && data.data && data.data.length > 0) {
          const p = data.data[0];

          currentVisitId = p.visit_id;
          currentPatientId = p.patient_id;

          if ($('activeVisitId')) $('activeVisitId').value = p.visit_id;
          if ($('activePatientId')) $('activePatientId').value = p.patient_id;

          $('activePatientName').textContent = `${p.first_name} ${p.last_name}`;
          $('activePatientAvatar').textContent = `${p.first_name?.[0] ?? ''}${p.last_name?.[0] ?? ''}`;
          $('activePatientAge').textContent = p.age ?? '--';
          $('activePatientGender').textContent = p.gender ?? '--';
          $('activePatientArrival').textContent = p.visit_datetime ? new Date(p.visit_datetime).toLocaleTimeString() : '--';
          $('activePatientVisitType').textContent = p.visit_type ?? '--';
          $('activePatientStatusText').textContent = p.status ?? '';

          setBadgeText($('activePatientBadge'), (p.status ?? 'waiting').replaceAll('_', ' '));

          // IMPORTANT: if already for_consultation, disable next step
          const status = (p.status ?? '').toLowerCase();
          $('nextStepBtn').disabled = (status === 'for_consultation');

        } else {
          setNoActivePatient();
        }

      })
      .catch(err => {
        console.error('Error fetching active patient:', err);
        setNoActivePatient();
      });
  }

  /* =============================
     FETCH QUEUE LIST (Waiting only)
     IMPORTANT: This assumes your backend list returns waiting patients.
     If backend still returns all statuses, we filter on frontend.
  ============================== */
  function fetchQueueList() {
    fetch('/CAVITE-MED/backend/medical_staff_get_visit_queue.php')
      .then(safeJson)
      .then(data => {

        const queueContainer = $('patientQueue');
        const countEl = $('queueCount');

        if (!queueContainer || !countEl) return;

        if (data.ok && data.data && data.data.length > 0) {

          // show ONLY waiting and in_progress (exclude for_consultation)
          const filtered = data.data.filter(p => {
            const s = (p.status ?? '').toLowerCase();
            return s === 'waiting' || s === 'in_progress';
          });

          countEl.textContent = filtered.length;
          queueContainer.innerHTML = '';

          if (filtered.length === 0) {
            queueContainer.innerHTML = `
              <div class="bg-secondary-50 rounded-base p-4 border border-border">
                <p class="text-sm text-text-secondary text-center">no patients in queue</p>
              </div>
            `;
            return;
          }

          filtered.forEach(patient => {

            const item = document.createElement('button');
            item.type = 'button';
            item.dataset.visitId = patient.visit_id;

            item.className =
              'w-full text-left bg-secondary-50 rounded-base p-4 border border-border hover:bg-secondary-100 transition';

            item.innerHTML = `
              <div class="flex items-center gap-3">
                <div class="patient-avatar bg-primary-100 text-primary-700">
                  ${(patient.first_name?.[0] ?? '')}${(patient.last_name?.[0] ?? '')}
                </div>
                <div class="flex-1">
                  <p class="font-medium text-text-primary">${patient.first_name} ${patient.last_name}</p>
                  <p class="text-xs text-text-secondary">${patient.visit_type ?? ''}</p>
                  <p class="text-xs text-text-tertiary">status: ${(patient.status ?? '').replaceAll('_',' ')}</p>
                </div>
              </div>
            `;

            item.addEventListener('click', function () {

              currentVisitId = patient.visit_id;
              currentPatientId = patient.patient_id;

              if ($('activeVisitId')) $('activeVisitId').value = patient.visit_id;
              if ($('activePatientId')) $('activePatientId').value = patient.patient_id;

              // highlight selected
              document.querySelectorAll('[data-visit-id]').forEach(el => el.classList.remove('ring-2', 'ring-primary-400'));
              item.classList.add('ring-2', 'ring-primary-400');

              // update active card
              $('activePatientName').textContent = `${patient.first_name} ${patient.last_name}`;
              $('activePatientAvatar').textContent = `${patient.first_name?.[0] ?? ''}${patient.last_name?.[0] ?? ''}`;
              $('activePatientAge').textContent = patient.age ?? '--';
              $('activePatientGender').textContent = patient.gender ?? '--';
              $('activePatientArrival').textContent = patient.visit_datetime ? new Date(patient.visit_datetime).toLocaleTimeString() : '--';
              $('activePatientVisitType').textContent = patient.visit_type ?? '--';

              setBadgeText($('activePatientBadge'), (patient.status ?? 'waiting').replaceAll('_', ' '));

              // if already for_consultation, do not allow next step
              const status = (patient.status ?? '').toLowerCase();
              $('nextStepBtn').disabled = (status === 'for_consultation');
            });

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
  fetchConsultationQueueList();

  /* =============================
     START VISIT (NEXT STEP)
  ============================== */
  $('nextStepBtn')?.addEventListener('click', function () {

    if (!currentVisitId) {
      alert('Please select a patient.');
      return;
    }

    fetch('/CAVITE-MED/backend/medical_staff_visit_workflow.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'start_visit', visit_id: currentVisitId })
    })
      .then(safeJson)
      .then(data => {

        if (!data.ok) {
          alert(data.error || 'Failed to start visit');
          return;
        }

        $('workflowPatientStatus').textContent = 'in progress';
        $('workflowVisitId').textContent = currentVisitId;
        $('workflowStartTime').textContent = new Date().toLocaleString();

        $('step1Badge').textContent = 'done';
        $('step1Meta').classList.remove('hidden');
        $('step2Badge').textContent = 'in progress';

        $('vitalSignsForm').classList.remove('hidden');
        if ($('cancelWorkflowBtn')) $('cancelWorkflowBtn').classList.remove('hidden');
if ($('finishVitalsBtnTop')) $('finishVitalsBtnTop').classList.remove('hidden');

        // update active badge too
        setBadgeText($('activePatientBadge'), 'in progress');
      })
      .catch(err => {
        console.error(err);
        alert('Error starting workflow.');
      });
  });

  /* =============================
     SAVE VITALS + SET STATUS FOR CONSULTATION
  ============================== */
  $('saveVitalsBtn')?.addEventListener('click', function () {

    if (!currentVisitId || !currentPatientId) {
      alert('No active visit.');
      return;
    }

    const bp = $('bloodPressure').value.trim();
    let sys = null, dia = null;

    if (bp.includes('/')) {
      const parts = bp.split('/');
      sys = parseInt(parts[0]) || null;
      dia = parseInt(parts[1]) || null;
    }

    const vitalsPayload = {
      visit_id: currentVisitId,
      patient_id: currentPatientId,
      bp_systolic: sys,
      bp_diastolic: dia,
      heart_rate: $('heartRate').value || null,
      temperature: $('temperature').value || null,
      spo2: $('spo2').value || null,
      respiratory_rate: $('respiratoryRate').value || null,
      blood_glucose: $('bloodGlucose').value || null,
      weight: $('weightKg').value || null,
      nurse_notes: $('vitalNotes').value || null
    };

    // 1) save vitals
    fetch('/CAVITE-MED/backend/medical_staff_save_vitals.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(vitalsPayload)
    })
      .then(safeJson)
      .then(data => {

        if (!data.ok) {
          alert(data.error || 'Failed to save vitals');
          return;
        }

        // 2) after saving vitals, set visit status = for_consultation
        return fetch('/CAVITE-MED/backend/medical_staff_visit_workflow.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            action: 'set_status',
            visit_id: currentVisitId,
            status: 'for_consultation'
          })
        }).then(safeJson);

      })
      .then(statusRes => {


        
        if (!statusRes || !statusRes.ok) {
          alert(statusRes?.error || 'Vitals saved but failed to update status.');
          return;
        }

        $('step2Badge').textContent = 'done';
        $('workflowPatientStatus').textContent = 'for consultation';
        setBadgeText($('activePatientBadge'), 'for consultation');

        // hide form to avoid saving again
        $('vitalSignsForm').classList.add('hidden');
        $('nextStepBtn').disabled = true;

        alert('Vitals saved. Patient moved to For Consultation.');


        // clear selected ids so you won't reuse the same patient again
currentVisitId = null;
currentPatientId = null;

if ($('activeVisitId')) $('activeVisitId').value = '';
if ($('activePatientId')) $('activePatientId').value = '';

setNoActivePatient(); // resets the left active card + disables buttons



        // refresh lists so patient disappears from waiting queue
        fetchQueueList();
        fetchActivePatient();

      })
      .catch(err => {
        console.error(err);
        alert('Error saving vitals.');
      });
  });
  /* =============================
     FETCH CONSULTATION QUEUE (for_consultation)
  ============================== */
  function fetchConsultationQueueList() {
    fetch('/CAVITE-MED/backend/medical_staff_get_visit_queue.php')
      .then(safeJson)
      .then(data => {
        const container = $('consultationQueue');
        if (!container) return;

        if (!(data.ok && data.data && data.data.length > 0)) {
          container.innerHTML = `
            <div class="bg-secondary-50 rounded-base p-4 border border-border">
              <p class="text-sm text-text-secondary text-center">no patients for consultation</p>
            </div>
          `;
          return;
        }

        const filtered = data.data.filter(p => (p.status ?? '').toLowerCase() === 'for_consultation');

        container.innerHTML = '';

        if (filtered.length === 0) {
          container.innerHTML = `
            <div class="bg-secondary-50 rounded-base p-4 border border-border">
              <p class="text-sm text-text-secondary text-center">no patients for consultation</p>
            </div>
          `;
          return;
        }

        filtered.forEach(patient => {
          const item = document.createElement('div');
          item.className = 'w-full text-left bg-secondary-50 rounded-base p-4 border border-border';

          item.innerHTML = `
            <div class="flex items-center gap-3">
              <div class="patient-avatar bg-primary-100 text-primary-700">
                ${(patient.first_name?.[0] ?? '')}${(patient.last_name?.[0] ?? '')}
              </div>
              <div class="flex-1">
                <p class="font-medium text-text-primary">${patient.first_name} ${patient.last_name}</p>
                <p class="text-xs text-text-secondary">${patient.visit_type ?? ''}</p>
                <p class="text-xs text-text-tertiary">status: ${(patient.status ?? '').replaceAll('_',' ')}</p>
              </div>
            </div>
          `;
          container.appendChild(item);
        });
      })
      .catch(err => console.error('Error fetching consultation queue:', err));
  }

  /* =============================
     INIT
  ============================== */
  fetchActivePatient();
  fetchQueueList();
  fetchConsultationQueueList();


});
