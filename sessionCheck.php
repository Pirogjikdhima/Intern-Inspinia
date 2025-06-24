<?php
require_once './vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . "/global/");
$dotenv->load();

require_once './global/config.php';
require_once './global/db.php';
require_once './global/request.php';
require_once './global/response.php';
require_once './global/helper.php';
require_once './global/api.php';

if (!isLoggedIn()) {
    header("Location: ./login.php");
    exit;
}
$conn = connectDB();

$username = decryptMessage($_SESSION['username']);
$role = decryptMessage($_SESSION['role']);
$email = decryptMessage($_SESSION['email']);
$user_id = decryptMessage($_SESSION['user_id']);


