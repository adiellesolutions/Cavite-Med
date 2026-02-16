document.addEventListener("DOMContentLoaded", () => {
    const API_URL = "../../backend/api/users_create.php";
  
    const modal = document.getElementById("add-user-modal");
    const openBtn = document.getElementById("add-user-btn");
    const closeBtn = document.getElementById("close-modal-btn");
    const cancelBtn = document.getElementById("cancel-btn");
    const form = document.getElementById("add-user-form");
    const msg = document.getElementById("um_msg");
  
    const roleSel = document.getElementById("um_role");
    const roleSection = document.getElementById("role-specific-section");
    const studentSection = document.getElementById("student-section");
    const teacherSection = document.getElementById("teacher-section");
  
    function setMsg(text, isError = false) {
      if (!msg) return;
      msg.textContent = text || "";
      msg.className = "text-sm " + (isError ? "text-red-600" : "text-green-600");
    }
  
    function setRequired(id, required) {
      const el = document.getElementById(id);
      if (!el) return;
      if (required) el.setAttribute("required", "required");
      else el.removeAttribute("required");
    }
  
    function hideRoles() {
      roleSection?.classList.add("hidden");
      studentSection?.classList.add("hidden");
      teacherSection?.classList.add("hidden");
  
      // student required off
      setRequired("um_student_id", false);
      setRequired("um_student_fullname", false);
      setRequired("um_guardian_fullname", false);
      setRequired("um_guardian_email", false);
      setRequired("um_card_uid", false);
  
      // teacher required off
      setRequired("um_teacher_id", false);
      setRequired("um_teacher_fullname", false);
    }
  
    function openModal() {
      modal.classList.remove("hidden");
      document.body.style.overflow = "hidden";
      setMsg("");
    }
  
    function closeModal() {
      modal.classList.add("hidden");
      document.body.style.overflow = "auto";
      form.reset();
      hideRoles();
      setMsg("");
    }
  
    // Ensure hidden on load (prevents "auto open" if CSS glitches)
    modal.classList.add("hidden");
    hideRoles();
  
    openBtn?.addEventListener("click", (e) => {
      e.preventDefault();
      openModal();
    });
  
    closeBtn?.addEventListener("click", (e) => {
      e.preventDefault();
      closeModal();
    });
  
    cancelBtn?.addEventListener("click", (e) => {
      e.preventDefault();
      closeModal();
    });
  
    modal?.addEventListener("click", (e) => {
      if (e.target === modal) closeModal();
    });
  
    roleSel?.addEventListener("change", () => {
      const role = roleSel.value;
      hideRoles();
  
      if (role === "student") {
        roleSection.classList.remove("hidden");
        studentSection.classList.remove("hidden");
  
        // required based on DB tables
        setRequired("um_student_id", true);
        setRequired("um_student_fullname", true);
  
        setRequired("um_guardian_fullname", true);
        setRequired("um_guardian_email", true);
  
        setRequired("um_card_uid", true);
      }
  
      if (role === "teacher") {
        roleSection.classList.remove("hidden");
        teacherSection.classList.remove("hidden");
  
        setRequired("um_teacher_id", true);
        setRequired("um_teacher_fullname", true);
      }
    });
  
    form?.addEventListener("submit", (e) => {
      e.preventDefault();
      setMsg("");
  
      const fd = new FormData(form);
  
      // Add role to fd just in case
      const role = document.getElementById("um_role")?.value || "";
      fd.set("role", role);
  
      // Basic account validation
      const username = document.getElementById("um_username")?.value.trim() || "";
      const password = document.getElementById("um_password")?.value || "";
      const status = document.getElementById("um_status")?.value || "";
  
      if (!username || !password || !role || !status) {
        setMsg("Complete required Account fields.", true);
        return;
      }
  
      // Student required validation
      if (role === "student") {
        const sid = document.getElementById("um_student_id")?.value.trim() || "";
        const sname = document.getElementById("um_student_fullname")?.value.trim() || "";
        const gname = document.getElementById("um_guardian_fullname")?.value.trim() || "";
        const gemail = document.getElementById("um_guardian_email")?.value.trim() || "";
        const cuid = document.getElementById("um_card_uid")?.value.trim() || "";
  
        if (!sid || !sname || !gname || !gemail || !cuid) {
          setMsg("Complete required Student / Guardian / RFID fields.", true);
          return;
        }
      }
  
      // Teacher required validation
      if (role === "teacher") {
        const tid = document.getElementById("um_teacher_id")?.value.trim() || "";
        const tname = document.getElementById("um_teacher_fullname")?.value.trim() || "";
  
        if (!tid || !tname) {
          setMsg("Complete required Teacher fields.", true);
          return;
        }
      }
  
      fetch(API_URL, {
        method: "POST",
        credentials: "include",
        body: fd,
      })
        .then((res) => res.json())
        .then((data) => {
          if (data.ok) {
            setMsg("User created successfully!");
            setTimeout(closeModal, 500);
            // optional reload:
            // location.reload();
          } else {
            setMsg(data.message || "Operation failed", true);
          }
        })
        .catch(() => setMsg("Server error", true));
    });
  });
  