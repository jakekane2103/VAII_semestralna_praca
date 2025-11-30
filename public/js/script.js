document.addEventListener("DOMContentLoaded", function () {
    const toggleBtn = document.getElementById("togglePassword");

    if (!toggleBtn) return; // ochrana ak je na stránke bez modalu

    toggleBtn.addEventListener("click", function () {
        const pass = document.getElementById("password");
        const icon = this.querySelector("i");

        if (pass.type === "password") {
            pass.type = "text";
            icon.classList.remove("bi-eye");
            icon.classList.add("bi-eye-slash");
        } else {
            pass.type = "password";
            icon.classList.remove("bi-eye-slash");
            icon.classList.add("bi-eye");
        }
    });
});
