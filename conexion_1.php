<?php

$host = "localhost";
$db   = "a0120377_cpt";
$user = "a0120377_cpt";
$pass = "visiondefuturo@CPT2026";

try {
    $conexion = new PDO(
        "mysql:host=$host;dbname=$db;charset=utf8",
        $user,
        $pass
    );

    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>