<?php
$host = "sql101.infinityfree.com";
$user = "if0_40076005";          // change if needed
$pass = "dvh6NY9mMN7";              // change if needed
$db   = "if0_40076005_cavitemed";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
?>
