<?php
include __DIR__ . "/conexion.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ==========================
// OBTENER GRADUADOS
// ==========================
$query = "
SELECT 
    a.id_alumno,
    CONCAT(a.apellido, ' ', a.nombres) AS nombre,
    c.nombre AS centro,
    YEAR(g.fecha) AS anio
FROM graduaciones g
JOIN alumnos a ON a.id_alumno = g.id_alumno
JOIN centros c ON c.id_centro = g.id_centro
ORDER BY g.fecha DESC, nombre
";

$stmt = $conexion->query($query);
?>

<h2>Alumnos Graduados</h2>

<table border="1" cellpadding="6" cellspacing="0">
    <tr>
        <th>Alumno</th>
        <th>Centro</th>
        <th>Año</th>
    </tr>

<?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) { ?>
    <tr>
        <td><?= htmlspecialchars($row['nombre']) ?></td>
        <td><?= htmlspecialchars($row['centro']) ?></td>
        <td><?= $row['anio'] ?></td>
    </tr>
<?php } ?>

</table>