<?php
session_start();
if (!isset($_SESSION['UserID']) || $_SESSION['UserType'] !== 'seller') {
    header('Location: login.php');
    exit;
}
include 'db.php';
include 'navbarseller.php';

if (!isset($_GET['buyer_id'])) {
    echo "ไม่พบข้อมูลผู้ซื้อ";
    exit;
}

$buyer_id = intval($_GET['buyer_id']);
$seller_id = $_SESSION['UserID'];

$sql = "SELECT o.OrderID, o.OrderDate, o.Status, SUM(od.Quantity * od.Price) as TotalAmount
        FROM Orders o
        JOIN OrderDetails od ON o.OrderID = od.OrderID
        JOIN Products p ON od.ProductID = p.ProductID
        WHERE o.BuyerID = ? AND p.SellerID = ?
        GROUP BY o.OrderID
        ORDER BY o.OrderDate DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $buyer_id, $seller_id);
$stmt->execute();
$result = $stmt->get_result();

$buyer_query = "SELECT FullName FROM Users WHERE UserID = ?";
$buyer_stmt = $conn->prepare($buyer_query);
$buyer_stmt->bind_param('i', $buyer_id);
$buyer_stmt->execute();
$buyer_result = $buyer_stmt->get_result();
$buyer_name = $buyer_result->fetch_assoc()['FullName'];
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>คำสั่งซื้อของ <?php echo htmlspecialchars($buyer_name); ?></title>
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
    <h1>คำสั่งซื้อของ <?php echo htmlspecialchars($buyer_name); ?></h1>

    <?php
    if ($result->num_rows > 0) {
        echo '<table>';
        echo '<thead>';
        echo '<tr>';
        echo '<th>คำสั่งซื้อ #</th>';
        echo '<th>วันที่สั่งซื้อ</th>';
        echo '<th>สถานะ</th>';
        echo '<th>ยอดรวม (บาท)</th>';
        echo '<th>รายละเอียด</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';

        while ($order = $result->fetch_assoc()) {
            echo '<tr>';
            echo '<td>' . $order['OrderID'] . '</td>';
            echo '<td>' . date('d/m/Y', strtotime($order['OrderDate'])) . '</td>';
            echo '<td>' . htmlspecialchars($order['Status']) . '</td>';
            echo '<td>฿' . number_format($order['TotalAmount'], 2) . '</td>';
            echo '<td><a href="order_info.php?order_id=' . $order['OrderID'] . '">ดูรายละเอียด</a></td>';
            echo '</tr>';
        }

        echo '</tbody>';
        echo '</table>';
    } else {
        echo '<p>ยังไม่มีคำสั่งซื้อจากลูกค้าคนนี้</p>';
    }
    ?>
</body>
</html>
