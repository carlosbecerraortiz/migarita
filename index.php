<?php
session_start();
date_default_timezone_set('America/Bogota');

$url = isset($_GET['url']) ? $_GET['url'] : 'login/index';

$partes = explode('/', $url);
$controllerName = ucfirst($partes[0]) . "Controller";
$method = $partes[1] ?? 'index';
$param = $partes[2] ?? null;

$controllerFile = "controller/" . $controllerName . ".php";

if (!file_exists($controllerFile)) {
    die("No existe el controlador: $controllerName");
}

require_once $controllerFile;

$controller = new $controllerName();

if (!method_exists($controller, $method)) {
    die("No existe el método $method en $controllerName");
}

if ($controllerName === "ClienteController" && $method === "buscarAjax") {
    require_once "controller/ClienteController.php";
    $controller = new ClienteController();
    $controller->buscarAjax();
    exit;
}

($param !== null) ? $controller->$method($param) : $controller->$method();
