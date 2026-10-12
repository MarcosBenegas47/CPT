<?php

require('fpdf/fpdf.php');
include("conexion.php");

$id_alumno = $_GET['id_alumno'] ?? 0;
$plan = $_GET['plan'] ?? 3;

if (!$id_alumno) {
    die("Alumno no especificado");
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
    die("Alumno no encontrado");
}


// ======================================================
// CLASE PDF
// ======================================================

class PDF extends FPDF
{
  function Header()
{
    // Título
    $this->SetFont('Arial', 'B', 15);

    $this->Cell(
        0,
        8,
        utf8_decode('REPORTE ACADÉMICO'),
        0,
        1,
        'C'
    );

    // Número de hoja
    $this->SetFont('Arial', '', 8);

    $this->Cell(
        0,
        5,
        'Hoja ' . $this->PageNo(),
        0,
        1,
        'R'
    );

    $this->Ln(2);
}

    function Footer()
    {
        $this->SetY(-15);

        $this->SetFont('Arial', '', 8);

        $this->Cell(
            0,
            10,
            utf8_decode('Centro de Preparación Teológica - Iglesia Visión de Futuro'),
            0,
            0,
            'C'
        );
    }
}


// ======================================================
// CREAR PDF
// ======================================================

$pdf = new PDF('P', 'mm', 'A4');

$pdf->SetMargins(10, 10, 10);
$pdf->SetAutoPageBreak(true, 18);

$pdf->AddPage();


// ======================================================
// DATOS DEL ALUMNO
// ======================================================

$pdf->SetFont('Arial', 'B', 11);

$pdf->Cell(30, 7, 'Alumno:', 0, 0);

$pdf->SetFont('Arial', '', 11);

$pdf->Cell(
    0,
    7,
    $alumno['apellido'] . ', ' . $alumno['nombres'],
    0,
    1
);

$pdf->SetFont('Arial', 'B', 11);

$pdf->Cell(30, 7, 'DNI:', 0, 0);

$pdf->SetFont('Arial', '', 11);

$pdf->Cell(
    0,
    7,
    $alumno['documento'],
    0,
    1
);

$pdf->Ln(5);


// ======================================================
// PLAN DE ESTUDIOS
// ======================================================

$pdf->SetFont('Arial', 'B', 11);

$pdf->Cell(
    0,
    8,
    utf8_decode('PLAN DE ESTUDIOS ' . $plan . ' AÑOS'),
    0,
    1
);


// Encabezado

$pdf->SetFont('Arial', 'B', 8);

$pdf->Cell(10, 7, utf8_decode('Nº'), 1, 0, 'C');
$pdf->Cell(75, 7, 'Materia', 1, 0, 'C');
$pdf->Cell(15, 7, 'Ano', 1, 0, 'C');
$pdf->Cell(30, 7, 'Centro', 1, 0, 'C');
$pdf->Cell(30, 7, 'Modalidad', 1, 0, 'C');
$pdf->Cell(20, 7, 'Nota', 1, 1, 'C');


// Consulta según el plan seleccionado

$campo_plan = ($plan == 2) ? "m.plan2" : "m.plan3";

$sql = "
    SELECT 
        m.nombre AS materia,
        m.plan2,
        m.plan3,
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

    WHERE $campo_plan BETWEEN 1 AND 52

    ORDER BY $campo_plan
";

$stmt = $conexion->prepare($sql);

$stmt->execute([
    'alumno' => $id_alumno
]);

// Datos

$pdf->SetFont('Arial', '', 7.5);

$total_aprobadas = 0;
$suma_notas = 0;


foreach ($stmt as $fila) {

    $anio = $fila['anio'] ?? '-';
    $centro = $fila['centro'] ?? '-';
    $modalidad = $fila['modalidad'] ?? '-';
    $nota = $fila['nota'] ?? '-';

    $numero_plan = ($plan == 2) ? $fila['plan2'] : $fila['plan3'];

    if ($fila['nota'] !== null) {

        $total_aprobadas++;

        $suma_notas += (float)$fila['nota'];
    }



    $pdf->Cell(
        10,
        6,
        $numero_plan,
        1,
        0,
        'C'
    );

    $pdf->Cell(
        75,
        6,
        utf8_decode($fila['materia']),
        1,
        0,
        'L'
    );

    $pdf->Cell(
        15,
        6,
        $anio,
        1,
        0,
        'C'
    );

    $pdf->Cell(
        30,
        6,
        utf8_decode($centro),
        1,
        0,
        'L'
    );

    $pdf->Cell(
        30,
        6,
        utf8_decode($modalidad),
        1,
        0,
        'L'
    );

    $pdf->Cell(
        20,
        6,
        $nota,
        1,
        1,
        'C'
    );
}


// ======================================================
// MATERIAS ADICIONALES
// ======================================================

// ======================================================
// MATERIAS ADICIONALES
// ======================================================

$campo_plan_adicional = ($plan == 2) ? "m.plan2" : "m.plan3";

$sql_adicionales = "
    SELECT
        m.nombre AS materia,
        i.anio,
        c.nombre AS centro,
        i.modalidad,
        n.nota

    FROM notas n

    INNER JOIN inscripciones i
        ON n.id_inscripcion = i.id_inscripcion

    INNER JOIN materias m
        ON i.id_materia = m.id_materia

    LEFT JOIN centros c
        ON i.id_centro = c.id_centro

    WHERE i.id_alumno = :alumno
      AND n.nota IS NOT NULL
      AND $campo_plan_adicional IS NULL

    ORDER BY m.nombre
";

$stmt_adicionales = $conexion->prepare($sql_adicionales);

$stmt_adicionales->execute([
    'alumno' => $id_alumno
]);

$adicionales = $stmt_adicionales->fetchAll(PDO::FETCH_ASSOC);


// ======================================================
// TITULO ADICIONALES
// ======================================================

$pdf->Ln(5);

$pdf->SetFont('Arial', 'B', 11);

$pdf->Cell(
    0,
    8,
    'MATERIAS ADICIONALES APROBADAS',
    0,
    1
);


if (count($adicionales) > 0) {

    $pdf->SetFont('Arial', 'B', 8);

    $pdf->Cell(85, 7, 'Materia', 1, 0, 'C');
    $pdf->Cell(15, 7, 'Ano', 1, 0, 'C');
    $pdf->Cell(30, 7, 'Centro', 1, 0, 'C');
    $pdf->Cell(30, 7, 'Modalidad', 1, 0, 'C');
    $pdf->Cell(20, 7, 'Nota', 1, 1, 'C');


    $pdf->SetFont('Arial', '', 7.5);


    foreach ($adicionales as $fila) {

        $total_aprobadas++;

        $suma_notas += (float)$fila['nota'];


        $anio = $fila['anio'] ?? '-';
        $centro = $fila['centro'] ?? '-';
        $modalidad = $fila['modalidad'] ?? '-';


        $pdf->Cell(
            85,
            6,
            utf8_decode($fila['materia']),
            1,
            0,
            'L'
        );

        $pdf->Cell(
            15,
            6,
            $anio,
            1,
            0,
            'C'
        );

        $pdf->Cell(
            30,
            6,
            utf8_decode($centro),
            1,
            0,
            'L'
        );

        $pdf->Cell(
            30,
            6,
            utf8_decode($modalidad),
            1,
            0,
            'L'
        );

        $pdf->Cell(
            20,
            6,
            $fila['nota'],
            1,
            1,
            'C'
        );
    }
}


// ======================================================
// RESUMEN
// ======================================================

$promedio = $total_aprobadas > 0
    ? round($suma_notas / $total_aprobadas, 2)
    : 0;


$pdf->Ln(6);

$pdf->SetFont('Arial', 'B', 11);

$pdf->Cell(
    0,
    8,
    'RESUMEN ACADEMICO',
    0,
    1
);


$pdf->SetFont('Arial', '', 10);

$pdf->Cell(
    60,
    7,
    'Materias aprobadas:',
    0,
    0
);

$pdf->SetFont('Arial', 'B', 10);

$pdf->Cell(
    30,
    7,
    $total_aprobadas,
    0,
    1
);


$pdf->SetFont('Arial', '', 10);

$pdf->Cell(
    60,
    7,
    'Promedio general:',
    0,
    0
);

$pdf->SetFont('Arial', 'B', 10);

$pdf->Cell(
    30,
    7,
    number_format($promedio, 2, ',', '.'),
    0,
    1
);


// ======================================================
// SALIDA
// ======================================================

// ======================================================
// GUARDAR COPIA EN LA CARPETA CERTIFICADOS
// ======================================================

$carpeta = __DIR__ . '/certificados';

if (!is_dir($carpeta)) {
    mkdir($carpeta, 0755, true);
}

$nombre_archivo = 'reporte_academico_' . $id_alumno . '.pdf';

$ruta_archivo = $carpeta . '/' . $nombre_archivo;

// Guardar en el servidor
$pdf->Output('F', $ruta_archivo);

// Mostrar el PDF en el navegador
$pdf->Output('I', $nombre_archivo);

?>