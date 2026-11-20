document.addEventListener("DOMContentLoaded", () => {

    const input = document.getElementById("buscador");
    const tabla = document.getElementById("tablaClientes");

    if (!input || !tabla) return;

    input.addEventListener("keyup", function () {

        const texto = this.value.trim();

        fetch(`/mi_garita/index.php?url=cliente/buscarAjax&q=${encodeURIComponent(texto)}`)
            .then(res => res.json())
            .then(data => {

                tabla.innerHTML = "";

                if (data.length === 0) {
                    tabla.innerHTML = `
                        <tr>
                            <td colspan="9" class="text-center text-muted">
                                No se encontraron resultados...
                            </td>
                        </tr>`;
                    return;
                }

                data.forEach(c => {

                    // Determinar el color del badge según el tipo de cliente
                    let colorBadge = 'bg-secondary';
                    const tipoCliente = (c.tipo_cliente || '').toLowerCase();
                    
                    switch(tipoCliente) {
                        case 'estudiante':
                            colorBadge = 'bg-primary';
                            break;
                        case 'administrativo':
                            colorBadge = 'bg-success';
                            break;
                        case 'profesor':
                            colorBadge = 'bg-warning';
                            break;
                        case 'egresado':
                            colorBadge = 'bg-info';
                            break;
                        case 'externo':
                            colorBadge = 'bg-danger';
                            break;
                    }

                    // Capitalizar primera letra del tipo de cliente
                    const tipoClienteCapitalizado = c.tipo_cliente ? 
                        c.tipo_cliente.charAt(0).toUpperCase() + c.tipo_cliente.slice(1) : '';

                    tabla.innerHTML += `
                    <tr>
                        <td>${c.id}</td>
                        <td>${c.cedula}</td>
                        <td>${c.nombre}</td>
                        <td>${c.apellido}</td>
                        <td>${c.telefono ?? ''}</td>
                        <td>
                            <span class="badge ${colorBadge}">${tipoClienteCapitalizado}</span>
                        </td>
                        <td>$ ${Number(c.saldo).toLocaleString("es-CO")}</td>

                        <td>
                            <a href="/mi_garita/index.php?url=cliente/editar/${c.id}"
                                class="btn btn-sm btn-primary">
                                <i class="bi bi-pencil-square"></i>
                            </a>

                            <button class="btn btn-sm btn-danger btnEliminar"
                                data-id="${c.id}"
                                data-nombre="${c.nombre} ${c.apellido}">
                                <i class="bi bi-trash"></i>
                            </button>

                            <a href="/mi_garita/index.php?url=cliente/seleccionarInforme/${c.id}"
                                class="btn btn-sm btn-success">
                                <i class="bi bi-file-earmark-text"></i>
                            </a>
                        </td>

                        <td>
                            <a href="/mi_garita/index.php?url=cliente/deuda/${c.id}"
                               class="btn btn-outline-success btn-sm">
                               Registrar Movimiento
                            </a>
                        </td>
                    </tr>`;
                });

                activarBotonesEliminar();
            })
            .catch(err => console.error("Error en la búsqueda AJAX:", err));
    });
});


function activarBotonesEliminar() {
    document.querySelectorAll(".btnEliminar").forEach(boton => {
        boton.addEventListener("click", () => {
            idEliminar = boton.dataset.id;
            let nombre = boton.dataset.nombre;

            document.getElementById("textoConfirmacion").innerHTML =
                `¿Estás seguro que deseas eliminar al cliente <b>${nombre}</b>?`;

            new bootstrap.Modal(document.getElementById('modalConfirmarEliminar')).show();
        });
    });
}