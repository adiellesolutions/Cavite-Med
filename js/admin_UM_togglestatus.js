document.addEventListener("click", function (e) {
    const btn = e.target.closest(".toggleStatusBtn");
    if (!btn) return;

    const userId = btn.dataset.id;
    const newStatus = btn.dataset.status;

    const confirmMsg =
        newStatus === "inactive"
            ? "Deactivate this user?"
            : "Activate this user?";

    if (!confirm(confirmMsg)) return;

    const formData = new FormData();
    formData.append("user_id", userId);
    formData.append("status", newStatus);

    fetch("../backend/admin_UM_togglestatus.php", {
        method: "POST",
        body: formData
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                location.reload(); // refresh table + badges
            } else {
                alert(data.message || "Status update failed");
            }
        })
        .catch(() => {
            alert("Server error");
        });
});
