<?php
session_start();
include 'db.php';  // รวมการเชื่อมต่อฐานข้อมูล

// ตรวจสอบการอัปเดตตะกร้า
if (isset($_POST['update_cart'])) {
    // อัปเดตจำนวนสินค้าในตะกร้า
    if (isset($_POST['quantity'])) {
        foreach ($_POST['quantity'] as $product_id => $quantity) {
            if ($quantity <= 0) {
                // ถ้าจำนวนสินค้าต่ำกว่าหรือเท่ากับ 0 ให้ลบสินค้านั้น
                unset($_SESSION['cart'][$product_id]);
            } else {
                // อัปเดตจำนวนสินค้าในตะกร้า
                $_SESSION['cart'][$product_id]['quantity'] = $quantity;
            }
        }
    }

    // ลบสินค้าหากกดปุ่ม "ลบ"
    if (isset($_POST['remove'])) {
        $remove_id = $_POST['remove'];
        unset($_SESSION['cart'][$remove_id]);  // ลบสินค้าจากตะกร้า
    }

    // รีไดเร็กต์กลับไปยังหน้าตะกร้า (cart.php)
    header('Location: cart.php');
    exit;
} else {
    // ถ้าไม่พบการส่งข้อมูลจากฟอร์ม ให้รีไดเร็กต์ไปที่หน้า cart.php
    header('Location: cart.php');
    exit;
}
?>
