document.addEventListener("DOMContentLoaded", () => {
    console.log("[force_change_password] DOM loaded");

    const form = document.getElementById("changePasswordForm");

    if (!form) {
        console.warn("[force_change_password] changePasswordForm NOT found on this page");
        return;
    }

    console.log("[force_change_password] changePasswordForm found");

    form.addEventListener("submit", e => {
        e.preventDefault();
        console.log("[force_change_password] Form submitted");

        const formData = new FormData(form);

        console.log("[force_change_password] Sending request to backend...");

        fetch("../backend/force_change_password.php", {
            method: "POST",
            body: formData
        })
        .then(res => {
            console.log("[force_change_password] Response status:", res.status);
            return res.json();
        })
        .then(data => {
            console.log("[force_change_password] Response data:", data);

            if (!data.success) {
                console.warn("[force_change_password] Update failed:", data.message);
                alert(data.message || "Password update failed");
                return;
            }

            console.log("[force_change_password] Password updated successfully");

            alert("Password updated successfully");

            console.log("[force_change_password] Redirecting user, role =", data.role);

            switch (data.role) {
                case "admin":
                    window.location.href = "admin_dashboard.php";
                    break;
                case "doctor":
                    window.location.href = "doctor_patient_records.html";
                    break;
                case "medical_staff":
                    window.location.href = "medical_staff_dashboard.html";
                    break;
                case "encoder":
                    window.location.href = "encoder_inventory.php";
                    break;
                default:
                    console.warn("[force_change_password] Unknown role, redirecting to login");
                    window.location.href = "system_login_portal.html";
            }
        })
        .catch(err => {
            console.error("[force_change_password] Fetch error:", err);
            alert("Server error. Please try again.");
        });
    });
});
