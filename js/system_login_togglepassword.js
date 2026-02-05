const togglePasswordBtn = document.getElementById("togglePassword");
const passwordInput = document.getElementById("password");
const eyeIcon = document.getElementById("eyeIcon");

togglePasswordBtn.addEventListener("click", () => {
    const isHidden = passwordInput.type === "password";

    // Toggle input type
    passwordInput.type = isHidden ? "text" : "password";

    // Toggle icon
    eyeIcon.innerHTML = isHidden
        ? `
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M13.875 18.825A10.05 10.05 0 0112 19
                 c-4.478 0-8.268-2.943-9.542-7
                 a9.956 9.956 0 012.347-3.742
                 M6.423 6.423A9.956 9.956 0 0112 5
                 c4.478 0 8.268 2.943 9.542 7
                 a9.956 9.956 0 01-4.132 5.411
                 M15 12a3 3 0 00-4.243-2.829
                 M9.88 9.88A3 3 0 0012 15
                 M3 3l18 18"/>
        `
        : `
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M2.458 12C3.732 7.943 7.523 5 12 5
                 c4.478 0 8.268 2.943 9.542 7
                 -1.274 4.057-5.064 7-9.542 7
                 -4.477 0-8.268-2.943-9.542-7z"/>
        `;
});
