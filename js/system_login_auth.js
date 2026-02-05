const loginForm = document.getElementById("loginForm");
const loginButton = document.getElementById("loginButton");
const loginSpinner = document.getElementById("loginSpinner");
const loginButtonText = document.getElementById("loginButtonText");

loginForm.addEventListener("submit", function (e) {
    e.preventDefault();

    loginButton.disabled = true;
    loginSpinner.classList.remove("hidden");
    loginButtonText.textContent = "Signing in...";

    const formData = new FormData(loginForm);

    fetch("../backend/system_login_auth.php", {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        console.log("Login response:", data);

        if (!data.success) {
            alert(data.message || "Login failed");
            resetButton();
            return;
        }

        /* =========================
           FORCE PASSWORD CHANGE
        ========================= */
        if (data.force_change_password === true) {
            window.location.href = "force_change_password.php";
            return;
        }

        /* =========================
           ROLE-BASED REDIRECT
        ========================= */
        switch (data.role) {
            case "admin":
                window.location.href = "admin_dashboard.php";
                break;
            case "doctor":
                window.location.href = "doctor_patient_records.html";
                break;
            case "medical_staff":
                window.location.href = "medical_staff_dashboard.php";
                break;
            case "encoder":
                window.location.href = "encoder_inventory.php";
                break;
            default:
                alert("Unknown role");
                resetButton();
        }
    })
    .catch(err => {
        console.error("Login error:", err);
        alert("Server error");
        resetButton();
    });
});

function resetButton() {
    loginButton.disabled = false;
    loginSpinner.classList.add("hidden");
    loginButtonText.textContent = "Sign In";
}
