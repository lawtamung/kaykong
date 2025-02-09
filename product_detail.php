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

    // คำนวณดาวเฉลี่ยจากรีวิว
    $avg_rating_sql = "SELECT AVG(Rating) as avg_rating FROM Reviews WHERE ProductID = ?";
    $avg_rating_stmt = $conn->prepare($avg_rating_sql);
    $avg_rating_stmt->bind_param("i", $product_id);
    $avg_rating_stmt->execute();
    $avg_rating_result = $avg_rating_stmt->get_result();
    $avg_rating_data = $avg_rating_result->fetch_assoc();

    // ตรวจสอบหากมีการคำนวณค่าเฉลี่ย
    $average_rating = 0;
    if ($avg_rating_data['avg_rating'] !== null) {
        $average_rating = round($avg_rating_data['avg_rating'], 1);  // ปัดทศนิยม 1 ตำแหน่ง
    }

    // ดึงข้อมูลรีวิวของสินค้า
    $reviews_sql = "SELECT * FROM Reviews WHERE ProductID = ?";
    $reviews_stmt = $conn->prepare($reviews_sql);
    $reviews_stmt->bind_param("i", $product_id);
    $reviews_stmt->execute();
    $reviews_result = $reviews_stmt->get_result();
} else {
    echo "ไม่พบสินค้านี้";
    exit;
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
            width: 100px;
            height: 200px;
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
        .rating {
            font-size: 20px;
            color: gold;
        }
        .review-section {
            margin-top: 40px;
        }
        .review-item {
            border-bottom: 1px solid #ddd;
            padding: 10px;
        }
        .review-item .rating {
            font-size: 18px;
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
        #reviews-section,
        #rating-section {
            display: none;  /* ซ่อนข้อมูลรีวิวและการให้คะแนนเริ่มต้น */
        }
        .rating-form select {
            font-size: 20px;
        }
    </style>
</head>
<body>

    <div class="product-detail">
        <h1><?php echo htmlspecialchars($product['ProductName']); ?></h1>

        <!-- แสดงหลายภาพ -->
        <?php
        $image_sql = "SELECT * FROM ProductImages WHERE ProductID = ?";
        $image_stmt = $conn->prepare($image_sql);
        $image_stmt->bind_param("i", $product_id);
        $image_stmt->execute();
        $image_result = $image_stmt->get_result();

        if ($image_result->num_rows > 0) {
            while ($image = $image_result->fetch_assoc()) {
                echo '<img class="product-image" src="' . htmlspecialchars($image['ImagePath']) . '" alt="Product Image">';
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

        <!-- แสดงคะแนนดาวเฉลี่ย -->
        <p>คะแนนรีวิวเฉลี่ย: 
            <?php
            for ($i = 0; $i < floor($average_rating); $i++) {
                echo "★"; // ดาวเต็ม
            }
            for ($i = 0; $i < (5 - floor($average_rating)); $i++) {
                echo "☆"; // ดาวว่าง
            }
            ?>
            (<?php echo number_format($average_rating, 1); ?>)
        </p>

        <!-- ปุ่มเพิ่มไปยังตะกร้า -->
        <form method="POST">
            <input type="hidden" name="product_id" value="<?php echo $product['ProductID']; ?>">
            <button type="submit" name="add_to_cart">เพิ่มไปยังตะกร้า</button>
        </form>

        <!-- ปุ่มการรีวิว -->
        <button id="toggle-reviews" onclick="toggleReviews()">แสดงรีวิวทั้งหมด</button>

        <!-- แสดงรีวิวทั้งหมด -->
        <div id="reviews-section">
            <?php while ($review = $reviews_result->fetch_assoc()): ?>
                <div class="review-item">
                    <div class="rating">
                        <?php for ($i = 0; $i < $review['Rating']; $i++): ?>
                            ★
                        <?php endfor; ?>
                    </div>
                    <p><?php echo htmlspecialchars($review['Comment']); ?></p>
                </div>
            <?php endwhile; ?>
        </div>

        <!-- ฟอร์มการให้คะแนน -->
        <button id="toggle-rating" onclick="toggleRating()">ให้คะแนนรีวิว</button>

        <div id="rating-section" class="rating-form">
            <form method="POST" action="add_review.php">
                <input type="hidden" name="product_id" value="<?php echo $product['ProductID']; ?>">
                <label for="rating">ให้คะแนน (1-5 ดาว):</label>
                <select name="rating" id="rating">
                    <option value="1">1</option>
                    <option value="2">2</option>
                    <option value="3">3</option>
                    <option value="4">4</option>
                    <option value="5">5</option>
                </select>
                <br><br>
                <label for="comment">ความคิดเห็น:</label>
                <textarea name="comment" id="comment" rows="4" cols="50"></textarea>
                <br><br>
                <button type="submit" name="submit_review">ส่งรีวิว</button>
            </form>
        </div>
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

        // ฟังก์ชัน toggle สำหรับการแสดง/ซ่อนรีวิว
        function toggleReviews() {
            var reviewsSection = document.getElementById("reviews-section");
            var toggleButton = document.getElementById("toggle-reviews");

            if (reviewsSection.style.display === "none") {
                reviewsSection.style.display = "block";
                toggleButton.innerHTML = "ซ่อนรีวิวทั้งหมด";
            } else {
                reviewsSection.style.display = "none";
                toggleButton.innerHTML = "แสดงรีวิวทั้งหมด";
            }
        }

        // ฟังก์ชัน toggle สำหรับการแสดง/ซ่อนฟอร์มให้คะแนน
        function toggleRating() {
            var ratingSection = document.getElementById("rating-section");
            var toggleButton = document.getElementById("toggle-rating");

            if (ratingSection.style.display === "none") {
                ratingSection.style.display = "block";
                toggleButton.innerHTML = "ซ่อนฟอร์มให้คะแนน";
            } else {
                ratingSection.style.display = "none";
                toggleButton.innerHTML = "ให้คะแนนรีวิว";
            }
        }
    </script>

</body>
</html>
