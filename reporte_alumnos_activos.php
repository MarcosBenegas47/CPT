<?php
require_once __DIR__ . "/conexion.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$rol = $_SESSION['rol'] ?? '';
$id_centro_session = $_SESSION['id_centro'] ?? 0;

// ==========================
// FILTRO CENTRO
// ==========================
$id_centro_filtro = '';

if ($rol === 'director') {
    $id_centro_filtro = $id_centro_session;
}

if ($rol === 'admin' && isset($_GET['id_centro'])) {
    $id_centro_filtro = (int) $_GET['id_centro'];
}

// ==========================
// LISTADO DE CENTROS (solo admin)
// ==========================
$centros = [];

if ($rol === 'admin') {
    $stmtCentros = $conexion->query("
        SELECT id_centro, nombre
        FROM centros
        ORDER BY nombre
    ");

    $centros = $stmtCentros->fetchAll(PDO::FETCH_ASSOC);
}

// ==========================
// CONSULTA
// ==========================
// ==========================
// CONSULTA
// ==========================
$sql = "
    SELECT
        i.anio,
        c.nombre AS centro,
        a.apellido,
        a.nombres,

        GROUP_CONCAT(
            DISTINCT m.nombre
            ORDER BY m.nombre
            SEPARATOR ', '
        ) AS materias,

        CASE
            WHEN a.lider_celula = 1 THEN 'Sí'
            ELSE 'No'
        END AS lider

    FROM inscripciones i

    INNER JOIN alumnos a
        ON a.id_alumno = i.id_alumno

    INNER JOIN materias m
        ON m.id_materia = i.id_materia

    INNER JOIN centros c
        ON c.id_centro = i.id_centro

    LEFT JOIN notas n
        ON n.id_inscripcion = i.id_inscripcion

    WHERE
        n.nota IS NULL
        AND i.anio = YEAR(CURDATE())
        AND i.activo = 1
";

if (!empty($id_centro_filtro)) {
    $sql .= " AND i.id_centro = :id_centro ";
}

$sql .= "
    GROUP BY
        i.anio,
        c.nombre,
        a.id_alumno,
        a.apellido,
        a.nombres,
        a.lider_celula

    ORDER BY
        i.anio DESC,
        c.nombre,
        a.apellido,
        a.nombres
";
$stmt = $conexion->prepare($sql);

if (!empty($id_centro_filtro)) {
    $stmt->bindValue(':id_centro', $id_centro_filtro, PDO::PARAM_INT);
}

$stmt->execute();
$registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Alumnos Activos por Centro</h2>

<?php if ($rol === 'admin'): ?>

    <form method="GET" style="margin-bottom:15px;">

        <input type="hidden" name="modulo" value="reporte_alumnos_activos">

        <select name="id_centro">
            <option value="">Todos los centros</option>

            <?php foreach ($centros as $c): ?>

                <option
                    value="<?= $c['id_centro'] ?>"
                    <?= ($id_centro_filtro == $c['id_centro']) ? 'selected' : '' ?>
                >
                    <?= htmlspecialchars($c['nombre']) ?>
                </option>

            <?php endforeach; ?>
        </select>

        <button type="submit">Filtrar</button>

    </form>

<?php endif; ?>

<table border="1" cellpadding="5" cellspacing="0" width="100%">
    <thead>
        <tr>
            <th>Año</th>
            <th>Centro</th>
            <th>Apellido</th>
            <th>Nombres</th>
            <th>Materia</th>
            <th>Líder de célula</th>
        </tr>
    </thead>

    <tbody>

        <?php if (count($registros) > 0): ?>

            <?php foreach ($registros as $fila): ?>

                <tr>
                    <td><?= htmlspecialchars($fila['anio']) ?></td>
                    <td><?= htmlspecialchars($fila['centro']) ?></td>
                    <td><?= htmlspecialchars($fila['apellido']) ?></td>
                    <td><?= htmlspecialchars($fila['nombres']) ?></td>
                    <td><?= htmlspecialchars($fila['materias']) ?></td>
                    <td><?= htmlspecialchars($fila['lider']) ?></td>
                </tr>

            <?php endforeach; ?>

        <?php else: ?>

            <tr>
                <td colspan="6">No hay alumnos activos sin nota.</td>
            </tr>

        <?php endif; ?>

    </tbody>
</table>