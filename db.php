<?php
// Heroku Postgres Credentials
$host = 'cd62ai72qd7d5j.cluster-czrs8kj4isg7.us-east-1.rds.amazonaws.com';
$db   = 'dbj0bkefhesm1m';
$user = 'u67vor368u4rkv';
$pass = 'pf575ac898c44c61256290114fbfcaa388bb6ec6ef8c669e8bfdd621054a36f35'; // Replace ** if you masked it
$port = '5432';

$dsn = "pgsql:host=$host;port=$port;dbname=$db;";

try {
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    
    // 1. Create Active Accounts Table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS active_accounts (
            id SERIAL PRIMARY KEY,
            cookie_data TEXT,
            date_checked TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // 2. Create Admins Table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS admins (
            id SERIAL PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL
        )
    ");

    // 3. Insert Default Admin if the table is empty (Username: admin, Password: admin123)
    $stmt = $pdo->query("SELECT COUNT(*) FROM admins");
    if ($stmt->fetchColumn() == 0) {
        $default_user = 'admin';
        $default_pass = password_hash('admin123', PASSWORD_DEFAULT);
        $insert = $pdo->prepare("INSERT INTO admins (username, password_hash) VALUES (?, ?)");
        $insert->execute([$default_user, $default_pass]);
    }
    
} catch (\PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
}
?>