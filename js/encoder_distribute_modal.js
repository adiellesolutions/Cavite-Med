document.addEventListener("DOMContentLoaded", function() {

    const modal = document.getElementById("distributionModal");
    const openBtn = document.getElementById("openDistributionModal");
    const closeBtn = document.getElementById("closeDistributionModal");
    const cancelBtn = document.getElementById("cancelDistributionModal");

    if (!modal) {
        console.error("Modal not found");
        return;
    }

    if (openBtn) {
        openBtn.addEventListener("click", function() {
            modal.classList.add("show");
            loadDistributionDropdowns();   // ✅ use show
        });
    }

    if (closeBtn) {
        closeBtn.addEventListener("click", function() {
            modal.classList.remove("show");  // ✅ remove show
        });
    }

    if (cancelBtn) {
        cancelBtn.addEventListener("click", function() {
            modal.classList.remove("show");
        });
    }

    // Close when clicking outside
    modal.addEventListener("click", function(e) {
        if (e.target === modal) {
            modal.classList.remove("show");
        }
    });

});