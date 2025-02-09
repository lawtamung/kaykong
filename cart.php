<?php
session_start();
if (!isset($_SESSION['UserID']) || $_SESSION['UserType'] !== 'buyer') {
    header('Location: login.php');
    exit();
}
include 'db.php';  // รวมการเชื่อมต่อฐานข้อมูล
include 'navbar.php';  // แสดง navbar ด้านบน

$buyerID = $_SESSION['UserID']; // รับค่า UserID ของผู้ใช้

// ดึงรายการสินค้าในตะกร้าจากฐานข้อมูล
$sql = "SELECT c.CartID, c.ProductID, c.Quantity, c.AddedAt, 
               p.ProductName, p.Price, p.Stock
        FROM Cart c
        INNER JOIN Products p ON c.ProductID = p.ProductID
        WHERE c.BuyerID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $buyerID);
$stmt->execute();
$result = $stmt->get_result();

// กดปุ่มลบสินค้าออกจากตะกร้า
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove'])) {
    $cartID = $_POST['cart_id'];

    $deleteSql = "DELETE FROM Cart WHERE CartID = ?";
    $deleteStmt = $conn->prepare($deleteSql);
    $deleteStmt->bind_param("i", $cartID);
    $deleteStmt->execute();

    header("Location: cart.php");
    exit();
}

// อัปเดตจำนวนสินค้าในตะกร้า
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $cartID = $_POST['cart_id'];
    $quantity = $_POST['quantity'];

    if ($quantity > 0) {
        $updateSql = "UPDATE Cart SET Quantity = ? WHERE CartID = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param("ii", $quantity, $cartID);
        $updateStmt->execute();
    }

    header("Location: cart.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตะกร้าสินค้า</title>
    <link rel="stylesheet" href="styles.css"> <!-- ใส่ CSS สำหรับการตกแต่ง -->
</head>
<body>
    <div class="container">
        <h1>ตะกร้าสินค้าของคุณ</h1>
        <table class="cart-table">
            <thead>
                <tr>
                    <th>ชื่อสินค้า</th>
                    <th>ราคา</th>
                    <th>จำนวน</th>
                    <th>ราคารวม</th>
                    <th>การจัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $totalAmount = 0;
                while ($row = $result->fetch_assoc()) {
                    $subtotal = $row['Price'] * $row['Quantity'];
                    $totalAmount += $subtotal;
                ?>
                <tr>
                    <td><?= htmlspecialchars($row['ProductName']); ?></td>
                    <td><?= number_format($row['Price'], 2); ?> ฿</td>
                    <td>
                        <form method="POST" action="cart.php">
                            <input type="hidden" name="cart_id" value="<?= $row['CartID']; ?>">
                            <input type="number" name="quantity" value="<?= $row['Quantity']; ?>" min="1" max="<?= $row['Stock']; ?>" class="quantity-input">
                            <button type="submit" name="update" class="btn-update">อัปเดต</button>
                        </form>
                    </td>
                    <td><?= number_format($subtotal, 2); ?> ฿</td>
                    <td>
                        <form method="POST" action="cart.php">
                            <input type="hidden" name="cart_id" value="<?= $row['CartID']; ?>">
                            <button type="submit" name="remove" class="btn-remove">ลบ</button>
                        </form>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
        <div class="cart-footer">
            <h2>ยอดรวม: <?= number_format($totalAmount, 2); ?> ฿</h2>
            <a href="checkout.php" class="btn-checkout">ชำระเงิน</a>
        </div>
    </div>
</body>
</html>
