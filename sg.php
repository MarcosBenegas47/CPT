<?php
session_start();

// Si está logueado → entra al sistema
if (isset($_SESSION['id_usuario'])) {
    header("Location: plantilla.php?modulo=inicio");
} else {
    // Si no → va al login
    header("Location: plantilla.php?modulo=login");
}

exit;