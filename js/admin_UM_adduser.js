document.getElementById("addUserForm").addEventListener("submit", function (e) {
    e.preventDefault();

    const form = e.target;
    const formData = new FormData(form);

    const isEdit = document.getElementById("edit_user_id").value !== "";
    const url = isEdit
        ? "../backend/admin_UM_updateuser.php"
        : "../backend/admin_UM_adduser.php";

    fetch(url, {
        method: "POST",
        body: formData
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert(isEdit ? "User updated successfully" : "User created successfully");
                location.reload();
            } else {
                alert(data.message || "Operation failed");
            }
        })
        .catch(() => alert("Server error"));
});
