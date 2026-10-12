<?php
include(__DIR__ . "/conexion.php");

$texto = $_GET['texto'] ?? '';

if ($texto == '') {

    $sql = "SELECT * FROM materias ORDER BY anio, nombre";
    $stmt = $conexion->prepare($sql);

} else {

    $sql = "SELECT * FROM materias 
            WHERE nombre LIKE :texto 
            ORDER BY anio, nombre";

    $stmt = $conexion->prepare($sql);
    $buscar = "%$texto%";
    $stmt->bindParam(':texto', $buscar);
}

$stmt->execute();

$materias = $stmt->fetchAll(PDO::FETCH_ASSOC);

$niveles = [
    1 => "1° Año",
    2 => "2° Año",
    3 => "3° Año",
    99 => "Extra"
];

echo "<table class='table table-sm table-hover'>
        <thead>
            <tr>
                <th>Materia</th>
                <th>Año</th>
            </tr>
        </thead>
        <tbody>";

foreach ($materias as $fila) {

    $nivel_texto = $niveles[$fila['anio']] ?? '';

    echo "<tr class='materia-item' style='cursor:pointer'
            data-id='{$fila['id_materia']}'
            data-nombre='{$fila['nombre']}'
            data-descripcion='{$fila['descripcion']}'
            data-anio='{$fila['anio']}'>

            <td><strong>{$fila['nombre']}</strong></td>
            <td>{$nivel_texto}</td>

          </tr>";
}

echo "</tbody></table>";

if (count($materias) == 0) {
    echo "<div class='list-group-item text-center'>
            No se encontraron resultados<br>
            <button class='btn btn-success btn-sm mt-2'
                onclick='agregarMateria(\"$texto\")'>
                + Nueva materia
            </button>
          </div>";
}