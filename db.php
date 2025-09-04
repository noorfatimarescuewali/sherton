<?php
$DB_HOST = 'localhost';
$DB_NAME = 'dbgbr2hj8ej44w';
$DB_USER = 'ud89fw4spumtd';
$DB_PASS = 'dpnpg9ge2uey';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
$mysqli->set_charset('utf8mb4');
?>
