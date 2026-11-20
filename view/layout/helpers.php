<?php
/**
 * Funciones de ayuda para las vistas.
 * Todas las salidas de datos pasan por e() para evitar inyección de HTML/JS.
 */

if (!function_exists('e')) {
    /** Escapa texto para imprimirlo en HTML. */
    function e($valor): string
    {
        return htmlspecialchars((string)($valor ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('url')) {
    /** URL interna de la app: url('cliente/index') => /mi_garita/index.php?url=cliente/index */
    function url(string $ruta = ''): string
    {
        return '/mi_garita/index.php?url=' . $ruta;
    }
}

if (!function_exists('asset')) {
    function asset(string $ruta): string
    {
        return '/mi_garita/assets/' . ltrim($ruta, '/');
    }
}

if (!function_exists('dinero')) {
    /** 15000 => "$ 15.000" (formato colombiano, sin decimales). */
    function dinero($valor): string
    {
        return '$ ' . number_format((float)$valor, 0, ',', '.');
    }
}

if (!function_exists('iniciales')) {
    function iniciales(?string $nombre, ?string $apellido = ''): string
    {
        $letra = fn($t) => mb_strtoupper(mb_substr(trim((string)$t), 0, 1, 'UTF-8'), 'UTF-8');

        return $letra($nombre) . $letra($apellido);
    }
}

if (!function_exists('clase_tipo')) {
    /** Clase CSS de la etiqueta según el tipo de cliente o rol. */
    function clase_tipo(?string $tipo): string
    {
        $tipo = strtolower(trim((string)$tipo));
        $conocidos = ['estudiante', 'profesor', 'administrativo', 'egresado', 'externo', 'superusuario'];

        return in_array($tipo, $conocidos, true) ? $tipo : '';
    }
}

if (!function_exists('fecha_corta')) {
    /** "2026-11-20 14:35:00" => "20 nov · 2:35 p. m." */
    function fecha_corta(?string $fecha): string
    {
        if (!$fecha) {
            return '';
        }
        $meses = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
        $t = strtotime($fecha);
        $hora = date('g:i', $t) . (date('A', $t) === 'AM' ? ' a. m.' : ' p. m.');

        return date('j', $t) . ' ' . $meses[(int)date('n', $t) - 1] . ' · ' . $hora;
    }
}

if (!function_exists('error_en')) {
    /**
     * Devuelve " is-invalid" si el código de error de la URL (?error=...) corresponde al campo.
     * Ej.: error=telefono_invalido marca el campo "telefono".
     */
    function error_en(string $campo): string
    {
        $error = $_GET['error'] ?? '';
        if ($error === '') {
            return '';
        }

        $alias = ['tipo' => 'id_tipo_cliente', 'no' => 'rol'];
        $prefijo = explode('_', $error)[0];
        $prefijo = $alias[$prefijo] ?? $prefijo;

        return $prefijo === $campo ? ' is-invalid' : '';
    }
}
