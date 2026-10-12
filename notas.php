<?php
require_once __DIR__ . "/conexion.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$id_usuario = $_SESSION['id_usuario'] ?? null;

$id_materia = $_GET['id_materia'] ?? '';
$anio = $_GET['anio'] ?? date("Y");
$id_centro = $_GET['id_centro'] ?? '';
$modalidad = $_GET['modalidad'] ?? 'Presencial';

// 🔒 FORZAR centro si no es admin
if ($_SESSION['rol'] !== 'admin') {
    $id_centro = $_SESSION['id_centro'];
}

// ==========================
// GUARDAR NOTAS
// ==========================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_notas'])) {

    try {

        foreach ($_POST['notas'] as $id_inscripcion => $nota) {

            if ($nota === '') {
                $nota = null;
            }

            if (!is_null($nota) && ($nota < 0 || $nota > 100)) {
                continue;
            }

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
                ':id' => $id_inscripcion,
                ':nota' => $nota,
                ':id_usuario' => $id_usuario
            ]);
        }

        $_SESSION['mensaje'] = "Notas guardadas correctamente";

        header("Location: plantilla.php?modulo=notas&id_materia=" . urlencode($id_materia) .
            "&anio=" . urlencode($anio) .
            "&id_centro=" . urlencode($id_centro) .
            "&modalidad=" . urlencode($modalidad));

        exit;

    } catch (PDOException $e) {

        echo "<pre>";
        echo $e->getMessage();
        echo "</pre>";
        exit;
    }
}
   
?>

<h3>📝 Carga de Notas</h3>

<?php
if (!empty($_SESSION['mensaje'])) {

    echo "
    <div style='padding:10px; background:#dfe6e9; margin-bottom:10px;'>
        {$_SESSION['mensaje']}
    </div>";

    unset($_SESSION['mensaje']);
}
?>

<!-- FILTROS -->
<form method="GET" action="plantilla.php">

    <input type="hidden" name="modulo" value="notas">

    <select name="id_materia" required>

        <option value="">Materia</option>

        <?php
        $materias = $conexion->query("
            SELECT id_materia, nombre
            FROM materias
            ORDER BY nombre
        ");

        foreach ($materias as $m) {

            $sel = ($id_materia == $m['id_materia']) ? 'selected' : '';

            echo "
            <option value='{$m['id_materia']}' $sel>
                {$m['nombre']}
            </option>";
        }
        ?>

    </select>

    <select name="anio" required style="width:100px;">

        <?php
        $anio_actual = date("Y");
        $anio_sel = $anio ?? $anio_actual;

        for ($i = $anio_actual + 1; $i >= $anio_actual - 25; $i--) {

            $selected = ($i == $anio_sel) ? "selected" : "";

            echo "<option value='$i' $selected>$i</option>";
        }
        ?>

    </select>

    <?php if ($_SESSION['rol'] === 'admin'): ?>

        <select name="id_centro">

            <option value="">Centro</option>

            <?php
            $centros = $conexion->query("
                SELECT id_centro, nombre
                FROM centros
                ORDER BY nombre
            ");

            foreach ($centros as $c) {

                $sel = ($id_centro == $c['id_centro']) ? 'selected' : '';

                echo "
                <option value='{$c['id_centro']}' $sel>
                    {$c['nombre']}
                </option>";
            }
            ?>

        </select>

    <?php endif; ?>

    <select name="modalidad">

        <option value="Presencial"
            <?= ($modalidad == 'Presencial') ? 'selected' : '' ?>>
            Presencial
        </option>

        <option value="Virtual"
            <?= ($modalidad == 'Virtual') ? 'selected' : '' ?>>
            Virtual
        </option>

    </select>

    <button type="submit">Cargar</button>

</form>

<hr>

<?php
if ($id_materia && $anio) {

    $stmt = $conexion->prepare("
        SELECT 
            i.id_inscripcion,
            a.apellido,
            a.nombres,
            n.nota
        FROM inscripciones i
        JOIN alumnos a
            ON a.id_alumno = i.id_alumno
        LEFT JOIN notas n
            ON n.id_inscripcion = i.id_inscripcion
        WHERE i.id_materia = :materia
        AND i.anio = :anio
        AND i.id_centro = :centro
        AND i.modalidad = :modalidad
        AND i.activo = 1
        ORDER BY a.apellido
    ");

    $stmt->execute([
        'materia' => $id_materia,
        'anio' => $anio,
        'centro' => $id_centro,
        'modalidad' => $modalidad
    ]);

    echo "
    <form method='POST'
          action='plantilla.php?modulo=notas&id_materia=$id_materia&anio=$anio&id_centro=$id_centro&modalidad=$modalidad'>";

    echo "<table border='1' cellpadding='5'>";

    echo "
    <tr>
        <th>Apellido</th>
        <th>Nombre</th>
        <th>Nota</th>
        <th>Estado</th>
    </tr>";

    foreach ($stmt as $fila) {

        $nota = $fila['nota'];

        // estado
        $estado = "";
        $color = "";

        if (is_null($nota)) {

            $estado = "Sin cargar";
            $color = "#b2bec3";

        } elseif ($nota >= 60) {

            $estado = "Aprobado";
            $color = "#2ecc71";

        } else {

            $estado = "Desaprobado";
            $color = "#e74c3c";
        }

        $valor_nota = is_null($nota) ? '' : $nota;

        echo "
        <tr>

            <td>{$fila['apellido']}</td>

            <td>{$fila['nombres']}</td>

            <td>

                <input type='number'
                       name='notas[{$fila['id_inscripcion']}]'
                       value='{$valor_nota}'
                       min='0'
                       max='100'
                       style='width:70px;'>

            </td>

            <td style='color:white; background:$color; text-align:center;'>

                $estado

            </td>

        </tr>";
    }

    echo "</table>";

    echo "<br>";

    echo "
    <button type='submit' name='guardar_notas'>
        💾 Guardar notas
    </button>";

    echo "</form>";
}
?>