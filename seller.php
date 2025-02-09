<?php     
session_start();
if ($_SESSION['UserType'] !== 'seller') {
    header('Location: login.php');
    exit;
}

include 'db.php';
include 'navbarseller.php';

// ดึงข้อมูลสินค้าของผู้ขาย
$seller_id = $_SESSION['UserID'];
$search_query = isset($_POST['search']) ? $_POST['search'] : "";

// ค้นหาสินค้า
$sql = "SELECT * FROM Products WHERE SellerID = ? AND (ProductName LIKE ? OR Description LIKE ?)";
$stmt = $conn->prepare($sql);
$search_param = '%' . $search_query . '%';
$stmt->bind_param('iss', $seller_id, $search_param, $search_param);
$stmt->execute();
$result = $stmt->get_result();

// ลบสินค้า
if (isset($_POST['delete'])) {
    $product_id = $_POST['product_id'];

    // ลบรูปภาพที่เกี่ยวข้อง
    $image_sql = "SELECT ImagePath FROM ProductImages WHERE ProductID = ?";
    $image_stmt = $conn->prepare($image_sql);
    $image_stmt->bind_param('i', $product_id);
    $image_stmt->execute();
    $image_result = $image_stmt->get_result();

    while ($image = $image_result->fetch_assoc()) {
        if (file_exists($image['ImagePath'])) {
            unlink($image['ImagePath']); // ลบไฟล์ภาพ
        }
    }

    // ลบรูปภาพออกจากฐานข้อมูล
    $delete_image_sql = "DELETE FROM ProductImages WHERE ProductID = ?";
    $delete_image_stmt = $conn->prepare($delete_image_sql);
    $delete_image_stmt->bind_param('i', $product_id);
    $delete_image_stmt->execute();

    // ลบสินค้า
    $delete_sql = "DELETE FROM Products WHERE ProductID = ? AND SellerID = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param('ii', $product_id, $seller_id);
    
    if ($delete_stmt->execute()) {
        echo "<script>alert('สินค้าถูกลบเรียบร้อยแล้ว!'); window.location.href='seller.php';</script>";
        exit;
    } else {
        echo "<script>alert('เกิดข้อผิดพลาดในการลบสินค้า!');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Panel</title>
    <style>
        .products {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: space-around;
            margin-top: 20px;
        }
        .product {
            border: 1px solid #ccc;
            border-radius: 10px;
            padding: 15px;
            width: 250px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            background-color: #fff;
            transition: transform 0.2s ease-in-out;
        }
        .product:hover {
            transform: scale(1.05);
        }
        .product img {
            width: 100%;
            height: auto;
            max-height: 200px;
            object-fit: cover;
            border-radius: 10px;
        }
        .product h3 {
            font-size: 1.2rem;
            margin: 10px 0;
        }
        .product p {
            font-size: 1rem;
            margin: 5px 0;
        }
        .product button {
            padding: 8px 12px;
            background-color: red;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.3s ease;
        }
        .product button:hover {
            background-color: #d42b2b;
        }
        .search-bar {
            position: absolute;
            top: 60px;
            right: 20px;
        }
        .search-bar input {
            padding: 10px;
            width: 200px;
            font-size: 1rem;
            margin-right: 10px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
        .search-bar button {
            padding: 10px;
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
            font-size: 1rem;
            border-radius: 5px;
        }
        .search-bar button:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <h1>ยินดีต้อนรับ, <?php echo $_SESSION['FullName']; ?>!</h1>
    <h2>จัดการสินค้าของคุณ</h2>

    <div class="search-bar">
        <form method="POST" action="">
            <input type="text" name="search" placeholder="ค้นหาสินค้า..." value="<?php echo htmlspecialchars($search_query); ?>">
            <button type="submit">ค้นหา</button>
        </form>
    </div>

    <div class="products">
        <?php
        if ($result->num_rows > 0) {
            while ($product = $result->fetch_assoc()) {
                $product_id = $product['ProductID'];

                // ดึงรูปภาพแรกของสินค้าจากตาราง ProductImages
                $image_sql = "SELECT ImagePath FROM ProductImages WHERE ProductID = ? ORDER BY ImageID ASC LIMIT 1";
                $image_stmt = $conn->prepare($image_sql);
                $image_stmt->bind_param('i', $product_id);
                $image_stmt->execute();
                $image_result = $image_stmt->get_result();
                $image = $image_result->fetch_assoc();
                
                // ใช้รูปแรก หรือใช้รูปดีฟอลต์หากไม่มี
                $first_image = $image ? $image['ImagePath'] : 'images/default.jpg';

                echo '<div class="product">';
                echo '<a href="edit_product.php?product_id=' . htmlspecialchars($product_id) . '">';
                echo '<img src="' . htmlspecialchars($first_image) . '" alt="' . htmlspecialchars($product['ProductName']) . '">';
                echo '</a>';
                echo '<h3>' . htmlspecialchars($product['ProductName']) . '</h3>';
                echo '<p>ราคา: ฿' . htmlspecialchars($product['Price']) . '</p>';
                echo '<p>จำนวนที่เหลือ: ' . htmlspecialchars($product['Stock']) . '</p>';
                
                // ปุ่มลบสินค้า
                echo '<form method="POST" action="" onsubmit="return confirm(\'คุณต้องการลบสินค้านี้จริงๆ หรือไม่?\');">';
                echo '<input type="hidden" name="product_id" value="' . htmlspecialchars($product_id) . '">';
                echo '<button type="submit" name="delete">ลบสินค้า</button>';
                echo '</form>';
                
                echo '</div>';
            }
        } else {
            echo '<p>คุณยังไม่มีสินค้าที่ลงขาย</p>';
        }
        ?>
    </div>
</body>
</html>
