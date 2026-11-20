/* MiGarita · se carga en el <head> (archivo, no en línea, por la política CSP).

   1. Animación de entrada cuando la página llega SIN transición de vista
      (recarga, URL escrita a mano o navegador sin View Transitions).
   2. Si la página abre un aviso (modal) al cargar, se omite la transición:
      durante la transición el menú lateral se dibuja en una capa aparte y la
      sombra del modal "parpadeaba" sobre él. El modal ya entra con su fundido.
   3. window.mgTransicionLista: se cumple cuando la página ya está estable,
      para abrir modales sin interferir con la transición. */
(function (html) {
    var hayModalAlCargar = function () {
        return !!document.querySelector("meta[name='mg-modal-al-cargar']");
    };

    if (!("onpagereveal" in window)) {
        html.classList.add("mg-entrada");
        window.mgTransicionLista = Promise.resolve();
        return;
    }

    window.mgTransicionLista = new Promise(function (listo) {
        addEventListener("pagereveal", function (e) {
            if (!e.viewTransition) {
                html.classList.add("mg-entrada");
                listo();
                return;
            }
            if (hayModalAlCargar()) {
                e.viewTransition.skipTransition();
                listo();
                return;
            }
            e.viewTransition.finished.then(listo, listo);
        }, { once: true });

        // Por seguridad: nunca esperar más de 300 ms
        setTimeout(listo, 300);
    });
})(document.documentElement);
