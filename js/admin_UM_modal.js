const addUserBtn = document.getElementById("addUserBtn");
const addUserModal = document.getElementById("addUserModal");
const closeModalBtn = document.getElementById("closeModal");
const cancelAddUserBtn = document.getElementById("cancelAddUser");
const addUserForm = document.getElementById("addUserForm");

const roleSelect = document.getElementById("roleSelect");
const editUserIdInput = document.getElementById("edit_user_id");

/* ===============================
   OPEN ADD USER MODAL
=============================== */
addUserBtn.addEventListener("click", () => {
    addUserForm.reset();
    editUserIdInput.value = "";
    roleSelect.value = "";

    document.getElementById("userModalTitle").textContent = "Add New User";
    document.getElementById("submitUserBtn").textContent = "Create User";

    addUserModal.classList.remove("hidden");
    addUserModal.classList.add("flex");
});

/* ===============================
   CLOSE MODAL FUNCTION
=============================== */
const closeModal = () => {
    addUserModal.classList.add("hidden");
    addUserModal.classList.remove("flex");
};

closeModalBtn.addEventListener("click", closeModal);
cancelAddUserBtn.addEventListener("click", closeModal);

addUserModal.addEventListener("click", (e) => {
    if (e.target === addUserModal) {
        closeModal();
    }
});

/* ===============================
   EDIT USER
=============================== */
document.addEventListener("click", function (e) {
    const btn = e.target.closest(".editUserBtn");
    if (!btn) return;

    const userId = btn.dataset.id;

    fetch(`../backend/admin_UM_getuser.php?user_id=${userId}`)
        .then(res => res.json())
        .then(data => {

            if (!data.success) {
                alert("Failed to load user data");
                return;
            }

            const user = data.user;

            /* Open modal */
            addUserModal.classList.remove("hidden");
            addUserModal.classList.add("flex");

            /* Set form values */
            editUserIdInput.value = user.user_id ?? "";

            document.querySelector("[name='full_name']").value = user.full_name ?? "";
            document.querySelector("[name='username']").value = user.username ?? "";
            document.querySelector("[name='email']").value = user.email ?? "";
            document.querySelector("[name='position']").value = user.position ?? "";
            document.querySelector("[name='contact_number']").value = user.contact_number ?? "";

            /* Health Center (NEW FIELD) */
            const healthCenterSelect = document.querySelector("[name='health_center_id']");
            if (healthCenterSelect) {
                healthCenterSelect.value = user.health_center_id ?? "";
            }

            /* Role */
            roleSelect.value = user.role ?? "";

            /* Update UI for Edit Mode */
            document.getElementById("userModalTitle").textContent = "Edit User";
            document.getElementById("submitUserBtn").textContent = "Update User";
        })
        .catch((error) => {
            console.error("Edit fetch error:", error);
            alert("Server error while loading user");
        });
});

addUserBtn.addEventListener("click", () => {
    addUserForm.reset();
    editUserIdInput.value = "";
    roleSelect.value = "";

    loadHealthCenters(); // 🔥 ADD THIS

    document.getElementById("submitUserBtn").textContent = "Create User";

    addUserModal.classList.remove("hidden");
    addUserModal.classList.add("flex");
});

function loadHealthCenters(selectedId = "") {

    const select = document.querySelector("[name='health_center_id']");
    if (!select) return;

    fetch("../backend/admin_fetch_healthcenters.php")
        .then(res => res.json())
        .then(res => {

            select.innerHTML = '<option value="">Select Health Center</option>';

            res.data.forEach(center => {
                const option = document.createElement("option");
                option.value = center.id;
                option.textContent = center.center_name;

                if (selectedId && selectedId == center.id) {
                    option.selected = true;
                }

                select.appendChild(option);
            });
        })
        .catch(err => {
            console.error("Failed to load health centers:", err);
        });
}