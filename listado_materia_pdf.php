<?php
require('fpdf/fpdf.php');
include("conexion.php");

// ==============================
// RECIBIR PARÁMETROS
// ==============================
$id_materia = $_GET['id_materia'] ?? 0;
$anio = $_GET['anio'] ?? 0;
$id_centro = $_GET['id_centro'] ?? null;

// ==============================
// OBTENER NOMBRE DE MATERIA
// ==============================
$materia_nombre = '';
if ($id_materia) {
    $stmt = $conexion->prepare("SELECT nombre FROM materias WHERE id_materia = ?");
    $stmt->execute([$id_materia]);
    $materia_nombre = $stmt->fetchColumn();
}

// ==============================
// CONSULTA DE ALUMNOS
// ==============================
$sql = "SELECT a.apellido, a.nombres
        FROM inscripciones i
        INNER JOIN alumnos a ON a.id_alumno = i.id_alumno
        WHERE i.id_materia = :id_materia
        AND i.anio = :anio
        AND i.activo = 1";

$params = [
    ':id_materia' => $id_materia,
    ':anio' => $anio
];

if (!empty($id_centro)) {
    $sql .= " AND i.id_centro = :id_centro";
    $params[':id_centro'] = $id_centro;
}

$sql .= " ORDER BY a.apellido, a.nombres";

$stmt = $conexion->prepare($sql);
$stmt->execute($params);
$alumnos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ==============================
// CREAR PDF
// ==============================
$pdf = new FPDF('L', 'mm', 'A4'); // Horizontal
$pdf->SetMargins(30, 15, 10);
$pdf->AddPage();

// ==============================
// TÍTULO
// ==============================
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 10, utf8_decode("Materia: $materia_nombre - Año: $anio"), 0, 1, 'C');

$pdf->Ln(3);

// ==============================
// ENCABEZADO DE TABLA
// ==============================
$pdf->SetFont('Arial', 'B', 12);

$pdf->Cell(50, 8, 'Apellido', 1);
$pdf->Cell(50, 8, 'Nombres', 1);

for ($i = 1; $i <= 15; $i++) {
    $pdf->Cell(10, 8, '', 1);
}

$pdf->Ln();

// ==============================
// DATOS DE ALUMNOS
// ==============================
$pdf->SetFont('Arial', '', 12);

$total = count($alumnos);

foreach ($alumnos as $a) {
    $pdf->Cell(50, 8, utf8_decode($a['apellido']), 1);
    $pdf->Cell(50, 8, utf8_decode($a['nombres']), 1);

    for ($i = 1; $i <= 15; $i++) {
        $pdf->Cell(10, 8, '', 1);
    }

    $pdf->Ln();
}

// ==============================
// FILAS VACÍAS CONTROLADAS
// ==============================
$maxFilas = 18; // Ajustar según necesidad

$faltantes = $maxFilas - $total;

if ($faltantes > 0) {
    for ($j = 0; $j < $faltantes; $j++) {
        $pdf->Cell(50, 8, '', 1);
        $pdf->Cell(50, 8, '', 1);

        for ($i = 1; $i <= 15; $i++) {
            $pdf->Cell(10, 8, '', 1);
        }

        $pdf->Ln();
    }
}

// ==============================
// SALIDA
// ==============================
$pdf->Output('I', 'listado_materia.pdf');
?>