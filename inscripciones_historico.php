<?php
require_once __DIR__ . "/conexion.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$rol = $_SESSION['rol'] ?? '';
$id_centro = $_SESSION['id_centro'] ?? 0;
$id_usuario = $_SESSION['id_usuario'] ?? null;

$es_sistemas = ($rol === 'admin' && $id_centro == 22);

if (!$es_sistemas) {

    header("Location: plantilla.php?modulo=inicio");
    exit;
}

// ==========================
// INPUT
// ==========================
$id_alumno = $_GET['id_alumno'] ?? '';
$id_centro = $_GET['id_centro'] ?? '';
$anio      = $_GET['anio'] ?? date("Y");
$modalidad = $_GET['modalidad'] ?? 'Presencial';
$texto     = $_GET['texto'] ?? '';

// validar modalidad
if (!in_array($modalidad, ['Presencial', 'Virtual'])) {
    $modalidad = 'Presencial';
}

// ==========================
// CONTEXTO
// ==========================
$contexto_cargado = ($id_centro && $anio && $modalidad);

// ==========================
// GUARDAR
// ==========================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar'])) {

    foreach ($_POST['nota'] as $id_materia => $nota) {

        if ($nota === '') continue;
        if ($nota < 0 || $nota > 100) continue;

        $id_inscripcion = $_POST['inscripcion'][$id_materia] ?? '';

        if ($modalidad === 'Virtual') {
            $id_centro = 1;
        }

        if ($id_inscripcion) {

            // actualizar datos faltantes de la inscripción
           $stmt = $conexion->prepare("
    UPDATE inscripciones
    SET
        anio = :anio,
        id_centro = :centro,
        modalidad = :modalidad,
        id_usuario_modificacion = :id_usuario
    WHERE id_inscripcion = :id
");

        $stmt->execute([
            'anio'       => $anio,
            'centro'     => $id_centro,
            'modalidad'  => $modalidad,
            'id_usuario' => $id_usuario,
            'id'         => $id_inscripcion
        ]);

            // actualizar nota
           $stmt = $conexion->prepare("
    UPDATE notas 
    SET 
        nota = :nota,
        id_usuario_modificacion = :id_usuario
    WHERE id_inscripcion = :id
");

        $stmt->execute([
            'nota'       => $nota,
            'id_usuario' => $id_usuario,
            'id'         => $id_inscripcion
        ]);
        } else {

            // intentar insertar inscripción
           $stmt = $conexion->prepare("
    INSERT INTO inscripciones 
    (
        id_alumno,
        id_materia,
        anio,
        id_centro,
        modalidad,
        id_usuario_alta
    )
    SELECT 
        :alumno,
        :materia,
        :anio,
        :centro,
        :modalidad,
        :id_usuario
    WHERE NOT EXISTS (
        SELECT 1 FROM inscripciones
        WHERE id_alumno = :alumno
        AND id_materia = :materia
        AND anio = :anio
    )
");

$stmt->execute([
    'alumno'     => $id_alumno,
    'materia'    => $id_materia,
    'anio'       => $anio,
    'centro'     => $id_centro,
    'modalidad'  => $modalidad,
    'id_usuario' => $id_usuario
]);

            // 🔥 SIEMPRE buscar la inscripción
            $stmt = $conexion->prepare("
            SELECT id_inscripcion 
            FROM inscripciones
            WHERE id_alumno = :alumno
            AND id_materia = :materia
            AND anio = :anio
        ");

            $stmt->execute([
                'alumno' => $id_alumno,
                'materia' => $id_materia,
                'anio'   => $anio
            ]);

            $row = $stmt->fetch();
            $id_inscripcion = $row['id_inscripcion'] ?? null;

            // guardar nota
            if ($id_inscripcion) {
                $stmt = $conexion->prepare("
                    INSERT INTO notas (
                        id_inscripcion,
                        nota,
                        id_usuario_alta
                    )
                    VALUES (
                        :id,
                        :nota,
                        :id_usuario
                    )
                    ON DUPLICATE KEY UPDATE 
                        nota = :nota,
                        id_usuario_modificacion = :id_usuario
                ");

                $stmt->execute([
                    'id'         => $id_inscripcion,
                    'nota'       => $nota,
                    'id_usuario' => $id_usuario
                ]);
            }
        }
    }

    header("Location: plantilla.php?modulo=inscripciones_historico&id_centro=$id_centro&anio=$anio&modalidad=$modalidad&ok=1");
    exit;
}

// ==========================
// BUSCAR ALUMNOS
// ==========================
$alumnos = [];

if ($texto !== '') {
    $stmt = $conexion->prepare("
        SELECT id_alumno, apellido, nombres, documento
        FROM alumnos
        WHERE apellido LIKE :t OR nombres LIKE :t OR documento LIKE :t
        ORDER BY apellido
        LIMIT 20
    ");
    $stmt->execute(['t' => "%$texto%"]);
    $alumnos = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ==========================
// MATERIAS DEL ALUMNO
// ==========================
$materias = [];

$alumno_sel = null;

if ($id_alumno) {
    $stmt = $conexion->prepare("
        SELECT apellido, nombres
        FROM alumnos
        WHERE id_alumno = :id
    ");
    $stmt->execute(['id' => $id_alumno]);
    $alumno_sel = $stmt->fetch(PDO::FETCH_ASSOC);
}

if ($id_alumno && $contexto_cargado) {

    $stmt = $conexion->prepare("
        SELECT 
            m.id_materia,
            m.nombre,
            m.anio,
            i.id_inscripcion,
            n.nota
        FROM materias m
        LEFT JOIN inscripciones i 
            ON i.id_materia = m.id_materia
            AND i.id_alumno = :alumno
            AND i.anio = :anio
        LEFT JOIN notas n 
            ON n.id_inscripcion = i.id_inscripcion
        ORDER BY m.anio, m.nombre
    ");

    $stmt->execute([
        'alumno' => $id_alumno,
        'anio'   => $anio
    ]);

    $materias = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<h3>📚 Carga histórica por alumno</h3>

<?php if (isset($_GET['ok'])): ?>
    <div style="color:green;">✔ Datos guardados correctamente</div>
<?php endif; ?>

<!-- ========================== -->
<!-- CONTEXTO -->
<!-- ========================== -->
<form method="GET" action="plantilla.php">

    <input type="hidden" name="modulo" value="inscripciones_historico">

    Centro:
    <select name="id_centro">
        <option value="">Seleccionar</option>
        <?php
        $centros = $conexion->query("SELECT id_centro, nombre FROM centros ORDER BY nombre");
        foreach ($centros as $c):
        ?>
            <option value="<?= $c['id_centro'] ?>" <?= ($id_centro == $c['id_centro']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($c['nombre']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    Año:
    <select name="anio">
        <?php
        $actual = date("Y");
        for ($i = $actual + 1; $i >= $actual - 25; $i--):
        ?>
            <option value="<?= $i ?>" <?= ($anio == $i) ? 'selected' : '' ?>><?= $i ?></option>
        <?php endfor; ?>
    </select>

    Modalidad:
    <select name="modalidad">
        <option value="Presencial" <?= ($modalidad == 'Presencial') ? 'selected' : '' ?>>Presencial</option>
        <option value="Virtual" <?= ($modalidad == 'Virtual') ? 'selected' : '' ?>>Virtual</option>
    </select>

    <button type="submit">Continuar</button>
</form>

<hr>

<?php if ($contexto_cargado): ?>

    <!-- ========================== -->
    <!-- BUSCADOR -->
    <!-- ========================== -->
    <form method="GET" action="plantilla.php">
        <input type="hidden" name="modulo" value="inscripciones_historico">
        <input type="hidden" name="id_centro" value="<?= $id_centro ?>">
        <input type="hidden" name="anio" value="<?= $anio ?>">
        <input type="hidden" name="modalidad" value="<?= $modalidad ?>">

        <input type="text" name="texto" placeholder="Buscar alumno..."
            value="<?= htmlspecialchars($texto) ?>">

        <button type="submit">Buscar</button>
    </form>

    <?php if (!empty($alumnos)): ?>
        <table>
            <tr>
                <th>Apellido</th>
                <th>Nombre</th>
                <th>DNI</th>
                <th></th>
            </tr>

            <?php foreach ($alumnos as $a): ?>
                <tr>
                    <td><?= htmlspecialchars($a['apellido']) ?></td>
                    <td><?= htmlspecialchars($a['nombres']) ?></td>
                    <td><?= htmlspecialchars($a['documento']) ?></td>
                    <td>
                        <a href="plantilla.php?modulo=inscripciones_historico&id_alumno=<?= $a['id_alumno'] ?>&id_centro=<?= $id_centro ?>&anio=<?= $anio ?>&modalidad=<?= $modalidad ?>">
                            Seleccionar
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>

        </table>
    <?php endif; ?>

<?php endif; ?>

<!-- ========================== -->
<!-- MATERIAS -->
<!-- ========================== -->
<?php if ($id_alumno && $contexto_cargado): ?>

    <?php if ($alumno_sel): ?>
        <div style="
        position: sticky;
        top: 0;
        background: #e8f4ff;
        padding: 10px;
        margin-bottom: 10px;
        border-bottom: 1px solid #ccc;
        z-index: 10;
    ">
            <strong>Alumno:</strong>
            <?= htmlspecialchars($alumno_sel['apellido'] . ', ' . $alumno_sel['nombres']) ?>
        </div>
    <?php endif; ?>

    <form method="POST" autocomplete="off">

        <input type="hidden" name="id_alumno" value="<?= $id_alumno ?>">
        <input type="hidden" name="id_centro" value="<?= $id_centro ?>">
        <input type="hidden" name="anio" value="<?= $anio ?>">
        <input type="hidden" name="modalidad" value="<?= $modalidad ?>">

        <table>
            <tr>
                <th>Año</th>
                <th>Materia</th>
                <th>Nota</th>
            </tr>

            <?php foreach ($materias as $i => $m): ?>
                <tr>
                    <td><?= $m['anio'] ?></td>
                    <td><?= htmlspecialchars($m['nombre']) ?></td>
                    <td>
                        <input type="number"
                            name="nota[<?= $m['id_materia'] ?>]"
                            value="<?= $m['nota'] ?>"
                            min="0" max="100"
                            step="0.01"
                            data-fila="<?= $i ?>"
                            class="nota"
                            style="width:70px;">

                        <input type="hidden"
                            name="inscripcion[<?= $m['id_materia'] ?>]"
                            value="<?= $m['id_inscripcion'] ?>">
                    </td>
                </tr>
            <?php endforeach; ?>

        </table>

        <br>
        <button type="submit" name="guardar">💾 Guardar todo</button>

    </form>

<?php endif; ?>