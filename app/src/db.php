<?php

$host = getenv('DB_HOST') ?: 'localhost';
$db   = getenv('DB_NAME') ?: 'examen_db';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: ''; 

$dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $user, $pass);
    // Configurar para que lance errores si algo falla
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Si falla, mostramos un mensaje bonito en lugar del error técnico
    die("<div style='color: red; text-align: center; padding: 20px;'>
            <h2>Error de Conexión a Base de Datos</h2>
            <p>" . $e->getMessage() . "</p>
            <p>Asegúrate de haber importado el archivo <strong>setup.sql</strong> en phpMyAdmin.</p>
         </div>");
}
?>