// File: public/js/script.js
// Purpose: Small site-wide UI helpers (password toggle and login modal autofocus).

// Password toggle handler
// Purpose: Toggle visibility of password inputs when a button with data-toggle='password' is clicked.
// - Buttons should have data-target attribute with the input id and contain an <i> icon to switch classes.
// Used: site-wide on auth forms (login/register) where toggling password visibility is desired.
document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll("[data-toggle='password']").forEach(btn => {
        btn.addEventListener("click", () => {
            const inputId = btn.getAttribute("data-target");
            const input = document.getElementById(inputId);
            const icon = btn.querySelector("i");

            if (!input) return;

            if (input.type === "password") {
                input.type = "text";
                if (icon) { icon.classList.remove("bi-eye-slash"); icon.classList.add("bi-eye"); }
            } else {
                input.type = "password";
                if (icon) { icon.classList.remove("bi-eye"); icon.classList.add("bi-eye-slash"); }
            }
        });
    });
});

// Login modal autofocus
// Purpose: When the Bootstrap login modal is shown, autofocus the email input for convenience.
// Used: bound to #loginModal shown.bs.modal event if both the modal and input (#email) exist on the page.
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
