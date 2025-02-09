<?php

session_start();

if ($_SESSION['UserType'] !== 'buyer') {
    header('Location: login.php');
    exit;
}

include 'db.php';  // รวมการเชื่อมต่อฐานข้อมูล

$buyer_id = $_SESSION['UserID']; // ผู้ใช้ที่เข้าสู่ระบบ (BuyerID)
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if ($order_id === 0) {
    echo "คำสั่งซื้อไม่ถูกต้อง";
    exit;
}

// ดึงข้อมูลคำสั่งซื้อจากฐานข้อมูล
$order_sql = "SELECT * FROM Orders WHERE OrderID = ? AND BuyerID = ?";
$order_stmt = $conn->prepare($order_sql);
$order_stmt->bind_param("ii", $order_id, $buyer_id);
$order_stmt->execute();
$order_result = $order_stmt->get_result();

if ($order_result->num_rows === 0) {
    echo "ไม่พบคำสั่งซื้อนี้";
    exit;
}

$order = $order_result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['slip_image'])) {
    $slip_image = $_FILES['slip_image'];

    // ตรวจสอบประเภทไฟล์และขนาด
    $allowed_types = ['image/jpeg', 'image/png'];
    if (in_array($slip_image['type'], $allowed_types) && $slip_image['size'] <= 5000000) { // จำกัดขนาดไฟล์ที่ 5MB
        $upload_dir = 'uploads/slips/';
        $file_path = $upload_dir . basename($slip_image['name']);
        if (move_uploaded_file($slip_image['tmp_name'], $file_path)) {
            // บันทึกข้อมูลสลิปในฐานข้อมูล
            $slip_sql = "UPDATE Orders SET PaymentSlip = ? WHERE OrderID = ?";
            $slip_stmt = $conn->prepare($slip_sql);
            $slip_stmt->bind_param("si", $file_path, $order_id);
            $slip_stmt->execute();

            // นำผู้ใช้ไปที่หน้า order confirmation
            header("Location: order_confirmation.php?order_id=$order_id");
            exit;
        } else {
            echo "ไม่สามารถอัปโหลดไฟล์สลิปได้";
        }
    } else {
        echo "ประเภทไฟล์ไม่ถูกต้องหรือไฟล์มีขนาดใหญ่เกินไป";
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ส่งสลิปการชำระเงิน</title>
</head>
<body>

<h1>กรุณาส่งสลิปการชำระเงิน</h1>

<form action="upload_slip.php?order_id=<?php echo $order_id; ?>" method="POST" enctype="multipart/form-data">
    <label for="slip_image">เลือกสลิปการชำระเงิน:</label>
    <input type="file" name="slip_image" id="slip_image" required><br><br>
    <button type="submit">อัปโหลดสลิป</button>
</form>

</body>
</html>
