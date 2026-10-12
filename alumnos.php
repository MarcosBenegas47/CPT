<?php
include __DIR__ . "/conexion.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$id_usuario = $_SESSION['id_usuario'] ?? null;

// ==========================
// FUNCIONES FORMATO
// ==========================
function mayusculas($texto) {
    return mb_strtoupper(trim($texto), 'UTF-8');
}

function capitalizar($texto) {
    return mb_convert_case(trim($texto), MB_CASE_TITLE, "UTF-8");
}

// ==========================
// VARIABLES
// ==========================
$accion = $_GET['accion'] ?? '';
$id = $_GET['id'] ?? '';
$buscar = $_GET['buscar'] ?? '';

// ==========================
// GUARDAR (ALTA / EDICIÓN)
// ==========================
if (isset($_POST['guardar'])) {

    $apellido = mayusculas($_POST['apellido']);
    $nombres = capitalizar($_POST['nombres']);
    $documento = $_POST['documento'];
    $telefono = $_POST['telefono'];
    $domicilio = $_POST['domicilio'];
    $centro = $_POST['centro'];
    $lider_celula = isset($_POST['lider_celula']) ? 1 : 0;


   if ($_POST['id'] == '') {

    $stmt = $conexion->prepare("
        INSERT INTO alumnos (
            apellido,
            nombres,
            documento,
            telefono,
            domicilio,
            centro,
            lider_celula,
            id_usuario_alta
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $apellido,
        $nombres,
        $documento,
        $telefono,
        $domicilio,
        $centro,
        $lider_celula,
        $id_usuario
    ]);

} else {

    $stmt = $conexion->prepare("
        UPDATE alumnos 
        SET 
            apellido=?,
            nombres=?,
            documento=?,
            telefono=?,
            domicilio=?,
            centro=?,
            lider_celula=?,
            id_usuario_modificacion=?
        WHERE id_alumno=?
    ");

    $stmt->execute([
        $apellido,
        $nombres,
        $documento,
        $telefono,
        $domicilio,
        $centro,
        $lider_celula,
        $id_usuario,
        $_POST['id']
    ]);
}

header("Location: plantilla.php?modulo=alumnos");
exit;
}

// ==========================
// ELIMINAR
// ==========================
if ($accion == 'eliminar' && $id != '') {
    $stmt = $conexion->prepare("DELETE FROM alumnos WHERE id_alumno=?");
    $stmt->execute([$id]);

    header("Location: plantilla.php?modulo=alumnos");
    exit;
}

// ==========================
// DATOS EDICIÓN / ALTA
// ==========================
$alumno = [
    'id_alumno' => '',
    'apellido' => '',
    'nombres' => '',
    'documento' => '', // $buscar,
    'telefono' => '',
    'domicilio' => '',
    'centro' => ''
];

if ($accion == 'editar' && $id != '') {
    $stmt = $conexion->prepare("SELECT * FROM alumnos WHERE id_alumno=?");
    $stmt->execute([$id]);
    $alumno = $stmt->fetch(PDO::FETCH_ASSOC);
}

// ==========================
// CENTROS (para combo)
// ==========================
$centros = $conexion->query("SELECT id_centro, nombre FROM centros ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);

// ==========================
// BUSCAR
// ==========================
$alumnos = [];

if ($buscar != '') {
    $sql = "SELECT a.*, c.nombre AS nombre_centro
        FROM alumnos a
        LEFT JOIN centros c ON a.centro = c.id_centro
        WHERE a.apellido LIKE ? OR a.nombres LIKE ? OR a.documento LIKE ?
        ORDER BY a.apellido, a.nombres";

    $params = ["%$buscar%", "%$buscar%", "%$buscar%"];

    $stmt = $conexion->prepare($sql);
    $stmt->execute($params);
    $alumnos = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$sin_resultados = ($buscar != '' && count($alumnos) == 0);
?>

<h2>Alumnos</h2>

<form method="GET" action="plantilla.php">
    <input type="hidden" name="modulo" value="alumnos">
    <input type="text" name="buscar" placeholder="Buscar por apellido, nombre o documento" value="<?= htmlspecialchars($buscar) ?>">
    <button type="submit">Buscar</button>
</form>

<hr>

<?php if ($buscar == ''): ?>
    <p>Ingrese un criterio de búsqueda para ver alumnos.</p>
<?php endif; ?>

<?php if ($sin_resultados): ?>
    <p style="color: red;">
        No se encontraron alumnos. Podés cargar uno nuevo abajo.
    </p>
<?php endif; ?>

<!-- FORMULARIO -->
<form method="POST">
    <input type="hidden" name="id" value="<?= $alumno['id_alumno'] ?>">

    <input type="text" name="apellido" placeholder="Apellido" required value="<?= $alumno['apellido'] ?>">
    <input type="text" name="nombres" placeholder="Nombres" required value="<?= $alumno['nombres'] ?>">
    <input type="text" name="documento" placeholder="Documento" value="<?= $alumno['documento'] ?>">
    <input type="text" name="telefono" placeholder="Teléfono" value="<?= $alumno['telefono'] ?>">
    <input type="text" name="domicilio" placeholder="Domicilio" value="<?= $alumno['domicilio'] ?>">
   <label>
    <input type="checkbox" name="lider_celula" value="1"
        <?= ($alumno['lider_celula'] ?? 0) ? 'checked' : '' ?>>
    Líder de célula
</label>

    <!-- SELECT CENTRO -->
    <select name="centro" required>
        <option value="">Centro</option>
        <?php foreach ($centros as $c): ?>
            <option value="<?= $c['id_centro'] ?>" <?= ($alumno['centro'] == $c['id_centro']) ? 'selected' : '' ?>>
                <?= $c['nombre'] ?>
            </option>
        <?php endforeach; ?>
    </select>

    <button type="submit" name="guardar">Guardar</button>

    <?php if ($accion == 'editar'): ?>
        <a href="plantilla.php?modulo=alumnos">Cancelar</a>
    <?php endif; ?>
</form>

<hr>

<!-- LISTADO -->
<?php if ($buscar != '' && count($alumnos) > 0): ?>
    <table border="1" cellpadding="5">
        <tr>
            <th>Apellido</th>
            <th>Nombres</th>
            <th>Documento</th>
            <th>Teléfono</th>
            <th>Centro</th>
            <th>Acciones</th>
        </tr>

        <?php foreach ($alumnos as $a): ?>
            <tr>
                <td><?= $a['apellido'] ?></td>
                <td><?= $a['nombres'] ?></td>
                <td><?= $a['documento'] ?></td>
                <td><?= $a['telefono'] ?></td>
                <td><?= $a['nombre_centro'] ?></td>
                <td>
                    <a href="plantilla.php?modulo=alumnos&accion=editar&id=<?= $a['id_alumno'] ?>">Editar</a>
                    |
                    <a href="plantilla.php?modulo=alumnos&accion=eliminar&id=<?= $a['id_alumno'] ?>" onclick="return confirm('¿Eliminar este alumno?')">Eliminar</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>