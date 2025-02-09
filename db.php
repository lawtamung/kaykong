<?php
// เปิดการแสดง Error
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = "localhost";
$username = "root";
$password = "";
$dbname = "kaykong";

// เชื่อมต่อฐานข้อมูล
$conn = new mysqli($host, $username, $password, $dbname);

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
// else {
    //echo "Connected successfully!";
//}

// ตั้งค่าการเชื่อมต่อให้รองรับ utf8mb4
$conn->set_charset("utf8mb4");
?>
