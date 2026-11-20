/* MiGarita · lista de clientes: búsqueda en vivo y filtros por estado */
document.addEventListener("DOMContentLoaded", () => {
    "use strict";

    const { esc, dinero, iniciales, aviso } = window.MG;
    const input = document.getElementById("buscador");
    const tabla = document.getElementById("tablaClientes");
    const resumen = document.getElementById("resumenLista");
    if (!input || !tabla) return;

    const filaVacia = tabla.querySelector("tr.vacio");
    let filtro = "todos";
    let temporizador = null;

    const URL_BASE = "/mi_garita/index.php?url=";
    const TIPOS = ["estudiante", "profesor", "administrativo", "egresado", "externo"];

    /** Misma estructura de fila que genera la vista PHP. */
    function fila(c) {
        const saldo = Number(c.saldo) || 0;
        const debe = saldo > 0;
        const nombre = `${c.nombre} ${c.apellido}`;
        const tipo = (c.tipo_cliente || "").toLowerCase();
        const claseTipo = TIPOS.includes(tipo) ? tipo : "";
        const tipoTexto = tipo ? tipo.charAt(0).toUpperCase() + tipo.slice(1) : "";
        const id = Number(c.id);

        return `
        <tr data-saldo="${saldo}">
            <td class="col-persona">
                <div class="mg-persona">
                    <span class="mg-avatar">${esc(iniciales(c.nombre, c.apellido))}</span>
                    <div class="datos">
                        <a class="nombre d-block text-reset" href="${URL_BASE}cliente/deuda/${id}">${esc(nombre)}</a>
                        <div class="sub">CC ${esc(c.cedula)}${c.telefono ? " · " + esc(c.telefono) : ""}</div>
                    </div>
                </div>
            </td>
            <td class="col-tipo"><span class="mg-etiqueta ${claseTipo}">${esc(tipoTexto)}</span></td>
            <td class="col-saldo derecha">
                <span class="mg-saldo ${debe ? "debe" : "al-dia"}">${dinero(saldo)}</span>
                <span class="mg-estado">${debe ? "Debe" : "Al día"}</span>
            </td>
            <td class="col-acciones">
                <div class="acciones">
                    <a href="${URL_BASE}cliente/deuda/${id}" class="btn btn-sm btn-suave"><i class="bi bi-plus-lg"></i> Movimiento</a>
                    <div class="dropdown">
                        <button class="btn btn-icono" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Más acciones para ${esc(nombre)}">
                            <i class="bi bi-three-dots-vertical"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="${URL_BASE}cliente/editar/${id}"><i class="bi bi-pencil"></i> Editar datos</a></li>
                            <li><a class="dropdown-item" href="${URL_BASE}cliente/seleccionarInforme/${id}"><i class="bi bi-file-earmark-pdf"></i> Generar informe</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item peligro" href="${URL_BASE}cliente/eliminar/${id}"
                                   data-titulo="¿Eliminar cliente?"
                                   data-confirmar="Se eliminará a ${esc(nombre)} y todo su historial. Esta acción no se puede deshacer.">
                                   <i class="bi bi-trash3"></i> Eliminar</a></li>
                        </ul>
                    </div>
                </div>
            </td>
        </tr>`;
    }

    function filas() {
        return Array.from(tabla.querySelectorAll("tr[data-saldo]"));
    }

    /** Aplica el filtro activo y actualiza los contadores. */
    function aplicarFiltro() {
        const todas = filas();
        let visibles = 0;
        const cuentas = { todos: todas.length, deuda: 0, aldia: 0 };

        todas.forEach(tr => {
            const debe = Number(tr.dataset.saldo) > 0;
            cuentas[debe ? "deuda" : "aldia"]++;
            const mostrar = filtro === "todos" || (filtro === "deuda" ? debe : !debe);
            tr.hidden = !mostrar;
            if (mostrar) visibles++;
        });

        Object.entries(cuentas).forEach(([clave, n]) => {
            const el = document.querySelector(`[data-cuenta="${clave}"]`);
            if (el) el.textContent = n;
        });

        filaVacia.hidden = visibles > 0;
        if (resumen) resumen.textContent = `${visibles} ${visibles === 1 ? "cliente" : "clientes"}`;
    }

    function buscar() {
        const texto = input.value.trim();
        fetch(`${URL_BASE}cliente/buscarAjax&q=${encodeURIComponent(texto)}`)
            .then(res => res.json())
            .then(data => {
                filas().forEach(tr => tr.remove());
                filaVacia.insertAdjacentHTML("beforebegin", data.map(fila).join(""));
                aplicarFiltro();
                window.MG.refrescar(tabla);
            })
            .catch(err => console.error("Error en la búsqueda:", err));
    }

    input.addEventListener("input", () => {
        clearTimeout(temporizador);
        temporizador = setTimeout(buscar, 200);
    });

    document.querySelectorAll("[data-filtro]").forEach(boton => {
        boton.addEventListener("click", () => {
            filtro = boton.dataset.filtro;
            document.querySelectorAll("[data-filtro]").forEach(b => b.classList.toggle("activo", b === boton));
            aplicarFiltro();
            window.MG.refrescar(tabla);
        });
    });

    if (input.value.trim() !== "") buscar();

    // Error al eliminar (cliente con saldo o inexistente)
    const error = document.getElementById("errorEliminar");
    if (error) aviso(error.dataset.titulo, error.dataset.texto);
});
