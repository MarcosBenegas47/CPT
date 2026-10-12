<?php
include __DIR__ . "/conexion.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$rol = $_SESSION['rol'] ?? '';
$id_centro_usuario = $_SESSION['id_centro'] ?? null;
$id_usuario = $_SESSION['id_usuario'] ?? null;

// ==========================
// VARIABLES (GET = filtros)
// ==========================
$id_materia = $_GET['id_materia'] ?? '';
$id_centro  = $_GET['id_centro'] ?? '';

if ($_SESSION['rol'] !== 'admin') {
    $id_centro = $id_centro_usuario;
}

$anio            = $_GET['anio'] ?? date("Y");
$modalidad       = $_GET['modalidad'] ?? '';
$fecha_inicio    = $_GET['fecha_inicio'] ?? '';
$fecha_fin       = $_GET['fecha_fin'] ?? '';
$buscar_alumno   = $_GET['buscar_alumno'] ?? '';

// ==========================
// ACCIONES (POST)
// ==========================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id_materia_post   = $_POST['id_materia'] ?? '';
    $id_centro_post    = $_POST['id_centro'] ?? '';
    $anio_post         = $_POST['anio'] ?? '';
    $modalidad_post    = $_POST['modalidad'] ?? '';
    $fecha_inicio_post = $_POST['fecha_inicio'] ?? null;
    $fecha_fin_post    = $_POST['fecha_fin'] ?? null;
    $id_alumno         = $_POST['id_alumno'] ?? '';

    // 🔒 FORZAR centro también en POST
    if ($_SESSION['rol'] !== 'admin') {
        $id_centro_post = $id_centro_usuario;
    }

    // ======================
    // INSCRIBIR
    // ======================



    if (isset($_POST['inscribir']) && $id_materia_post && $anio_post && $id_alumno) {

        if ($rol != 'admin' && strtolower($modalidad_post) === 'virtual') {

            $_SESSION['mensaje'] = "No tenés permiso para modalidad virtual";

        } else {

            if (strtolower($modalidad_post) === 'virtual') {
                $id_centro_post = 1;
            }

            // ==========================
            // VERIFICAR HISTORIAL
            // ==========================
            $stmt = $conexion->prepare("
                SELECT n.nota
                FROM inscripciones i
                LEFT JOIN notas n ON n.id_inscripcion = i.id_inscripcion
                WHERE i.id_alumno = :alumno
                AND i.id_materia = :materia
            ");

            $stmt->execute([
                'alumno' => $id_alumno,
                'materia' => $id_materia_post
            ]);

            $ya_aprobado = false;
            $ya_curso = false;

            foreach ($stmt as $row) {

                $ya_curso = true;

                if (!is_null($row['nota']) && $row['nota'] >= 60) {
                    $ya_aprobado = true;
                    break;
                }
            }

            // ==========================
            // DECISIÓN
            // ==========================
            if ($ya_aprobado) {

                $_SESSION['mensaje'] = "El alumno ya aprobó esta materia";

            } else {

                if ($ya_curso) {
                    $_SESSION['mensaje'] = "El alumno ya cursó esta materia anteriormente";
                } else {
                    $_SESSION['mensaje'] = "Alumno inscripto correctamente";
                }

            // ==========================
// ==========================
// VERIFICAR INSCRIPCIÓN EXISTENTE
// ==========================

$stmt = $conexion->prepare("
    SELECT
        id_inscripcion,
        activo
    FROM inscripciones
    WHERE id_alumno = :alumno
      AND id_materia = :materia
      AND anio = :anio
");

$stmt->execute([
    'alumno' => $id_alumno,
    'materia' => $id_materia_post,
    'anio' => $anio_post
]);

$inscripcion_existente = $stmt->fetch(PDO::FETCH_ASSOC);

if ($inscripcion_existente) {

    $stmt = $conexion->prepare("
        UPDATE inscripciones
        SET
            activo = 1,
            fecha_inicio = :fecha_inicio,
            fecha_fin = :fecha_fin,
            id_centro = :centro,
            modalidad = :modalidad,
            id_usuario_modificacion = :id_usuario
        WHERE id_inscripcion = :id
    ");

    $stmt->execute([
        'fecha_inicio' => $fecha_inicio_post,
        'fecha_fin' => $fecha_fin_post,
        'centro' => $id_centro_post ?: $id_centro_usuario,
        'modalidad' => $modalidad_post ?: 'Presencial',
        'id_usuario' => $id_usuario,
        'id' => $inscripcion_existente['id_inscripcion']
    ]);

} else {

    $stmt = $conexion->prepare("
        INSERT INTO inscripciones (
            id_alumno,
            id_materia,
            anio,
            fecha_inicio,
            fecha_fin,
            id_centro,
            modalidad,
            id_usuario_alta
        )
        VALUES (
            :alumno,
            :materia,
            :anio,
            :fecha_inicio,
            :fecha_fin,
            :centro,
            :modalidad,
            :id_usuario
        )
    ");

  $stmt->execute([
    'alumno' => $id_alumno,
    'materia' => $id_materia_post,
    'anio' => $anio_post,
    'fecha_inicio' => $fecha_inicio_post,
    'fecha_fin' => $fecha_fin_post,
    'centro' => $id_centro_post ?: $id_centro_usuario,
    'modalidad' => $modalidad_post ?: 'Presencial',
    'id_usuario' => $id_usuario
]);
}
}
}
}
// ======================
// BORRAR
// ======================
if (isset($_POST['borrar'])) {

    $stmt = $conexion->prepare("
        UPDATE inscripciones
        SET
            activo = 0,
            id_usuario_baja = :id_usuario,
            fecha_baja = NOW()
        WHERE id_inscripcion = :id
    ");

    $stmt->execute([
        'id_usuario' => $id_usuario,
        'id' => $_POST['borrar']
    ]);
}

    // ======================
// EDITAR AÑO
// ======================
if (isset($_POST['editar_anio'])) {

    $id = $_POST['id_inscripcion'];
    $nuevo_anio = $_POST['nuevo_anio'];

    $sql = "
        UPDATE inscripciones 
        SET 
            anio = :anio,
            id_usuario_modificacion = :id_usuario
        WHERE id_inscripcion = :id
    ";

    $stmt = $conexion->prepare($sql);

    $stmt->execute([
        'anio' => $nuevo_anio,
        'id_usuario' => $id_usuario,
        'id' => $id
    ]);

    echo "<div style='color:green;'>✔ Año actualizado correctamente</div>";
}

    // ======================
    // REDIRECCIÓN
    // ======================
   header("Location: plantilla.php?modulo=inscripciones&id_materia=$id_materia_post&anio=$anio_post&id_centro=$id_centro_post&modalidad=$modalidad_post&fecha_inicio=$fecha_inicio_post&fecha_fin=$fecha_fin_post");

    exit;
}
?>

<h3>📚 Inscripciones</h3>

<?php
if (!empty($_SESSION['mensaje'])) {

    echo "
    <div style='padding:10px; background:#ffeaa7; margin-bottom:10px;'>
        {$_SESSION['mensaje']}
    </div>";

    unset($_SESSION['mensaje']);
}
?>

<!-- ==========================
FILTROS
========================== -->

<form method="GET" action="plantilla.php">

    <input type="hidden" name="modulo" value="inscripciones">

    <!-- PRIMERA LÍNEA -->
<div style="margin-bottom:10px; display:flex; gap:10px; flex-wrap:wrap; align-items:center;">

    <!-- MATERIA -->
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

    <!-- AÑO -->
    <label for="anio">Año:</label>

    <select name="anio" id="anio" required>

        <option value="">Seleccionar</option>

        <?php
        $anio_actual = date("Y");
        $anio_seleccionado = $_GET['anio'] ?? $anio_actual;

        for ($i = $anio_actual + 1; $i >= $anio_actual - 25; $i--) {

            $selected = ($i == $anio_seleccionado) ? "selected" : "";

            echo "<option value='$i' $selected>$i</option>";
        }
        ?>
    </select>

    <!-- CENTRO -->
    <select name="id_centro">

        <option value="">-- Todos los centros --</option>

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

    <!-- MODALIDAD -->
    <select name="modalidad">

        <option value="">-- Todas --</option>

        <option value="Presencial"
            <?= ($modalidad == 'Presencial') ? 'selected' : '' ?>>
            Presencial
        </option>

        <option value="Virtual"
            <?= ($modalidad == 'Virtual') ? 'selected' : '' ?>>
            Virtual
        </option>

    </select>

</div>

<!-- SEGUNDA LÍNEA -->
<div style="margin-bottom:15px; display:flex; gap:10px; align-items:center;">

    <label>Inicio:</label>
    <input type="date" name="fecha_inicio" value="<?= $fecha_inicio ?>">

    <label>Fin:</label>
    <input type="date" name="fecha_fin" value="<?= $fecha_fin ?>">

    <button type="submit">🔍 Cargar</button>

</div>

</form>

<hr>

<?php

// ==========================
// BUSCAR + INSCRIBIR ALUMNO
// ==========================
echo "<h4>➕ Inscribir alumno</h4>";

// BUSCADOR
echo "<form method='GET' action='plantilla.php'>";

echo "<input type='hidden' name='modulo' value='inscripciones'>";
echo "<input type='hidden' name='id_materia' value='$id_materia'>";
echo "<input type='hidden' name='anio' value='$anio'>";
echo "<input type='hidden' name='id_centro' value='$id_centro'>";
echo "<input type='hidden' name='modalidad' value='$modalidad'>";
echo "<input type='hidden' name='fecha_inicio' value='$fecha_inicio'>";
echo "<input type='hidden' name='fecha_fin' value='$fecha_fin'>";

echo "
<input type='text'
       name='buscar_alumno'
       placeholder='Buscar alumno...'
       value='$buscar_alumno'>";

echo "<button type='submit'>Buscar</button>";

echo "</form>";

// RESULTADOS
if (!empty($buscar_alumno)) {

    $stmt = $conexion->prepare("
        SELECT 
            id_alumno,
            apellido,
            nombres,
            documento
        FROM alumnos
        WHERE apellido LIKE :t
           OR nombres LIKE :t
           OR documento LIKE :t
        ORDER BY apellido
        LIMIT 20
    ");

    $stmt->execute([
        't' => "%$buscar_alumno%"
    ]);

    echo "<table border='1' cellpadding='5'>";

    echo "
    <tr>
        <th>Apellido</th>
        <th>Nombre</th>
        <th>DNI</th>
        <th></th>
    </tr>";

    foreach ($stmt as $a) {

        // HISTORIAL
        $stmt_hist = $conexion->prepare("
            SELECT i.anio, n.nota
            FROM inscripciones i
            LEFT JOIN notas n 
                ON n.id_inscripcion = i.id_inscripcion
            WHERE i.id_alumno = :alumno
            AND i.id_materia = :materia
            ORDER BY i.anio DESC
        ");

        $stmt_hist->execute([
            'alumno' => $a['id_alumno'],
            'materia' => $id_materia
        ]);

        $historial = $stmt_hist->fetchAll(PDO::FETCH_ASSOC);

        $info = "";

        foreach ($historial as $h) {

            if (!is_null($h['nota'])) {

                if ($h['nota'] >= 60) {

                    $info .= "
                    <div style='color:green; font-size:12px;'>
                        ✔ {$h['anio']}: {$h['nota']}
                    </div>";

                } else {

                    $info .= "
                    <div style='color:#e67e22; font-size:12px;'>
                        ⚠ {$h['anio']}: {$h['nota']}
                    </div>";
                }

            } else {

                $info .= "
                <div style='color:#999; font-size:12px;'>
                    • {$h['anio']}: sin nota
                </div>";
            }
        }

        echo "
        <tr>

            <td>
                {$a['apellido']}<br>
                <small>$info</small>
            </td>

            <td>{$a['nombres']}</td>

            <td>{$a['documento']}</td>

            <td>

                <form method='POST'
                      action='plantilla.php?modulo=inscripciones'
                      style='margin:0;'>

                    <input type='hidden'
                           name='id_alumno'
                           value='{$a['id_alumno']}'>

                    <input type='hidden'
                           name='id_materia'
                           value='$id_materia'>

                    <input type='hidden'
                           name='anio'
                           value='$anio'>

                    <input type='hidden'
                           name='id_centro'
                           value='$id_centro'>

                    <input type='hidden'
                           name='modalidad'
                           value='$modalidad'>

                    <input type='hidden'
                           name='fecha_inicio'
                           value='$fecha_inicio'>

                    <input type='hidden'
                           name='fecha_fin'
                           value='$fecha_fin'>

                    <button type='submit' name='inscribir'>
                        ➕
                    </button>

                </form>

            </td>

        </tr>";
    }

    echo "</table>";
}

// ==========================
// MOSTRAR INSCRIPTOS
// ==========================
if ($id_materia && $anio) {

    $params = [
        'materia' => $id_materia,
        'anio' => $anio
    ];

    $sql = "
        SELECT 
            i.id_inscripcion,
            i.anio,
            i.fecha_inicio,
            i.fecha_fin,
            a.apellido,
            a.nombres,
            a.documento,
            c.nombre as centro,
            i.modalidad

        FROM inscripciones i

        JOIN alumnos a
            ON a.id_alumno = i.id_alumno

        LEFT JOIN centros c
            ON i.id_centro = c.id_centro

        WHERE i.id_materia = :materia
        AND i.anio = :anio
        AND i.activo = 1
    ";

    if (!empty($modalidad)) {

        $sql .= " AND i.modalidad = :modalidad";

        $params['modalidad'] = $modalidad;
    }

    if (!empty($id_centro)) {

        $sql .= " AND i.id_centro = :centro";

        $params['centro'] = $id_centro;
    }

    $sql .= " ORDER BY a.apellido, a.nombres";

    $stmt = $conexion->prepare($sql);

    $stmt->execute($params);

    echo "<h4>👥 Inscriptos</h4>";

    echo "<table border='1' cellpadding='5'>";

    echo "
    <tr>
        <th>Apellido</th>
        <th>Nombre</th>
        <th>DNI</th>
        <th>Centro</th>
        <th>Modalidad</th>
        <th></th>
    </tr>";

    $hay = false;

    foreach ($stmt as $fila) {

        $hay = true;

        echo "
        <tr>

            <td>{$fila['apellido']}</td>
            <td>{$fila['nombres']}</td>
            <td>{$fila['documento']}</td>
            <td>{$fila['centro']}</td>
            <td>{$fila['modalidad']}</td>

            <td>

                <form method='POST'
                      action='plantilla.php?modulo=inscripciones'
                      style='margin:0; display:flex; gap:5px;'>

                    <input type='hidden'
                           name='id_inscripcion'
                           value='{$fila['id_inscripcion']}'>

                    <input type='number'
                           name='nuevo_anio'
                           value='$anio'
                           style='width:80px;'>

                    <button type='submit' name='editar_anio'>
                        OK
                    </button>

                </form>

            </td>

            <td>

                <form method='POST'
                      action='plantilla.php?modulo=inscripciones'
                      style='margin:0;'>

                    <input type='hidden'
                           name='id_materia'
                           value='$id_materia'>

                    <input type='hidden'
                           name='anio'
                           value='$anio'>

                    <input type='hidden'
                           name='id_centro'
                           value='$id_centro'>

                    <input type='hidden'
                           name='modalidad'
                           value='$modalidad'>

                    <button type='submit'
                            name='borrar'
                            value='{$fila['id_inscripcion']}'
                            onclick=\"return confirm('¿Eliminar esta inscripción?')\">

                        ❌

                    </button>

                </form>

            </td>

        </tr>";
    }

    echo "</table>";

    if (!$hay) {
        echo "<p>No hay inscriptos para este filtro.</p>";
    }
}
?>