const modal = document.getElementById("addDisposalModal");

document.getElementById("addNewRecordBtn").onclick = () => {
    modal.classList.remove("hidden");
    loadDisposalMedicines();
};

document.getElementById("closeDisposalModal").onclick =
document.getElementById("cancelDisposal").onclick = () => {
    modal.classList.add("hidden");
};
