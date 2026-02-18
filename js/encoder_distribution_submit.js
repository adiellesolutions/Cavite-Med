document.addEventListener("DOMContentLoaded", function () {

    const form = document.getElementById("distributionForm");
    const modal = document.getElementById("distributionModal");

    if (!form) {
        console.error("distributionForm not found in DOM");
        return;
    }

    form.addEventListener("submit", function (e) {
        e.preventDefault();

        const formData = new FormData(form);
        formData.append("action", "create");

        fetch("../backend/encoder_distribution_add.php", {
            method: "POST",
            body: formData
        })
        .then(res => res.json())
        .then(data => {

            if (!data.success) {
                alert(data.message || "Failed to create distribution");
                return;
            }

            alert("Distribution created successfully!");

            form.reset();
            modal.classList.remove("show");

            if (typeof loadDistributions === "function") {
                loadDistributions();
            }
        })
        .catch(err => {
            console.error("Submission error:", err);
            alert("Server error");
        });
    });

});
