/* MiGarita · formulario de movimiento (fiado / abono) */
document.addEventListener("DOMContentLoaded", () => {
    "use strict";

    const form = document.getElementById("formMovimiento");
    if (!form) return;

    const monto = document.getElementById("monto");
    const metodo = document.getElementById("metodo_pago");
    const bloqueMetodo = document.getElementById("bloqueMetodo");
    const pagarTodo = document.getElementById("pagarTodo");
    const avisoAbono = document.getElementById("avisoAbono");
    const descripcion = document.getElementById("descripcion");
    const saldo = Number(document.getElementById("saldoActual")?.dataset.saldo || 0);

    const tipoActual = () => (form.querySelector("input[name='tipo']:checked") || {}).value || "";
    const valorMonto = () => Number((monto.value || "").replace(/\D/g, "")) || 0;
    const formatear = n => Number(n).toLocaleString("es-CO");

    function actualizar() {
        const esAbono = tipoActual() === "abono";

        // El método de pago solo aplica a los abonos
        metodo.disabled = !esAbono;
        bloqueMetodo.hidden = !esAbono;
        pagarTodo.hidden = !esAbono || saldo <= 0;

        // Sugerencias de descripción según el tipo
        document.querySelectorAll("#sugerencias [data-tipo]").forEach(chip => {
            chip.hidden = chip.dataset.tipo !== (esAbono ? "abono" : "fiado");
        });

        avisoAbono.hidden = !(esAbono && valorMonto() > saldo);
    }

    form.querySelectorAll("input[name='tipo']").forEach(r => r.addEventListener("change", actualizar));
    monto.addEventListener("input", actualizar);

    // Montos rápidos
    document.querySelectorAll("[data-monto]").forEach(chip => {
        chip.addEventListener("click", () => {
            monto.value = formatear(chip.dataset.monto);
            monto.dispatchEvent(new Event("input"));
            monto.focus();
        });
    });

    // Descripciones frecuentes
    document.querySelectorAll("#sugerencias [data-texto]").forEach(chip => {
        chip.addEventListener("click", () => {
            descripcion.value = chip.dataset.texto;
            descripcion.focus();
        });
    });

    actualizar();
});
