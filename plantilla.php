<?php

$modulo = $_GET['modulo'] ?? 'inicio';
$modulo = basename($modulo);

$modulos_validos = [
    'inicio',
    'login',
    'alumnos',
    'materias',
    'inscripciones',
    'inscripciones_historico',
    'notas',
    'listado_materia',
    'reporte_alumno',
    'egresados',
    'graduados',
    'reportes',
    'reporte_alumnos_activos',
    'auditoria',
    'usuarios_alta'
];

if (!in_array($modulo, $modulos_validos)) {
    $modulo = 'inicio';
}

$modulo_actual = $modulo;
include 'header.php';

$archivo = $modulo . '.php';

if (file_exists($archivo)) {
    include $archivo; // ← SOLO UNA VEZ
} else {
    echo "Módulo no encontrado";
}

include 'footer.php';