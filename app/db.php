<?php
$host = 'db'; // The name of the MySQL service in docker-compose
$db   = 'simulacija';
$user = 'worker_app';
$pass = 'worker_password';

$dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     // Za potrebe debuggovanja u simulaciji, mada se produkciji skrivaju greske baze
     die("Connection failed: " . $e->getMessage());
}
?>
