
<?php

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$host = 'localhost';
$username = 'root';
$password = '';
$database = 'campusconnect_db';

try {
    $conn = new mysqli($host, $username, $password, $database);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $exception) {
    error_log('CampusConnect database connection failed: ' . $exception->getMessage());
    http_response_code(500);
    exit('Database connection failed. Import database.sql and verify includes/db.php settings.');
}
