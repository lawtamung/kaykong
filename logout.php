<?php

session_start(); // เริ่มต้น session



// ทำลายข้อมูลทั้งหมดใน session

session_unset();



// ทำลาย session

session_destroy();



// รีไดเร็กต์ผู้ใช้ไปยังหน้าล็อกอิน

header('Location: login.php');

exit;

?>
