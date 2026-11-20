/* MiGarita · animación de entrada cuando la página llega sin transición de vista
   (recarga, URL escrita a mano o navegador sin View Transitions entre páginas).
   Se carga en el <head> como archivo (no en línea) para cumplir la política CSP. */
(function (html) {
    if (!("onpagereveal" in window)) {
        html.classList.add("mg-entrada");
        return;
    }
    addEventListener("pagereveal", function (e) {
        if (!e.viewTransition) html.classList.add("mg-entrada");
    });
})(document.documentElement);
