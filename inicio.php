<div style="padding:30px; font-family:Arial, Helvetica, sans-serif;">

    <h2 style="margin-bottom:10px; color:#2c3e50;">
        Bienvenido al Sistema CPT
    </h2>

    <p style="margin-bottom:10px; color:#555;">
        👤 <?= $_SESSION['nombre'] ?? '' ?> 
       
    </p>

    <?php if (!empty($_SESSION['centro'])): ?>
        <p style="margin-bottom:30px; color:#777;">
            📍 Centro: <?= $_SESSION['centro'] ?>
        </p>
    <?php endif; ?>

    <!--<p style="margin-bottom:30px; color:#555;">
        Panel principal de acceso a módulos del sistema.
    </p> -->

    <!-- MÓDULOS PRINCIPALES -->
    <h3 style="color:#34495e; margin-bottom:15px;">Módulos Operativos</h3>

    <div style="
        display:grid;
        grid-template-columns:repeat(auto-fit,minmax(240px,1fr));
        gap:20px;
        margin-bottom:35px;
    ">

        <a href="plantilla.php?modulo=alumnos" style="text-decoration:none;">
        <div style="background:#fff; border-radius:10px; padding:20px; box-shadow:0 2px 8px rgba(0,0,0,0.08);">
            <h3 style="margin-top:0; color:#007bff;">📋 Alumnos</h3>
            <p style="color:#555;">Gestión de alumnos.</p>
        </div>
        </a>

        <a href="plantilla.php?modulo=inscripciones" style="text-decoration:none;">
        <div style="background:#fff; border-radius:10px; padding:20px; box-shadow:0 2px 8px rgba(0,0,0,0.08);">
            <h3 style="margin-top:0; color:#dc3545;">📝 Inscripciones</h3>
            <p style="color:#555;">Registro de inscripciones.</p>
        </div>
        </a>

    <!--    <a href="plantilla.php?modulo=inscripciones_historico" style="text-decoration:none;">
        <div style="background:#fff; border-radius:10px; padding:20px; box-shadow:0 2px 8px rgba(0,0,0,0.08);">
            <h3 style="margin-top:0; color:#6f42c1;">📚 Histórico</h3>
            <p style="color:#555;">Carga histórica de notas.</p>
        </div>
        </a>  -->

        <a href="plantilla.php?modulo=notas" style="text-decoration:none;">
        <div style="background:#fff; border-radius:10px; padding:20px; box-shadow:0 2px 8px rgba(0,0,0,0.08);">
            <h3 style="margin-top:0; color:#6f42c1;">🎓 Notas</h3>
            <p style="color:#555;">Carga y consulta de notas.</p>
        </div>
        </a>

        <a href="plantilla.php?modulo=listado_materia" style="text-decoration:none;">
        <div style="background:#fff; border-radius:10px; padding:20px; box-shadow:0 2px 8px rgba(0,0,0,0.08);">
            <h3 style="margin-top:0; color:#fd7e14;">📊 Listados</h3>
            <p style="color:#555;">Listados por materia.</p>
        </div>
        </a>

        <a href="plantilla.php?modulo=reporte_alumno" style="text-decoration:none;">
        <div style="background:#fff; border-radius:10px; padding:20px; box-shadow:0 2px 8px rgba(0,0,0,0.08);">
            <h3 style="margin-top:0; color:#20c997;">📄 Reporte Alumno</h3>
            <p style="color:#555;">Historial académico del alumno.</p>
        </div>
        </a>

        <a href="plantilla.php?modulo=egresados" style="text-decoration:none;">
        <div style="background:#fff; border-radius:10px; padding:20px; box-shadow:0 2px 8px rgba(0,0,0,0.08);">
            <h3 style="margin-top:0; color:#cc00cc;">📄 Egresados</h3>
            <p style="color:#555;">Futuras graduaciones</p>
        </div>
        </a>

    </div>

    <!-- SEGUNDA ETAPA 
    <h3 style="color:#7f8c8d; margin-bottom:15px;">Módulos de Segunda Etapa</h3>

    <div style="
        display:grid;
        grid-template-columns:repeat(auto-fit,minmax(240px,1fr));
        gap:20px;
    ">

        <div style="background:#f8f9fa; border-radius:10px; padding:20px; border:1px dashed #bbb;">
            <h3 style="margin-top:0; color:#6c757d;">👤 Usuarios</h3>
            <p style="color:#666;">Administración de usuarios.</p>
        </div>

        <div style="background:#f8f9fa; border-radius:10px; padding:20px; border:1px dashed #bbb;">
            <h3 style="margin-top:0; color:#6c757d;">⚙️ Sistema</h3>
            <p style="color:#666;">Configuración general.</p>
        </div> 

    </div>  -->

</div>