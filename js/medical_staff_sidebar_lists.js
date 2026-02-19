document.addEventListener("DOMContentLoaded", () => {
  const recentPatientsList = document.getElementById("recentPatientsList");

  if (!recentPatientsList) return;

  function escapeHtml(str) {
    return String(str ?? "")
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");
  }

  function initials(name) {
    const parts = String(name || "").trim().split(/\s+/);
    const a = parts[0]?.[0] || "";
    const b = parts[1]?.[0] || "";
    return (a + b).toUpperCase() || "??";
  }

  function timeAgo(dateStr) {
    if (!dateStr) return "";
    const dt = new Date(dateStr.replace(" ", "T"));
    if (isNaN(dt)) return "";
    const diff = Math.floor((Date.now() - dt.getTime()) / 1000);
    if (diff < 60) return `${diff}s ago`;
    const m = Math.floor(diff / 60);
    if (m < 60) return `${m}m ago`;
    const h = Math.floor(m / 60);
    if (h < 24) return `${h}h ago`;
    const d = Math.floor(h / 24);
    return `${d}d ago`;
  }

  async function loadSidebar() {
    recentPatientsList.innerHTML =
      `<div class="text-xs text-text-secondary">Loading recent patients...</div>`;

    try {
      const res = await fetch("../backend/medical_staff_sidebar_lists.php", {
        credentials: "same-origin",
      });

      const data = await res.json();
      if (!data.ok) throw new Error(data.error || "Failed to load recent patients");

      const recent = data.recent || [];

      if (!recent.length) {
        recentPatientsList.innerHTML =
          `<div class="text-xs text-text-secondary">No recent patients.</div>`;
        return;
      }

      recentPatientsList.innerHTML = recent
        .map((p) => {
          return `
            <div class="card p-3 card-hover cursor-pointer" data-patient-id="${escapeHtml(
              p.patient_id
            )}">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-primary-100 text-primary-700 font-semibold flex items-center justify-center text-sm">
                  ${escapeHtml(initials(p.full_name))}
                </div>
                <div class="flex-1 min-w-0">
                  <h4 class="text-sm font-medium text-text-primary truncate">
                    ${escapeHtml(p.full_name)}
                  </h4>
                  <p class="text-xs text-text-secondary">
                    MRN: ${escapeHtml(p.mrn)}
                  </p>
                </div>
                <span class="text-xs text-text-tertiary">
                  ${escapeHtml(timeAgo(p.last_viewed_at))}
                </span>
              </div>
            </div>
          `;
        })
        .join("");
    } catch (err) {
      console.error(err);
      recentPatientsList.innerHTML =
        `<div class="text-xs text-error">Failed to load recent patients.</div>`;
    }
  }

  loadSidebar();
});
