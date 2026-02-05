document.addEventListener("DOMContentLoaded", () => {
    const els = {
      // header
      initials: document.getElementById("ph_initials"),
      name: document.getElementById("ph_name"),
      statusBadge: document.getElementById("ph_status_badge"),
      mrn: document.getElementById("ph_mrn"),
      dobAge: document.getElementById("ph_dob_age"),
      gender: document.getElementById("ph_gender"),
      blood: document.getElementById("ph_blood"),
      phone: document.getElementById("ph_phone"),
      email: document.getElementById("ph_email"),
      address: document.getElementById("ph_address"),
  
      // demographics tab
      demoFullName: document.getElementById("demo_full_name"),
      demoPreferred: document.getElementById("demo_preferred_name"),
      demoMarital: document.getElementById("demo_marital_status"),
      demoOccupation: document.getElementById("demo_occupation"),
      demoLanguage: document.getElementById("demo_language"),
  
      // medical info
      allergies: document.getElementById("med_allergies"),
      conditions: document.getElementById("med_conditions"),
      meds: document.getElementById("med_meds"),
      immun: document.getElementById("med_immunization"),
  
      // vitals tab
      v_bp: document.getElementById("v_bp"),
      v_hr: document.getElementById("v_hr"),
      v_temp: document.getElementById("v_temp"),
      v_spo2: document.getElementById("v_spo2"),
      v_rr: document.getElementById("v_rr"),
      v_bg: document.getElementById("v_bg"),
      v_weight: document.getElementById("v_weight"),
      v_notes: document.getElementById("v_notes"),
      v_recorded_at: document.getElementById("v_recorded_at"),
  
      // right panel timeline
      timeline: document.getElementById("visitTimeline"),
  
      // tabs content areas
      insuranceContent: document.getElementById("insurance_content"),
      emergencyContent: document.getElementById("emergency_content"),
      documentsContent: document.getElementById("documents_content"),
  
      // buttons to enable after selecting patient
      btnEditPatient: document.getElementById("btnEditPatient"),
      btnNewVisit: document.getElementById("btnNewVisit"),
    };
  
    function esc(s) {
      return String(s ?? "")
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
    }
  
    function setText(el, value, fallback = "—") {
      if (!el) return;
      el.textContent = (value === 0 || value) ? String(value) : fallback;
    }
  
    function initialsFrom(name) {
      const parts = String(name || "").trim().split(/\s+/);
      const a = parts[0]?.[0] || "";
      const b = parts[1]?.[0] || "";
      return (a + b).toUpperCase() || "--";
    }
  
    function formatDate(dateStr) {
      if (!dateStr) return "—";
      const d = new Date(dateStr);
      if (isNaN(d)) return "—";
      return d.toLocaleDateString(undefined, { year: "numeric", month: "short", day: "numeric" });
    }
  
    function formatDateTime(dateStr) {
      if (!dateStr) return "—";
      const d = new Date(String(dateStr).replace(" ", "T"));
      if (isNaN(d)) return "—";
      return d.toLocaleString(undefined, {
        year: "numeric", month: "short", day: "numeric",
        hour: "numeric", minute: "2-digit"
      });
    }
  
    function calcAge(dobStr) {
      if (!dobStr) return "";
      const dob = new Date(dobStr);
      if (isNaN(dob)) return "";
      const now = new Date();
      let age = now.getFullYear() - dob.getFullYear();
      const m = now.getMonth() - dob.getMonth();
      if (m < 0 || (m === 0 && now.getDate() < dob.getDate())) age--;
      return age >= 0 ? `${age}y` : "";
    }
  
    function setBadgeStatus(status) {
      if (!els.statusBadge) return;
      const s = String(status || "").toLowerCase();
      els.statusBadge.className = "badge " + (s === "active" ? "badge-success" : "badge-secondary");
      els.statusBadge.textContent = s ? (s === "active" ? "Active Patient" : "Inactive Patient") : "—";
    }
  
    function pills(container, text, kind = "secondary") {
      if (!container) return;
  
      const arr = String(text || "")
        .split(/,|\n/)
        .map(x => x.trim())
        .filter(Boolean);
  
      if (!arr.length) {
        container.innerHTML = `<span class="text-sm text-text-secondary">None</span>`;
        return;
      }
  
      const badgeClass =
        kind === "error" ? "badge badge-error" :
        kind === "warning" ? "badge badge-warning" :
        "badge badge-secondary";
  
      container.innerHTML = arr.map(x => `<span class="${badgeClass}">${esc(x)}</span>`).join(" ");
    }
  
    function listMeds(ul, text) {
      if (!ul) return;
  
      const arr = String(text || "")
        .split(/\n|,/)
        .map(x => x.trim())
        .filter(Boolean);
  
      if (!arr.length) {
        ul.innerHTML = `<li class="text-sm text-text-secondary">None</li>`;
        return;
      }
  
      ul.innerHTML = arr.map(x => `<li>• ${esc(x)}</li>`).join("");
    }
  
    function renderVitals(latest) {
      if (!latest) {
        setText(els.v_bp, "—");
        setText(els.v_hr, "—");
        setText(els.v_temp, "—");
        setText(els.v_spo2, "—");
        setText(els.v_rr, "—");
        setText(els.v_bg, "—");
        setText(els.v_weight, "—");
        setText(els.v_notes, "No vitals recorded yet.");
        setText(els.v_recorded_at, "");
        return;
      }
  
      const bp = (latest.bp_systolic && latest.bp_diastolic)
        ? `${latest.bp_systolic}/${latest.bp_diastolic}`
        : "—";
  
      setText(els.v_bp, bp);
      setText(els.v_hr, latest.heart_rate ?? "—");
      setText(els.v_temp, latest.temperature ?? "—");
      setText(els.v_spo2, latest.spo2 ?? "—");
      setText(els.v_rr, latest.respiratory_rate ?? "—");
      setText(els.v_bg, latest.blood_glucose ?? "—");
      setText(els.v_weight, latest.weight ?? "—");
      setText(els.v_notes, latest.nurse_notes ?? "—");
      setText(els.v_recorded_at, latest.recorded_at ? `Recorded: ${formatDateTime(latest.recorded_at)}` : "");
    }
  
    // RIGHT PANEL timeline
    function renderTimeline(visits) {
      if (!els.timeline) return;
  
      if (!visits || !visits.length) {
        els.timeline.innerHTML = `<div class="text-sm text-text-secondary">No visits yet.</div>`;
        return;
      }
  
      els.timeline.innerHTML = `
        <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-border"></div>
        ${visits.map((v, idx) => {
          const n = idx + 1;
          const status = String(v.status || "").toLowerCase();
          const badge =
            status === "completed" ? "badge badge-success" :
            status === "scheduled" ? "badge badge-primary" :
            "badge badge-secondary";
  
          return `
            <div class="relative pl-10">
              <div class="absolute left-0 w-8 h-8 rounded-full bg-primary text-white flex items-center justify-center text-xs font-semibold">
                ${n}
              </div>
  
              <div class="card p-4">
                <div class="flex items-start justify-between mb-2">
                  <div>
                    <h4 class="text-sm font-semibold text-text-primary">${esc(v.visit_type || "Visit")}</h4>
                    <p class="text-xs text-text-secondary">${esc(formatDateTime(v.visit_datetime))}</p>
                  </div>
                  <span class="${badge}">${esc(v.status || "—")}</span>
                </div>
                <p class="text-sm text-text-secondary mb-1">${esc(v.doctor_name || "—")}</p>
                <p class="text-xs text-text-tertiary">${esc(v.location_name || "")}</p>
              </div>
            </div>
          `;
        }).join("")}
      `;
    }
  
    // Insurance tab
    function renderInsurance(list) {
      if (!els.insuranceContent) return;
  
      if (!list || !list.length) {
        els.insuranceContent.innerHTML = `<div class="text-sm text-text-secondary">No insurance on file.</div>`;
        return;
      }
  
      const label = (t) => t === "primary" ? "Primary Insurance" : "Secondary Insurance";
      const badge = (s) => {
        const v = String(s || "").toLowerCase();
        return v === "verified"
          ? `<span class="badge badge-success">Verified</span>`
          : `<span class="badge badge-secondary">Unverified</span>`;
      };
  
      els.insuranceContent.innerHTML = list.map(ins => `
        <div class="border border-border rounded-base p-4 mb-4">
          <div class="flex items-center justify-between mb-3">
            <h3 class="text-base font-semibold text-text-primary">${esc(label(ins.coverage_type))}</h3>
            ${badge(ins.verified_status)}
          </div>
  
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
            <div>
              <p class="text-text-secondary">Provider</p>
              <p class="text-text-primary font-medium">${esc(ins.provider_name || "—")}</p>
            </div>
            <div>
              <p class="text-text-secondary">Policy Number</p>
              <p class="text-text-primary font-medium">${esc(ins.policy_number || "—")}</p>
            </div>
            <div>
              <p class="text-text-secondary">Group Number</p>
              <p class="text-text-primary font-medium">${esc(ins.group_number || "—")}</p>
            </div>
            <div>
              <p class="text-text-secondary">Effective Date</p>
              <p class="text-text-primary font-medium">${esc(ins.effective_date || "—")}</p>
            </div>
            <div>
              <p class="text-text-secondary">Subscriber</p>
              <p class="text-text-primary font-medium">${esc(ins.subscriber_name || "—")}</p>
            </div>
            <div>
              <p class="text-text-secondary">Relationship</p>
              <p class="text-text-primary font-medium">${esc(ins.relationship || "—")}</p>
            </div>
          </div>
  
          ${ins.verified_at ? `<div class="text-xs text-text-tertiary mt-3">Verified at: ${esc(ins.verified_at)}</div>` : ""}
        </div>
      `).join("");
    }
  
    // Emergency tab
    function renderEmergency(list) {
      if (!els.emergencyContent) return;
  
      if (!list || !list.length) {
        els.emergencyContent.innerHTML = `<div class="text-sm text-text-secondary">No emergency contacts on file.</div>`;
        return;
      }
  
      els.emergencyContent.innerHTML = list.map(c => `
        <div class="border border-border rounded-base p-4 mb-3">
          <div class="flex items-start justify-between">
            <div>
              <div class="text-base font-semibold text-text-primary">${esc(c.full_name || "—")}</div>
              <div class="text-sm text-text-secondary">${esc(c.relationship || "—")}</div>
            </div>
            ${Number(c.is_primary) === 1 ? `<span class="badge badge-primary">Primary</span>` : `<span class="badge badge-secondary">Contact</span>`}
          </div>
  
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-3 text-sm">
            <div>
              <p class="text-text-secondary">Phone</p>
              <p class="text-text-primary font-medium">${esc(c.phone || "—")}</p>
            </div>
            <div>
              <p class="text-text-secondary">Email</p>
              <p class="text-text-primary font-medium">${esc(c.email || "—")}</p>
            </div>
            <div class="md:col-span-2">
              <p class="text-text-secondary">Address</p>
              <p class="text-text-primary font-medium">${esc(c.address || "—")}</p>
            </div>
          </div>
        </div>
      `).join("");
    }
  
    // Documents tab
    function renderDocuments(list) {
      if (!els.documentsContent) return;
  
      if (!list || !list.length) {
        els.documentsContent.innerHTML = `<div class="text-sm text-text-secondary">No documents uploaded.</div>`;
        return;
      }
  
      els.documentsContent.innerHTML = `
        <div class="text-sm text-text-secondary mb-3">${list.length} document(s)</div>
        <div class="space-y-3">
          ${list.map(d => `
            <div class="border border-border rounded-base p-4 hover:bg-secondary-50 transition-colors">
              <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                  <div class="text-base font-medium text-text-primary">${esc(d.document_title || "Untitled")}</div>
                  <div class="text-sm text-text-secondary mt-1">
                    ${esc((d.file_type || "").toUpperCase() || "FILE")}
                    ${d.file_size_kb ? `• ${esc(d.file_size_kb)} KB` : ""}
                    ${d.uploaded_at ? `• ${esc(d.uploaded_at)}` : ""}
                    ${d.uploaded_by_name ? `• ${esc(d.uploaded_by_name)}` : ""}
                  </div>
                </div>
  
                ${d.file_path ? `
                  <a class="btn btn-outline btn-sm" href="${esc(d.file_path)}" target="_blank" rel="noopener">
                    View
                  </a>
                ` : ""}
              </div>
            </div>
          `).join("")}
        </div>
      `;
    }
  
    function renderAll(data) {
      const p = data.patient || {};
      const prof = data.profile || {};
      const latestVitals = data.latest_vitals || null;
  
      const fullName = [p.first_name, p.middle_name, p.last_name].filter(Boolean).join(" ");
      const pref = p.preferred_name || "";
  
      // ✅ THIS IS THE MOST IMPORTANT FIX (modal uses this)
      window.__selectedPatient = p;
  
      // ✅ enable buttons when patient is selected
      if (els.btnEditPatient) els.btnEditPatient.disabled = !p.patient_id;
      if (els.btnNewVisit) els.btnNewVisit.disabled = !p.patient_id;
  
      setText(els.name, fullName || "—");
      setText(els.initials, initialsFrom(fullName));
      setBadgeStatus(p.status);
  
      setText(els.mrn, p.mrn);
      setText(els.gender, p.gender);
      setText(els.blood, p.blood_type || "—");
  
      const age = calcAge(p.date_of_birth);
      setText(els.dobAge, `${formatDate(p.date_of_birth)}${age ? ` (${age})` : ""}`);
  
      setText(els.phone, p.phone);
      setText(els.email, p.email || "—");
      const addr = [p.address_line, p.city, p.state, p.zip_code].filter(Boolean).join(", ");
      setText(els.address, addr || "—");
  
      // demographics
      setText(els.demoFullName, fullName || "—");
      setText(els.demoPreferred, pref || "—");
      setText(els.demoMarital, p.marital_status || "—");
      setText(els.demoOccupation, p.occupation || "—");
      setText(els.demoLanguage, p.preferred_language || "—");
  
      // profile
      pills(els.allergies, prof.allergies, "error");
      pills(els.conditions, prof.chronic_conditions, "warning");
      listMeds(els.meds, prof.current_medications);
      setText(els.immun, prof.immunization_status || "unknown");
  
      // vitals
      renderVitals(latestVitals);
  
      // tabs
      renderInsurance(data.insurance || []);
      renderEmergency(data.emergency_contacts || []);
      renderDocuments(data.documents || []);
  
      // timeline
      renderTimeline(data.visits || []);
    }
  
    async function loadPatient(patientId) {
      if (!patientId) return;
  
      try {
        const res = await fetch(
          `../backend/medical_staff_get_patient_details.php?patient_id=${encodeURIComponent(patientId)}`,
          { credentials: "same-origin" }
        );
  
        const data = await res.json();
        if (!data.ok) throw new Error(data.error || "Failed to load patient details");
  
        renderAll(data);
  
        const url = new URL(window.location.href);
        url.searchParams.set("patient_id", patientId);
        window.history.replaceState({}, "", url.toString());
      } catch (err) {
        console.error(err);
        setText(els.name, "Failed to load patient");
      }
    }
  
    // ✅ expose loader globally so modal save can refresh
    window.loadPatient = loadPatient;
  
    // tab switching (keep yours, this is safe)
    const tabButtons = document.querySelectorAll("[data-tab]");
    tabButtons.forEach((btn) => {
      btn.addEventListener("click", () => {
        const tab = btn.getAttribute("data-tab");
  
        tabButtons.forEach((b) => b.classList.remove("nav-item-active", "border-b-2", "border-primary"));
        btn.classList.add("nav-item-active", "border-b-2", "border-primary");
  
        document.querySelectorAll(".tab-content").forEach((el) => el.classList.add("hidden"));
        const target = document.getElementById(`${tab}-tab`);
        if (target) target.classList.remove("hidden");
      });
    });
  
    const pid = new URL(window.location.href).searchParams.get("patient_id");
    if (pid) loadPatient(pid);
  
    document.addEventListener("click", (e) => {
      const card = e.target.closest("[data-patient-id]");
      if (!card) return;
      e.preventDefault();
  
      const patientId = card.getAttribute("data-patient-id");
      if (!patientId) return;
  
      loadPatient(patientId);
    });
  });
  