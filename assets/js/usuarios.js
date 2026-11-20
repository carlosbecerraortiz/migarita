/* MiGarita · lista de usuarios: búsqueda en vivo y eliminación */
document.addEventListener("DOMContentLoaded", () => {
    "use strict";

    const { esc, iniciales, confirmar, aviso } = window.MG;
    const input = document.getElementById("buscadorUsuarios");
    const tabla = document.getElementById("tablaUsuarios");
    const resumen = document.getElementById("resumenUsuarios");
    if (!input || !tabla) return;

    const URL_BASE = "/mi_garita/index.php?url=";
    const idSesion = Number(document.body.dataset.sessionId || 0);
    const esSuper = document.body.dataset.sessionRol === "superusuario";
    const filaVacia = tabla.querySelector("tr.vacio");
    let temporizador = null;

    /** Misma estructura de fila que genera la vista PHP, con los mismos permisos. */
    function fila(u) {
        const id = Number(u.id);
        const esYo = id === idSesion;
        const nombre = `${u.nombre} ${u.apellido}`;
        const rol = u.rol === "superusuario"
            ? '<span class="mg-etiqueta superusuario">Superusuario</span>'
            : '<span class="mg-etiqueta">Usuario</span>';
        const editar = (esYo || esSuper)
            ? `<a href="${URL_BASE}user/editar/${id}" class="btn btn-sm btn-claro"><i class="bi bi-pencil"></i> Editar</a>`
            : "";
        const eliminar = (esSuper && !esYo)
            ? `<button type="button" class="btn btn-sm btn-icono text-danger" data-eliminar-usuario="${id}"
                       data-nombre="${esc(nombre)}" aria-label="Eliminar a ${esc(nombre)}" title="Eliminar">
                   <i class="bi bi-trash3"></i></button>`
            : "";

        return `
        <tr data-id="${id}">
            <td class="col-persona">
                <div class="mg-persona">
                    <span class="mg-avatar">${esc(iniciales(u.nombre, u.apellido))}</span>
                    <div class="datos">
                        <div class="nombre">${esc(nombre)}${esYo ? ' <span class="mg-etiqueta ms-1">Tú</span>' : ""}</div>
                        <div class="sub">@${esc(u.usuario)} · CC ${esc(u.cedula)}</div>
                    </div>
                </div>
            </td>
            <td class="ocultar-movil">${esc(u.correo)}</td>
            <td class="col-saldo">${rol}</td>
            <td class="col-acciones"><div class="acciones">${editar}${eliminar}</div></td>
        </tr>`;
    }

    function contar() {
        const n = tabla.querySelectorAll("tr[data-id]").length;
        filaVacia.hidden = n > 0;
        if (resumen) resumen.textContent = `${n} ${n === 1 ? "usuario" : "usuarios"}`;
    }

    function buscar() {
        // POST: lo que se busca no queda en la URL ni en los registros del servidor
        fetch(`${URL_BASE}user/buscarAjax`, {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "q=" + encodeURIComponent(input.value.trim()),
        })
            .then(res => res.json())
            .then(data => {
                tabla.querySelectorAll("tr[data-id]").forEach(tr => tr.remove());
                filaVacia.insertAdjacentHTML("beforebegin", data.map(fila).join(""));
                contar();
                window.MG.refrescar(tabla);
            })
            .catch(err => console.error("Error en la búsqueda:", err));
    }

    input.addEventListener("input", () => {
        clearTimeout(temporizador);
        temporizador = setTimeout(buscar, 200);
    });

    // Eliminar (funciona también con las filas generadas por la búsqueda)
    const MENSAJES = {
        sin_permisos: "Solo un superusuario puede eliminar usuarios.",
        auto_eliminar: "No puedes eliminar tu propia cuenta.",
        id_no_recibido: "No se recibió el usuario a eliminar.",
    };

    tabla.addEventListener("click", async e => {
        const boton = e.target.closest("[data-eliminar-usuario]");
        if (!boton) return;

        const ok = await confirmar({
            titulo: "¿Eliminar usuario?",
            texto: `${boton.dataset.nombre} ya no podrá entrar a MiGarita.`,
        });
        if (!ok) return;

        try {
            const res = await fetch(`${URL_BASE}user/eliminarAjax`, {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: "id=" + encodeURIComponent(boton.dataset.eliminarUsuario),
            });
            const datos = await res.json();

            if (datos.ok) {
                // El aviso "Usuario eliminado" ya quedó en la sesión del servidor
                window.location.href = `${URL_BASE}user/index`;
                return;
            }
            aviso("No se pudo eliminar", MENSAJES[datos.error] || "Ocurrió un error inesperado.");
        } catch (err) {
            aviso("No se pudo eliminar", "Revisa tu conexión e inténtalo de nuevo.");
        }
    });
});
