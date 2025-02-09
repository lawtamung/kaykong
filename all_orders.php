<?php
session_start();
if ($_SESSION['UserType'] !== 'buyer') {
    header('Location: login.php');
    exit;
}
include 'db.php';
include 'navbar.php';

$userId = $_SESSION['UserID'];

$orderSqlAll = "SELECT o.OrderID, o.OrderDate, o.Status, SUM(od.Quantity * p.Price) AS TotalAmount
                FROM Orders o
                JOIN OrderDetails od ON o.OrderID = od.OrderID
                JOIN Products p ON od.ProductID = p.ProductID
                WHERE o.BuyerID = ? 
                GROUP BY o.OrderID
                ORDER BY o.OrderDate DESC";

$orderStmtAll = $conn->prepare($orderSqlAll);
$orderStmtAll->bind_param('i', $userId);
$orderStmtAll->execute();
$orderResultAll = $orderStmtAll->get_result();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ประวัติการสั่งซื้อทั้งหมด</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h1>ประวัติการสั่งซื้อทั้งหมด</h1>
        <?php if ($orderResultAll->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>หมายเลขคำสั่งซื้อ</th>
                        <th>วันที่สั่งซื้อ</th>
                        <th>สถานะ</th>
                        <th>ยอดรวม</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($order = $orderResultAll->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $order['OrderID']; ?></td>
                            <td><?php echo date('d/m/Y', strtotime($order['OrderDate'])); ?></td>
                            <td><?php echo ucfirst($order['Status']); ?></td>
                            <td><?php echo number_format($order['TotalAmount'], 2); ?> ฿</td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>คุณยังไม่มีประวัติการสั่งซื้อ</p>
        <?php endif; ?>
    </div>
</body>
</html>
