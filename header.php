<?php
ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// módulo actual
$modulo = $_GET['modulo'] ?? 'inicio';

// 🔒 protección
if (!isset($_SESSION['id_usuario']) && $modulo !== 'login') {
    header("Location: plantilla.php?modulo=login");
    exit;
}

// 🔁 evitar volver al login si ya está logueado
if (isset($_SESSION['id_usuario']) && $modulo === 'login') {
    header("Location: plantilla.php?modulo=inicio");
    exit;
}

$rol = $_SESSION['rol'] ?? '';
$id_centro = $_SESSION['id_centro'] ?? 0;

$es_admin = ($rol === 'admin');
$es_director = ($rol === 'director');
$es_operador = ($rol === 'operador');

$es_sistemas = ($es_admin && $id_centro == 22);
$es_direccion_general = ($es_admin && $id_centro == 1);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Sistema CPT</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <style>
    body {
        font-family: Arial, sans-serif;
        margin: 0;
        background: #f5f5f5;
    }

    .header {
    background: #2c3e50;
    color: white;
    padding: 15px;

    display: flex;
    align-items: center;
    gap: 12px;
    }

    .header h2 {
        margin: 0;
    }

    .logo {
        height:60px;
    }
    .menu {
        background: #34495e;
        padding: 25px 25px;
    }

    .menu a {
        color: white;
        margin-right: 15px;
        text-decoration: none;
        font-size: 14px;
    }

    .menu a:hover {
        text-decoration: underline;
    }

    .contenido {
        max-width: 1000px;
        margin: 20px auto;
        padding: 15px;
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }

    footer {
        background: #2c3e50;
        color: white;
        text-align: center;
        padding: 10px;
        margin-top: 20px;
    }

    .menu a.activo {
        background: #1abc9c;
        padding: 5px 10px;
        border-radius: 5px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
        font-size: 14px;
    }

    th {
        background: #2c3e50;
        color: white;
        text-align: left;
    }

    td, th {
        padding: 10px;
        border-bottom: 1px solid #ddd;
    }

    tr:nth-child(even) {
        background: #f9f9f9;
    }

    tr:hover {
        background: #f1f1f1;
    }

    button {
    padding: 5px 10px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    }

    button[name="inscribir"] {
        background: #27ae60;
        color: white;
    }

    button[name="inscribir"]:hover {
        background: #219150;
    }

    button[name="borrar"] {
        background: #e74c3c;
        color: white;
    }

    button[name="borrar"]:hover {
        background: #c0392b;
    }

    /* Chrome, Safari, Edge */
    input[type=number]::-webkit-outer-spin-button,
    input[type=number]::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    /* Firefox */
    input[type=number] {
        -moz-appearance: textfield;
    }

    
    @media print {

    /* Oculta TODO */
    body * {
        visibility: hidden;
    }

    /* Muestra SOLO la planilla */
    #zona-impresion, #zona-impresion * {
        visibility: visible;
    }

    /* La posiciona arriba */
    #zona-impresion {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
    }

    /* Oculta botones */
    button {
        display: none;
    }
}

</style>
</head>
<body>

<div class="header">
    <img src="img/logo_cpt.png" alt="Logo CPT" class="logo">
    <h2>Sistema de gestión - CPT</h2>
</div>
<div class="menu">

    
<?php if ($es_admin || $es_director || $es_operador): ?>
        <a href="plantilla.php?modulo=inicio"
            class="<?= ($modulo == 'inicio') ? 'activo' : '' ?>">
           Inicio
        </a>
    <?php endif; ?>

    <?php if ($es_admin || $es_director || $es_operador): ?>
        <a href="plantilla.php?modulo=alumnos"
            class="<?= ($modulo == 'alumnos') ? 'activo' : '' ?>">
            Alumnos
        </a>
    <?php endif; ?>

    <?php if ($es_admin): ?>
        <a href="plantilla.php?modulo=materias"
            class="<?= ($modulo == 'materias') ? 'activo' : '' ?>">
            Materias
        </a>
    <?php endif; ?>

    <?php if ($es_admin || $es_director || $es_operador): ?>
        <a href="plantilla.php?modulo=inscripciones"
            class="<?= ($modulo == 'inscripciones') ? 'activo' : '' ?>">
            Inscripciones
        </a>
    <?php endif; ?>

    <?php if ($es_admin || $es_director || $es_operador): ?>
        <a href="plantilla.php?modulo=notas"
            class="<?= ($modulo == 'notas') ? 'activo' : '' ?>">
            Notas
        </a>
    <?php endif; ?>

    <?php if ($es_admin || $es_director || $es_operador): ?>
        <a href="plantilla.php?modulo=listado_materia"
            class="<?= ($modulo == 'listado_materia') ? 'activo' : '' ?>">
           Listado x Materia
        </a>
    <?php endif; ?>

        <?php if ($es_admin || $es_director || $es_operador): ?>
        <a href="plantilla.php?modulo=reporte_alumno"
            class="<?= ($modulo == 'reporte_alumno') ? 'activo' : '' ?>">
           Reporte x Alumno
        </a>
    <?php endif; ?> 

    <?php if ($es_admin || $es_director || $es_operador): ?>
        <a href="plantilla.php?modulo=egresados"
            class="<?= ($modulo == 'egresados') ? 'activo' : '' ?>">
            Egresados
        </a>
    <?php endif; ?>
    
   
    <?php if ($es_admin): ?>
        <a href="plantilla.php?modulo=graduados"
            class="<?= ($modulo == 'graduados') ? 'activo' : '' ?>">
            Graduados
        </a>
    <?php endif; ?>

     <?php if ($es_admin): ?>
        <a href="plantilla.php?modulo=reportes"
            class="<?= ($modulo == 'reportes') ? 'activo' : '' ?>">
            Reporte de Sistema
        </a>
    <?php endif; ?>

    <?php if ($es_sistemas): ?>
    <a href="plantilla.php?modulo=inscripciones_historico"
        class="<?= ($modulo == 'inscripciones_historico') ? 'activo' : '' ?>">
        Histórico
    </a>
    <?php endif; ?>

    <?php if ($es_sistemas): ?>
        <a href="plantilla.php?modulo=usuarios_alta"
            class="<?= ($modulo == 'usuarios_alta') ? 'activo' : '' ?>">
            Sistemas
        </a>
    <?php endif; ?>

    <?php if ($es_sistemas || $es_admin || $es_director || $es_operador): ?>
        <a href="plantilla.php?modulo=reporte_alumnos_activos"    
           class="<?= ($modulo == 'reporte_alumnos_activos') ? 'activo' : '' ?>">
           Alumnos activos
        </a>
    <?php endif; ?> 

     <?php if ($es_sistemas): ?>
        <a href="plantilla.php?modulo=auditoria"
            class="<?= ($modulo == 'auditoria') ? 'activo' : '' ?>">
            Auditoría
        </a>
    <?php endif; ?>

    <span style="float:right; color:#2ecc71;">
        👤 <?= $_SESSION['nombre'] ?? '' ?>
        | <a href="logout.php" style="color:white;">Salir</a>
    </span>

</div>

<div class="contenido">

<script src="js/app.js"></script>