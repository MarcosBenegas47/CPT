<?php
require_once("conexion.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 🔐 Control de acceso (ajustalo si querés)
$rol = $_SESSION['rol'] ?? '';
// if ($rol !== 'admin') {
//    echo "<div class='alert alert-danger'>No tenés permisos para ver este módulo</div>";
//    return;
// }

// 📅 Filtro por año
$anio = $_GET['anio'] ?? '';

$filtro = "WHERE i.activo = 1";
$params = [];

if ($anio != '') {

    $filtro .= " AND i.anio = :anio";

    $params[':anio'] = $anio;
}
?>

<div class="container mt-4">

<h2>📊 Reportes del Sistema</h2>
<hr>

<!-- FILTRO -->
<form method="GET" class="mb-4">
    <input type="hidden" name="modulo" value="reportes">

    <div class="row">
        <div class="col-md-3">
            <select name="anio" class="form-select">
                <option value="">Todos los años</option>
                <option value="2024" <?= ($anio == '2024') ? 'selected' : '' ?>>2024</option>
                <option value="2025" <?= ($anio == '2025') ? 'selected' : '' ?>>2025</option>
            </select>
        </div>

        <div class="col-md-2">
            <button type="submit" class="btn btn-primary">Filtrar</button>
        </div>
    </div>
</form>

<!-- ========================= -->
<!-- INSCRIPTOS POR CENTRO -->
<!-- ========================= -->

<h4>Inscriptos por Centro</h4>

<table class="table table-bordered table-striped">
<tr>
    <th>Centro</th>
    <th>Cantidad</th>
</tr>

<?php
$sql = "SELECT c.nombre, COUNT(*) as cantidad
        FROM inscripciones i
        INNER JOIN centros c 
            ON i.id_centro = c.id_centro
        $filtro
        GROUP BY c.nombre
        ORDER BY cantidad DESC";

$stmt = $conexion->prepare($sql);
$stmt->execute($params);

while($fila = $stmt->fetch(PDO::FETCH_ASSOC)){
    echo "<tr>
            <td>{$fila['nombre']}</td>
            <td>{$fila['cantidad']}</td>
          </tr>";
}
?>
</table>

<!-- ========================= -->
<!-- INSCRIPTOS POR MATERIA -->
<!-- ========================= -->

<h4>Inscriptos por Materia</h4>

<table class="table table-bordered table-striped">
<tr>
    <th>Materia</th>
    <th>Cantidad</th>
</tr>

<?php
$sql = "SELECT m.nombre, COUNT(*) as cantidad
        FROM inscripciones i
        INNER JOIN materias m ON i.id_materia = m.id_materia
        $filtro
        GROUP BY m.nombre
        ORDER BY cantidad DESC";

$stmt = $conexion->prepare($sql);
$stmt->execute($params);

while($fila = $stmt->fetch(PDO::FETCH_ASSOC)){
    echo "<tr>
            <td>{$fila['nombre']}</td>
            <td>{$fila['cantidad']}</td>
          </tr>";
}
?>
</table>

<!-- ========================= -->
<!-- ÚLTIMAS INSCRIPCIONES -->
<!-- ========================= -->

<h4>Últimas Inscripciones</h4>

<table class="table table-bordered table-striped">
<tr>
    <th>Alumno</th>
    <th>Centro</th>
    <th>Materia</th>
    <th>Año</th>
</tr>

<?php
$sql = "SELECT a.apellido,
               a.nombres,
               c.nombre AS centro,
               m.nombre,
               i.anio
        FROM inscripciones i

        INNER JOIN alumnos a 
            ON i.id_alumno = a.id_alumno

        INNER JOIN materias m 
            ON i.id_materia = m.id_materia

        INNER JOIN centros c 
            ON i.id_centro = c.id_centro

        $filtro    
        ORDER BY i.id_inscripcion DESC
        LIMIT 50";
$stmt = $conexion->prepare($sql);
$stmt->execute($params);

while($fila = $stmt->fetch(PDO::FETCH_ASSOC)){
    echo "<tr>
            <td>{$fila['apellido']}, {$fila['nombres']}</td>
            <td>{$fila['centro']}</td>
            <td>{$fila['nombre']}</td>
            <td>{$fila['anio']}</td>
          </tr>";
}
?>
</table>

<!-- ========================= -->
<!-- ACTIVIDAD POR USUARIO (si existe relación) -->
<!-- ========================= -->

<?php
// ⚠️ Esto solo funciona si tenés id_usuario en inscripciones
try {
    $sql = "SELECT u.usuario, COUNT(*) as acciones
            FROM inscripciones i
            INNER JOIN usuarios u ON i.id_usuario = u.id_usuario
            GROUP BY u.usuario
            ORDER BY acciones DESC";

    $stmt = $conexion->prepare($sql);
    $stmt->execute();
?>

<h4>Actividad por Usuario</h4>

<table class="table table-bordered table-striped">
<tr>
    <th>Usuario</th>
    <th>Acciones</th>
</tr>

<?php
    while($fila = $stmt->fetch(PDO::FETCH_ASSOC)){
        echo "<tr>
                <td>{$fila['usuario']}</td>
                <td>{$fila['acciones']}</td>
              </tr>";
    }
?>
</table>

<?php
} catch (Exception $e) {
    // Si no existe la relación, no rompe el sistema
}
?>

</div>