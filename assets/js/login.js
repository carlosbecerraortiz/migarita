document.addEventListener("DOMContentLoaded", function () {

    const passwordInput = document.getElementById("yourPassword");
    const toggleBtn = document.getElementById("togglePassword");
    const icon = document.getElementById("iconoPassword");

    toggleBtn.addEventListener("click", function () {

        const tipoActual = passwordInput.getAttribute("type");

        if (tipoActual === "password") {
            passwordInput.setAttribute("type", "text");
            icon.classList.remove("bi-eye-slash");
            icon.classList.add("bi-eye");
        } else {
            passwordInput.setAttribute("type", "password");
            icon.classList.remove("bi-eye");
            icon.classList.add("bi-eye-slash");
        }
    });

});
