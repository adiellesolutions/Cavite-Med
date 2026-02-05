document.addEventListener("DOMContentLoaded", () => {
    const openBtn = document.getElementById("addNewSupplierBtn");
    const modal = document.getElementById("supplierModal");
    const closeTop = document.getElementById("closeSupplierModal");
    const closeBottom = document.getElementById("closeSupplierModalBottom");

    // Safety check
    if (!openBtn || !modal) {
        console.error("Supplier button or modal not found");
        return;
    }

    // OPEN
    openBtn.addEventListener("click", () => {
        modal.classList.remove("hidden");
    });

    // CLOSE (top X)
    if (closeTop) {
        closeTop.addEventListener("click", () => {
            modal.classList.add("hidden");
        });
    }

    // CLOSE (bottom button)
    if (closeBottom) {
        closeBottom.addEventListener("click", () => {
            modal.classList.add("hidden");
        });
    }

    // CLOSE on backdrop
    modal.addEventListener("click", (e) => {
        if (e.target === modal) {
            modal.classList.add("hidden");
        }
    });
});
