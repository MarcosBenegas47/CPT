<?php

require('fpdf/fpdf.php');
include("conexion.php");

$id_alumno = $_GET['id_alumno'] ?? 0;

if (!$id_alumno) {
    die("Alumno no especificado.");
}


// ======================================================
// DATOS DEL ALUMNO
// ======================================================

$stmt = $conexion->prepare("
    SELECT apellido, nombres, documento
    FROM alumnos
    WHERE id_alumno = :id
");

$stmt->execute(['id' => $id_alumno]);
$alumno = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$alumno) {
    die("Alumno no encontrado.");
}


// ======================================================
// MATERIAS DEL PLAN 3
// ======================================================

$sql = "
    SELECT
        m.plan3,
        m.nombre AS materia,
        i.id_inscripcion,
        i.anio,
        c.nombre AS centro,
        i.modalidad,
        n.nota
    FROM materias m

    LEFT JOIN inscripciones i
        ON i.id_materia = m.id_materia
        AND i.id_alumno = :alumno
        AND i.activo = 1

    LEFT JOIN centros c
        ON i.id_centro = c.id_centro

    LEFT JOIN notas n
        ON n.id_inscripcion = i.id_inscripcion

    WHERE m.plan3 BETWEEN 1 AND 49

    ORDER BY m.plan3
";

$stmt = $conexion->prepare($sql);
$stmt->execute(['alumno' => $id_alumno]);

$materias = $stmt->fetchAll(PDO::FETCH_ASSOC);


// ======================================================
// DATOS PARA EL RESUMEN
// ======================================================

$cantidad_con_nota = 0;
$suma_notas = 0;

foreach ($materias as $fila) {

    if ($fila['nota'] !== null) {
        $cantidad_con_nota++;
        $suma_notas += $fila['nota'];
    }
}

$pendientes = 49 - $cantidad_con_nota;

$promedio = $cantidad_con_nota > 0
    ? round($suma_notas / $cantidad_con_nota, 2)
    : 0;


// ======================================================
// CREAR PDF
// ======================================================

$pdf = new FPDF('P', 'mm', 'A4');

$pdf->SetMargins(10, 10, 10);
$pdf->SetAutoPageBreak(true, 12);

$pdf->AddPage();


// ======================================================
// ENCABEZADO
// ======================================================

$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 8, utf8_decode('CENTRO DE PREPARACIÓN TEOLÓGICA'), 0, 1, 'C');

$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 8, utf8_decode('REPORTE ACADÉMICO'), 0, 1, 'C');

$pdf->Ln(3);


// Datos del alumno

$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(25, 6, 'Alumno:', 0, 0);

$pdf->SetFont('Arial', '', 10);
$pdf->Cell(
    100,
    6,
    utf8_decode($alumno['apellido'] . ', ' . $alumno['nombres']),
    0,
    0
);

$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(20, 6, 'DNI:', 0, 0);

$pdf->SetFont('Arial', '', 10);
$pdf->Cell(
    50,
    6,
    $alumno['documento'],
    0,
    1
);


$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(25, 6, 'Plan:', 0, 0);

$pdf->SetFont('Arial', '', 10);
$pdf->Cell(
    100,
    6,
    'Plan 3 años',
    0,
    1
);

$pdf->Ln(4);


// ======================================================
// ENCABEZADO DE TABLA
// ======================================================

$pdf->SetFont('Arial', 'B', 8);

$pdf->Cell(10, 8, 'Nº', 1, 0, 'C');
$pdf->Cell(78, 8, utf8_decode('Materia'), 1, 0, 'C');
$pdf->Cell(15, 8, utf8_decode('Cursó'), 1, 0, 'C');
$pdf->Cell(18, 8, 'Año', 1, 0, 'C');
$pdf->Cell(45, 8, 'Centro', 1, 0, 'C');
$pdf->Cell(35, 8, utf8_decode('Modalidad'), 1, 0, 'C');
$pdf->Cell(18, 8, 'Nota', 1, 1, 'C');


// ======================================================
// MATERIAS
// ======================================================

$pdf->SetFont('Arial', '', 8);

foreach ($materias as $fila) {

    $curso = $fila['id_inscripcion'] ? 'SI' : 'NO';

    $anio = $fila['anio'] ?? '-';

    $centro = $fila['centro'] ?? '-';

    $modalidad = $fila['modalidad'] ?? '-';

    $nota = $fila['nota'] !== null
        ? $fila['nota']
        : '-';


    $pdf->Cell(
        10,
        7,
        $fila['plan3'],
        1,
        0,
        'C'
    );

    $pdf->Cell(
        78,
        7,
        utf8_decode($fila['materia']),
        1,
        0
    );

    $pdf->Cell(
        15,
        7,
        $curso,
        1,
        0,
        'C'
    );

    $pdf->Cell(
        18,
        7,
        $anio,
        1,
        0,
        'C'
    );

    $pdf->Cell(
        45,
        7,
        utf8_decode($centro),
        1,
        0
    );

    $pdf->Cell(
        35,
        7,
        utf8_decode($modalidad),
        1,
        0
    );

    $pdf->Cell(
        18,
        7,
        $nota,
        1,
        1,
        'C'
    );
}


// ======================================================
// RESUMEN
// ======================================================

$pdf->Ln(5);

$pdf->SetFont('Arial', 'B', 10);

$pdf->Cell(
    55,
    7,
    utf8_decode('Materias con nota:'),
    0,
    0
);

$pdf->SetFont('Arial', '', 10);

$pdf->Cell(
    25,
    7,
    $cantidad_con_nota . ' / 49',
    0,
    0
);


$pdf->SetFont('Arial', 'B', 10);

$pdf->Cell(
    50,
    7,
    utf8_decode('Materias pendientes:'),
    0,
    0
);

$pdf->SetFont('Arial', '', 10);

$pdf->Cell(
    25,
    7,
    $pendientes,
    0,
    0
);


$pdf->SetFont('Arial', 'B', 10);

$pdf->Cell(
    35,
    7,
    'Promedio:',
    0,
    0
);

$pdf->SetFont('Arial', '', 10);

$pdf->Cell(
    25,
    7,
    number_format($promedio, 2, ',', '.'),
    0,
    1
);


// ======================================================
// SALIDA
// ======================================================

$nombreArchivo =
    'RA' .
    $id_alumno .
    '_1.pdf';

$pdf->Output('D', $nombreArchivo);

?>