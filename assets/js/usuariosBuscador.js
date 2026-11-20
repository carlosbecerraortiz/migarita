document.addEventListener("DOMContentLoaded", () => {

    const input = document.getElementById("buscadorUsuarios");
    const tabla = document.getElementById("tablaUsuarios");

    const sessionId = parseInt(document.body.dataset.sessionId || 0);
    const sessionRol = document.body.dataset.sessionRol || '';

    if (!input || !tabla) return;

    activarEliminarUsuario(); 

    input.addEventListener("keyup", function () {

        const texto = this.value.trim();

        fetch(`/mi_garita/index.php?url=user/buscarAjax&q=${encodeURIComponent(texto)}`)
            .then(res => res.json())
            .then(data => {

                renderUsuarios(data, sessionId, sessionRol);
                activarEliminarUsuario(); // SIEMPRE DESPUÉS de renderizar
            })
            .catch(error => {
                console.error('Error en la búsqueda:', error);
                tabla.innerHTML = `
                    <tr>
                        <td colspan="8" class="text-center text-danger">
                            Error al realizar la búsqueda
                        </td>
                    </tr>`;
            });
    });

});


function renderUsuarios(data, sessionId, sessionRol) {

    const tabla = document.getElementById("tablaUsuarios");
    tabla.innerHTML = "";

    if (data.length === 0) {
        tabla.innerHTML = `
            <tr>
                <td colspan="8" class="text-center text-muted">
                    No se encontraron resultados...
                </td>
            </tr>`;
        return;
    }

    data.forEach(u => {

        const badgeRol = 
            u.rol === "superusuario"
                ? '<span class="badge bg-danger">Superusuario</span>'
                : '<span class="badge bg-secondary">Usuario</span>';

        const puedeEditar = (sessionId == u.id) || (sessionRol === 'superusuario');
        const puedeEliminar = (sessionRol === 'superusuario') && (sessionId != u.id);

        const btnEditar = puedeEditar
            ? `<a href="/mi_garita/index.php?url=user/editar/${u.id}" class="btn btn-sm btn-primary"><i class="bi bi-pencil-square"></i></a>`
            : `<button class="btn btn-sm btn-secondary" disabled><i class="bi bi-pencil-square"></i></button>`;

        const tituloEliminar = (sessionId == u.id)
            ? "No puedes eliminarte a ti mismo"
            : "Sin permisos";

        const btnEliminar = puedeEliminar
            ? `<button class="btn btn-sm btn-danger btnEliminarUsuario" data-id="${u.id}" data-nombre="${u.nombre} ${u.apellido}"><i class="bi bi-trash"></i></button>`
            : `<button class="btn btn-sm btn-secondary" disabled title="${tituloEliminar}"><i class="bi bi-trash"></i></button>`;

        tabla.innerHTML += `
            <tr>
                <td>${u.id}</td>
                <td>${u.cedula}</td>
                <td>${u.nombre}</td>
                <td>${u.apellido}</td>
                <td>${u.usuario}</td>
                <td>${u.correo}</td>
                <td>${badgeRol}</td>
                <td>${btnEditar} ${btnEliminar}</td>
            </tr>`;
    });
}


function activarEliminarUsuario() {

    document.querySelectorAll(".btnEliminarUsuario").forEach(boton => {

        boton.addEventListener("click", () => {

            const id = boton.dataset.id;
            const nombre = boton.dataset.nombre;

            document.getElementById("textoConfirmacion").innerHTML =
                `¿Estás seguro que deseas eliminar al usuario <b>${nombre}</b>?`;

            const modal = new bootstrap.Modal(document.getElementById('modalConfirmarEliminar'));
            modal.show();

            const btnConf = document.getElementById("btnEliminarConfirmado");
            btnConf.replaceWith(btnConf.cloneNode(true));

            document.getElementById("btnEliminarConfirmado").addEventListener("click", () => {

                fetch("/mi_garita/index.php?url=user/eliminarAjax", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: `id=${id}`
                })
                .then(res => res.json())
                .then(resp => {

                    bootstrap.Modal.getInstance(document.getElementById('modalConfirmarEliminar')).hide();

                    if (resp.ok) {
                        window.location.href = "/mi_garita/index.php?url=user/index&ok=eliminado";
                        return;
                    } 
                    else if (resp.error === "auto_eliminar") {
                        document.getElementById("textoErrorPermisos").innerHTML = "No puedes eliminarte a ti mismo.";
                        new bootstrap.Modal(document.getElementById('modalErrorPermisos')).show();
                    }
                    else if (resp.error === "sin_permisos") {
                        document.getElementById("textoErrorPermisos").innerHTML = "No tienes permisos para eliminar usuarios.";
                        new bootstrap.Modal(document.getElementById('modalErrorPermisos')).show();
                    }
                });
            });
        });
    });
}
