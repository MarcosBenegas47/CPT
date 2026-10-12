<?php
session_start();
require('conexion.php');
require(__DIR__ . '/fpdf/fpdf.php');

// =======================
// SEGURIDAD (Director y Admin)
// =======================
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['admin', 'director'])) {
    die("Acceso denegado");
}

// =======================
// VALIDAR ID
// =======================
if (!isset($_GET['id_alumno'])) {
    die("Alumno no especificado");
}

$id_alumno = $_GET['id_alumno'];

// =======================
// DATOS DEL ALUMNO
// =======================
$sql = "SELECT apellido, nombres, documento
        FROM alumnos
        WHERE id_alumno = :id";

$stmt = $conexion->prepare($sql);
$stmt->execute(['id' => $id_alumno]);
$alumno = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$alumno) {
    die("Alumno no encontrado");
}

// =======================
// ¿ES GRADUADO?
// =======================
$sql = "SELECT fecha
        FROM graduaciones
        WHERE id_alumno = :id";

$stmt = $conexion->prepare($sql);
$stmt->execute(['id' => $id_alumno]);
$graduacion = $stmt->fetch(PDO::FETCH_ASSOC);

$es_graduado = $graduacion ? true : false;

// =======================
// MATERIAS APROBADAS
// =======================

$es_graduado = $graduacion ? true : false;

// PLAN PARA EL CERTIFICADO
$plan = $_GET['plan'] ?? 3;

if ($plan == 2) {
    $orden = "m.plan2 IS NULL, m.plan2 ASC, m.nombre ASC";
} else {
    $orden = "m.plan3 IS NULL, m.plan3 ASC, m.nombre ASC";
}

// ====================
// MATERIAS APROBADAS
// ====================


$sql = "SELECT m.nombre, m.anio, n.nota
        FROM inscripciones i
        JOIN materias m ON i.id_materia = m.id_materia
        JOIN notas n ON n.id_inscripcion = i.id_inscripcion
        WHERE i.id_alumno = :id
        AND n.nota >= 60
        ORDER BY $orden";

$stmt = $conexion->prepare($sql);
$stmt->execute(['id' => $id_alumno]);
$materias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Para un alumno graduado usamos únicamente el AÑO de la graduación.
// No mostramos el día ni el mes, ya que no se dispone de la fecha
// exacta de culminación de los estudios.
$anio_culminacion = null;

if ($es_graduado && !empty($graduacion['fecha'])) {
    $anio_culminacion = date('Y', strtotime($graduacion['fecha']));
}

// =======================
// FUNCION PARA TEXTO FPDF
// =======================
function pdfText($texto)
{
    return utf8_decode($texto);
}

// =======================
// PDF
// =======================
$pdf = new FPDF('P', 'mm', 'A4');
$pdf->SetMargins(18, 12, 18);
$pdf->SetAutoPageBreak(false);
$pdf->AddPage();

// =======================
// HOJA YA MEMBRETADA
// =======================
// No se genera logo ni encabezado institucional.
// Conservamos la posición vertical del contenido del certificado.
$pdf->SetY(56);

// =======================
// FECHA DEL CERTIFICADO
// =======================
$meses = [
    1 => 'enero',
    2 => 'febrero',
    3 => 'marzo',
    4 => 'abril',
    5 => 'mayo',
    6 => 'junio',
    7 => 'julio',
    8 => 'agosto',
    9 => 'septiembre',
    10 => 'octubre',
    11 => 'noviembre',
    12 => 'diciembre'
];

$pdf->SetFont('Arial', '', 10);
$pdf->Cell(
    0,
    5,
    pdfText('Buenos Aires, ' . date('j') . ' de ' . $meses[(int) date('n')] . ' de ' . date('Y')),
    0,
    1,
    'R'
);

$pdf->Ln(5);

// =======================
// TEXTO INSTITUCIONAL
// =======================
$pdf->SetFont('Arial', '', 10.5);

$apellido = $alumno['apellido'];
$nombres = $alumno['nombres'];
$dni = $alumno['documento'];

if ($es_graduado && $anio_culminacion !== null) {
    $texto = "Por medio de la presente, certifico que $apellido, $nombres D.N.I. $dni ha cursado satisfactoriamente en el Instituto Bíblico de la Iglesia Visión de Futuro, Fichero de Culto 1960, y Personería Jurídica Nro. 110 culminando dichos estudios en el año $anio_culminacion.-";
} else {
    $texto = "Por medio de la presente, certifico que $apellido, $nombres D.N.I. $dni ha cursado satisfactoriamente en el Instituto Bíblico de la Iglesia Visión de Futuro, Fichero de Culto 1960, y Personería Jurídica Nro. 110 las materias que se detallan a continuación.-";
}

$pdf->MultiCell(0, 5.5, pdfText($texto), 0, 'J');

$pdf->Ln(3);

$pdf->SetFont('Arial', '', 10.5);
$pdf->MultiCell(
    0,
    5.5,
    pdfText('A continuación, se detallan las materias cursadas y aprobadas:'),
    0,
    'L'
);

$pdf->Ln(4);

// =======================
// MATERIAS EN DOS COLUMNAS
// =======================
$pdf->SetFont('Arial', '', 9);

$total = count($materias);
$mitad = (int) ceil($total / 2);

$columnaIzq = array_slice($materias, 0, $mitad);
$columnaDer = array_slice($materias, $mitad);

$xIzq = 18;
$xDer = 105;

$anchoMateria = 73;
$anchoNota = 9;
$altoFila = 5.1;

$filas = max(count($columnaIzq), count($columnaDer));

for ($i = 0; $i < $filas; $i++) {

    $y = $pdf->GetY();

    // Columna izquierda
    if (isset($columnaIzq[$i])) {
        $materia = pdfText($columnaIzq[$i]['nombre']);
        $nota = $columnaIzq[$i]['nota'];

        $pdf->SetXY($xIzq, $y);
        $pdf->Cell($anchoMateria, $altoFila, $materia, 0, 0, 'L');
        $pdf->Cell($anchoNota, $altoFila, $nota, 0, 0, 'R');
    }

    // Columna derecha
    if (isset($columnaDer[$i])) {
        $materia = pdfText($columnaDer[$i]['nombre']);
        $nota = $columnaDer[$i]['nota'];

        $pdf->SetXY($xDer, $y);
        $pdf->Cell($anchoMateria, $altoFila, $materia, 0, 0, 'L');
        $pdf->Cell($anchoNota, $altoFila, $nota, 0, 0, 'R');
    }

    $pdf->SetY($y + $altoFila);
}

/// =======================
// CIERRE
// =======================
$y_cierre = $pdf->GetY() + 10;

if ($y_cierre > 250) {
    $y_cierre = 250;
}

$pdf->SetY($y_cierre);
$pdf->SetFont('Arial', '', 9.5);

$pdf->MultiCell(
    0,
    5,
    pdfText('Ante cualquier consulta, puede comunicarse a centrodepreparacionteologica@gmail.com'),
    0,
    'L'
);

$pdf->MultiCell(
    0,
    5,
    pdfText('Quedando a su entera disposición.-'),
    0,
    'L'
);

// =======================
// FIRMA
// =======================
$pdf->SetY($pdf->GetY() + 5);
$pdf->SetFont('Arial', '', 9.5);
$pdf->Cell(0, 5, '______________________________', 0, 1, 'R');
$pdf->Cell(0, 5, pdfText('Firma autorizada'), 0, 1, 'R');

// =======================
// GUARDAR ARCHIVO
// =======================
$centro = $_SESSION['id_centro'] ?? 1;

$nombre_archivo = "CA{$id_alumno}_{$centro}.pdf";
$ruta_archivo = __DIR__ . "/certificados/" . $nombre_archivo;

if (!is_dir(__DIR__ . '/certificados')) {
    mkdir(__DIR__ . '/certificados', 0755, true);
}

if (file_exists($ruta_archivo)) {
    unlink($ruta_archivo);
}

$pdf->Output("F", $ruta_archivo);

// =======================
// MOSTRAR
// =======================
$pdf->Output("I", $nombre_archivo);
?>
