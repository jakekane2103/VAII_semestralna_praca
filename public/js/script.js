document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll("[data-toggle='password']").forEach(btn => {
        btn.addEventListener("click", () => {
            const inputId = btn.getAttribute("data-target");
            const input = document.getElementById(inputId);
            const icon = btn.querySelector("i");

            if (!input) return;

            if (input.type === "password") {
                input.type = "text";
                icon.classList.remove("bi-eye-slash");
                icon.classList.add("bi-eye");
            } else {
                input.type = "password";
                icon.classList.remove("bi-eye");
                icon.classList.add("bi-eye-slash");
            }
        });
    });
});



document.addEventListener("DOMContentLoaded", () => {

    // Autofocus on username in login modal
    const loginModal = document.getElementById("loginModal");
    const loginEmailInput = document.getElementById("email");

    if (loginModal && loginEmailInput) {
        loginModal.addEventListener("shown.bs.modal", () => {
            loginEmailInput.focus();
        });
    }

});
