<?php
$db_server = "localhost";
$db_username = "root";
$db_password = "";
$db_name = "internship";
$db_port = "3306";

$conn = new mysqli($db_server, $db_username, $db_password, $db_name, $db_port);

if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Database connection failed: " . mysqli_connect_error()]);
    exit;
}
return $conn;

