<?php
include("conexion.php");

$id = $_GET['id'] ?? null;

if (!$id) {
    echo "ID no válido";
    exit;
}

// GUARDAR CAMBIO
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nuevo_anio = $_POST['anio'];

    $sql = "UPDATE inscripciones SET anio = ? WHERE id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->execute([$nuevo_anio, $id]);

    echo "<div style='color:green;'>✔ Año actualizado correctamente</div>";
}

// TRAER DATOS ACTUALES
$sql = "SELECT i.*, a.nombre 
        FROM inscripciones i
        JOIN alumnos a ON a.id = i.id_alumno
        WHERE i.id = ?";
$stmt = $conexion->prepare($sql);
$stmt->execute([$id]);
$inscripcion = $stmt->fetch();

if (!$inscripcion) {
    echo "No se encontró la inscripción";
    exit;
}
?>

<h2>Editar inscripción</h2>

<p><strong>Alumno:</strong> <?= $inscripcion['nombre'] ?></p>
<p><strong>Año actual:</strong> <?= $inscripcion['anio'] ?></p>

<form method="POST">
    <label>Nuevo año:</label>
    <input type="number" name="anio" value="<?= $inscripcion['anio'] ?>" required>
    <br><br>
    <button type="submit">Guardar</button>
</form>