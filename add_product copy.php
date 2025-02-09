<?php    

session_start();

if ($_SESSION['UserType'] !== 'seller') {

    header('Location: login.php');

    exit;

}

include 'db.php';  // รวมการเชื่อมต่อฐานข้อมูล

include 'navbarseller.php';  // รวม navbar เข้ามา



// ถ้ามีการกด submit เพื่อเพิ่มสินค้า

if (isset($_POST['submit'])) {

    $product_name = $_POST['product_name'];

    $description = $_POST['description'];

    $price = $_POST['price'];

    $stock = $_POST['stock'];

    $category = $_POST['category'];

    $seller_id = $_SESSION['UserID'];



    // จัดการอัปโหลดหลายไฟล์

    $images = [];

    if (isset($_FILES['images']) && count($_FILES['images']['name']) > 0) {

        for ($i = 0; $i < count($_FILES['images']['name']); $i++) {

            if ($_FILES['images']['error'][$i] == 0) {

                // สร้างชื่อไฟล์ใหม่เพื่อหลีกเลี่ยงการซ้ำ

                $image_name = 'uploads/product/' . basename($_FILES['images']['name'][$i]);

                move_uploaded_file($_FILES['images']['tmp_name'][$i], $image_name);

                $images[] = $image_name; // เก็บไฟล์ภาพที่อัปโหลด

            }

        }

    }



    // แปลง array ของรูปภาพเป็น string เพื่อเก็บในฐานข้อมูล (ใช้คั่นด้วยเครื่องหมาย comma)

    $image_list = implode(',', $images);



    // บันทึกข้อมูลสินค้าลงในฐานข้อมูล

    $sql = "INSERT INTO Products (SellerID, ProductName, Description, Price, Stock, Category, Image) 

            VALUES (?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);

    // ใช้ตัวแปรจากฟอร์ม

    $stmt->bind_param('issdiis', 

        $seller_id,  // SellerID

        $product_name,  // ProductName

        $description,  // Description

        $price,  // Price

        $stock,  // Stock

        $category,  // Category

        $image_list  // Images (หลายไฟล์, ใช้คั่นด้วย comma)

    );



    if ($stmt->execute()) {

        echo '<p>สินค้าได้ถูกเพิ่มเรียบร้อยแล้ว!</p>';

    } else {

        echo '<p>เกิดข้อผิดพลาดในการเพิ่มสินค้า!</p>';

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



        <label for="images">รูปภาพสินค้า:</label><br>

        <input type="file" id="images" name="images[]" accept="image/*" multiple><br><br>



        <button type="submit" name="submit">เพิ่มสินค้า</button>

    </form>

</body>

</html>

