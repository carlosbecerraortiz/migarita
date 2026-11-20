document.addEventListener("DOMContentLoaded", function () {

    const selector = document.getElementById("tipoInforme");
    if (!selector) return;

    selector.addEventListener("change", function() {
        let opcion = this.value;

        document.getElementById("bloqueMes").classList.add("oculto");
        document.getElementById("bloqueFechas").classList.add("oculto");
        document.getElementById("bloqueCompleto").classList.add("oculto");

        if (opcion === "mes") {
            document.getElementById("bloqueMes").classList.remove("oculto");
        }
        else if (opcion === "fechas") {
            document.getElementById("bloqueFechas").classList.remove("oculto");
        }
        else if (opcion === "completo") {
            document.getElementById("bloqueCompleto").classList.remove("oculto");
        }
    });

});
