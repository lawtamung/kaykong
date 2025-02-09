<?php 
session_start();
if ($_SESSION['UserType'] !== 'seller') {
    header('Location: login.php');
    exit;
}

include 'db.php';  
include 'navbarseller.php';
$seller_id = $_SESSION['UserID'];

$product_id = $_GET['product_id'];

// ดึงข้อมูลสินค้าจากฐานข้อมูล
$sql = "SELECT * FROM Products WHERE ProductID = ? AND SellerID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $product_id, $seller_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

if (!$product) {
    echo "ไม่พบสินค้านี้.";
    exit;
}

// ดึงข้อมูลรูปภาพสินค้า
$image_sql = "SELECT ImageID, ImagePath FROM ProductImages WHERE ProductID = ?";
$image_stmt = $conn->prepare($image_sql);
$image_stmt->bind_param('i', $product_id);
$image_stmt->execute();
$image_result = $image_stmt->get_result();
$images = $image_result->fetch_all(MYSQLI_ASSOC);

// อัปเดตสินค้า
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $product_name = $_POST['product_name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $category = $_POST['category'];

    // อัปเดตข้อมูลสินค้า
    $update_sql = "UPDATE Products SET ProductName = ?, Description = ?, Price = ?, Stock = ?, Category = ? WHERE ProductID = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param('ssdiis', $product_name, $description, $price, $stock, $category, $product_id);

    if ($update_stmt->execute()) {
        echo "สินค้าถูกอัปเดตเรียบร้อยแล้ว!";
        header("Location: seller.php");
        exit;
    } else {
        echo "เกิดข้อผิดพลาดในการอัปเดตสินค้า: " . $update_stmt->error;
    }
}

// อัปโหลดรูปภาพใหม่
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['images'])) {
    $upload_dir = 'uploads/product/'; // โฟลเดอร์เก็บไฟล์
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true); // สร้างโฟลเดอร์หากยังไม่มี
    }

    foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
        if ($_FILES['images']['error'][$key] == 0) {
            $file_ext = pathinfo($_FILES['images']['name'][$key], PATHINFO_EXTENSION);
            $new_filename = $upload_dir . uniqid("product_") . "." . $file_ext;

            if (move_uploaded_file($tmp_name, $new_filename)) {
                // บันทึกลงฐานข้อมูล
                $insert_sql = "INSERT INTO ProductImages (ProductID, ImagePath) VALUES (?, ?)";
                $insert_stmt = $conn->prepare($insert_sql);
                $insert_stmt->bind_param('is', $product_id, $new_filename);
                $insert_stmt->execute();
            }
        }
    }
    header("Location: edit_product.php?product_id=" . $product_id);
    exit;
}

// ลบรูปภาพที่ถูกเลือก
if (isset($_POST['delete_image'])) {
    $image_id = $_POST['delete_image'];

    // ดึงข้อมูลรูปภาพจากฐานข้อมูล
    $delete_image_sql = "SELECT ImagePath FROM ProductImages WHERE ImageID = ?";
    $delete_image_stmt = $conn->prepare($delete_image_sql);
    $delete_image_stmt->bind_param('i', $image_id);
    $delete_image_stmt->execute();
    $image_result = $delete_image_stmt->get_result();

    if ($image_result->num_rows > 0) {
        $image = $image_result->fetch_assoc();
        $image_path = $image['ImagePath'];

        // ลบไฟล์รูปภาพจาก server
        if (file_exists($image_path)) {
            unlink($image_path); // ลบไฟล์
        }

        // ลบข้อมูลจากฐานข้อมูล
        $delete_image_sql = "DELETE FROM ProductImages WHERE ImageID = ?";
        $delete_image_stmt = $conn->prepare($delete_image_sql);
        $delete_image_stmt->bind_param('i', $image_id);
        $delete_image_stmt->execute();

        echo "<script>alert('ลบรูปภาพเรียบร้อยแล้ว!'); window.location.href='edit_product.php?product_id=" . $product_id . "';</script>";
        exit;
    }
}

// ลบสินค้าและลบรูปภาพทั้งหมด
if (isset($_POST['delete'])) {
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
    <title>แก้ไขสินค้า</title>
</head>
<body>
    <h1>แก้ไขสินค้า</h1>
    <form action="edit_product.php?product_id=<?= $product_id ?>" method="POST">
        <label>ชื่อสินค้า:</label><br>
        <input type="text" name="product_name" value="<?= htmlspecialchars($product['ProductName']) ?>" required><br><br>

        <label>คำอธิบายสินค้า:</label><br>
        <textarea name="description" required><?= htmlspecialchars($product['Description']) ?></textarea><br><br>

        <label>ราคา:</label><br>
        <input type="number" name="price" value="<?= htmlspecialchars($product['Price']) ?>" required><br><br>

        <label>จำนวนสินค้า:</label><br>
        <input type="number" name="stock" value="<?= htmlspecialchars($product['Stock']) ?>" required><br><br>

        <label>หมวดหมู่:</label><br>
        <input type="text" name="category" value="<?= htmlspecialchars($product['Category']) ?>" required><br><br>

        <button type="submit" name="update">อัปเดตสินค้า</button>
    </form>

    <h2>อัปโหลดรูปภาพใหม่</h2>
    <form action="edit_product.php?product_id=<?= $product_id ?>" method="POST" enctype="multipart/form-data">
        <input type="file" name="images[]" accept="image/*" multiple><br><br>
        <button type="submit">อัปโหลดรูป</button>
    </form>

    <h2>รูปภาพสินค้า</h2>
    <?php foreach ($images as $img) { ?>
        <img src="<?= htmlspecialchars($img['ImagePath']) ?>" style="width: 150px; height: auto;">
        <form method="POST" onsubmit="return confirm('คุณต้องการลบรูปนี้จริงๆ หรือไม่?');">
            <input type="hidden" name="delete_image" value="<?= $img['ImageID'] ?>">
            <button type="submit">ลบรูปนี้</button>
        </form>
        <br>
    <?php } ?>

    <h2>ลบสินค้า</h2>
    <form method="POST" onsubmit="return confirm('คุณต้องการลบสินค้านี้จริงๆ หรือไม่?');">
        <button type="submit" name="delete">ลบสินค้า</button>
    </form>
</body>
</html>
