<?php  
session_start();
include 'db.php';  // เชื่อมต่อฐานข้อมูล
include 'navbaradmin.php';

// ตรวจสอบว่าเป็นแอดมินหรือไม่
if ($_SESSION['UserType'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// ดึงข้อมูลคำสั่งซื้อที่สถานะเป็น 'completed' (จัดส่งสำเร็จแล้ว) และที่ใช้ QR
$sqlOrders = "SELECT o.OrderID, o.BuyerID, o.TotalAmount, o.OrderDate, o.Status, o.PaymentSlip, o.AdminPaymentSlip,
                    u.Username AS buyer_username, u.Email AS buyer_email
              FROM Orders o
              JOIN Users u ON o.BuyerID = u.UserID
              WHERE o.Status = 'completed' AND o.PaymentSlip IS NOT NULL
              ORDER BY o.OrderDate DESC"; // เพิ่มการเรียงลำดับตามวันที่

$stmtOrders = $conn->prepare($sqlOrders);
$stmtOrders->execute();
$orders = $stmtOrders->get_result();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการคำสั่งซื้อ - Admin</title>
    <style>
        .completed { color: green; font-weight: bold; }
        .pending { color: orange; font-weight: bold; }
        .cancelled { color: red; font-weight: bold; }
        img { max-width: 200px; max-height: 200px; } /* ขนาดรูปที่แสดง */
    </style>
</head>
<body>

<h1>จัดการคำสั่งซื้อ (จัดส่งสำเร็จแล้ว)</h1>

<table border="1" cellpadding="10">
    <thead>
        <tr>
            <th>ลำดับ</th>
            <th>หมายเลขคำสั่งซื้อ</th>
            <th>ผู้ซื้อ</th>
            <th>อีเมลผู้ซื้อ</th>
            <th>จำนวนเงินรวม</th>
            <th>วันที่คำสั่งซื้อ</th>
            <th>สถานะ</th>
            <th>ข้อมูลผู้ขาย</th>
            <th>ข้อมูลบัญชีผู้ขาย</th>
            <th>อัปโหลดสลิปโอนเงิน</th>
            <th>ดูสลิปที่อัปโหลด (ลูกค้า)</th>
            <th>ดูสลิปที่อัปโหลด (แอดมิน)</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $index = 1;
        while ($order = $orders->fetch_assoc()) {
            // ดึงข้อมูลผู้ขายจากคำสั่งซื้อ
            $orderID = $order['OrderID'];
            $sqlOrderDetails = "SELECT p.SellerID, u.FullName AS seller_name, u.PhoneNumber AS seller_phone, 
                                       ua.Address AS seller_address, u.AccountNumber, u.BankName
                                FROM OrderDetails od
                                JOIN Products p ON od.ProductID = p.ProductID
                                JOIN Users u ON p.SellerID = u.UserID
                                LEFT JOIN UserAddresses ua ON u.UserID = ua.UserID
                                WHERE od.OrderID = ? LIMIT 1";  // ใช้ LIMIT 1 เพื่อดึงข้อมูลผู้ขายเพียงครั้งเดียว
            $stmtOrderDetails = $conn->prepare($sqlOrderDetails);
            $stmtOrderDetails->bind_param("i", $orderID);
            $stmtOrderDetails->execute();
            $orderDetails = $stmtOrderDetails->get_result();
            $sellerDetails = $orderDetails->fetch_assoc();  // ดึงข้อมูลผู้ขาย

            echo "<tr>
                    <td>{$index}</td>
                    <td>{$order['OrderID']}</td>
                    <td>{$order['buyer_username']}</td>
                    <td>{$order['buyer_email']}</td>
                    <td>" . number_format($order['TotalAmount'], 2) . "</td>
                    <td>{$order['OrderDate']}</td>
                    <td class='completed'>{$order['Status']}</td>
                    <td>";
            
            if ($sellerDetails) {
                echo "<strong>ผู้ขาย:</strong> {$sellerDetails['seller_name']}<br>
                      <strong>เบอร์โทรศัพท์:</strong> {$sellerDetails['seller_phone']}<br><br>";
            }

            echo "</td>
                  <td>";
            
            if ($sellerDetails) {
                echo "<strong>บัญชีธนาคาร:</strong> {$sellerDetails['AccountNumber']}<br>";
                echo "<strong>ชื่อธนาคาร:</strong> {$sellerDetails['BankName']}<br><br>";
            }
            
            echo "</td>
                  <td>
                    <form action='upload_admin_slip.php' method='post' enctype='multipart/form-data'>
                        <input type='hidden' name='order_id' value='{$orderID}'>
                        <input type='file' name='admin_slip' required>
                        <button type='submit'>อัปโหลด</button>
                    </form>
                  </td>
                  <td>";
            
                    // ตรวจสอบการมีอยู่ของสลิปการชำระเงิน
            if (!empty($order['PaymentSlip'])) {
                // อัปเดตเส้นทางไปยังโฟลเดอร์ uploads/slips/
                $payment_slip_path = 'uploads/slips/' . basename($order['PaymentSlip']);
                echo '<a href="' . $payment_slip_path . '" data-lightbox="payment-slip" data-title="Payment Slip">
                        <img src="' . $payment_slip_path . '" alt="Payment Slip" width="100">
                    </a>';
            } else {
                echo 'ไม่มี';
            }

            echo "</td>
                  <td>";
            
           // ตรวจสอบการมีอยู่ของสลิปแอดมิน
            if (!empty($order['AdminPaymentSlip'])) {
                // อัปเดตเส้นทางไปยังโฟลเดอร์ uploads/slips_admin/
                $admin_payment_slip_path = 'uploads/slips_admin/' . basename($order['AdminPaymentSlip']);
                echo '<a href="' . $admin_payment_slip_path . '" data-lightbox="admin-payment-slip" data-title="สลิปแอดมิน">';
                echo "<img src='" . $admin_payment_slip_path . "' alt='สลิปแอดมิน' width='100'>";
                echo '</a>';
            } else {
                echo "ยังไม่มีสลิปแอดมิน";
            }

            echo "</td>
                </tr>";
            $index++;
        }
        ?>
    </tbody>
</table>

</body>
</html>
