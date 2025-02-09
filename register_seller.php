<?php 
session_start();
include 'db.php';  // เชื่อมต่อฐานข้อมูล

// เช็คว่าได้ส่งฟอร์มมาหรือไม่
if (isset($_POST['submit'])) {
    // รับค่าจากฟอร์ม
    $fullName = $_POST['fullName'];
    $address = $_POST['address'];
    $email = $_POST['email'];
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $phoneNumber = $_POST['phoneNumber'];
    $accountNumber = $_POST['accountNumber'];  // เลขบัญชีธนาคาร
    $bankName = $_POST['bankName'];  // ชื่อธนาคาร

    // ตรวจสอบว่า username ซ้ำหรือไม่
    $sqlUsername = "SELECT * FROM Users WHERE Username = ?";
    if ($stmt = $conn->prepare($sqlUsername)) {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            echo "ชื่อผู้ใช้นี้มีการใช้งานแล้ว!";
            exit;
        }
    }

    // ตรวจสอบว่า email ซ้ำหรือไม่
    $sqlEmail = "SELECT * FROM Users WHERE Email = ?";
    if ($stmt = $conn->prepare($sqlEmail)) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            echo "อีเมลนี้มีการใช้งานแล้ว!";
            exit;
        }
    }

    // บันทึกข้อมูลผู้ใช้ลงในฐานข้อมูล
    $sql = "INSERT INTO Users (Username, Password, Email, FullName, PhoneNumber, Address, AccountNumber, BankName, UserType) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'seller')";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ssssssss", $username, $password, $email, $fullName, $phoneNumber, $address, $accountNumber, $bankName);
        $stmt->execute();
        echo "สมัครสมาชิกสำเร็จ!";
    } else {
        echo "เกิดข้อผิดพลาดในการบันทึกข้อมูล";
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สมัครสมาชิกสำหรับผู้ขาย</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 130vh;
            margin: 0;
            background-color: #f4f4f4;
        }
        .container {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px; /* เพิ่มขนาดฟอร์มให้มีขนาดพอดี */
            text-align: center;
        }
        .container h1 {
            margin-bottom: 20px;
            font-size: 24px;
            color: #333;
        }
        .container input, .container textarea, .container select {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
        }
        .container button {
            width: 100%;
            padding: 10px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
        }
        .container button:hover {
            background-color: #45a049;
        }
        .links {
            margin-top: 15px;
        }
        .links a {
            color: #4CAF50;
            text-decoration: none;
            font-size: 14px;
        }
        .links a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <div class="container">
        <h1>สมัครสมาชิกสำหรับผู้ขาย</h1>

        <!-- ฟอร์มกรอกข้อมูล -->
        <form action="register_seller.php" method="POST">
            <label for="fullName">ชื่อเต็ม:</label>
            <input type="text" id="fullName" name="fullName" required><br>

            <label for="address">ที่อยู่:</label>
            <textarea id="address" name="address" required></textarea><br>

            <label for="email">อีเมล:</label>
            <input type="email" id="email" name="email" required><br>

            <label for="username">ชื่อผู้ใช้:</label>
            <input type="text" id="username" name="username" required><br>

            <label for="password">รหัสผ่าน:</label>
            <input type="password" id="password" name="password" required><br>

            <label for="phoneNumber">เบอร์โทรศัพท์:</label>
            <input type="text" id="phoneNumber" name="phoneNumber" required><br>

            <label for="accountNumber">เลขที่บัญชีธนาคาร:</label>
            <input type="text" id="accountNumber" name="accountNumber" required><br>

            <label for="bankName">ชื่อธนาคาร:</label>
            <select id="bankName" name="bankName" required>
                <option value="KBANK">กสิกรไทย</option>
                <option value="SCB">ไทยพาณิชย์</option>
                <option value="BAY">กรุงศรี</option>
                <option value="BBL">กรุงเทพ</option>
                <!-- เพิ่มธนาคารอื่น ๆ ตามต้องการ -->
            </select><br>

            <button type="submit" name="submit">สมัครสมาชิก</button>
        </form>

        <!-- ปุ่มลิงก์ไปหน้าเข้าสู่ระบบ และสมัครบัญชีผู้ใช้ -->
        <div class="links">
            <p>มีบัญชีแล้ว? <a href="login.php">เข้าสู่ระบบที่นี่</a></p>
            <p>หากคุณต้องการสมัครเป็นผู้ซื้อ <a href="register.php">สมัครสมาชิกผู้ซื้อที่นี่</a></p>
        </div>
    </div>

</body>
</html>
