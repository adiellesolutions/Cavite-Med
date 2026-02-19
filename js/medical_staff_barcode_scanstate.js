document.addEventListener("DOMContentLoaded", () => {

    const scanBtn = document.getElementById("addNewItemBtn");
    if (!scanBtn) return;

    /* ===================================
       Hidden Barcode Input
    =================================== */
    const barcodeInput = document.createElement("input");
    barcodeInput.type = "text";
    barcodeInput.style.position = "absolute";
    barcodeInput.style.opacity = "0";
    barcodeInput.style.pointerEvents = "none";
    document.body.appendChild(barcodeInput);

    let scanActive = false;

    /* ===================================
       Helper: Reset Button UI
    =================================== */
    function resetButton() {
        scanActive = false;
        barcodeInput.blur();
        barcodeInput.value = "";

        scanBtn.classList.remove("btn-warning");
        scanBtn.classList.add("btn-primary");

        scanBtn.innerHTML = `
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            Add New Item
        `;
    }

    /* ===================================
       Toggle Scan Mode
    =================================== */
    scanBtn.addEventListener("click", () => {

        // If already scanning → cancel
        if (scanActive) {
            resetButton();
            return;
        }

        // Activate scanning
        scanActive = true;
        barcodeInput.value = "";
        barcodeInput.focus();

        scanBtn.classList.remove("btn-primary");
        scanBtn.classList.add("btn-warning");
        scanBtn.innerText = "Scanning... (Click again to cancel)";
    });

    /* ===================================
       Handle Barcode Scan
    =================================== */
    barcodeInput.addEventListener("keydown", (e) => {

        if (!scanActive || e.key !== "Enter") return;

        e.preventDefault();

        const scannedBarcode = barcodeInput.value.trim();

        resetButton();

        if (!scannedBarcode) return;

        processScan(scannedBarcode);
    });

    /* ===================================
       Send to Backend
    =================================== */
    function processScan(barcode) {

        fetch("../backend/medical_staff_scan_distribute.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded"
            },
            body: "barcode=" + encodeURIComponent(barcode)
        })
        .then(res => res.json())
        .then(data => {

            if (data.success) {

                window.location.reload();

            } else {

                alert(data.message || "Distribution failed");

            }

        })
        .catch(error => {
            console.error(error);
            alert("Server error while processing scan");
        });
    }

});
