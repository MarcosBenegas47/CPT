<?php
require_once __DIR__ . "/conexion.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 🔒 Solo admin puede acceder
if (($_SESSION['rol'] ?? '') !== 'admin') {
    echo "Acceso restringido";
    exit;
}

$mensaje = "";

// ==========================
// GUARDAR USUARIO
// ==========================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $usuario = trim($_POST['usuario'] ?? '');
    $clave   = trim($_POST['clave'] ?? '');
    $nombre  = trim($_POST['nombre'] ?? '');
    $rol     = $_POST['rol'] ?? '';
    $id_centro = $_POST['id_centro'] ?? '';

    if (!$usuario || !$clave || !$nombre || !$rol || !$id_centro) {
        $mensaje = "⚠ Completar todos los campos";
    } else {

        // verificar usuario existente
        $stmt = $conexion->prepare("SELECT id_usuario FROM usuarios WHERE usuario = :u");
        $stmt->execute(['u' => $usuario]);

        if ($stmt->fetch()) {
            $mensaje = "❌ El usuario ya existe";
        } else {

            // 🔐 encriptar contraseña
            $hash = password_hash($clave, PASSWORD_DEFAULT);

            $stmt = $conexion->prepare("
                INSERT INTO usuarios (usuario, clave, nombre, id_centro, rol)
                VALUES (:usuario, :clave, :nombre, :centro, :rol)
            ");

            $stmt->execute([
                'usuario' => $usuario,
                'clave'   => $hash,
                'nombre'  => $nombre,
                'centro'  => $id_centro,
                'rol'     => $rol
            ]);

            $mensaje = "✔ Usuario creado correctamente";
        }
    }
}
?>

<h3>👤 Alta de Usuarios</h3>

<?php if ($mensaje): ?>
    <div style="margin-bottom:10px; padding:10px; background:#ecf0f1;">
        <?= $mensaje ?>
    </div>
<?php endif; ?>

<form method="POST">

    Usuario:<br>
    <input type="text" name="usuario" required><br><br>

    Nombre:<br>
    <input type="text" name="nombre" required><br><br>

    Contraseña:<br>
    <input type="password" name="clave" required><br><br>

    Rol:<br>
    <select name="rol" required>
        <option value="">Seleccionar</option>
        <option value="operador">Operador</option>
        <option value="director">Secretaría</option>
        <option value="director_general">Director General</option>
        <option value="superadmin">Superadmin</option>
    </select><br><br>

    Centro:<br>
    <select name="id_centro" required>
        <option value="">Seleccionar</option>
        <?php
        $centros = $conexion->query("SELECT id_centro, nombre FROM centros ORDER BY nombre");
        foreach ($centros as $c) {
            echo "<option value='{$c['id_centro']}'>{$c['nombre']}</option>";
        }
        ?>
    </select><br><br>

    <button type="submit">Crear usuario</button>

</form>