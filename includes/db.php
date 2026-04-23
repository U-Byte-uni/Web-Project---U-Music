<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $mysqli = new mysqli("localhost", "root", "1234", "u-music");
    $mysqli->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    error_log("Database connection error: " . $e->getMessage());
    http_response_code(500);
    echo "Database connection error. Please try again later.";
    exit;
}
