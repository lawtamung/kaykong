<?php
session_start();
if ($_SESSION['UserType'] !== 'buyer') {
    header('Location: login.php');
    exit;
}

include 'db.php';  // รวมการเชื่อมต่อฐานข้อมูล
include 'navbar.php';  // รวม navbar เข้ามา

// ตรวจสอบว่ามีการส่ง product_id มาหรือไม่
if (isset($_GET['product_id'])) {
    $product_id = intval($_GET['product_id']);
    
    // ดึงข้อมูลสินค้าจากฐานข้อมูล
    $sql = "SELECT * FROM Products WHERE ProductID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();

    if (!$product) {
        echo "ไม่พบสินค้านี้";
        exit;
    }
} else {
    echo "ไม่พบสินค้านี้";
    exit;
}

// ตรวจสอบว่าได้ทำการคลิกปุ่ม "เพิ่มไปยังตะกร้า" หรือไม่
if (isset($_POST['add_to_cart'])) {
    // ตรวจสอบว่า session มีข้อมูลผู้ใช้ (BuyerID) หรือไม่
    $buyer_id = $_SESSION['UserID']; // สมมติว่าได้เก็บ UserID ใน session

    $product_id = intval($_POST['product_id']);
    $quantity = 1; // ตั้งค่าปริมาณสินค้าเป็น 1 หากไม่มีการเลือกจำนวน

    // ตรวจสอบว่ามีสินค้าชิ้นนี้ในตะกร้าของผู้ใช้แล้วหรือไม่
    $checkCartSql = "SELECT * FROM Cart WHERE BuyerID = ? AND ProductID = ?";
    $checkCartStmt = $conn->prepare($checkCartSql);
    $checkCartStmt->bind_param("ii", $buyer_id, $product_id);
    $checkCartStmt->execute();
    $checkCartResult = $checkCartStmt->get_result();

    if ($checkCartResult->num_rows > 0) {
        // ถ้ามีสินค้าชิ้นนี้ในตะกร้าแล้ว ให้เพิ่มจำนวน
        $updateCartSql = "UPDATE Cart SET Quantity = Quantity + 1 WHERE BuyerID = ? AND ProductID = ?";
        $updateCartStmt = $conn->prepare($updateCartSql);
        $updateCartStmt->bind_param("ii", $buyer_id, $product_id);
        $updateCartStmt->execute();
    } else {
        // ถ้ายังไม่มีสินค้าในตะกร้า, เพิ่มสินค้าลงในตะกร้า
        $addToCartSql = "INSERT INTO Cart (BuyerID, ProductID, Quantity) VALUES (?, ?, ?)";
        $addToCartStmt = $conn->prepare($addToCartSql);
        $addToCartStmt->bind_param("iii", $buyer_id, $product_id, $quantity);
        $addToCartStmt->execute();
    }

    echo "<p>เพิ่มสินค้าลงในตะกร้าแล้ว!</p>";
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายละเอียดสินค้า</title>
    <style>
        .product-detail {
            width: 80%;
            margin: 20px auto;
            text-align: center;
        }

        .product-detail img {
            width: 300px;
            height: 300px;
            object-fit: cover;
            cursor: pointer;
            transition: transform 0.2s ease-in-out;
        }

        .product-detail img:hover {
            transform: scale(1.05);
        }

        .product-info {
            margin-top: 20px;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            max-width: 90%;
            max-height: 90%;
            object-fit: contain;
        }

        .close {
            position: absolute;
            top: 20px;
            right: 35px;
            color: #fff;
            font-size: 40px;
            font-weight: bold;
            transition: 0.3s;
        }

        .close:hover,
        .close:focus {
            color: #bbb;
            text-decoration: none;
            cursor: pointer;
        }
    </style>
</head>
<body>

    <div class="product-detail">
        <h1><?php echo htmlspecialchars($product['ProductName']); ?></h1>

        <!-- แสดงหลายภาพ -->
        <?php
        if (!empty($product['Image'])) {
            $images = explode(',', $product['Image']);
            foreach ($images as $image) {
                echo '<img class="product-image" src="' . htmlspecialchars($image) . '" alt="Product Image">';
            }
        } else {
            echo '<p>ไม่มีรูปภาพเพิ่มเติมสำหรับสินค้านี้</p>';
        }
        ?>

        <div class="product-info">
            <p><?php echo htmlspecialchars($product['Description']); ?></p>
            <p>ราคา: ฿<?php echo htmlspecialchars($product['Price']); ?></p>
            <p>สต็อก: <?php echo htmlspecialchars($product['Stock']); ?></p>
        </div>

        <!-- ปุ่มเพิ่มไปยังตะกร้า -->
        <form method="POST">
            <input type="hidden" name="product_id" value="<?php echo $product['ProductID']; ?>">
            <button type="submit" name="add_to_cart">เพิ่มไปยังตะกร้า</button>
        </form>
    </div>

    <!-- Modal สำหรับแสดงภาพขนาดใหญ่ -->
    <div id="myModal" class="modal">
        <span class="close" id="closeModal">&times;</span>
        <img class="modal-content" id="modalImage">
    </div>

    <script>
        var modal = document.getElementById("myModal");
        var imgs = document.querySelectorAll(".product-image");
        var modalImg = document.getElementById("modalImage");
        var closeBtn = document.getElementById("closeModal");

        imgs.forEach(function(img) {
            img.onclick = function() {
                modal.style.display = "block";
                modalImg.src = this.src;
            };
        });

        closeBtn.onclick = function() {
            modal.style.display = "none";
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>

</body>
</html>
