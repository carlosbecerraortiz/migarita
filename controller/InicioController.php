<?php
require_once __DIR__ . "/../model/ClienteModel.php";
require_once __DIR__ . "/../app/services/PanelService.php";

/**
 * Panel de inicio: resumen de la cartera de la garita.
 */
class InicioController
{
    public function __construct()
    {
        if (!isset($_SESSION['id'])) {
            header("Location: /mi_garita/index.php?url=login/index");
            exit;
        }
    }

    public function index()
    {
        $panel = (new PanelService(new ClienteModel()))->resumen();

        require __DIR__ . "/../view/inicio/index.php";
    }
}
