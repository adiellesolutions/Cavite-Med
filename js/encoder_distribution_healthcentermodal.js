document.addEventListener("DOMContentLoaded", function () {

    const modal = document.getElementById("healthCenterModal");

    document.getElementById("viewHealthCenterBtn")
        ?.addEventListener("click", function () {

            const modal = document.getElementById("healthCenterModal");

            modal.classList.add("show");

            // 🔥 fetch data when opening
            loadHealthCenters();
        });
        
    document.getElementById("closeHealthCenterModal")
        ?.addEventListener("click", function () {
            modal.classList.remove("show");
        });

    document.getElementById("closeHealthCenterModalBottom")
        ?.addEventListener("click", function () {
            modal.classList.remove("show");
        });

});
