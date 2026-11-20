document.addEventListener("DOMContentLoaded", () => {

    const tipo = document.querySelector("select[name='tipo']");
    const metodo = document.getElementById("metodo_pago");

    if (!tipo || !metodo) return;

    function actualizarMetodo() {
        if (tipo.value === "abono") {
            metodo.disabled = false;
        } else {
            metodo.disabled = true;
        }
    }

    // Ejecutar al cargar
    actualizarMetodo();

    // Ejecutar cuando cambie el tipo
    tipo.addEventListener("change", actualizarMetodo);
});
