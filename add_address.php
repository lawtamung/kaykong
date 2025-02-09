<?php  
session_start();
if ($_SESSION['UserType'] !== 'buyer') {
    header('Location: login.php');
    exit;
}

include 'db.php'; // เชื่อมต่อฐานข้อมูล

$buyer_id = $_SESSION['UserID']; // ผู้ใช้ที่เข้าสู่ระบบ (BuyerID)

// ดึงข้อมูลจังหวัดจากฐานข้อมูล
$provinces_sql = "SELECT * FROM Provinces"; // สมมุติว่ามีตาราง Provinces
$provinces_result = $conn->query($provinces_sql);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // รับค่าที่กรอกจากฟอร์ม
    $house_number = $_POST['house_number'];
    $village = $_POST['village'];
    $street = $_POST['street'];
    $province = $_POST['province'];
    $district = $_POST['district'];  // อำเภอให้กรอกเอง
    $sub_district = $_POST['sub_district'];
    $zipcode = $_POST['zipcode'];

    // ตรวจสอบการกรอกข้อมูล
    if (empty($house_number) || empty($village) || empty($street) || empty($province) || empty($district) || empty($sub_district) || empty($zipcode)) {
        echo "กรุณากรอกข้อมูลให้ครบถ้วน";
    } else {
        // รวมข้อมูลที่อยู่ทั้งหมดเป็นข้อความเดียว
        $full_address = $house_number . " " . $village . " " . $street . " " . $province . " " . $district . " " . $sub_district . " " . $zipcode;

        // บันทึกที่อยู่ใหม่ในฐานข้อมูล
        $insert_address_sql = "INSERT INTO UserAddresses (UserID, Address) VALUES (?, ?)";
        $insert_address_stmt = $conn->prepare($insert_address_sql);
        $insert_address_stmt->bind_param("is", $buyer_id, $full_address);
        if ($insert_address_stmt->execute()) {
            echo "ที่อยู่ถูกเพิ่มเรียบร้อยแล้ว!";
            header("Location: checkout.php"); // หลังจากเพิ่มที่อยู่เสร็จแล้วจะกลับไปที่หน้า checkout
            exit;
        } else {
            echo "เกิดข้อผิดพลาดในการเพิ่มที่อยู่: " . $insert_address_stmt->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เพิ่มที่อยู่ใหม่</title>
</head>
<body>

    <h1>เพิ่มที่อยู่ใหม่</h1>
    <form action="add_address.php" method="POST">
        <label for="house_number">เลขที่บ้าน:</label><br>
        <input type="text" name="house_number" id="house_number" required><br><br>

        <label for="village">หมู่บ้าน/ชุมชน:</label><br>
        <input type="text" name="village" id="village" required><br><br>

        <label for="street">ถนน:</label><br>
        <input type="text" name="street" id="street"><br><br>


        <label for="province">จังหวัด:</label><br>
        <select name="province" id="province" required>
            <option value="">เลือกจังหวัด</option>
            <?php while ($province = $provinces_result->fetch_assoc()) { ?>
                <option value="<?= $province['ProvinceName'] ?>"><?= $province['ProvinceName'] ?></option>
            <?php } ?>
        </select><br><br>

        <label for="district">อำเภอ:</label><br>
        <input type="text" name="district" id="district" required><br><br>

        <label for="sub_district">ตำบล:</label><br>
        <input type="text" name="sub_district" id="sub_district" required><br><br>

        <label for="zipcode">รหัสไปรษณีย์:</label><br>
        <input type="text" name="zipcode" id="zipcode" required><br><br>

        <button type="submit">เพิ่มที่อยู่</button>
    </form>

    <br>
    <a href="checkout.php">กลับไปที่หน้าชำระเงิน</a>

</body>
</html>
