/* MiGarita · utilidades comunes de la interfaz */
(function () {
    "use strict";

    /** Escapa texto antes de insertarlo como HTML (evita inyección). */
    function esc(texto) {
        return String(texto == null ? "" : texto)
            .replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;").replace(/'/g, "&#39;");
    }

    /** 15000 => "$ 15.000" */
    function dinero(valor) {
        return "$ " + Math.round(Number(valor) || 0).toLocaleString("es-CO");
    }

    function iniciales(nombre, apellido) {
        const letra = t => (t || "").trim().charAt(0).toUpperCase();
        return letra(nombre) + letra(apellido);
    }

    /** Muestra el modal de confirmación y resuelve true si el usuario acepta. */
    function confirmar(opciones) {
        return new Promise(resolve => {
            const modalEl = document.getElementById("modalConfirmar");
            document.getElementById("confirmarTitulo").textContent = opciones.titulo || "¿Estás seguro?";
            document.getElementById("confirmarTexto").textContent = opciones.texto || "";

            const viejo = document.getElementById("confirmarAceptar");
            const aceptar = viejo.cloneNode(true);
            aceptar.textContent = opciones.boton || "Eliminar";
            viejo.replaceWith(aceptar);

            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            let aceptado = false;

            aceptar.addEventListener("click", () => { aceptado = true; modal.hide(); });
            modalEl.addEventListener("hidden.bs.modal", () => resolve(aceptado), { once: true });
            modal.show();
        });
    }

    /** Muestra un mensaje de aviso/error en un modal. */
    function aviso(titulo, texto) {
        document.getElementById("avisoTitulo").textContent = titulo;
        document.getElementById("avisoTexto").textContent = texto;
        bootstrap.Modal.getOrCreateInstance(document.getElementById("modalAviso")).show();
    }

    window.MG = { esc, dinero, iniciales, confirmar, aviso };

    document.addEventListener("DOMContentLoaded", () => {
        // Avisos de éxito: se cierran solos o con la X
        document.querySelectorAll(".mg-aviso").forEach(el => {
            const cerrar = () => el.remove();
            el.querySelector(".btn-close")?.addEventListener("click", cerrar);
            setTimeout(cerrar, 4500);
        });

        // Quitar ?ok=... de la URL para que el aviso no reaparezca al recargar
        if (window.history.replaceState && /[?&]ok=/.test(location.search)) {
            const url = new URL(location.href);
            url.searchParams.delete("ok");
            history.replaceState(null, "", url);
        }

        // Enlaces que piden confirmación: <a data-confirmar="texto" href="...">
        document.addEventListener("click", async e => {
            const enlace = e.target.closest("[data-confirmar]");
            if (!enlace) return;
            e.preventDefault();
            const ok = await confirmar({
                titulo: enlace.dataset.titulo || "¿Eliminar?",
                texto: enlace.dataset.confirmar,
            });
            if (ok) window.location.href = enlace.getAttribute("href");
        });
    });
})();
