<?php
include __DIR__ . "/conexion.php";

// ==========================
// FILTROS
// ==========================
$id_materia = $_GET['id_materia'] ?? '';
$anio = $_GET['anio'] ?? '';
$id_centro = $_GET['id_centro'] ?? '';

// ==========================
// COMBOS
// ==========================
$materias = $conexion->query("SELECT id_materia, nombre FROM materias ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
$centros = $conexion->query("SELECT id_centro, nombre FROM centros ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
$nombre_materia = '';

if ($id_materia) {
    $stmt = $conexion->prepare("SELECT nombre FROM materias WHERE id_materia = ?");
    $stmt->execute([$id_materia]);
    $nombre_materia = $stmt->fetchColumn();
}
// ==========================
// DATOS
// ==========================
$alumnos = [];

if ($id_materia && $anio) {

    $sql = "SELECT a.apellido, a.nombres
        FROM inscripciones i
        INNER JOIN alumnos a ON i.id_alumno = a.id_alumno
        WHERE i.id_materia = ?
        AND i.anio = ?
        AND i.modalidad = 'Presencial'
        AND i.activo = 1";

    $params = [$id_materia, $anio];

    if ($id_centro) {
        $sql .= " AND i.id_centro = ?";
        $params[] = $id_centro;
    }

    $sql .= " ORDER BY a.apellido, a.nombres";

    $stmt = $conexion->prepare($sql);
    $stmt->execute($params);
    $alumnos = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<h2>Listado por materia</h2>

<form method="GET" action="plantilla.php">
    <input type="hidden" name="modulo" value="listado_materia">

    <select name="id_materia" required>
        <option value="">Materia</option>
        <?php foreach ($materias as $m): ?>
            <option value="<?= $m['id_materia'] ?>" <?= ($id_materia == $m['id_materia']) ? 'selected' : '' ?>>
                <?= $m['nombre'] ?>
            </option>
        <?php endforeach; ?>
    </select>

    <select name="anio" required>
        <option value="">Año</option>
        <?php for ($i = date('Y'); $i >= 2000; $i--): ?>
            <option value="<?= $i ?>" <?= ($anio == $i) ? 'selected' : '' ?>><?= $i ?></option>
        <?php endfor; ?>
    </select>

    <select name="id_centro">
        <option value="">Todos los centros</option>
        <?php foreach ($centros as $c): ?>
            <option value="<?= $c['id_centro'] ?>" <?= ($id_centro == $c['id_centro']) ? 'selected' : '' ?>>
                <?= $c['nombre'] ?>
            </option>
        <?php endforeach; ?>
    </select>

    <button type="submit">Ver</button>
</form>

<hr>

<?php if ($id_materia && $anio): ?>

<style>
@media print {
    body {
        font-size: 12px;
    }
}

.tabla-planilla {
    border-collapse: collapse;
    width: 100%;
}

.tabla-planilla th,
.tabla-planilla td {
    border: 1px solid #000;
    padding: 2px;
}

.col-apellido {
    width: 6cm;
}

.col-nombres {
    width: 6cm;
}

.col-extra {
    width: 1.5cm;
    text-align: center;
}
</style>

<button onclick="window.print()">Imprimir</button>
<a href="listado_materia_pdf.php?id_materia=<?= $id_materia ?>&anio=<?= $anio ?>&id_centro=<?= $id_centro ?>" 
   target="_blank" 
   class="btn btn-success">
   PDF
</a>

<br><br>

<div id="zona-impresion">

<h3>
    <?= $nombre_materia ?? '' ?> - <?= $anio ?>
</h3>

<table class="tabla-planilla">
    <tr>
        <th class="col-apellido">Apellido</th>
        <th class="col-nombres">Nombres</th>

        <?php for ($i = 1; $i <= 15; $i++): ?>
            <th class="col-extra"></th>
        <?php endfor; ?>
    </tr>

    <?php foreach ($alumnos as $a): ?>
        <tr>
            <td class="col-apellido"><?= $a['apellido'] ?></td>
            <td class="col-nombres"><?= $a['nombres'] ?></td>

            <?php for ($i = 1; $i <= 15; $i++): ?>
                <td class="col-extra">&nbsp;</td>
            <?php endfor; ?>
        </tr>
    <?php endforeach; ?>
</table>
</div>

<?php endif; ?>