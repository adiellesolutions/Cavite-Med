document.addEventListener("DOMContentLoaded", () => {

    const barcodeBtn = document.getElementById("barcodeModeBtn");
    const barcodeInput = document.getElementById("barcodeInput");
    const medicineSelect = document.getElementById("medicineSelect");

    if (!barcodeBtn || !barcodeInput || !medicineSelect) return;

    let scanActive = false;

    // Activate barcode mode
    barcodeBtn.addEventListener("click", () => {

        // Ensure medicines are loaded
        if (medicineSelect.options.length <= 1) {
            alert("Please open Add Record first and wait for medicines to load.");
            return;
        }

        scanActive = true;

        barcodeInput.value = "";
        barcodeInput.focus();

        barcodeBtn.classList.remove("btn-outline");
        barcodeBtn.classList.add("btn-primary");
    });

    // Capture scan (scanner sends Enter at end)
    barcodeInput.addEventListener("keydown", e => {
        if (!scanActive) return;
        if (e.key !== "Enter") return;

        e.preventDefault();

        const scannedBarcode = barcodeInput.value.trim();

        // 🔴 EXIT SCAN MODE IMMEDIATELY
        scanActive = false;
        barcodeInput.value = "";
        barcodeInput.blur();

        barcodeBtn.classList.remove("btn-primary");
        barcodeBtn.classList.add("btn-outline");

        if (!scannedBarcode) return;

        matchBarcode(scannedBarcode);
    });

    function matchBarcode(barcode) {
        const cleanBarcode = barcode.trim();
        let matchedOption = null;

        const options = Array.from(medicineSelect.options);

        for (const opt of options) {
            if (!opt.dataset.barcode) continue;

            if (opt.dataset.barcode.trim() === cleanBarcode) {
                matchedOption = opt;
                break;
            }
        }

        if (!matchedOption) {
            alert("Scanned barcode not found:\n" + cleanBarcode);
            return;
        }

        // Force-select option
        options.forEach(o => (o.selected = false));
        matchedOption.selected = true;

        medicineSelect.dispatchEvent(
            new Event("change", { bubbles: true })
        );

        console.log("Auto-selected medicine:", matchedOption.textContent);
    }

});
