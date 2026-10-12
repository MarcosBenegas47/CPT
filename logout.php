<?php
session_start();

require 'conexion.php';

// Registrar salida
if (!empty($_SESSION['id_acceso'])) {

    $stmt = $conexion->prepare("
        UPDATE auditoria_accesos
        SET fecha_salida = NOW()
        WHERE id = ?
    ");

    $stmt->execute([
        $_SESSION['id_acceso']
    ]);
}

// Vaciar variables de sesión
$_SESSION = array();

// Destruir sesión
session_destroy();

// Redirigir al login
header("Location: plantilla.php?modulo=login");
exit;
?>