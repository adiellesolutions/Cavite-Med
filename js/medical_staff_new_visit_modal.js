document.addEventListener('DOMContentLoaded', function () {
    const startBtn = document.getElementById('startNewSessionBtn');
    const modal = document.getElementById('newVisitModal');
    const closeBtn = document.getElementById('closeNewVisitModal');
    const cancelBtn = document.getElementById('cancelNewVisitBtn');
    const addBtn = document.getElementById('addVisitToQueueBtn');
  
    const searchInput = document.getElementById('visitPatientSearch');
    const patientList = document.getElementById('patientList');
    const selectedNameSpan = document.getElementById('visitSelectedPatientName');
  
    const hiddenPatientId = document.getElementById('visitPatientId');
  
    const visitTypeEl = document.getElementById('visitType');
    const visitReasonEl = document.getElementById('visitReason');
    const visitPriorityEl = document.getElementById('visitPriority');
  
    let searchTimer = null;
  
    function openModal() {
      modal.classList.remove('hidden');
    }
  
    function closeModal() {
      modal.classList.add('hidden');
      // optional reset
      // resetVisitModal();
    }
  
    function resetVisitModal() {
      searchInput.value = '';
      hiddenPatientId.value = '';
      selectedNameSpan.textContent = 'None';
      patientList.style.display = 'none';
      patientList.innerHTML = '';
      visitTypeEl.value = '';
      visitReasonEl.value = '';
      visitPriorityEl.value = 'medium';
    }
  
    // Open / close
    if (startBtn) startBtn.addEventListener('click', openModal);
    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
  
    // Close modal when clicking backdrop
    modal.addEventListener('click', (e) => {
      if (e.target === modal) closeModal();
    });
  
    // Search patients (debounced)
    searchInput.addEventListener('input', function () {
      const q = this.value.trim();
  
      // Clear old selection whenever user types
      hiddenPatientId.value = '';
      selectedNameSpan.textContent = 'None';
  
      if (q.length < 2) {
        patientList.style.display = 'none';
        patientList.innerHTML = '';
        return;
      }
  
      clearTimeout(searchTimer);
      searchTimer = setTimeout(() => {
        fetch(`/CAVITE-MED/backend/medical_staff_create_visit.php?query=${encodeURIComponent(q)}`, {
          method: 'GET',
          headers: { 'Accept': 'application/json' }
        })
          .then(async (res) => {
            const text = await res.text(); // to debug if backend returns HTML
            try {
              return JSON.parse(text);
            } catch (e) {
              console.error('Backend did not return JSON:', text);
              throw new Error('Invalid JSON from server');
            }
          })
          .then((patients) => {
            patientList.innerHTML = '';
  
            if (!Array.isArray(patients) || patients.length === 0) {
              patientList.style.display = 'none';
              return;
            }
  
            patientList.style.display = 'block';
  
            patients.forEach((p) => {
              const opt = document.createElement('option');
              opt.value = p.patient_id; // ✅ real ID
              opt.textContent = `${p.first_name} ${p.last_name} (${p.mrn})`;
              patientList.appendChild(opt);
            });
          })
          .catch((err) => {
            console.error('Search error:', err);
            patientList.style.display = 'none';
            patientList.innerHTML = '';
          });
      }, 250);
    });
  
    // When user picks a patient from dropdown
    patientList.addEventListener('change', function () {
      const opt = this.options[this.selectedIndex];
      if (!opt) return;
  
      hiddenPatientId.value = opt.value; // ✅ store patient_id
      selectedNameSpan.textContent = opt.textContent;
  
      // show text in input for UX, but DO NOT treat it as ID
      searchInput.value = opt.textContent;
  
      // hide dropdown after select
      patientList.style.display = 'none';
    });
  
    // Create visit
    addBtn.addEventListener('click', function () {
      const patientId = hiddenPatientId.value; // ✅ use hidden ID
      const visitType = visitTypeEl.value;
      const visitReason = visitReasonEl.value.trim();
      const priority = visitPriorityEl.value;
  
      if (!patientId) {
        alert('Please select a patient from the search results.');
        return;
      }
      if (!visitType || !visitReason || !priority) {
        alert('Please fill in all required fields!');
        return;
      }
  
      const payload = {
        patient_id: Number(patientId),
        visit_type: visitType,
        visit_reason: visitReason,
        priority: priority,
        status: 'waiting'
      };
  
      fetch('/CAVITE-MED/backend/medical_staff_create_visit.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify(payload)
      })
        .then(async (res) => {
          const text = await res.text();
          try {
            return JSON.parse(text);
          } catch (e) {
            console.error('Backend did not return JSON:', text);
            throw new Error('Invalid JSON from server');
          }
        })
        .then((data) => {
          if (data.success) {
            closeModal();
            alert(`Visit added to queue. Visit ID: ${data.visit_id}`);
            // optional: refresh your queue here
            // loadTodayQueue();
          } else {
            alert(data.message || 'Failed to add visit to queue.');
          }
        })
        .catch((error) => {
          console.error('Error:', error);
          alert('Error while adding visit.');
        });
    });
  });
  