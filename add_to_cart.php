<?php
session_start();
if ($_SESSION['UserType'] !== 'buyer') {
    header('Location: login.php');
    exit;
}

include 'db.php'; // เชื่อมต่อฐานข้อมูล

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    $buyer_id = $_SESSION['UserID'];
    $product_id = intval($_POST['product_id']);
    $quantity = 1; // ค่าเริ่มต้นใส่สินค้าครั้งละ 1 ชิ้น

    // ตรวจสอบว่าสินค้าอยู่ในตะกร้าแล้วหรือไม่
    $check_cart_sql = "SELECT * FROM Cart WHERE BuyerID = ? AND ProductID = ?";
    $check_cart_stmt = $conn->prepare($check_cart_sql);
    $check_cart_stmt->bind_param("ii", $buyer_id, $product_id);
    $check_cart_stmt->execute();
    $cart_result = $check_cart_stmt->get_result();

    if ($cart_result->num_rows > 0) {
        // ถ้าสินค้าอยู่ในตะกร้าแล้ว ให้เพิ่มจำนวน
        $update_cart_sql = "UPDATE Cart SET Quantity = Quantity + ? WHERE BuyerID = ? AND ProductID = ?";
        $update_cart_stmt = $conn->prepare($update_cart_sql);
        $update_cart_stmt->bind_param("iii", $quantity, $buyer_id, $product_id);
        $update_cart_stmt->execute();
    } else {
        // ถ้ายังไม่มีสินค้าในตะกร้า ให้เพิ่มเข้าไป
        $add_cart_sql = "INSERT INTO Cart (BuyerID, ProductID, Quantity) VALUES (?, ?, ?)";
        $add_cart_stmt = $conn->prepare($add_cart_sql);
        $add_cart_stmt->bind_param("iii", $buyer_id, $product_id, $quantity);
        $add_cart_stmt->execute();
    }

    // Redirect กลับไปที่หน้าหลัก
    header("Location: cart.php");
    exit;
} else {
    echo "เกิดข้อผิดพลาดในการเพิ่มสินค้า";
}
?>
