<?php
$db_host = "localhost";
$db_name = "zamane1_db";
$db_user = "zamane1_local";
$db_pass = ";2<alEf2";

$pdo = new PDO(
    "mysql:host=$db_host;dbname=$db_name;charset=utf8",
    $db_user,
    $db_pass,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
