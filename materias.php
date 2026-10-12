<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include(__DIR__ . "/conexion.php");
include(__DIR__ . "/funciones_materias_nuevo.php");

// 🔴 ELIMINAR (opcional, podés dejarlo comentado)
/*
if (isset($_GET['eliminar'])) {

    $resultado = eliminarMateria($_GET['eliminar']);

    if ($resultado == "relacionado") {
        header("Location: plantilla.php?modulo=materias&error=relacionado");
    } elseif ($resultado == "noexiste") {
        header("Location: plantilla.php?modulo=materias&error=noexiste");
    } else {
        header("Location: plantilla.php?modulo=materias&ok=eliminado");
    }
    exit;
}
*/

// 💾 GUARDAR
if ($_POST) {
    $resultado = guardarMateria($_POST);

    if ($resultado == "existe") {
        header("Location: plantilla.php?modulo=materias&error=existe");
    } else {
        header("Location: plantilla.php?modulo=materias&ok=1");
    }
    exit;
}
?>

<div class="container mt-3">

<h4>📘 Materias</h4>

<!-- BUSCADOR -->
<div class="row mb-3">
    <div class="col-md-6 position-relative">
        <input type="text" id="buscar" class="form-control" placeholder="Buscar materia" autocomplete="off">
        <div id="resultados" class="list-group"></div>
    </div>
</div>

<!-- BOTÓN NUEVA -->
<button class="btn btn-success mb-3" onclick="mostrarFormulario()">+ Nueva materia</button>

<!-- FORMULARIO -->
<div id="formulario-materia" style="display:none;" class="row">
    <div class="col-md-6">

        <form method="POST">

            <input type="hidden" id="id_materia" name="id_materia">

            <input type="text" id="nombre" name="nombre" class="form-control mb-2" placeholder="Nombre" required>

            <select id="anio" name="anio" class="form-select mb-2" required>
                <option value="">-- Nivel de cursada --</option>
                <option value="1">1° Año</option>
                <option value="2">2° Año</option>
                <option value="3">3° Año</option>
                <option value="99">Extra</option>
            </select>

            <input type="text" id="descripcion" name="descripcion" class="form-control mb-2" placeholder="Descripción">

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Guardar</button>
                <button type="button" class="btn btn-secondary" onclick="ocultarFormulario()">Cancelar</button>
            </div>

        </form>

    </div>
</div>

</div>

<script>
document.addEventListener("DOMContentLoaded", function() {

    const input = document.getElementById("buscar");
    const resultados = document.getElementById("resultados");

    input.addEventListener("keyup", function() {

        let texto = this.value;

        if (texto.length < 2) {
            resultados.innerHTML = "";
            return;
        }

        fetch("buscar_materias_nuevo.php?texto=" + encodeURIComponent(texto))
            .then(res => res.text())
            .then(data => {
                resultados.innerHTML = data;
            });
    });

    document.addEventListener("click", function(e) {

        const item = e.target.closest(".materia-item");

        if (item) {

            mostrarFormulario();

            document.getElementById("id_materia").value = item.dataset.id;
            document.getElementById("nombre").value = item.dataset.nombre;
            document.getElementById("descripcion").value = item.dataset.descripcion;
            document.getElementById("anio").value = item.dataset.anio;

            resultados.innerHTML = "";
        }
    });

});

function mostrarFormulario() {
    document.getElementById("formulario-materia").style.display = "block";
    document.getElementById("id_materia").value = "";
    document.getElementById("anio").value = 1;
}

function ocultarFormulario() {
    document.getElementById("formulario-materia").style.display = "none";
}

window.agregarMateria = function(texto) {

    mostrarFormulario();

    document.getElementById("nombre").value = texto;
    document.getElementById("descripcion").value = "";
};
</script>