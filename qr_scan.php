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

// คำนวณยอดรวมสำหรับการแสดง QR Code
$total_amount = $order['TotalAmount'];
$total_amount_for_qr = $total_amount * 100; // สมมติว่าแปลงจำนวนเงินจากบาทเป็นสตางค์

// สร้าง URL สำหรับ QR Code
$qr_url = "https://api.qrserver.com/v1/create-qr-code/?data=$total_amount_for_qr&size=150x150"; // URL สำหรับสร้าง QR Code

?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สแกน QR Code เพื่อชำระเงิน</title>
</head>
<body>

    <h1>โปรดสแกน QR Code ด้านล่างเพื่อทำการชำระเงิน</h1>
    <img src="<?php echo $qr_url; ?>" alt="QR Code สำหรับการชำระเงิน"><br>
    <p>จำนวนเงิน: ฿<?php echo number_format($total_amount, 2); ?></p>

    <p>เมื่อชำระเงินเสร็จสิ้นแล้ว กรุณากลับไปที่หน้า <a href="upload_slip.php?order_id=<?php echo $order_id; ?>">ส่งสลิปการชำระเงิน</a> เพื่อยืนยันสถานะคำสั่งซื้อ</p>

</body>
</html>
