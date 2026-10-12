
<?php
include("conexion.php");

$id_alumno = $_GET['id_alumno'] ?? 0;
$texto = $_GET['texto'] ?? '';
$plan = $_GET['plan'] ?? 3;
?>

<h3>📊 Reporte por alumno</h3>

<!-- 🔍 BUSCADOR -->
<form method="get" class="mb-3">
    <input type="hidden" name="modulo" value="reporte_alumno">

    <div class="d-flex align-items-end gap-2 flex-wrap">

        <div>
            <label class="form-label mb-1"><strong>Buscar alumno:</strong></label>
            <input type="text" name="texto" placeholder="Buscar alumno..." 
                   value="<?= htmlspecialchars($texto) ?>"
                   class="form-control">
        </div>

        <div>
            <label class="form-label mb-1"><strong>Plan:</strong></label>
            <select name="plan" class="form-select">
                <option value="3" <?= $plan == 3 ? 'selected' : '' ?>>Plan 3</option>
                <option value="2" <?= $plan == 2 ? 'selected' : '' ?>>Plan 2</option>
            </select>
        </div>

        <div>
            <button class="btn btn-primary">Buscar</button>
        </div>

    </div>
</form>

<?php
// 🔍 RESULTADOS DE BÚSQUEDA
if ($texto != '') {

    $stmt = $conexion->prepare("
        SELECT id_alumno, apellido, nombres, documento
        FROM alumnos
        WHERE apellido LIKE :texto 
        OR nombres LIKE :texto 
        OR documento LIKE :texto
        ORDER BY apellido
    ");

    $stmt->execute(['texto' => "%$texto%"]);

    echo "<table class='table table-bordered'>";
    echo "<tr><th>Apellido</th><th>Nombres</th><th>DNI</th><th></th></tr>";

    foreach ($stmt as $fila) {
        echo "<tr>
            <td>{$fila['apellido']}</td>
            <td>{$fila['nombres']}</td>
            <td>{$fila['documento']}</td>
            <td>
                <a href='plantilla.php?modulo=reporte_alumno&id_alumno={$fila['id_alumno']}&plan={$plan}'
                   class='btn btn-sm btn-info'>
                   Ver reporte
                </a>
            </td>
        </tr>";
    }

    echo "</table>";
}


// 📊 MOSTRAR REPORTE SI HAY ALUMNO
if ($id_alumno) {

    // 🔎 Datos del alumno
    $stmt = $conexion->prepare("
        SELECT apellido, nombres, documento 
        FROM alumnos 
        WHERE id_alumno = :id
    ");

    $stmt->execute(['id' => $id_alumno]);
    $alumno = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($alumno) {

        echo "<h4>{$alumno['apellido']}, {$alumno['nombres']}</h4>";
        echo "<p><strong>DNI:</strong> {$alumno['documento']}</p>";

        // 🔘 CERTIFICADO (solo admin y secretaria)
        if (in_array($_SESSION['rol'], ['admin', 'director'])) {
            echo "<div class='d-flex align-items-center gap-2 mb-3 flex-wrap'>";

            echo "<label for='versionCertificado' class='mb-0'><strong>Versión:</strong></label>";

            echo "<select id='versionCertificado' class='form-select form-select-sm' style='width: 190px;'>
                    <option value='certificado.php' selected>Con membrete</option>
                    <option value='certificado_sm.php'>Hoja membretada</option>
                  </select>";

            echo "<a id='btnCertificado'
                     href='certificado.php?id_alumno={$id_alumno}&plan={$plan}'
                     class='btn btn-success btn-sm' target='_blank'>
                     📄 Generar Certificado
                  </a>";

            echo "</div>";

            echo "<script>
        function actualizarCertificado() {
            const version = document.getElementById('versionCertificado').value;

            document.getElementById('btnCertificado').href =
                version + '?id_alumno={$id_alumno}&plan={$plan}';
        }

        document.getElementById('versionCertificado').addEventListener('change', actualizarCertificado);
      </script>";
        }
      echo "<a href='reporte_alumno_pdf.php?id_alumno={$id_alumno}&plan={$plan}' 
         class='btn btn-primary btn-sm mb-3' target='_blank'>
         📄 Generar Reporte PDF
      </a>";

// ======================================================
// PLAN DE ESTUDIOS
// ======================================================

$plan = $_GET['plan'] ?? 3;

// Definir campo de orden según el plan seleccionado
if ($plan == 2) {
    $campo_plan = "m.plan2";
} else {
    $campo_plan = "m.plan3";
}

$sql = "SELECT 
            m.nombre AS materia,
            m.plan2,
            m.plan3,
            i.id_inscripcion,
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

        ORDER BY $campo_plan";

$stmt = $conexion->prepare($sql);
$stmt->execute(['alumno' => $id_alumno]);


// ======================================================
// PLAN DE ESTUDIOS
// ======================================================

echo "<h5 class='mt-4 mb-3'>PLAN DE ESTUDIOS — PLAN $plan</h5>";

echo "<table class='table table-bordered table-sm'>";

echo "<thead class='table-light'>";
echo "<tr>
        <th style='width: 45px;'>Nº</th>
        <th>Materia</th>
        <th style='width: 65px;'>Año</th>
        <th style='width: 100px;'>Centro</th>
        <th style='width: 100px;'>Modalidad</th>
        <th style='width: 65px;'>Nota</th>
      </tr>";
echo "</thead>";

echo "<tbody>";


// Variables generales del reporte

$total_aprobadas = 0;
$suma_notas = 0;

$pendientes_plan3 = 0;


foreach ($stmt as $fila) {

    // Una materia se considera aprobada solamente si tiene nota
    $aprobada = $fila['nota'] !== null;

    if ($aprobada) {

        $total_aprobadas++;

        $suma_notas += (float)$fila['nota'];

    } else {

        $pendientes_plan3++;
    }


    $anio = $fila['anio'] ?? '—';
    $centro = $fila['centro'] ?? '—';
    $modalidad = $fila['modalidad'] ?? '—';
    $nota = $fila['nota'] ?? '—';


    echo "<tr>";

    $numero_plan = ($plan == 2) ? $fila['plan2'] : $fila['plan3'];

    echo "<td class='text-center'>$numero_plan</td>";

    echo "<td>{$fila['materia']}</td>";

    echo "<td class='text-center'>$anio</td>";

    echo "<td>$centro</td>";

    echo "<td>$modalidad</td>";

    echo "<td class='text-center'>$nota</td>";

    echo "</tr>";
}

echo "</tbody>";
echo "</table>";


// ======================================================
// MATERIAS ADICIONALES APROBADAS
// ======================================================

// Una materia es adicional si:
// - tiene nota
// - NO pertenece al plan seleccionado

$campo_plan_adicional = ($plan == 2) ? "m.plan2" : "m.plan3";

$sql_adicionales = "SELECT
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

                    ORDER BY m.nombre";

$stmt_adicionales = $conexion->prepare($sql_adicionales);
$stmt_adicionales->execute(['alumno' => $id_alumno]);

$adicionales = $stmt_adicionales->fetchAll(PDO::FETCH_ASSOC);

// ======================================================
// MOSTRAR ADICIONALES
// ======================================================

echo "<h5 class='mt-4 mb-3'>MATERIAS ADICIONALES APROBADAS</h5>";

if (count($adicionales) > 0) {

    echo "<table class='table table-bordered table-sm'>";

    echo "<thead class='table-light'>";
    echo "<tr>
            <th>Materia</th>
            <th style='width: 65px;'>Año</th>
            <th style='width: 100px;'>Centro</th>
            <th style='width: 100px;'>Modalidad</th>
            <th style='width: 65px;'>Nota</th>
          </tr>";
    echo "</thead>";

    echo "<tbody>";


    foreach ($adicionales as $fila) {

        $total_aprobadas++;

        $suma_notas += (float)$fila['nota'];

        $anio = $fila['anio'] ?? '—';
        $centro = $fila['centro'] ?? '—';
        $modalidad = $fila['modalidad'] ?? '—';


        echo "<tr>";

        echo "<td>{$fila['materia']}</td>";

        echo "<td class='text-center'>$anio</td>";

        echo "<td>$centro</td>";

        echo "<td>$modalidad</td>";

        echo "<td class='text-center'>{$fila['nota']}</td>";

        echo "</tr>";
    }

    echo "</tbody>";
    echo "</table>";

} else {

    echo "<p class='text-muted'>No hay materias adicionales aprobadas.</p>";
}


// ======================================================
// RESUMEN ACADÉMICO
// ======================================================

$promedio = $total_aprobadas > 0
    ? round($suma_notas / $total_aprobadas, 2)
    : 0;


echo "<div class='mt-4 mb-3'>";

echo "<h5 class='mb-3'>RESUMEN ACADÉMICO</h5>";

echo "<div class='card'>";

echo "<div class='card-body'>";

echo "<p class='mb-1'>
        <strong>Materias aprobadas:</strong> $total_aprobadas
      </p>";


echo "<p class='mb-0'>
        <strong>Promedio general:</strong> " .
        number_format($promedio, 2, ',', '.') .
      "</p>";

echo "</div>";

echo "</div>";

echo "</div>";
    } else {
        echo "<p>Alumno no encontrado</p>";
    }
}
?>

