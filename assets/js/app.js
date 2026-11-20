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

    /** Reinicia la animación de entrada de las filas de una tabla (tras una búsqueda). */
    function refrescar(tbody) {
        tbody.classList.remove("refrescada");
        void tbody.offsetWidth; // fuerza al navegador a reiniciar la animación
        tbody.classList.add("refrescada");
    }

    window.MG = { esc, dinero, iniciales, confirmar, aviso, refrescar };

    document.addEventListener("DOMContentLoaded", () => {
        // Avisos de éxito: se cierran solos o con la X
        document.querySelectorAll(".mg-aviso").forEach(el => {
            const cerrar = () => el.remove();
            el.querySelector(".btn-close")?.addEventListener("click", cerrar);
            setTimeout(cerrar, 4500);
        });

        // Una vez mostrada la alerta (éxito o error), dejar la URL limpia: así el
        // código de la alerta no queda expuesto ni reaparece al recargar o compartir.
        const PARAMETROS_ALERTA = ["ok", "error", "monto"];
        const url = new URL(location.href);
        if (window.history.replaceState && PARAMETROS_ALERTA.some(p => url.searchParams.has(p))) {
            PARAMETROS_ALERTA.forEach(p => url.searchParams.delete(p));
            // Se conserva "url=cliente/index" legible (sin %2F)
            history.replaceState(history.state, "", url.pathname + "?" + url.searchParams.toString().replace(/%2F/gi, "/"));
        }

        // Acciones que piden confirmación: <button data-confirmar="texto" data-accion="/ruta">
        // Se envían por POST (nunca como un enlace que se pueda abrir o compartir).
        document.addEventListener("click", async e => {
            const boton = e.target.closest("[data-confirmar]");
            if (!boton) return;
            e.preventDefault();
            const ok = await confirmar({
                titulo: boton.dataset.titulo || "¿Eliminar?",
                texto: boton.dataset.confirmar,
            });
            if (!ok) return;

            const form = document.createElement("form");
            form.method = "POST";
            form.action = boton.dataset.accion;
            form.hidden = true;
            document.body.appendChild(form);
            form.submit();
        });
    });
})();
