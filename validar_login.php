<?php
session_start();
require 'conexion.php';

// 1. Validar datos
if (!isset($_POST['usuario'], $_POST['clave'])) {
    header("Location: plantilla.php?modulo=login&error=1");
    exit;
}

$usuario = trim($_POST['usuario']);
$password = trim($_POST['clave']);

// 2. Buscar usuario (SIN contraseña)
$sql = "SELECT u.*, c.nombre AS centro_nombre
        FROM usuarios u
        LEFT JOIN centros c ON u.id_centro = c.id_centro
        WHERE u.usuario = :usuario";

$stmt = $conexion->prepare($sql);
$stmt->execute(['usuario' => $usuario]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

// 3. Validar contraseña correctamente
if ($user && password_verify($password, $user['clave'])) {

    session_regenerate_id(true);

    $_SESSION['id_usuario'] = $user['id_usuario'];
    $_SESSION['centro']     = $user['centro_nombre'];
    $_SESSION['id_centro']  = $user['id_centro'];
    $_SESSION['rol']        = $user['rol'];
    $_SESSION['nombre']     = $user['nombre'];
    $_SESSION['usuario']    = $user['usuario'];

    $stmt = $conexion->prepare("
    INSERT INTO auditoria_accesos
    (
        id_usuario,
        fecha_ingreso,
        ip,
        navegador
    )
    VALUES
    (
        ?,
        NOW(),
        ?,
        ?
    )
");

$stmt->execute([
    $user['id_usuario'],
    $_SERVER['REMOTE_ADDR'] ?? '',
    $_SERVER['HTTP_USER_AGENT'] ?? ''
]);

$_SESSION['id_acceso'] = $conexion->lastInsertId();
    
    header("Location: plantilla.php?modulo=inicio");
    exit;

} else {

    $_SESSION = [];
    session_destroy();

    header("Location: plantilla.php?modulo=login&error=1");
    exit;
}