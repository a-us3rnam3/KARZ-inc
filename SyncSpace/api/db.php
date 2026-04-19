<?php
$db_host = "localhost";
$db_name = "rotarum_db";
$db_user = "rotarum_local";
$db_pass = ">+)SY]Ph";

$pdo = new PDO(
    "mysql:host=$db_host;dbname=$db_name;charset=utf8",
    $db_user,
    $db_pass,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
