<?php

require_once __DIR__ . "/conexion.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
|--------------------------------------------------------------------------
| CONTROL DE ACCESO
|--------------------------------------------------------------------------
*/

$usuarios_permitidos = [6];

if (
    !isset($_SESSION['id_usuario'])
    || !in_array($_SESSION['id_usuario'], $usuarios_permitidos)
) {

    http_response_code(403);

    die("Acceso denegado");
}


/*
|--------------------------------------------------------------------------
| VARIABLES
|--------------------------------------------------------------------------
*/

$tabla = $_GET['tabla'] ?? 'alumnos';
$tipo = $_GET['tipo'] ?? '';
$usuario = $_GET['usuario'] ?? '';
$desde = $_GET['desde'] ?? '';
$hasta = $_GET['hasta'] ?? '';

/*
|--------------------------------------------------------------------------
| TABLAS PERMITIDAS
|--------------------------------------------------------------------------
*/

$tablas_permitidas = ['alumnos', 'inscripciones', 'notas'];

if (!in_array($tabla, $tablas_permitidas)) {
    die("Tabla no permitida");
}

/*
|--------------------------------------------------------------------------
| COLUMNAS SEGÚN TABLA
|--------------------------------------------------------------------------
*/

$columnas = [];

switch ($tabla) {

    case 'alumnos':

        $columnas = [
            'id_alumno',
            'apellido',
            'nombres',
            'documento',
            'fecha_alta',
            'fecha_modificacion',
            'fecha_baja',
            'id_usuario_alta',
            'id_usuario_modificacion',
            'id_usuario_baja'
        ];

        break;

    case 'inscripciones':

        $columnas = [
            'id_inscripcion',
            'id_alumno',
            'id_materia',
            'fecha_alta',
            'fecha_modificacion',
            'fecha_baja',
            'id_usuario_alta',
            'id_usuario_modificacion',
            'id_usuario_baja'
        ];

        break;

    case 'notas':

        $columnas = [
            'id_inscripcion',
            'nota',
            'fecha_alta',
            'fecha_modificacion',
            'id_usuario_alta',
            'id_usuario_modificacion'
        ];

        break;
}

/*
|--------------------------------------------------------------------------
| ALIAS COLUMNAS
|--------------------------------------------------------------------------
*/

$alias = [

    'id_alumno' => 'ID Alumno',
    'apellido' => 'Apellido',
    'nombres' => 'Nombres',
    'documento' => 'Documento',

    'id_inscripcion' => 'ID Inscripción',
    'id_materia' => 'Materia',

    'id_nota' => 'ID Nota',
    'nota' => 'Nota',

    'fecha_alta' => 'Fecha Alta',
    'fecha_modificacion' => 'Fecha Modificación',
    'fecha_baja' => 'Fecha Baja',

    'id_usuario_alta' => 'Usuario Alta',
    'id_usuario_modificacion' => 'Usuario Modificación',
    'id_usuario_baja' => 'Usuario Baja'
];

/*
|--------------------------------------------------------------------------
| CONSULTA SQL
|--------------------------------------------------------------------------
*/

$sql = "SELECT " . implode(",", $columnas) . "
        FROM $tabla
        WHERE 1=1";

$params = [];

/*
|--------------------------------------------------------------------------
| FILTRO TIPO
|--------------------------------------------------------------------------
*/

switch ($tipo) {

    case 'altas':

        if (in_array('fecha_alta', $columnas)) {
            $sql .= " AND fecha_alta IS NOT NULL";
        }

        break;

    case 'modificaciones':

        if (in_array('fecha_modificacion', $columnas)) {
            $sql .= " AND fecha_modificacion IS NOT NULL";
        }

        break;

    case 'bajas':

        if (in_array('fecha_baja', $columnas)) {
            $sql .= " AND fecha_baja IS NOT NULL";
        }

        break;
}

/*
|--------------------------------------------------------------------------
| FILTRO USUARIO
|--------------------------------------------------------------------------
*/

if (!empty($usuario)) {

    $condiciones_usuario = [];

    if (in_array('id_usuario_alta', $columnas)) {
        $condiciones_usuario[] = "id_usuario_alta = :usuario";
    }

    if (in_array('id_usuario_modificacion', $columnas)) {
        $condiciones_usuario[] = "id_usuario_modificacion = :usuario";
    }

    if (in_array('id_usuario_baja', $columnas)) {
        $condiciones_usuario[] = "id_usuario_baja = :usuario";
    }

    if (!empty($condiciones_usuario)) {

        $sql .= " AND (" . implode(" OR ", $condiciones_usuario) . ")";

        $params[':usuario'] = $usuario;
    }
}

/*
|--------------------------------------------------------------------------
| FILTRO FECHA DESDE
|--------------------------------------------------------------------------
*/

if (!empty($desde)) {

    $condiciones_fecha = [];

    if (in_array('fecha_alta', $columnas)) {
        $condiciones_fecha[] = "DATE(fecha_alta) >= :desde";
    }

    if (in_array('fecha_modificacion', $columnas)) {
        $condiciones_fecha[] = "DATE(fecha_modificacion) >= :desde";
    }

    if (in_array('fecha_baja', $columnas)) {
        $condiciones_fecha[] = "DATE(fecha_baja) >= :desde";
    }

    if (!empty($condiciones_fecha)) {

        $sql .= " AND (" . implode(" OR ", $condiciones_fecha) . ")";

        $params[':desde'] = $desde;
    }
}

/*
|--------------------------------------------------------------------------
| FILTRO FECHA HASTA
|--------------------------------------------------------------------------
*/

if (!empty($hasta)) {

    $condiciones_fecha = [];

    if (in_array('fecha_alta', $columnas)) {
        $condiciones_fecha[] = "DATE(fecha_alta) <= :hasta";
    }

    if (in_array('fecha_modificacion', $columnas)) {
        $condiciones_fecha[] = "DATE(fecha_modificacion) <= :hasta";
    }

    if (in_array('fecha_baja', $columnas)) {
        $condiciones_fecha[] = "DATE(fecha_baja) <= :hasta";
    }

    if (!empty($condiciones_fecha)) {

        $sql .= " AND (" . implode(" OR ", $condiciones_fecha) . ")";

        $params[':hasta'] = $hasta;
    }
}

/*
|--------------------------------------------------------------------------
| ORDEN
|--------------------------------------------------------------------------
*/

$orden = [];

if (in_array('fecha_modificacion', $columnas)) {
    $orden[] = 'fecha_modificacion';
}

if (in_array('fecha_alta', $columnas)) {
    $orden[] = 'fecha_alta';
}

if (in_array('fecha_baja', $columnas)) {
    $orden[] = 'fecha_baja';
}

$sql .= " ORDER BY COALESCE(" . implode(",", $orden) . ") DESC
LIMIT 200";
/*
|--------------------------------------------------------------------------
| EJECUCIÓN
|--------------------------------------------------------------------------
*/

$stmt = $conexion->prepare($sql);
$stmt->execute($params);

$resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">

<title>Auditoría</title>

<style>

body{
    font-family: Arial;
    margin:20px;
}

table{
    border-collapse: collapse;
    width:100%;
    background:white;
}

th{
    background:#2c3e50;
    color:white;
    padding:10px;
    text-align:left;
    border:1px solid #ccc;
}

td{
    padding:8px;
    border:1px solid #ccc;
    font-size:14px;
}

tr:nth-child(even){
    background:#f8f9fa;
}

tr:hover{
    background:#fff3cd;
}

.alta{
    background:#d4edda !important;
}

.modificacion{
    background:#d1ecf1 !important;
}

.baja{
    background:#f8d7da !important;
}

form{
    margin-bottom:20px;
}

input, select, button{
    padding:6px;
    margin-right:10px;
}

</style>

</head>

<body>

<h2>Auditoría del Sistema</h2>

<form method="GET" action="plantilla.php">

    <input type="hidden"
           name="modulo"
           value="auditoria">

    <label>Tabla:</label>

    <select name="tabla">

        <option value="alumnos" <?= $tabla == 'alumnos' ? 'selected' : '' ?>>
            Alumnos
        </option>

        <option value="inscripciones" <?= $tabla == 'inscripciones' ? 'selected' : '' ?>>
            Inscripciones
        </option>

        <option value="notas" <?= $tabla == 'notas' ? 'selected' : '' ?>>
            Notas
        </option>

    </select>

    <label>Tipo:</label>

    <select name="tipo">

        <option value="">Todos</option>

        <option value="altas" <?= $tipo == 'altas' ? 'selected' : '' ?>>
            Altas
        </option>

        <option value="modificaciones" <?= $tipo == 'modificaciones' ? 'selected' : '' ?>>
            Modificaciones
        </option>

        <option value="bajas" <?= $tipo == 'bajas' ? 'selected' : '' ?>>
            Bajas
        </option>

    </select>

    <label>Usuario:</label>

    <input type="text" name="usuario"
        value="<?= htmlspecialchars($usuario) ?>">

    <label>Desde:</label>

    <input type="date" name="desde" value="<?= $desde ?>">

    <label>Hasta:</label>

    <input type="date" name="hasta" value="<?= $hasta ?>">

    <button type="submit">Buscar</button>

</form>

<table>

<tr>

<?php foreach($columnas as $columna): ?>

    <th>

        <?= $alias[$columna] ?? $columna ?>

    </th>

<?php endforeach; ?>

</tr>

<?php foreach($resultados as $fila): ?>

<?php

$clase = '';

if (!empty($fila['fecha_baja'])) {

    $clase = 'baja';

} elseif (!empty($fila['fecha_modificacion'])) {

    $clase = 'modificacion';

} elseif (!empty($fila['fecha_alta'])) {

    $clase = 'alta';
}

?>

<tr class="<?= $clase ?>">

<?php foreach($columnas as $columna): ?>

    <td>

        <?= htmlspecialchars($fila[$columna] ?? '') ?>

    </td>

<?php endforeach; ?>

</tr>

<?php endforeach; ?>

</table>

</body>
</html>