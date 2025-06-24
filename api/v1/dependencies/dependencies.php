<?php
require_once "../../../vendor/autoload.php";
use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable(__DIR__ . '/../../../global/');
$dotenv->load();

require_once "../../../global/config.php";
require_once "../../../global/db.php";
require_once "../../../global/request.php";
require_once "../../../global/response.php";
require_once "../../../global/helper.php";
require_once "../../../global/api.php";

