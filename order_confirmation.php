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
// ลด Stock ของสินค้า
$update_stock_sql = "UPDATE Products p
                     JOIN OrderDetails od ON p.ProductID = od.ProductID
                     SET p.Stock = p.Stock - od.Quantity
                     WHERE od.OrderID = ?";
$update_stock_stmt = $conn->prepare($update_stock_sql);
$update_stock_stmt->bind_param("i", $order_id);
$update_stock_stmt->execute();


$order = $order_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ยืนยันคำสั่งซื้อ</title>
    <style>
        .order-confirmation {
            width: 80%;
            margin: 20px auto;
            text-align: center;
        }

        table {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
        }

        th {
            background-color: #f4f4f4;
        }

        .total {
            margin-top: 20px;
            font-size: 1.2em;
            font-weight: bold;
        }
    </style>
</head>
<body>

    <div class="order-confirmation">
        <h1>ยืนยันคำสั่งซื้อ #<?php echo $order['OrderID']; ?></h1>
        <p>คำสั่งซื้อของคุณได้รับการยืนยันแล้ว</p>
        <p>สถานะคำสั่งซื้อ: <?php echo htmlspecialchars($order['Status']); ?></p>
        <p>วันที่สั่งซื้อ: <?php echo date('d/m/Y H:i', strtotime($order['OrderDate'])); ?></p>

        <h2>รายละเอียดคำสั่งซื้อ</h2>
        <table>
            <tr>
                <th>ชื่อสินค้า</th>
                <th>ราคา</th>
                <th>จำนวน</th>
                <th>รวม</th>
            </tr>

            <?php
            // ดึงรายละเอียดสินค้าในคำสั่งซื้อจาก OrderDetails
            $order_details_sql = "SELECT p.ProductName, od.Quantity, od.Price 
                                  FROM OrderDetails od
                                  JOIN Products p ON od.ProductID = p.ProductID
                                  WHERE od.OrderID = ?";
            $order_details_stmt = $conn->prepare($order_details_sql);
            $order_details_stmt->bind_param("i", $order_id);
            $order_details_stmt->execute();
            $order_details_result = $order_details_stmt->get_result();

            $total_amount = 0;
            while ($item = $order_details_result->fetch_assoc()) {
                $subtotal = $item['Quantity'] * $item['Price'];
                $total_amount += $subtotal;
                echo '<tr>';
                echo '<td>' . htmlspecialchars($item['ProductName']) . '</td>';
                echo '<td>฿' . number_format($item['Price'], 2) . '</td>';
                echo '<td>' . htmlspecialchars($item['Quantity']) . '</td>';
                echo '<td>฿' . number_format($subtotal, 2) . '</td>';
                echo '</tr>';
            }
            ?>
        </table>

        <div class="total">
            ยอดรวม: ฿<?php echo number_format($total_amount, 2); ?>
        </div>

        <p>วิธีการชำระเงิน: <?php echo htmlspecialchars($order['PaymentMethod']); ?></p>

        <p>ขอบคุณที่ช็อปกับเรา!</p>
        <a href="index.php">กลับไปหน้าหลัก</a>
    </div>

</body>
</html>
