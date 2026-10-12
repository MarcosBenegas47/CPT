<?php
include("conexion.php");

function guardarMateria($data) {
    global $conexion;

    $nombre = mb_strtoupper(trim($data['nombre']), 'UTF-8');
    $descripcion = mb_convert_case(trim($data['descripcion']), MB_CASE_TITLE, 'UTF-8');
    $anio = $data['anio'];

    // UPDATE
    if (!empty($data['id_materia'])) {

        $sql = "UPDATE materias 
                SET nombre = :nombre, 
                    descripcion = :descripcion,
                    anio = :anio
                WHERE id_materia = :id";

        $stmt = $conexion->prepare($sql);
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':descripcion', $descripcion);
        $stmt->bindParam(':anio', $anio);
        $stmt->bindParam(':id', $data['id_materia']);
        $stmt->execute();

        return "actualizado";
    }

    // VERIFICAR DUPLICADO
    $sql = "SELECT * FROM materias 
            WHERE nombre = :nombre 
            AND anio = :anio";

    $stmt = $conexion->prepare($sql);
    $stmt->bindParam(':nombre', $nombre);
    $stmt->bindParam(':anio', $anio);
    $stmt->execute();

    if ($stmt->fetch()) {
        return "existe";
    }

    // INSERT
    $sql = "INSERT INTO materias (nombre, descripcion, anio) 
            VALUES (:nombre, :descripcion, :anio)";

    $stmt = $conexion->prepare($sql);
    $stmt->bindParam(':nombre', $nombre);
    $stmt->bindParam(':descripcion', $descripcion);
    $stmt->bindParam(':anio', $anio);
    $stmt->execute();

    return "insertado";
}