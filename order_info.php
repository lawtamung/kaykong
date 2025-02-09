<?php
session_start();
if (!isset($_SESSION['UserID']) || $_SESSION['UserType'] !== 'seller') {
    header('Location: login.php');
    exit;
}
include 'db.php';
include 'navbarseller.php';

// ตรวจสอบว่ามีการส่งค่า order_id หรือไม่
if (!isset($_GET['order_id'])) {
    echo "ไม่พบข้อมูลคำสั่งซื้อ";
    exit;
}

$order_id = intval($_GET['order_id']);
$seller_id = $_SESSION['UserID'];

// ดึงข้อมูลคำสั่งซื้อ
$sql = "SELECT o.OrderID, o.OrderDate, o.Status, u.FullName as BuyerName, 
               od.Quantity, od.Price, p.ProductName 
        FROM Orders o
        JOIN OrderDetails od ON o.OrderID = od.OrderID
        JOIN Products p ON od.ProductID = p.ProductID
        JOIN Users u ON o.BuyerID = u.UserID
        WHERE o.OrderID = ? AND p.SellerID = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $order_id, $seller_id);
$stmt->execute();
$result = $stmt->get_result();

// ตรวจสอบว่ามีข้อมูลคำสั่งซื้อหรือไม่
if ($result->num_rows == 0) {
    echo "ไม่พบคำสั่งซื้อนี้ หรือคุณไม่มีสิทธิ์เข้าถึง";
    exit;
}

$order = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายละเอียดคำสั่งซื้อ #<?php echo $order['OrderID']; ?></title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f4f4f4;
        }
    </style>
</head>
<body>
    <h1>รายละเอียดคำสั่งซื้อ #<?php echo $order['OrderID']; ?></h1>
    <p><strong>ผู้ซื้อ:</strong> <?php echo htmlspecialchars($order['BuyerName']); ?></p>
    <p><strong>วันที่สั่งซื้อ:</strong> <?php echo date('d/m/Y', strtotime($order['OrderDate'])); ?></p>
    <p><strong>สถานะ:</strong> <?php echo htmlspecialchars($order['Status']); ?></p>

    <h2>รายการสินค้า</h2>
    <table>
        <thead>
            <tr>
                <th>ชื่อสินค้า</th>
                <th>จำนวน</th>
                <th>ราคา/หน่วย</th>
                <th>ราคารวม</th>
            </tr>
        </thead>
        <tbody>
            <?php
            do {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($order['ProductName']) . '</td>';
                echo '<td>' . $order['Quantity'] . '</td>';
                echo '<td>฿' . number_format($order['Price'], 2) . '</td>';
                echo '<td>฿' . number_format($order['Quantity'] * $order['Price'], 2) . '</td>';
                echo '</tr>';
            } while ($order = $result->fetch_assoc());
            ?>
        </tbody>
    </table>

    <a href="order_history.php">🔙 กลับไปหน้ารายการคำสั่งซื้อ</a>


</body>
</html>
