/* MiGarita · vista previa en vivo del cliente o usuario mientras se llena el formulario */
document.addEventListener("DOMContentLoaded", () => {
    "use strict";

    const previa = document.querySelector("[data-previa='nombre']");
    if (!previa) return;

    const campo = id => document.getElementById(id);
    const salida = clave => document.querySelector(`[data-previa='${clave}']`);
    const TIPOS = ["estudiante", "profesor", "administrativo", "egresado", "externo", "superusuario"];

    function actualizar() {
        const nombre = (campo("nombre")?.value || "").trim();
        const apellido = (campo("apellido")?.value || "").trim();
        const completo = `${nombre} ${apellido}`.trim();

        salida("iniciales").textContent = window.MG.iniciales(nombre, apellido) || "?";
        const nombreEl = salida("nombre");
        nombreEl.textContent = completo;
        if (!completo) nombreEl.innerHTML = `<span class="vacio">${nombreEl.dataset.vacio || "Nombre"}</span>`;

        // Línea secundaria: "CC 123 · 300..." o "@usuario · CC 123"
        const partes = [];
        const usuario = (campo("usuario")?.value || "").trim();
        const cedula = (campo("cedula")?.value || "").trim();
        const telefono = (campo("telefono")?.value || "").trim();
        if (usuario) partes.push("@" + usuario);
        if (cedula) partes.push("CC " + cedula);
        if (telefono) partes.push(telefono);
        const detalle = salida("detalle");
        if (detalle) detalle.textContent = partes.join(" · ") || detalle.dataset.vacio || "";

        // Tipo de cliente o rol
        const select = campo("id_tipo_cliente") || campo("rol");
        const tipo = salida("tipo");
        if (select && tipo) {
            const opcion = select.options[select.selectedIndex];
            const texto = select.value ? opcion.text.split("·")[0].trim() : "";
            const clase = texto.toLowerCase();
            tipo.textContent = texto;
            tipo.className = "mg-etiqueta " + (TIPOS.includes(clase) ? clase : "");
            tipo.hidden = !texto;
        }
    }

    ["nombre", "apellido", "cedula", "telefono", "usuario"].forEach(id => campo(id)?.addEventListener("input", actualizar));
    ["id_tipo_cliente", "rol"].forEach(id => campo(id)?.addEventListener("change", actualizar));

    const nombreEl = salida("nombre");
    nombreEl.dataset.vacio = nombreEl.textContent.trim();
    const detalle = salida("detalle");
    if (detalle) detalle.dataset.vacio = detalle.textContent.trim();

    actualizar();
});
