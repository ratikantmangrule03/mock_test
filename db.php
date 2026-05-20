<?php
session_start();

$host = getenv('DB_HOST') ?: '127.0.0.1';
$db   = getenv('DB_NAME') ?: 'mock_portal';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';

// CHANGED: 'mysql' is now 'pgsql'
$dsn = "pgsql:host=$host;dbname=$db;options='--client_encoding=UTF8'";

$options = [
 PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
 PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
 PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
 $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
 die("Database connection failed: " . $e->getMessage());
}
