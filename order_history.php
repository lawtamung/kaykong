<?php 
session_start();

if (!isset($_SESSION['UserID']) || $_SESSION['UserType'] !== 'seller') {
    header('Location: login.php');
    exit;
}

include 'db.php';  // รวมการเชื่อมต่อฐานข้อมูล
include 'navbarseller.php';  // รวม navbar เข้ามา

// กำหนดจำนวนคำสั่งซื้อที่จะแสดงต่อหน้า
$items_per_page = 10;

// คำนวณหน้าปัจจุบันจาก URL
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $items_per_page;

// ตรวจสอบว่าได้ส่งค่า product_id มาหรือไม่
if (isset($_GET['product_id'])) {

    $product_id = $_GET['product_id'];

    // กรองข้อมูลตามสถานะ
    $status_filter = isset($_GET['status_filter']) ? $_GET['status_filter'] : '';
    $filter_condition = $status_filter ? "AND o.Status = ?" : "";

    // ดึงข้อมูลคำสั่งซื้อที่เกี่ยวข้องกับสินค้า และเรียงตามหมายเลขคำสั่งซื้อจากน้อยไปมาก
    $sql = "SELECT o.OrderID, o.OrderDate, o.Status, od.Quantity, od.Price, p.ProductName, u.UserID as BuyerID, u.FullName as BuyerName, o.PaymentSlip, o.AdminPaymentSlip
    FROM Orders o
    JOIN OrderDetails od ON o.OrderID = od.OrderID
    JOIN Products p ON od.ProductID = p.ProductID
    JOIN Users u ON o.BuyerID = u.UserID
    WHERE p.SellerID = ? $filter_condition
    ORDER BY o.OrderID DESC  
    LIMIT ?, ?";


    $stmt = $conn->prepare($sql);
    if ($status_filter) {
        $stmt->bind_param('isii', $product_id, $status_filter, $offset, $items_per_page);
    } else {
        $stmt->bind_param('iii', $product_id, $offset, $items_per_page);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $product_query = "SELECT ProductName FROM Products WHERE ProductID = ?";
    $product_stmt = $conn->prepare($product_query);
    $product_stmt->bind_param('i', $product_id);
    $product_stmt->execute();
    $product_result = $product_stmt->get_result();

    $product_name = "";
    if ($product_result->num_rows > 0) {
        $product = $product_result->fetch_assoc();
        $product_name = $product['ProductName'];
    }

} else {
    // ถ้าไม่มีการเลือกสินค้าที่เจาะจง ให้ดึงคำสั่งซื้อทั้งหมดของผู้ขาย
    $seller_id = $_SESSION['UserID'];
    $status_filter = isset($_GET['status_filter']) ? $_GET['status_filter'] : '';
    $filter_condition = $status_filter ? "AND o.Status = ?" : "";

    // ดึงข้อมูลคำสั่งซื้อทั้งหมดของผู้ขาย
    $sql = "SELECT o.OrderID, o.OrderDate, o.Status, od.Quantity, od.Price, p.ProductName, u.UserID as BuyerID, u.FullName as BuyerName, o.PaymentSlip, o.AdminPaymentSlip
        FROM Orders o
        JOIN OrderDetails od ON o.OrderID = od.OrderID
        JOIN Products p ON od.ProductID = p.ProductID
        JOIN Users u ON o.BuyerID = u.UserID
        WHERE p.SellerID = ? $filter_condition
        ORDER BY o.OrderID DESC  
        LIMIT ?, ?";

    
    $stmt = $conn->prepare($sql);
    if ($status_filter) {
        $stmt->bind_param('isii', $seller_id, $status_filter, $offset, $items_per_page);
    } else {
        $stmt->bind_param('iii', $seller_id, $offset, $items_per_page);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $product_name = "ทั้งหมด";  // กำหนดชื่อว่า "ทั้งหมด" เมื่อไม่ได้เลือกสินค้าจำเพาะ
}

// คำนวณจำนวนหน้า
$count_sql = "SELECT COUNT(*) AS total FROM Orders o
              JOIN OrderDetails od ON o.OrderID = od.OrderID
              JOIN Products p ON od.ProductID = p.ProductID
              WHERE p.SellerID = ? $filter_condition";
$count_stmt = $conn->prepare($count_sql);
if ($status_filter) {
    $count_stmt->bind_param('is', $seller_id, $status_filter);
} else {
    $count_stmt->bind_param('i', $seller_id);
}
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_orders = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_orders / $items_per_page);

// ลบคำสั่งซื้อ
if (isset($_GET['delete_order_id'])) {
    $order_id_to_delete = $_GET['delete_order_id'];
    $delete_sql = "DELETE FROM Orders WHERE OrderID = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param('i', $order_id_to_delete);

    if ($delete_stmt->execute()) {
        echo "<script>alert('คำสั่งซื้อลบสำเร็จ!'); window.location.href = 'order_history.php';</script>";
    } else {
        echo "<script>alert('เกิดข้อผิดพลาดในการลบคำสั่งซื้อ');</script>";
    }
}

// แก้ไขสถานะคำสั่งซื้อ
if (isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['status'];

    $update_sql = "UPDATE Orders SET Status = ? WHERE OrderID = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param('si', $new_status, $order_id);

    if ($update_stmt->execute()) {
        echo "<script>alert('สถานะคำสั่งซื้ออัปเดตสำเร็จ!'); window.location.href = 'order_history.php';</script>";
    } else {
        echo "<script>alert('เกิดข้อผิดพลาดในการอัปเดตสถานะ');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>คำสั่งซื้อของสินค้า: <?php echo $product_name; ?></title>

    <link href="https://cdn.jsdelivr.net/npm/lightbox2@2.11.3/dist/css/lightbox.min.css" rel="stylesheet">

    <style>
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f4f4f4;
        }
        .action-btn {
            padding: 5px 10px;
            background-color: #f44336;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 1rem;
        }
    </style>
</head>
<body>

<h1>คำสั่งซื้อของสินค้า: <?php echo $product_name; ?></h1>

<form method="GET" action="">
    <label for="status_filter">เลือกสถานะ:</label>
    <select name="status_filter">
        <option value="">ทั้งหมด</option>
        <option value="Pending" <?php echo isset($_GET['status_filter']) && $_GET['status_filter'] == 'Pending' ? 'selected' : ''; ?>>รอการชำระเงิน</option>
        <option value="Completed" <?php echo isset($_GET['status_filter']) && $_GET['status_filter'] == 'Completed' ? 'selected' : ''; ?>>สำเร็จ</option>
        <option value="Shipping" <?php echo isset($_GET['status_filter']) && $_GET['status_filter'] == 'Shipping' ? 'selected' : ''; ?>>กำลังจัดส่ง</option>
        <option value="Cancelled" <?php echo isset($_GET['status_filter']) && $_GET['status_filter'] == 'Cancelled' ? 'selected' : ''; ?>>ยกเลิก</option>
    </select>
    <button type="submit">กรอง</button>
</form>

<?php
if ($result->num_rows > 0) {
    echo '<table>';
    echo '<thead>';
    echo '<tr>';
    echo '<th>คำสั่งซื้อ #</th>';
    echo '<th>วันที่สั่งซื้อ</th>';
    echo '<th>สถานะ</th>';
    echo '<th>ชื่อผู้ซื้อ</th>';
    echo '<th>ID ผู้ซื้อ</th>';
    echo '<th>ชื่อสินค้า</th>';
    echo '<th>จำนวน</th>';
    echo '<th>ราคา</th>';
    echo '<th>สลิปการชำระเงิน (ผู้ซื้อ)</th>';
    echo '<th>สลิปการชำระเงิน (แอดมิน)</th>';
    echo '<th>การกระทำ</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    while ($order = $result->fetch_assoc()) {
        echo '<tr>';
        echo '<td>' . $order['OrderID'] . '</td>';
        echo '<td>' . date('d/m/Y', strtotime($order['OrderDate'])) . '</td>';
        echo '<td>';
        echo '<form method="POST" action="">';
        echo '<input type="hidden" name="order_id" value="' . $order['OrderID'] . '">';
        echo '<select name="status">';
        echo '<option value="Pending" ' . ($order['Status'] == 'Pending' ? 'selected' : '') . '>รอการชำระเงิน</option>';
        echo '<option value="Completed" ' . ($order['Status'] == 'Completed' ? 'selected' : '') . '>สำเร็จ</option>';
        echo '<option value="Shipping" ' . ($order['Status'] == 'Shipping' ? 'selected' : '') . '>กำลังจัดส่ง</option>';
        echo '<option value="Cancelled" ' . ($order['Status'] == 'Cancelled' ? 'selected' : '') . '>ยกเลิก</option>';
        echo '</select>';
        echo '<button type="submit" name="update_status">อัปเดตสถานะ</button>';
        echo '</form>';
        echo '</td>';
        echo '<td>' . $order['BuyerName'] . '</td>';
        echo '<td>' . $order['BuyerID'] . '</td>';
        echo '<td>' . $order['ProductName'] . '</td>';
        echo '<td>' . $order['Quantity'] . '</td>';
        echo '<td>฿' . number_format($order['Price'], 2) . '</td>';
        
        // แสดง PaymentSlip
        if (!empty($order['PaymentSlip'])) {
            // อัปเดตเส้นทางไปยังโฟลเดอร์ uploads/slips_admin
            $payment_slip_path = 'uploads/slips/' . basename($order['PaymentSlip']);
            echo '<td><a href="' . $payment_slip_path . '" data-lightbox="payment-slip" data-title="Payment Slip"><img src="' . $payment_slip_path . '" alt="Payment Slip" width="100"></a></td>';
        } else {
            echo '<td>ไม่มี</td>';
        }

        // แสดง AdminPaymentSlip
        if (!empty($order['AdminPaymentSlip'])) {
            // อัปเดตเส้นทางไปยังโฟลเดอร์ uploads/slips_admin
            $admin_payment_slip_path = 'uploads/slips_admin/' . basename($order['AdminPaymentSlip']);
            echo '<td><a href="' . $admin_payment_slip_path . '" data-lightbox="admin-payment-slip" data-title="Admin Payment Slip"><img src="' . $admin_payment_slip_path . '" alt="Admin Payment Slip" width="100"></a></td>';
        } else {
            echo '<td>ไม่มี</td>';
        }

        echo '<td>';
        echo '<a href="?delete_order_id=' . $order['OrderID'] . '" class="action-btn" onclick="return confirm(\'คุณแน่ใจหรือไม่ที่จะลบคำสั่งซื้อนี้?\')">ลบ</a>';
        echo '</td>';
        echo '</tr>';
    }
    echo '</tbody>';
    echo '</table>';
} else {
    echo '<p>ยังไม่มีคำสั่งซื้อสำหรับสินค้านี้</p>';
}

// การแสดงลิงก์แบ่งหน้า
if ($total_pages > 1) {
    echo '<div class="pagination">';
    for ($i = 1; $i <= $total_pages; $i++) {
        echo '<a href="?page=' . $i . '&product_id=' . $product_id . '&status_filter=' . $status_filter . '">' . $i . '</a>';
    }
    echo '</div>';
}
?>

<script src="https://cdn.jsdelivr.net/npm/lightbox2@2.11.3/dist/js/lightbox.min.js"></script>
</body>
</html>
