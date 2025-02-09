<?php     
session_start();
if ($_SESSION['UserType'] !== 'buyer') {
    header('Location: login.php');
    exit;
}
include 'db.php';  // รวมการเชื่อมต่อฐานข้อมูล
include 'navbar.php';  // รวม navbar เข้ามา

// การค้นหาสินค้า
$search_query = "";
if (isset($_POST['search'])) {
    $search_query = $_POST['search'];
}

// ดึงข้อมูลสินค้าที่ตรงกับคำค้นหาจากฐานข้อมูล
$sql = "SELECT * FROM Products WHERE ProductName LIKE ? OR Description LIKE ?";
$stmt = $conn->prepare($sql);
$search_param = '%' . $search_query . '%';
$stmt->bind_param('ss', $search_param, $search_param);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buyer Panel</title>
    <style>
        .products {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: space-around;
        }
        .product {
            border: 1px solid #ccc;
            border-radius: 10px;
            padding: 10px;
            width: 250px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .product img {
            width: 100%;
            height: auto;
            max-height: 200px;
            object-fit: cover;
            border-radius: 10px;
            cursor: pointer;
        }
        .product h3 {
            font-size: 1.2rem;
            margin: 10px 0;
        }
        .product p {
            font-size: 1rem;
            margin: 5px 0;
        }
        .search-bar {
            position: absolute;
            top: 100px;
            right: 20px;
            z-index: 10;
        }
        .search-bar input {
            padding: 10px;
            width: 300px;
            font-size: 1rem;
            margin-right: 10px;
        }
        .search-bar button {
            padding: 10px;
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
            font-size: 1rem;
        }
        .search-bar button:hover {
            background-color: #45a049;
        }
        .rating {
            font-size: 20px;
            color: gold;
        }
    </style>
</head>
<body>
    <!-- Content for Buyer Panel -->
    <h1>ยินดีต้อนรับ, <?php echo $_SESSION['FullName']; ?>!</h1>
    <h2>สำรวจสินค้าของเรา</h2>

    <!-- ฟอร์มค้นหาสินค้า -->
    <div class="search-bar">
        <form method="POST" action="">
            <input type="text" name="search" placeholder="ค้นหาสินค้า..." value="<?php echo htmlspecialchars($search_query); ?>">
            <button type="submit">ค้นหา</button>
        </form>
    </div>

    <!-- แสดงสินค้าที่ตรงกับการค้นหา -->
    <div class="products">
        <?php
        if ($result->num_rows > 0) {
            // ลูปผ่านข้อมูลสินค้าและแสดง
            while ($product = $result->fetch_assoc()) {
                // ดึงรูปภาพจากตาราง ProductImages
                $product_id = $product['ProductID'];
                $sql_images = "SELECT * FROM ProductImages WHERE ProductID = ?";
                $stmt_images = $conn->prepare($sql_images);
                $stmt_images->bind_param("i", $product_id);
                $stmt_images->execute();
                $images_result = $stmt_images->get_result();
                
                // หากมีรูปภาพหลายรูปให้เอารูปแรกมาแสดง
                $first_image = 'uploads/product/default.jpg'; // ใช้รูปแรก หรือรูปดีฟอลต์หากไม่มี
                if ($images_result->num_rows > 0) {
                    $first_image_data = $images_result->fetch_assoc();
                    $first_image = $first_image_data['ImagePath'];
                }

                // คำนวณดาวเฉลี่ย
                $avg_rating_sql = "SELECT AVG(Rating) as avg_rating FROM Reviews WHERE ProductID = ?";
                $avg_rating_stmt = $conn->prepare($avg_rating_sql);
                $avg_rating_stmt->bind_param("i", $product['ProductID']);
                $avg_rating_stmt->execute();
                $avg_rating_result = $avg_rating_stmt->get_result();
                $avg_rating_data = $avg_rating_result->fetch_assoc();
                $average_rating = $avg_rating_data['avg_rating'] ?? 0;
                
                // แสดงข้อมูลสินค้า
                echo '<div class="product">';
                
                // แสดงรูปภาพพร้อมลิงก์ไปยังหน้าเกี่ยวกับสินค้า
                echo '<a href="product_detail.php?product_id=' . htmlspecialchars($product['ProductID']) . '">';
                echo '<img src="' . htmlspecialchars($first_image) . '" alt="' . htmlspecialchars($product['ProductName']) . '">';
                echo '</a>';

                // แสดงชื่อสินค้า ราคา และคะแนนดาวเฉลี่ย
                echo '<h3>' . htmlspecialchars($product['ProductName']) . '</h3>';
                echo '<p>ราคา: ฿' . htmlspecialchars($product['Price']) . '</p>';
                
                // แสดงดาวเฉลี่ย
                echo '<div class="rating">';
                for ($i = 0; $i < floor($average_rating); $i++) {
                    echo "★"; // ดาวเต็ม
                }
                for ($i = 0; $i < (5 - floor($average_rating)); $i++) {
                    echo "☆"; // ดาวว่าง
                }
                echo " (" . number_format($average_rating, 1) . ")";
                echo '</div>';

                // ปุ่มใส่ตะกร้า
                echo '<form method="POST" action="add_to_cart.php">';
                echo '<input type="hidden" name="product_id" value="' . htmlspecialchars($product['ProductID']) . '">';
                echo '<button type="submit">ใส่ตะกร้า</button>';
                echo '</form>';
                echo '</div>';
            }
        } else {
            echo '<p>ไม่พบสินค้าที่ตรงกับการค้นหา</p>';
        }
        ?>
    </div>
</body>
</html>
