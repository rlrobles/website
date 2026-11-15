<?php
date_default_timezone_set('Asia/Manila');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$db_server = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "sbm_db";

try {
    $conn = new mysqli($db_server, $db_user, $db_pass, $db_name);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $conn->query("SET time_zone = '+08:00'");
    $conn->set_charset("utf8mb4");
    } catch (mysqli_sql_exception $e) {

    error_log("Database connection error: " . $e->getMessage());

    http_response_code(500);
    exit("Unable to connect to the database. Please try again later.");

}
?>