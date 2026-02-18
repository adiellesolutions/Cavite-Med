document.addEventListener("click", function(e){

    const btn = e.target.closest(".archiveHealthCenterBtn");
    if(!btn) return;

    const id = btn.dataset.id;

    if(!confirm("Archive this health center?")) return;

    const formData = new FormData();
    formData.append("center_id", id);

    fetch("../backend/encoder_distribution_healthcenterarchive.php", {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then(data => {

        if(!data.success){
            alert(data.message || "Archive failed");
            return;
        }

        alert("Health center archived successfully!");

        if(typeof loadHealthCenters === "function"){
            loadHealthCenters();
        }

        if(typeof loadDistributionDropdowns === "function"){
            loadDistributionDropdowns();
        }

    })
    .catch(err => {
        console.error("Archive error:", err);
        alert("Server error");
    });

});
