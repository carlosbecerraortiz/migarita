document.addEventListener("DOMContentLoaded", () => {

    const inputMonto = document.getElementById("monto");

    if (!inputMonto) return;

    if (inputMonto.value !== "") {
        let valor = inputMonto.value.replace(/\D/g, "");
        if (valor !== "") {
            inputMonto.value = Number(valor).toLocaleString("es-CO");
        }
    }

    inputMonto.addEventListener("input", function () {
        let valor = this.value.replace(/\D/g, "");

        if (valor === "") {
            this.value = "";
            return;
        }

        this.value = Number(valor).toLocaleString("es-CO");
    });

    const form = inputMonto.closest("form");

    if (form) {
        form.addEventListener("submit", () => {
            inputMonto.value = inputMonto.value.replace(/\./g, "");
        });
    }
});
