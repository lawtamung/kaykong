<?php  
session_start();

if ($_SESSION['UserType'] !== 'buyer') {
    header('Location: login.php');
    exit;
}

include 'db.php'; // เชื่อมต่อฐานข้อมูล
include 'navbar.php';
$buyer_id = $_SESSION['UserID']; // ผู้ใช้ที่เข้าสู่ระบบ (BuyerID)

// ตรวจสอบตะกร้าว่ามีสินค้าหรือไม่
$cart_sql = "SELECT * FROM Cart WHERE BuyerID = ?";
$cart_stmt = $conn->prepare($cart_sql);
$cart_stmt->bind_param("i", $buyer_id);
$cart_stmt->execute();
$cart_result = $cart_stmt->get_result();

if ($cart_result->num_rows === 0) {
    echo "ตะกร้าสินค้าของคุณว่างเปล่า";
    exit;
}

// ดึงที่อยู่หลักของผู้ใช้จาก Users
$user_address_sql = "SELECT Address FROM Users WHERE UserID = ?";
$user_address_stmt = $conn->prepare($user_address_sql);
$user_address_stmt->bind_param("i", $buyer_id);
$user_address_stmt->execute();
$user_address_result = $user_address_stmt->get_result();
$user_address = $user_address_result->fetch_assoc();

// ดึงที่อยู่ที่ผู้ใช้มีอยู่จาก UserAddresses
$address_sql = "SELECT * FROM UserAddresses WHERE UserID = ?";
$address_stmt = $conn->prepare($address_sql);
$address_stmt->bind_param("i", $buyer_id);
$address_stmt->execute();
$address_result = $address_stmt->get_result();

// ตรวจสอบว่ามีที่อยู่หรือไม่
if ($address_result->num_rows === 0 && !$user_address['Address']) {
    $address_options = 'โปรดใส่ที่อยู่ <a href="edit_profile.php">เพิ่มที่อยู่</a>';
} else {
    // แสดงที่อยู่ที่มีอยู่ให้ผู้ใช้เลือก
    $address_options = '';
    if ($user_address['Address']) {
        $address_options .= '<option value="user_address">' . htmlspecialchars($user_address['Address']) . ' (ที่อยู่หลัก)</option>';
    }
    while ($address = $address_result->fetch_assoc()) {
        $address_options .= '<option value="' . $address['AddressID'] . '">' . htmlspecialchars($address['Address']) . '</option>';
    }
}

// ตรวจสอบว่ามีการส่งฟอร์มที่อยู่และวิธีการชำระเงินหรือไม่
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $address_id = isset($_POST['address_id']) ? $_POST['address_id'] : null;

    $payment_method = $_POST['payment_method'];

    if (empty($address_id) || empty($payment_method)) {
        echo "กรุณาเลือกที่อยู่และวิธีการชำระเงิน";
    } else {
        // ดึงที่อยู่จาก UserAddresses หรือที่อยู่หลักจาก Users
        if ($address_id === 'user_address') {
            $address = $user_address['Address'];  // ใช้ที่อยู่หลักจาก Users
        } else {
            $selected_address_sql = "SELECT * FROM UserAddresses WHERE AddressID = ?";
            $selected_address_stmt = $conn->prepare($selected_address_sql);
            $selected_address_stmt->bind_param("i", $address_id);
            $selected_address_stmt->execute();
            $selected_address_result = $selected_address_stmt->get_result();
            $selected_address = $selected_address_result->fetch_assoc();
            $address = $selected_address['Address'];
        }

        // สร้างคำสั่งซื้อใหม่
        $total_amount = 0;
        $order_sql = "INSERT INTO Orders (BuyerID, TotalAmount, ShippingAddress, PaymentMethod) VALUES (?, ?, ?, ?)";
        $order_stmt = $conn->prepare($order_sql);
        $order_stmt->bind_param("idss", $buyer_id, $total_amount, $address, $payment_method);
        $order_stmt->execute();
        $order_id = $order_stmt->insert_id; // ดึง ID ของคำสั่งซื้อที่เพิ่งสร้าง

        // เพิ่มข้อมูลใน OrderDetails จากตะกร้า
        while ($item = $cart_result->fetch_assoc()) {
            $product_id = $item['ProductID'];
            $quantity = $item['Quantity'];

            // ดึงข้อมูลราคาจากฐานข้อมูล
            $product_sql = "SELECT Price FROM Products WHERE ProductID = ?";
            $product_stmt = $conn->prepare($product_sql);
            $product_stmt->bind_param("i", $product_id);
            $product_stmt->execute();
            $product_result = $product_stmt->get_result();
            $product = $product_result->fetch_assoc();
            $price = $product['Price'];

            $subtotal = $quantity * $price;
            $total_amount += $subtotal;

            // เพิ่มรายการใน OrderDetails
            $order_details_sql = "INSERT INTO OrderDetails (OrderID, ProductID, Quantity, Price) VALUES (?, ?, ?, ?)";
            $order_details_stmt = $conn->prepare($order_details_sql);
            $order_details_stmt->bind_param("iiid", $order_id, $product_id, $quantity, $price);
            $order_details_stmt->execute();
        }

        // อัปเดตยอดรวมคำสั่งซื้อ
        $update_order_sql = "UPDATE Orders SET TotalAmount = ? WHERE OrderID = ?";
        $update_order_stmt = $conn->prepare($update_order_sql);
        $update_order_stmt->bind_param("di", $total_amount, $order_id);
        $update_order_stmt->execute();

        // ลบสินค้าในตะกร้า
        $delete_cart_sql = "DELETE FROM Cart WHERE BuyerID = ?";
        $delete_cart_stmt = $conn->prepare($delete_cart_sql);
        $delete_cart_stmt->bind_param("i", $buyer_id);
        $delete_cart_stmt->execute();

        // นำผู้ใช้ไปยังหน้า order confirmation
        if ($payment_method == 'qr_code') {
            header("Location: qr_scan.php?order_id=$order_id");
        } else {
            header("Location: order_confirmation.php?order_id=$order_id");
        }
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตรวจสอบข้อมูลการสั่งซื้อ</title>
</head>
<body>

<h1>ตรวจสอบข้อมูลการสั่งซื้อ</h1>

<!-- ฟอร์มเลือกที่อยู่และวิธีการชำระเงิน -->
<form action="checkout.php" method="POST">
    <label for="address">เลือกที่อยู่สำหรับจัดส่ง:</label><br>
    <?php if ($address_result->num_rows > 0 || $user_address['Address']) { ?>
        <select name="address_id" id="address" required>
            <?php echo $address_options; ?>
        </select><br><br>
    <?php } else { ?>
        <p><?php echo $address_options; ?></p>
    <?php } ?>

    <label for="payment_method">วิธีการชำระเงิน:</label><br>
    <select name="payment_method" id="payment_method" required>
        <option value="cash_on_delivery">เก็บเงินปลายทาง</option>
        <option value="qr_code">ชำระเงินด้วย QR Code</option>
    </select><br><br>

    <button type="submit">ยืนยันคำสั่งซื้อ</button>
</form>

<br>
<a href="add_address.php">เพิ่มที่อยู่ใหม่</a>

</body>
</html>
