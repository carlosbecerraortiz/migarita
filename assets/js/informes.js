/* MiGarita · selección del tipo de informe */
document.addEventListener("DOMContentLoaded", () => {
    "use strict";

    const form = document.getElementById("formInforme");
    if (!form) return;

    const bloque = document.getElementById("bloqueFechas");
    const desde = document.getElementById("desde");
    const hasta = document.getElementById("hasta");

    function actualizar() {
        const porFechas = form.querySelector("input[name='tipo']:checked")?.value === "fechas";
        // Las fechas siempre ocupan su lugar (sin saltos); solo se activan con "Entre fechas".
        bloque.classList.toggle("inactivas", !porFechas);
        bloque.setAttribute("aria-disabled", String(!porFechas));
        // Los campos deshabilitados no se envían, así solo viajan cuando se usan
        desde.disabled = !porFechas;
        hasta.disabled = !porFechas;
    }

    form.querySelectorAll("input[name='tipo']").forEach(r => r.addEventListener("change", () => {
        actualizar();
        if (r.value === "fechas" && r.checked) desde.focus();
    }));

    // "Hasta" no puede ser anterior a "Desde"
    desde.addEventListener("change", () => { hasta.min = desde.value; });

    form.addEventListener("submit", e => {
        if (!form.checkValidity()) {
            e.preventDefault();
            form.reportValidity();
        }
    });

    actualizar();
});
