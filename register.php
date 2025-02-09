<?php
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email']; // อีเมล
    $firstName = $_POST['firstName']; // ชื่อ
    $lastName = $_POST['lastName']; // นามสกุล
    $phoneNumber = $_POST['phoneNumber']; // หมายเลขโทรศัพท์
    $password = $_POST['password'];
    $userType = 'buyer'; // กำหนดให้เป็น 'buyer' เท่านั้น
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    // รวมชื่อและนามสกุลเป็น FullName
    $fullName = $firstName . ' ' . $lastName;

    // ตรวจสอบว่าอีเมลหรือชื่อผู้ใช้ซ้ำในฐานข้อมูลหรือไม่
    $sql = "SELECT * FROM Users WHERE Username = ? OR Email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $username, $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo "<p style='color: red;'>ชื่อผู้ใช้หรืออีเมลนี้มีคนใช้แล้ว!</p>";
    } else {
        // บันทึกผู้ใช้ใหม่
        $sql = "INSERT INTO Users (Username, Email, FullName, PhoneNumber, Password, UserType) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssssss', $username, $email, $fullName, $phoneNumber, $passwordHash, $userType);
        if ($stmt->execute()) {
            // หลังจากสมัครเสร็จให้ไปที่หน้า login
            header('Location: login.php');
            exit;  // ทำการหยุดกระบวนการหลังจากรีไดเรค
        } else {
            echo "<p style='color: red;'>เกิดข้อผิดพลาด: " . $stmt->error . "</p>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สมัครสมาชิก</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background-color: #f4f4f4;
        }
        .register-container {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            width: 350px;
            text-align: center;
        }
        .register-container h1 {
            margin-bottom: 20px;
            font-size: 24px;
            color: #333;
        }
        .register-container input, .register-container select {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
        }
        .register-container button {
            width: 100%;
            padding: 10px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
        }
        .register-container button:hover {
            background-color: #45a049;
        }
        .register-container .links {
            margin-top: 15px;
        }
        .register-container .links a {
            color: #4CAF50;
            text-decoration: none;
            font-size: 14px;
        }
        .register-container .links a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <div class="register-container">
        <h1>สมัครสมาชิกใหม่</h1>
        <form method="POST" action="">
            <input type="text" name="username" placeholder="ชื่อผู้ใช้" required /><br><br>
            <input type="email" name="email" placeholder="อีเมล" required /><br><br>
            <input type="text" name="firstName" placeholder="ชื่อ" required /><br><br> <!-- ชื่อ -->
            <input type="text" name="lastName" placeholder="นามสกุล" required /><br><br> <!-- นามสกุล -->
            <input type="text" name="phoneNumber" placeholder="หมายเลขโทรศัพท์" required /><br><br> <!-- เบอร์โทร -->
            <input type="password" name="password" placeholder="รหัสผ่าน" required /><br><br>

            <!-- ไม่ต้องให้เลือก userType แค่กำหนดเป็น 'buyer' -->
            <input type="hidden" name="userType" value="buyer" />

            <button type="submit">สมัครสมาชิก</button>
        </form>

        <div class="links">
            <p>มีบัญชีแล้ว? <a href="login.php">เข้าสู่ระบบที่นี่</a></p>
        </div>
    </div>

</body>
</html>
