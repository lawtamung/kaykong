<?php
session_start();
include 'db.php';

// ตรวจสอบว่าเป็นแอดมินหรือไม่
if ($_SESSION['UserType'] !== 'admin') {
    echo "เฉพาะแอดมินเท่านั้นที่สามารถอัปโหลดสลิปได้";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['admin_slip']) && isset($_POST['order_id'])) {
    $orderID = intval($_POST['order_id']);
    $file = $_FILES['admin_slip'];
    $uploadDir = 'uploads/slips_admin/';
    $fileName = time() . '_' . basename($file['name']);
    $filePath = $uploadDir . $fileName;
    
    // ตรวจสอบประเภทไฟล์ (อนุญาตเฉพาะไฟล์ภาพ)
    $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
    if (!in_array($file['type'], $allowedTypes)) {
        echo "รูปภาพต้องเป็นไฟล์ JPG หรือ PNG เท่านั้น";
        exit;
    }

    // ย้ายไฟล์ที่อัปโหลดไปยังโฟลเดอร์เป้าหมาย
    if (move_uploaded_file($file['tmp_name'], $filePath)) {
        // อัปเดตฐานข้อมูล
        $sql = "UPDATE Orders SET AdminPaymentSlip = ? WHERE OrderID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $fileName, $orderID);
        if ($stmt->execute()) {
            echo "อัปโหลดสลิปสำเร็จ!";
            header("Location: admin_orders.php"); // กลับไปหน้าหลักของแอดมิน
        } else {
            echo "เกิดข้อผิดพลาดในการบันทึกข้อมูล";
        }
    } else {
        echo "เกิดข้อผิดพลาดในการอัปโหลดไฟล์";
    }
}
?>
