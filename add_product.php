<?php     
session_start();

// ตรวจสอบสิทธิ์การเข้าใช้งาน
if ($_SESSION['UserType'] !== 'seller') {
    header('Location: login.php');
    exit;
}

include 'db.php';  // รวมการเชื่อมต่อฐานข้อมูล
include 'navbarseller.php';  // รวม navbar

$seller_id = $_SESSION['UserID'];

// ถ้ามีการกด submit เพื่อเพิ่มสินค้า
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $product_name = $_POST['product_name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $category = $_POST['category'];

    // บันทึกข้อมูลสินค้าในตาราง Products
    $sql = "INSERT INTO Products (SellerID, ProductName, Description, Price, Stock, Category) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('issdis', $seller_id, $product_name, $description, $price, $stock, $category);

    if ($stmt->execute()) {
        $product_id = $stmt->insert_id; // ได้ค่า ProductID ของสินค้าที่เพิ่มใหม่
        
        // อัปโหลดและบันทึกภาพ
        if (isset($_FILES['images']) && count($_FILES['images']['name']) > 0) {
            $upload_dir = 'uploads/product/'; // โฟลเดอร์เก็บไฟล์
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true); // สร้างโฟลเดอร์หากยังไม่มี
            }

            foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['images']['error'][$key] == 0) {
                    $file_ext = pathinfo($_FILES['images']['name'][$key], PATHINFO_EXTENSION);
                    $new_filename = $upload_dir . uniqid("product_") . "." . $file_ext;

                    if (move_uploaded_file($tmp_name, $new_filename)) {
                        // บันทึกลงตาราง ProductImages
                        $image_sql = "INSERT INTO ProductImages (ProductID, ImagePath) VALUES (?, ?)";
                        $image_stmt = $conn->prepare($image_sql);
                        $image_stmt->bind_param('is', $product_id, $new_filename);
                        $image_stmt->execute();
                    }
                }
            }
        }

        echo '<p style="color: green;">สินค้าได้ถูกเพิ่มเรียบร้อยแล้ว!</p>';
    } else {
        echo '<p style="color: red;">เกิดข้อผิดพลาดในการเพิ่มสินค้า!</p>';
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เพิ่มสินค้า</title>
</head>
<body>
    <h1>เพิ่มสินค้าใหม่</h1>
    
    <!-- ฟอร์มสำหรับเพิ่มสินค้า -->
    <form method="POST" action="add_product.php" enctype="multipart/form-data">
        <label for="product_name">ชื่อสินค้า:</label><br>
        <input type="text" id="product_name" name="product_name" required><br><br>

        <label for="description">รายละเอียดสินค้า:</label><br>
        <textarea id="description" name="description" required></textarea><br><br>

        <label for="price">ราคา:</label><br>
        <input type="number" id="price" name="price" step="0.01" required><br><br>

        <label for="stock">จำนวนสต็อก:</label><br>
        <input type="number" id="stock" name="stock" required><br><br>

        <label for="category">หมวดหมู่:</label><br>
        <input type="text" id="category" name="category"><br><br>

        <label for="images">รูปภาพสินค้า (อัปโหลดได้หลายรูป):</label><br>
        <input type="file" id="images" name="images[]" accept="image/*" multiple><br><br>

        <button type="submit" name="submit">เพิ่มสินค้า</button>
    </form>
</body>
</html>
