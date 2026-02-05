document.addEventListener("DOMContentLoaded", () => {
    const favoritesList = document.getElementById("favoritesList");
    const recentPatientsList = document.getElementById("recentPatientsList");
  
    if (!favoritesList || !recentPatientsList) return;
  
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
      favoritesList.innerHTML = `<div class="text-xs text-text-secondary">Loading favorites...</div>`;
      recentPatientsList.innerHTML = `<div class="text-xs text-text-secondary">Loading recent patients...</div>`;
  
      try {
        const res = await fetch("../backend/medical_staff_sidebar_lists.php", {
          credentials: "same-origin",
        });
        const data = await res.json();
        if (!data.ok) throw new Error(data.error || "Failed to load sidebar lists");
  
        const favorites = data.favorites || [];
        const recent = data.recent || [];
  
        // Favorites render
        if (!favorites.length) {
          favoritesList.innerHTML = `<div class="text-xs text-text-secondary">No favorites yet.</div>`;
        } else {
          favoritesList.innerHTML = favorites
            .map((p) => {
              const statusBadge =
                p.status === "active"
                  ? `<span class="badge badge-success">Active</span>`
                  : `<span class="badge badge-secondary">Inactive</span>`;
  
              const lastVisit = p.last_visit
                ? `<span class="text-xs text-text-tertiary">Last visit: ${escapeHtml(p.last_visit)}</span>`
                : `<span class="text-xs text-text-tertiary">No visits yet</span>`;
  
              return `
                <div class="card p-3 card-interactive cursor-pointer" data-patient-id="${escapeHtml(
                  p.patient_id
                )}">
                  <div class="flex items-start justify-between">
                    <div class="flex-1">
                      <div class="flex items-center gap-2">
                        <h4 class="text-sm font-medium text-text-primary">${escapeHtml(p.full_name)}</h4>
                        <svg class="w-4 h-4 text-warning" fill="currentColor" viewBox="0 0 20 20">
                          <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                      </div>
                      <p class="text-xs text-text-secondary mt-1">MRN: ${escapeHtml(p.mrn)}</p>
                      <div class="flex items-center gap-2 mt-2">
                        ${statusBadge}
                        ${lastVisit}
                      </div>
                    </div>
                  </div>
                </div>
              `;
            })
            .join("");
        }
  
        // Recent render
        if (!recent.length) {
          recentPatientsList.innerHTML = `<div class="text-xs text-text-secondary">No recent patients.</div>`;
        } else {
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
                      <h4 class="text-sm font-medium text-text-primary truncate">${escapeHtml(
                        p.full_name
                      )}</h4>
                      <p class="text-xs text-text-secondary">MRN: ${escapeHtml(p.mrn)}</p>
                    </div>
                    <span class="text-xs text-text-tertiary">${escapeHtml(timeAgo(p.last_viewed_at))}</span>
                  </div>
                </div>
              `;
            })
            .join("");
        }
      } catch (err) {
        console.error(err);
        favoritesList.innerHTML = `<div class="text-xs text-error">Failed to load favorites.</div>`;
        recentPatientsList.innerHTML = `<div class="text-xs text-error">Failed to load recent patients.</div>`;
      }
    }
  
    // Click handling for dynamically created cards (event delegation)
    
  
    loadSidebar();
  });
  