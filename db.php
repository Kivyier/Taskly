<?php
/**
 * db.php — PDO Database Connection Module
 * Connects PHP to the MySQL 'task_db' database via PDO.
 * Uses prepared statements for all queries to prevent SQL Injection.
 */

$host     = 'localhost';
$dbname   = 'task_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );

    // Throw exceptions on database errors
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Return rows as associative arrays by default
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database Connection Error: " . $e->getMessage());
}
?>
