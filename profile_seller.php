<?php 
session_start();
include 'db.php';  // เชื่อมต่อฐานข้อมูล
include 'navbarseller.php';
// ตรวจสอบว่าเป็นผู้ขายหรือไม่
if ($_SESSION['UserType'] !== 'seller') {
    header('Location: login.php');
    exit;
}

// ดึงข้อมูลผู้ขายจาก session
$userID = $_SESSION['UserID'];  // ใช้ UserID จาก session

// หากมีการส่งฟอร์มการแก้ไขข้อมูล
if (isset($_POST['update'])) {
    // รับค่าที่ส่งมาจากฟอร์ม
    $fullName = $_POST['fullName'];
    $phoneNumber = $_POST['phoneNumber'];
    $address = $_POST['address'];
    $accountNumber = $_POST['accountNumber'];
    $bankName = $_POST['bankName'];

    // อัพเดตข้อมูลในฐานข้อมูล
    $sql = "UPDATE Users 
            SET FullName = ?, PhoneNumber = ?, Address = ?, AccountNumber = ?, BankName = ? 
            WHERE UserID = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("sssssi", $fullName, $phoneNumber, $address, $accountNumber, $bankName, $userID);
        if ($stmt->execute()) {
            echo "ข้อมูลโปรไฟล์ถูกอัพเดตเรียบร้อย!";
        } else {
            echo "เกิดข้อผิดพลาดในการอัพเดตข้อมูล!";
        }
    }
}

// ดึงข้อมูลผู้ขายจากฐานข้อมูล
$sqlUser = "SELECT * FROM Users WHERE UserID = ?";
$stmtUser = $conn->prepare($sqlUser);
$stmtUser->bind_param("i", $userID);
$stmtUser->execute();
$userResult = $stmtUser->get_result();
$user = $userResult->fetch_assoc();

?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>โปรไฟล์ผู้ขาย</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .container { width: 50%; margin: auto; }
        h1 { text-align: center; }
        label { display: block; margin: 10px 0 5px; }
        input[type="text"], textarea, select { width: 100%; padding: 8px; margin-bottom: 10px; }
        button { padding: 10px 15px; background-color: #4CAF50; color: white; border: none; cursor: pointer; }
        button:hover { background-color: #45a049; }
        .readonly { background-color: #f0f0f0; }
    </style>
    <script>
        function enableEdit() {
            // เปลี่ยน input field ให้สามารถแก้ไขได้
            document.getElementById('fullName').removeAttribute('readonly');
            document.getElementById('phoneNumber').removeAttribute('readonly');
            document.getElementById('address').removeAttribute('readonly');
            document.getElementById('accountNumber').removeAttribute('readonly');
            document.getElementById('bankName').removeAttribute('disabled');
            
            // เปลี่ยนปุ่มเป็นปุ่มอัพเดต
            document.getElementById('updateButton').style.display = 'inline';
            document.getElementById('editButton').style.display = 'none';
        }
    </script>
</head>
<body>

<div class="container">
    <h1>โปรไฟล์ของคุณ</h1>

    <!-- ฟอร์มแสดงข้อมูลผู้ขาย -->
    <form action="profile_seller.php" method="POST">
        <label for="fullName">ชื่อเต็ม:</label>
        <input type="text" id="fullName" name="fullName" value="<?= $user['FullName'] ?>" readonly><br>

        <label for="phoneNumber">เบอร์โทรศัพท์:</label>
        <input type="text" id="phoneNumber" name="phoneNumber" value="<?= $user['PhoneNumber'] ?>" readonly><br>

        <label for="address">ที่อยู่:</label>
        <textarea id="address" name="address" readonly><?= $user['Address'] ?></textarea><br>

        <label for="accountNumber">เลขที่บัญชีธนาคาร:</label>
        <input type="text" id="accountNumber" name="accountNumber" value="<?= $user['AccountNumber'] ?>" readonly><br>

        <label for="bankName">ชื่อธนาคาร:</label>
        <select id="bankName" name="bankName" disabled>
            <option value="KBANK" <?= $user['BankName'] === 'KBANK' ? 'selected' : '' ?>>กสิกรไทย</option>
            <option value="SCB" <?= $user['BankName'] === 'SCB' ? 'selected' : '' ?>>ไทยพาณิชย์</option>
            <option value="BAY" <?= $user['BankName'] === 'BAY' ? 'selected' : '' ?>>กรุงศรี</option>
            <option value="BBL" <?= $user['BankName'] === 'BBL' ? 'selected' : '' ?>>กรุงเทพ</option>
        </select><br>

        <!-- ปุ่มแก้ไขข้อมูล -->
        <button type="button" id="editButton" onclick="enableEdit()">แก้ไขข้อมูล</button>
        
        <!-- ปุ่มอัพเดตข้อมูล -->
        <button type="submit" name="update" id="updateButton" style="display:none;">อัพเดตข้อมูล</button>
    </form>
</div>

</body>
</html>
