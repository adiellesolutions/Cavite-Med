const addUserBtn = document.getElementById("addUserBtn");
const addUserModal = document.getElementById("addUserModal");
const closeModalBtn = document.getElementById("closeModal");
const cancelAddUserBtn = document.getElementById("cancelAddUser");
const addUserForm = document.getElementById("addUserForm");

const roleSelect = document.getElementById("roleSelect");
const editUserIdInput = document.getElementById("edit_user_id");


addUserBtn.addEventListener("click", () => {
    addUserForm.reset();
    editUserIdInput.value = "";
    roleSelect.value = "";

    // ✅ RESET UI STATE
    document.getElementById("userModalTitle").textContent = "Add New User";
    document.getElementById("submitUserBtn").textContent = "Create User";

    addUserModal.classList.remove("hidden");
    addUserModal.classList.add("flex");
});


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

            addUserModal.classList.remove("hidden");
            addUserModal.classList.add("flex");

            editUserIdInput.value = user.user_id;
            document.querySelector("[name='full_name']").value = user.full_name;
            document.querySelector("[name='username']").value = user.username;
            document.querySelector("[name='email']").value = user.email ?? "";
            document.querySelector("[name='position']").value = user.position ?? "";
            document.querySelector("[name='contact_number']").value = user.contact_number ?? "";
            document.querySelector("[name='clinic']").value = user.clinic ?? "";
            document.querySelector("select[name='status']").value = user.status;

            roleSelect.value = user.role;


            // Add mode
            document.getElementById("userModalTitle").textContent = "Add New User";

            // Edit mode
            document.getElementById("userModalTitle").textContent = "Edit User";
        })
        .catch(() => {
            alert("Server error while loading user");
        });
});

addUserBtn.addEventListener("click", () => {
    addUserForm.reset();
    editUserIdInput.value = "";
    roleSelect.value = "";

    // Button text
    document.getElementById("submitUserBtn").textContent = "Create User";

    addUserModal.classList.remove("hidden");
    addUserModal.classList.add("flex");
});

