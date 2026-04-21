<?php
/**
 * Date: 2026-04-01
 * Description: Database connection configuration for SyncSpace. Establishes a
 *              PDO connection to the MySQL database and makes $pdo available
 *              to any file that requires this script.
 */
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
