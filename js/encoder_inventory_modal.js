document.addEventListener("DOMContentLoaded", () => {
    const addBtn = document.getElementById("addNewItemBtn");
    const fabBtn = document.getElementById("quickAddFab");
    const modal = document.getElementById("addItemModal");
    const closeModal = document.getElementById("closeModal");
    const cancelBtn = document.getElementById("cancelAddItem");

    function openModal() {
        modal.classList.remove("hidden");
        document.body.style.overflow = "hidden";
    }

    function closeModalFn() {
        modal.classList.add("hidden");
        document.body.style.overflow = "";
    }

    addBtn?.addEventListener("click", openModal);
    fabBtn?.addEventListener("click", openModal);
    closeModal?.addEventListener("click", closeModalFn);
    cancelBtn?.addEventListener("click", closeModalFn);

    // Close when clicking outside modal
    modal.addEventListener("click", (e) => {
        if (e.target === modal) closeModalFn();
    });
});