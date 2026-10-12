<?php
include __DIR__ . "/conexion.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$rol = $_SESSION['rol'] ?? '';
$id_centro = $_SESSION['id_centro'] ?? 0;
$id_usuario = $_SESSION['id_usuario'] ?? null;

$es_admin = ($rol === 'admin');
$es_direccion_general = ($es_admin && $id_centro == 1);
$es_sistemas = ($es_admin && $id_centro == 22);
$es_director = ($rol === 'director');

$puede_graduar = ($es_admin || $es_director);

// ==========================
// REGISTRAR GRADUACIÓN
// ==========================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['graduar'])) {

     if (!$puede_graduar) {

        header("Location: plantilla.php?modulo=egresados");
        exit;
    }

    $id_alumno = $_POST['id_alumno'] ?? null;
    $anio = $_POST['anio'] ?? null;
    $fecha = $anio ? $anio . "-01-01" : null;

   // Dirección General y Sistemas pueden elegir centro
if ($es_direccion_general || $es_sistemas) {

    $id_centro_graduacion = $_POST['id_centro'] ?? null;

} else {

    // resto: centro fijo de sesión
    $id_centro_graduacion = $id_centro;
}

    if ($id_alumno && $id_centro_graduacion && $fecha) {

       $stmt = $conexion->prepare("
    INSERT INTO graduaciones (
        id_alumno,
        id_centro,
        fecha,
        id_usuario_alta
    )
    VALUES (
        :id_alumno,
        :id_centro,
        :fecha,
        :id_usuario
    )
");

try {

    $stmt->execute([
        ':id_alumno' => $id_alumno,
        ':id_centro' => $id_centro_graduacion,
        ':fecha' => $fecha,
        ':id_usuario' => $id_usuario
    ]);
            header("Location: plantilla.php?modulo=egresados&ok=1");
            exit;

        } catch (PDOException $e) {

            if ($e->getCode() == 23000) {

                header("Location: plantilla.php?modulo=egresados&dup=1");
                exit;

            } else {

                echo "<div style='color:red;'>Error: "
                    . $e->getMessage()
                    . "</div>";
            }
        }
    }
}

// ==========================
// OBTENER CENTROS
// ==========================
$centros_stmt = $conexion->query("
    SELECT id_centro, nombre 
    FROM centros 
    ORDER BY nombre
");

$centros = $centros_stmt->fetchAll(PDO::FETCH_ASSOC);

// ==========================
// FILTRO POR CENTRO
// ==========================
$filtro_centro = '';

if (!$es_direccion_general && !$es_sistemas) {

    $filtro_centro = " AND i.id_centro = $id_centro ";
}

// ==========================
// OBTENER EGRESADOS
// ==========================
$query = "
SELECT 
    a.id_alumno,
    CONCAT(a.apellido, ' ', a.nombres) AS nombre,
    COUNT(DISTINCT i.id_materia) AS materias_aprobadas
FROM alumnos a

JOIN inscripciones i 
    ON i.id_alumno = a.id_alumno

JOIN notas n 
    ON n.id_inscripcion = i.id_inscripcion

LEFT JOIN graduaciones g 
    ON g.id_alumno = a.id_alumno

WHERE g.id_alumno IS NULL
$filtro_centro

GROUP BY a.id_alumno

HAVING COUNT(DISTINCT i.id_materia) >= 40

ORDER BY nombre;
";

$egresados = $conexion->query($query);
?>

<h2>Egresados (pendientes de graduación)</h2>

<table border="1" cellpadding="6" cellspacing="0">

    <tr>
        <th>Alumno</th>
        <th>Materias aprobadas</th>
        <?php if ($puede_graduar) { ?>
        <th>Acción</th>
        <?php } ?>
    </tr>

<?php while ($row = $egresados->fetch(PDO::FETCH_ASSOC)) { ?>

    <tr>

        <td>
            <?= htmlspecialchars($row['nombre']) ?>
        </td>

        <td>
            <?= $row['materias_aprobadas'] ?>
        </td>

        <?php if ($puede_graduar) { ?>

        <td>

            <form method="POST" style="display:inline-block;">

                <input type="hidden"
                       name="id_alumno"
                       value="<?= $row['id_alumno'] ?>">

                    <?php if ($es_direccion_general || $es_sistemas) { ?>

                    <select name="id_centro" required>

                        <option value="">Centro</option>

                        <?php foreach ($centros as $c) { ?>

                            <option value="<?= $c['id_centro'] ?>">
                                <?= htmlspecialchars($c['nombre']) ?>
                            </option>

                        <?php } ?>

                    </select>

                <?php } else { ?>

                    <input type="hidden"
                           name="id_centro"
                           value="<?= $id_centro ?>">

                    <?= htmlspecialchars($_SESSION['nombre_centro'] ?? '') ?>

                <?php } ?>

                <input type="number"
                       name="anio"
                       value="<?= date('Y') ?>"
                       min="2000"
                       max="2100"
                       required
                       style="width:80px;">

                <button type="submit" name="graduar">
                    Graduar
                </button>

            </form>

        </td>

    </tr>

<?php }} ?>

</table>