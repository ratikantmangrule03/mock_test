<?php
session_start();

$host = getenv('DB_HOST') ?: 'mocktest-ratikantmangrule.a.aivencloud.com';
$port = getenv('DB_PORT') ?: '25821'; // FALLBACK CHANGED TO YOUR REAL PORT
$db   = getenv('DB_NAME') ?: 'defaultdb';
$user = getenv('DB_USER') ?: 'avnadmin';
$pass = getenv('DB_PASS') ?: '';

// Added 'port=$port' and 'sslmode=require' (Aiven requires strict SSL)
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
