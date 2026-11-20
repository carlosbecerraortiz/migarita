/* MiGarita · inicio de sesión */
document.addEventListener("DOMContentLoaded", () => {
    "use strict";

    const passwordInput = document.getElementById("yourPassword");
    const toggleBtn = document.getElementById("togglePassword");
    const icon = document.getElementById("iconoPassword");

    // Mostrar / ocultar contraseña
    if (toggleBtn && passwordInput && icon) {
        toggleBtn.addEventListener("click", () => {
            const oculta = passwordInput.type === "password";
            passwordInput.type = oculta ? "text" : "password";
            icon.classList.toggle("bi-eye", oculta);
            icon.classList.toggle("bi-eye-slash", !oculta);
        });
    }

    // Validación de campos vacíos antes de enviar
    document.querySelectorAll(".needs-validation").forEach(form => {
        form.addEventListener("submit", e => {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            form.classList.add("was-validated");
        });
    });
});
