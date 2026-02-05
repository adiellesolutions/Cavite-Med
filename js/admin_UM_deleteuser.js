document.addEventListener("click", function (e) {
    const btn = e.target.closest(".deleteUserBtn");
    if (!btn) return;

    const userId = btn.dataset.id;

    if (!confirm("Are you sure you want to delete this user?")) return;

    const formData = new FormData();
    formData.append("user_id", userId);

    fetch("../backend/admin_UM_deleteuser.php", {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert("User deleted successfully");
            location.reload();
        } else {
            alert(data.message || "Delete failed");
        }
    })
    .catch(() => {
        alert("Server error");
    });
});
