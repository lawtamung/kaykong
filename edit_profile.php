<?php   
session_start();

if ($_SESSION['UserType'] !== 'buyer') {
    header('Location: login.php');
    exit;
}

include 'db.php';  // รวมการเชื่อมต่อฐานข้อมูล
include 'navbar.php';  // รวม navbar เข้ามา

// ดึงข้อมูลผู้ใช้ปัจจุบัน
$userId = $_SESSION['UserID'];

$sql = "SELECT * FROM Users WHERE UserID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// ดึงข้อมูลที่อยู่จากตาราง UserAddresses
$sqlAddresses = "SELECT * FROM UserAddresses WHERE UserID = ?";
$stmtAddresses = $conn->prepare($sqlAddresses);
$stmtAddresses->bind_param('i', $userId);
$stmtAddresses->execute();
$resultAddresses = $stmtAddresses->get_result();

// ดึงข้อมูลจังหวัดจากฐานข้อมูล
$provinces_sql = "SELECT * FROM Provinces"; // สมมุติว่ามีตาราง Provinces
$provinces_result = $conn->query($provinces_sql);

// ตรวจสอบการอัปเดตข้อมูล
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // รับข้อมูลที่อัปเดตจากฟอร์ม
    $fullName = $_POST['fullName'];
    $phoneNumber = $_POST['phoneNumber'];

    // แยกข้อมูลที่อยู่ที่กรอกมา
    $houseNumber = $_POST['houseNumber'];
    $soi = $_POST['soi'];
    $road = $_POST['road'];
    $subdistrict = $_POST['subdistrict'];
    $district = $_POST['district'];
    $province = $_POST['province'];
    $postalCode = $_POST['postalCode'];  // รหัสไปรษณีย์

    // สร้างที่อยู่ทั้งหมดโดยการนำข้อมูลที่กรอกมารวมกัน
    $fullAddress = "บ้านเลขที่ " . $houseNumber . ", ซอย " . $soi . ", ถนน " . $road . ", ตำบล " . $subdistrict . ", อำเภอ " . $district . ", จังหวัด " . $province . ", รหัสไปรษณีย์ " . $postalCode;

    // อัปเดตข้อมูลในตาราง Users
    $sqlUpdate = "UPDATE Users SET FullName = ?, PhoneNumber = ?, Address = ? WHERE UserID = ?";
    $stmtUpdate = $conn->prepare($sqlUpdate);
    $stmtUpdate->bind_param('sssi', $fullName, $phoneNumber, $fullAddress, $userId);

    if ($stmtUpdate->execute()) {
        echo "อัปเดตโปรไฟล์สำเร็จ!";
        // เปลี่ยนเส้นทางกลับไปที่หน้าโปรไฟล์
        header('Location: profile.php');
        exit;
    } else {
        echo "เกิดข้อผิดพลาดในการอัปเดตโปรไฟล์.";
    }
}

// ลบที่อยู่
if (isset($_GET['deleteAddressId'])) {
    $addressId = $_GET['deleteAddressId'];
    $sqlDelete = "DELETE FROM UserAddresses WHERE AddressID = ?";
    $stmtDelete = $conn->prepare($sqlDelete);
    $stmtDelete->bind_param('i', $addressId);
    $stmtDelete->execute();
    header('Location: profile.php');  // รีเฟรชหน้าโปรไฟล์หลังจากลบที่อยู่
    exit;
}

?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขโปรไฟล์</title>
</head>

<body>
    <h1>แก้ไขโปรไฟล์</h1>

    <form method="POST" action="">
        <label for="fullName">ชื่อเต็ม:</label>
        <input type="text" id="fullName" name="fullName" value="<?php echo $user['FullName']; ?>" required><br><br>

        <label for="phoneNumber">เบอร์โทรศัพท์:</label>
        <input type="text" id="phoneNumber" name="phoneNumber" value="<?php echo $user['PhoneNumber']; ?>" required><br><br>

        <h3>ที่อยู่หลัก:</h3>
        <label for="houseNumber">บ้านเลขที่:</label>
        <input type="text" id="houseNumber" name="houseNumber" required><br><br>

        <label for="soi">ซอย:</label>
        <input type="text" id="soi" name="soi"><br><br>

        <label for="road">ถนน:</label>
        <input type="text" id="road" name="road"><br><br>

        <label for="subdistrict">ตำบล:</label>
        <input type="text" id="subdistrict" name="subdistrict" required><br><br>

        <label for="district">อำเภอ:</label>
        <input type="text" id="district" name="district" required><br><br>

        <label for="province">จังหวัด:</label>
        <select name="province" id="province" required>
            <option value="">เลือกจังหวัด</option>
            <?php while ($province = $provinces_result->fetch_assoc()) { ?>
                <option value="<?= $province['ProvinceName'] ?>"><?= $province['ProvinceName'] ?></option>
            <?php } ?>
        </select><br><br>

        <label for="postalCode">รหัสไปรษณีย์:</label>
        <input type="text" id="postalCode" name="postalCode" required><br><br>

        <button type="submit">บันทึก</button>
    </form>

    <h3>ที่อยู่ที่มีอยู่:</h3>
    <?php if ($resultAddresses->num_rows > 0): ?>
        <ul>
        <?php while ($address = $resultAddresses->fetch_assoc()): ?>
            <li>
                <?php echo htmlspecialchars($address['Address']); ?>
                <a href="?deleteAddressId=<?php echo $address['AddressID']; ?>" onclick="return confirm('คุณต้องการลบที่อยู่นี้หรือไม่?');">ลบ</a>
            </li>
        <?php endwhile; ?>
        </ul>
    <?php else: ?>
        <p>ไม่มีที่อยู่ที่บันทึกไว้.</p>
    <?php endif; ?>

</body>

</html>
