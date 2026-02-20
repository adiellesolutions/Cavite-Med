// ../js/medical_staff_dashboard.js
(() => {
    const $ = (id) => document.getElementById(id);
  
    function escapeHtml(str) {
      if (str === null || str === undefined) return "";
      return String(str)
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
    }
  
    function formatTime(dt) {
      // dt from MySQL: "YYYY-MM-DD HH:MM:SS"
      const d = new Date(dt.replace(" ", "T"));
      if (isNaN(d.getTime())) return "";
      return d.toLocaleTimeString("en-US", { hour: "2-digit", minute: "2-digit", hour12: true });
    }
  
    function setText(id, val) {
      const el = $(id);
      if (el) el.textContent = String(val ?? 0);
    }
  
    function renderRecent(list) {
      const wrap = $("recentActivityList");
      const empty = $("recentActivityEmpty");
  
      if (!wrap) return;
  
      wrap.innerHTML = "";
      if (!Array.isArray(list) || list.length === 0) {
        if (empty) empty.classList.remove("hidden");
        return;
      }
      if (empty) empty.classList.add("hidden");
  
      list.forEach((a) => {
        const time = formatTime(a.activity_time);
        const type = a.type;
  
        const icon =
          type === "registered"
            ? `
            <svg class="w-4 h-4 text-accent-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
            </svg>`
            : `
            <svg class="w-4 h-4 text-success-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>`;
  
        const badgeClass = type === "registered" ? "bg-accent-100" : "bg-success-100";
        const text =
          type === "registered" ? "registered as new patient" : "vitals recorded";
  
        const div = document.createElement("div");
        div.className = "flex items-center gap-3 p-3 bg-secondary-50 rounded-base";
        div.innerHTML = `
          <div class="w-8 h-8 rounded-full ${badgeClass} flex items-center justify-center">
            ${icon}
          </div>
          <div class="flex-1">
            <p class="text-sm text-text-primary">
              ${escapeHtml(a.name)} ${text}
            </p>
            <p class="text-xs text-text-secondary">${escapeHtml(time)}</p>
          </div>
        `;
        wrap.appendChild(div);
      });
    }
  
    async function loadDashboard() {
      try {
        const res = await fetch("../backend/medical_staff_dashboard_data.php", {
          credentials: "same-origin",
        });
        const data = await res.json();
  
        if (!data.ok) throw new Error(data.error || "Failed to load dashboard");
  
        const s = data.stats || {};
        setText("statPendingDispensing", s.pending_dispensing);
        setText("statCompletedToday", s.completed_today);
        setText("statRequiringAttention", s.requiring_attention);
        setText("statTotalPatients", s.total_patients);
  
        setText("statPatientsToday", s.patients_today);
        setText("statVitalsToday", s.vitals_today);
  
        renderRecent(data.recent_activity || []);
      } catch (e) {
        console.error(e);
        // Optional: show fallback
        const errEl = $("dashboardError");
        if (errEl) {
          errEl.classList.remove("hidden");
          errEl.textContent = e.message || "Failed to load dashboard";
        }
      }
    }
  
    document.addEventListener("DOMContentLoaded", () => {
      loadDashboard();
      // refresh every 30s if you want
      setInterval(loadDashboard, 30000);
    });
  })();
  