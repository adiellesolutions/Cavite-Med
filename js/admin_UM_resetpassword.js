document.addEventListener("click", function (e) {
    const btn = e.target.closest(".resetPasswordBtn");
    if (!btn) return;

    const userId = btn.dataset.id;

    if (!confirm("Reset password to default?")) return;

    const formData = new FormData();
    formData.append("user_id", userId);

    fetch("../backend/admin_UM_resetpassword.php", {
        method: "POST",
        body: formData
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert("Password reset successfully");
            } else {
                alert(data.message || "Password reset failed");
            }
        })
        .catch(() => {
            alert("Server error");
        });
});
