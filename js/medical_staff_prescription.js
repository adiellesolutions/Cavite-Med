(() => {
    const $ = (id) => document.getElementById(id);
    const api = "../backend/medical_staff_prescription_api.php";
  
    let selected = null; // { meta, items }
    let pending = null;  // { prescription_id, item_id, qty }
  
    const search = $("globalPatientSearch");
    const clearBtn = $("clearGlobalSearch");
    const filterStatus = $("filterStatus");
    const refreshBtn = $("refreshBtn");
  
    const listEl = $("prescriptionList");
    const listEmpty = $("prescriptionListEmpty");
  
    const detailsEl = $("prescriptionDetails");
    const emptyDetails = $("noPrescriptionSelected");
    const printBtn = $("printPrescriptionBtn");
  
    const recentEl = $("recentDispensing");
    const recentEmpty = $("recentDispensingEmpty");
  
    const modal = $("dispensingModal");
    const modalSummary = $("modalSummary");
    const closeModalBtn = $("closeDispensingModal");
    const cancelModalBtn = $("cancelDispensingBtn");
    const confirmModalBtn = $("confirmDispensingBtn");
    const notesEl = $("adminNotes");
    const verifyEl = $("confirmVerification");
  
    function esc(s) {
      return String(s ?? "").replace(/[&<>"']/g, c =>
        ({ "&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#39;" }[c])
      );
    }
  
    async function fetchJSON(url, opts) {
      const res = await fetch(url, opts);
      const data = await res.json().catch(() => null);
      if (!res.ok || !data?.ok) throw new Error(data?.error || "Request failed");
      return data;
    }
  
    function badge(status) {
      status = String(status || "").toLowerCase();
      if (status === "dispensed") return `<span class="badge badge-success">Dispensed</span>`;
      if (status === "cancelled") return `<span class="badge badge-error">Cancelled</span>`;
      return `<span class="badge badge-warning">Active</span>`;
    }
  
    // -------- LIST ----------
    async function loadList() {
      const q = search.value.trim();
      const st = filterStatus.value;
  
      const data = await fetchJSON(`${api}?action=list&status=${encodeURIComponent(st)}&q=${encodeURIComponent(q)}`);
      const rows = data.data || [];
  
      listEl.innerHTML = "";
      if (!rows.length) {
        listEmpty.classList.remove("hidden");
        return;
      }
      listEmpty.classList.add("hidden");
  
      rows.forEach(r => {
        const btn = document.createElement("button");
        btn.type = "button";
        btn.className = "prescription-select-btn w-full text-left p-4 rounded-base border transition-colors hover:bg-secondary-50";
        btn.innerHTML = `
          <div class="flex items-start justify-between gap-3">
            <div class="flex-1">
              <p class="font-medium text-text-primary">${esc(r.patient_name)}</p>
              <p class="text-sm text-text-secondary">MRN: ${esc(r.mrn)} • RX#: ${esc(r.prescription_number)}</p>
              <p class="text-xs text-text-secondary mt-1">${esc(r.created_at)}</p>
            </div>
            <div>${badge(r.status)}</div>
          </div>
        `;
        btn.addEventListener("click", () => selectPrescription(r.prescription_id));
        listEl.appendChild(btn);
      });
    }
  
    // -------- DETAILS ----------
    async function selectPrescription(prescription_id) {
      const data = await fetchJSON(`${api}?action=details&prescription_id=${encodeURIComponent(prescription_id)}`);
      selected = data;
      renderDetails();
    }
  
    function renderDetails() {
      if (!selected) {
        detailsEl.classList.add("hidden");
        emptyDetails.classList.remove("hidden");
        printBtn.disabled = true;
        return;
      }
      const { meta, items } = selected;
      printBtn.disabled = false;
  
      emptyDetails.classList.add("hidden");
      detailsEl.classList.remove("hidden");
  
      const itemsHtml = items.map(it => {
        const max = Number(it.qty_remaining);
        const disabled = max <= 0 ? "disabled" : "";
        return `
          <div class="bg-white rounded-base border border-border p-4">
            <div class="flex items-start justify-between mb-2">
              <div>
                <p class="text-lg font-semibold text-text-primary">${esc(it.medicine_name)}</p>
                <p class="text-sm text-text-secondary">${esc(it.category || "")}</p>
              </div>
              <span class="badge ${Number(it.current_stock)>0 ? "badge-success" : "badge-error"}">
                ${Number(it.current_stock)>0 ? "In Stock" : "Out of Stock"}
              </span>
            </div>
  
            <div class="grid grid-cols-2 gap-4 text-sm">
              <div><p class="text-text-secondary">Dosage</p><p class="font-medium">${esc(it.dosage_amount)}${esc(it.dosage_unit)}</p></div>
              <div><p class="text-text-secondary">Frequency</p><p class="font-medium">${esc(it.frequency_template)}</p></div>
              <div><p class="text-text-secondary">Route</p><p class="font-medium">${esc(it.route_admin)}</p></div>
              <div><p class="text-text-secondary">Duration</p><p class="font-medium">${esc(it.duration_amount)} ${esc(it.duration_unit)}</p></div>
            </div>
  
            <div class="mt-4 pt-4 border-t border-border grid grid-cols-3 gap-3 text-sm">
              <div><p class="text-text-secondary">Prescribed</p><p class="font-semibold">${esc(it.qty_prescribed)}</p></div>
              <div><p class="text-text-secondary">Dispensed</p><p class="font-semibold">${esc(it.qty_dispensed)}</p></div>
              <div><p class="text-text-secondary">Remaining</p><p class="font-semibold">${esc(it.qty_remaining)}</p></div>
            </div>
  
            <div class="mt-4 flex items-center gap-3">
              <input class="input w-28 text-center" type="number" min="1" max="${esc(max)}" value="1" data-qty="${esc(it.item_id)}" ${disabled}>
              <button class="btn btn-primary" type="button" data-btn="${esc(it.item_id)}" ${disabled}>Record Dispensing</button>
            </div>
          </div>
        `;
      }).join("");
  
      detailsEl.innerHTML = `
        <div class="bg-secondary-50 rounded-base p-4">
          <div class="flex items-center justify-between">
            <div>
              <h4 class="text-xl font-semibold text-text-primary">Electronic Prescription</h4>
              <p class="text-text-secondary">${badge(meta.status)}</p>
            </div>
            <div class="text-right text-sm text-text-secondary">
              <p>RX #: <span class="font-semibold">${esc(meta.prescription_number)}</span></p>
              <p>Date: <span class="font-medium">${esc(meta.created_at)}</span></p>
            </div>
          </div>
        </div>
  
        <div class="grid grid-cols-2 gap-6">
          <div>
            <h5 class="font-semibold text-text-primary mb-2">Patient</h5>
            <p class="text-sm"><span class="text-text-secondary">Name:</span> <span class="font-medium">${esc(meta.patient_name)}</span></p>
            <p class="text-sm"><span class="text-text-secondary">MRN:</span> <span class="font-medium">${esc(meta.mrn)}</span></p>
            <p class="text-sm"><span class="text-text-secondary">Gender:</span> <span class="font-medium">${esc(meta.gender)}</span></p>
          </div>
          <div>
            <h5 class="font-semibold text-text-primary mb-2">Prescriber</h5>
            <p class="text-sm"><span class="text-text-secondary">Doctor:</span> <span class="font-medium">${esc(meta.doctor_name)}</span></p>
          </div>
        </div>
  
        <div class="border-t border-border pt-6">
          <h5 class="font-semibold text-text-primary mb-4">Medication Information</h5>
          <div class="space-y-4">${itemsHtml}</div>
        </div>
  
        ${meta.special_instructions ? `
          <div class="border-t border-border pt-6">
            <h5 class="font-semibold text-text-primary mb-2">Special Instructions</h5>
            <div class="bg-secondary-50 rounded-base p-4 text-sm">${esc(meta.special_instructions)}</div>
          </div>` : ""}
      `;
  
      // attach handlers
      items.forEach(it => {
        const btn = detailsEl.querySelector(`[data-btn="${it.item_id}"]`);
        if (!btn) return;
        btn.addEventListener("click", () => openModal(it.item_id));
      });
    }
  
    function openModal(item_id) {
      const { meta, items } = selected;
      const it = items.find(x => Number(x.item_id) === Number(item_id));
      const qtyInput = detailsEl.querySelector(`[data-qty="${item_id}"]`);
      const qty = Number(qtyInput?.value || 0);
  
      if (!qty || qty <= 0) return alert("Enter qty > 0");
      if (qty > Number(it.qty_remaining)) return alert("Qty exceeds remaining");
  
      pending = { prescription_id: meta.prescription_id, item_id: it.item_id, qty };
  
      modalSummary.innerHTML = `
        <p class="font-semibold text-text-primary">${esc(meta.patient_name)}</p>
        <p class="text-sm text-text-secondary">RX#: ${esc(meta.prescription_number)}</p>
        <div class="mt-3 text-sm space-y-1">
          <div class="flex justify-between"><span class="text-text-secondary">Medication:</span><span class="font-medium">${esc(it.medicine_name)}</span></div>
          <div class="flex justify-between"><span class="text-text-secondary">Dispense now:</span><span class="font-semibold text-primary">${esc(qty)}</span></div>
        </div>
      `;
  
      notesEl.value = "";
      verifyEl.checked = false;
      modal.classList.remove("hidden");
    }
  
    function closeModal() {
      modal.classList.add("hidden");
      pending = null;
    }
  
    async function confirmDispense() {
      if (!pending) return;
      if (!verifyEl.checked) return alert("Please confirm verification.");
  
      await fetchJSON(`${api}?action=dispense`, {
        method: "POST",
        headers: { "Content-Type":"application/json" },
        body: JSON.stringify({
          prescription_id: pending.prescription_id,
          item_id: pending.item_id,
          qty: pending.qty,
          notes: notesEl.value || ""
        })
      });
  
      closeModal();
      await selectPrescription(selected.meta.prescription_id);
      await loadList();
      await loadRecent();
      alert("Dispensing recorded.");
    }
  
    // -------- RECENT ----------
    async function loadRecent() {
      const data = await fetchJSON(`${api}?action=recent`);
      const rows = data.data || [];
  
      recentEl.innerHTML = "";
      if (!rows.length) {
        recentEmpty.classList.remove("hidden");
        return;
      }
      recentEmpty.classList.add("hidden");
  
      rows.forEach(r => {
        const div = document.createElement("div");
        div.className = "flex items-center justify-between p-3 rounded-base bg-secondary-50";
        div.innerHTML = `
          <div>
            <p class="text-sm font-medium text-text-primary">${esc(r.medicine_name)} dispensed</p>
            <p class="text-xs text-text-secondary">${esc(r.patient_name)} • ${esc(r.dispensed_qty)} • ${esc(r.dispensed_at)}</p>
          </div>
          <span class="badge badge-success">${esc(r.prescription_number)}</span>
        `;
        recentEl.appendChild(div);
      });
    }
  
    // events
    search.addEventListener("input", () => {
      clearBtn.classList.toggle("hidden", search.value.length === 0);
      clearTimeout(search._t);
      search._t = setTimeout(loadList, 250);
    });
  
    clearBtn.addEventListener("click", () => {
      search.value = "";
      clearBtn.classList.add("hidden");
      loadList();
    });
  
    filterStatus.addEventListener("change", loadList);
    refreshBtn.addEventListener("click", () => { loadList(); loadRecent(); });
  
    closeModalBtn.addEventListener("click", closeModal);
    cancelModalBtn.addEventListener("click", closeModal);
    modal.addEventListener("click", (e) => { if (e.target === modal) closeModal(); });
    confirmModalBtn.addEventListener("click", confirmDispense);
  
    printBtn.addEventListener("click", () => window.print());
  
    // init
    (async () => {
      detailsEl.classList.add("hidden");
      await loadList();
      await loadRecent();
    })();
  })();
  