<?php
// app/db.php

$host = 'localhost';
$db   = 'travel_app';
$user = 'root';   // default for XAMPP
$pass = '';       // default is empty
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    // For now just show the error. Later we can make this nicer.
    exit('Database connection failed: ' . $e->getMessage());
}