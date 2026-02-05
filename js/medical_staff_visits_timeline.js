document.addEventListener("DOMContentLoaded", () => {
    const timeline = document.getElementById("visitTimeline");
    if (!timeline) return;
  
    function esc(s) {
      return String(s ?? "")
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
    }
  
    function formatDateTime(dtStr) {
      if (!dtStr) return "—";
      const d = new Date(dtStr.replace(" ", "T"));
      if (isNaN(d)) return "—";
      return d.toLocaleString(undefined, {
        year: "numeric",
        month: "short",
        day: "numeric",
        hour: "numeric",
        minute: "2-digit",
      });
    }
  
    function badgeClass(status) {
      const s = String(status || "").toLowerCase();
      if (s === "completed") return "badge badge-success";
      if (s === "scheduled") return "badge badge-primary";
      if (s === "cancelled") return "badge badge-secondary";
      return "badge badge-secondary";
    }
  
    function dotClass(status) {
      const s = String(status || "").toLowerCase();
      if (s === "completed") return "bg-success";
      if (s === "scheduled") return "bg-primary";
      if (s === "cancelled") return "bg-secondary-300";
      return "bg-secondary-300";
    }
  
    function render(visits) {
      if (!visits || !visits.length) {
        timeline.innerHTML = `<div class="text-sm text-text-secondary">No visits yet.</div>`;
        return;
      }
  
      timeline.innerHTML = `
        <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-border"></div>
        ${visits
          .map((v, idx) => {
            return `
              <div class="relative pl-10">
                <div class="absolute left-0 w-8 h-8 rounded-full ${dotClass(
                  v.status
                )} text-white flex items-center justify-center text-xs font-semibold">
                  ${idx + 1}
                </div>
  
                <div class="card p-4">
                  <div class="flex items-start justify-between mb-2">
                    <div>
                      <h4 class="text-sm font-semibold text-text-primary">${esc(
                        v.visit_type || "Visit"
                      )}</h4>
                      <p class="text-xs text-text-secondary">${esc(
                        formatDateTime(v.visit_datetime)
                      )}</p>
                    </div>
                    <span class="${badgeClass(v.status)}">${esc(
              v.status || "—"
            )}</span>
                  </div>
  
                  <p class="text-sm text-text-secondary mb-1">${esc(
                    v.doctor_name || "—"
                  )}</p>
  
                  ${
                    v.location_name
                      ? `<p class="text-xs text-text-tertiary">${esc(v.location_name)}</p>`
                      : ""
                  }
  
                  ${
                    v.notes
                      ? `<p class="text-sm text-text-secondary mt-3">${esc(v.notes)}</p>`
                      : ""
                  }
                </div>
              </div>
            `;
          })
          .join("")}
      `;
    }
  
    async function loadVisits(patientId) {
      if (!patientId) return;
  
      timeline.innerHTML = `<div class="text-sm text-text-secondary">Loading visits...</div>`;
  
      try {
        const res = await fetch(
          `../backend/medical_staff_get_patient_visits.php?patient_id=${encodeURIComponent(
            patientId
          )}`,
          { credentials: "same-origin" }
        );
  
        const data = await res.json();
        if (!data.ok) throw new Error(data.error || "Failed to load visits");
  
        render(data.visits || []);
      } catch (err) {
        console.error(err);
        timeline.innerHTML = `<div class="text-sm text-error">Failed to load visits.</div>`;
      }
    }
  
    // ✅ Listen when user clicks any patient card (recent/search/sidebar)
    document.addEventListener("click", (e) => {
      const card = e.target.closest("[data-patient-id]");
      if (!card) return;
  
      const pid = card.getAttribute("data-patient-id");
      if (!pid) return;
  
      loadVisits(pid);
    });
  
    // ✅ Auto load if URL has patient_id
    const pid = new URL(window.location.href).searchParams.get("patient_id");
    if (pid) loadVisits(pid);
  
    // ✅ Allow other JS to trigger it manually
    window.loadVisitTimeline = loadVisits;
  });
  