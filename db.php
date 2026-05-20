<?php
session_start();

$host = getenv('DB_HOST');
$port = getenv('DB_PORT'); 
$db   = getenv('DB_NAME');
$user = getenv('DB_USER');
$pass = getenv('DB_PASS');

// Strict PostgreSQL connection string with SSL required
$dsn = "pgsql:host=$host;port=$port;dbname=$db;sslmode=require;options='--client_encoding=UTF8'";

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
