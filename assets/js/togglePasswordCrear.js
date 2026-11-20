document.addEventListener("DOMContentLoaded", function () {

    const passwordInput = document.getElementById("passwordCrear");
    const toggleBtn = document.getElementById("togglePasswordCrear");
    const icon = document.getElementById("iconPasswordCrear");

    if (toggleBtn && passwordInput && icon) {
        toggleBtn.addEventListener("click", function () {
            const tipo = passwordInput.getAttribute("type");

            if (tipo === "password") {
                passwordInput.setAttribute("type", "text");
                icon.classList.remove("bi-eye-slash");
                icon.classList.add("bi-eye");
            } else {
                passwordInput.setAttribute("type", "password");
                icon.classList.remove("bi-eye");
                icon.classList.add("bi-eye-slash");
            }
        });
    }

});