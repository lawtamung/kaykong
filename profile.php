<?php 
session_start();

if ($_SESSION['UserType'] !== 'buyer') {
    header('Location: login.php');
    exit;
}

include 'db.php';  // รวมการเชื่อมต่อฐานข้อมูล
include 'navbar.php';  // รวม navbar เข้ามา

// Get user's profile data
$userId = $_SESSION['UserID'];
$sql = "SELECT * FROM Users WHERE UserID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// ดึงข้อมูลที่อยู่จากฟิลด์ Address ในตาราง Users
$address = $user['Address'];  // ดึงที่อยู่จากฟิลด์ Address

// ดึงข้อมูลประวัติการสั่งซื้อ (แสดง 5 รายการแรก)
$orderSql = "SELECT o.OrderID, o.OrderDate, o.Status, SUM(od.Quantity * p.Price) AS TotalAmount
             FROM Orders o
             JOIN OrderDetails od ON o.OrderID = od.OrderID
             JOIN Products p ON od.ProductID = p.ProductID
             WHERE o.BuyerID = ? 
             GROUP BY o.OrderID
             ORDER BY o.OrderDate DESC
             LIMIT 5";  // จำกัดรายการที่ดึงมาแค่ 5 รายการแรก

$orderStmt = $conn->prepare($orderSql);
$orderStmt->bind_param('i', $userId);
$orderStmt->execute();
$orderResult = $orderStmt->get_result();

// ดึงข้อมูลประวัติการสั่งซื้อทั้งหมดเพื่อใช้ใน "แสดงทั้งหมด"
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

// เช็คจำนวนคำสั่งซื้อทั้งหมด
$totalOrders = $orderResultAll->num_rows;

// ฟังก์ชันในการอัพเดตสถานะคำสั่งซื้อ
if (isset($_GET['orderId']) && isset($_GET['action']) && $_GET['action'] == 'markAsReceived') {
    $orderId = $_GET['orderId'];

    // อัพเดตสถานะของคำสั่งซื้อในฐานข้อมูล
    $updateSql = "UPDATE Orders SET Status = 'Completed' WHERE OrderID = ?";
    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->bind_param('i', $orderId);
    $updateStmt->execute();
    header("Location: profile.php");  // รีเฟรชหน้า
    exit;
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>โปรไฟล์ของฉัน</title>
    <link rel="stylesheet" href="styles.css"> <!-- เชื่อมโยงกับไฟล์ CSS -->
</head>
<body>
    <div class="container">
        <h1>โปรไฟล์ของฉัน</h1>
        <div class="profile-info">
            <p><strong>ชื่อเต็ม:</strong> <?php echo $user['FullName']; ?></p>
            <p><strong>อีเมล:</strong> <?php echo $user['Email']; ?></p>
            <p><strong>เบอร์โทรศัพท์:</strong> <?php echo $user['PhoneNumber']; ?></p>

            <h3>ที่อยู่:</h3>
            <?php if (!empty($address)): ?>
                <ul>
                    <li><?php echo htmlspecialchars($address); ?></li>
                </ul>
            <?php else: ?>
                <p>คุณยังไม่ได้เพิ่มที่อยู่</p>
            <?php endif; ?>

            <p><strong>ประเภทผู้ใช้:</strong> <?php echo ucfirst($user['UserType']); ?></p>
        </div>

        <div class="profile-actions">
            <a href="edit_profile.php" class="btn-edit-profile">ตั้งค่าโปรไฟล์</a>
        </div>

        <h2>ประวัติการสั่งซื้อ</h2>
        <?php if ($orderResult->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>หมายเลขคำสั่งซื้อ</th>
                        <th>วันที่สั่งซื้อ</th>
                        <th>สถานะ</th>
                        <th>ยอดรวม</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($order = $orderResult->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $order['OrderID']; ?></td>
                            <td><?php echo date('d/m/Y', strtotime($order['OrderDate'])); ?></td>
                            <td><?php echo ucfirst($order['Status']); ?></td>
                            <td><?php echo number_format($order['TotalAmount'], 2); ?> ฿</td>
                            <td>
                                <?php if ($order['Status'] == 'Shipping'): ?>
                                    <a href="?orderId=<?php echo $order['OrderID']; ?>&action=markAsReceived" class="btn-received">ได้รับสินค้าแล้ว</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

            <!-- แสดงปุ่ม "แสดงประวัติการสั่งซื้อทั้งหมด" ถ้ามีคำสั่งซื้อมากกว่า 5 รายการ -->
            <?php if ($totalOrders > 5): ?>
                <a href="all_orders.php" class="btn-show-more">แสดงประวัติการสั่งซื้อทั้งหมด</a>
            <?php endif; ?>
        <?php else: ?>
            <p>คุณยังไม่มีประวัติการสั่งซื้อ</p>
        <?php endif; ?>
    </div>
</body>
</html>
