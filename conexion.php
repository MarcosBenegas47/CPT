<?php
$host = "localhost";
$db = "cpt";
$user = "root";
$pass = "";

try {
    $conexion = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // echo "Conexión exitosa"; // opcional para probar
} catch (PDOException $e) {
    echo "Error de conexión: " . $e->getMessage();
}
?>