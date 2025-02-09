<?php 
session_start();
include 'db.php';
include 'navbaradmin.php';
if ($_SESSION['UserType'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// ดึงข้อมูลผู้ใช้ทั้งหมดจากฐานข้อมูล
$sql = "SELECT * FROM Users";
$stmt = $conn->prepare($sql);
$stmt->execute();
$users = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการผู้ใช้ - Admin</title>
    <style>
        .banned { color: red; font-weight: bold; }
        .active { color: green; font-weight: bold; }
    </style>
</head>
<body>

    <h1>จัดการผู้ใช้</h1>

    <table border="1" cellpadding="10">
        <thead>
            <tr>
                <th>ลำดับ</th>
                <th>ชื่อผู้ใช้</th>
                <th>อีเมล</th>
                <th>ประเภทผู้ใช้</th>
                <th>สถานะ</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $index = 1;
            while ($user = $users->fetch_assoc()) {
                $status = $user['IsBanned'] ? "<span class='banned'>ถูกแบน</span>" : "<span class='active'>ปกติ</span>";
                
                echo "<tr>
                        <td>{$index}</td>
                        <td><a href='view_user.php?user_id={$user['UserID']}'>{$user['Username']}</a></td>
                        <td>{$user['Email']}</td>
                        <td>{$user['UserType']}</td>
                        <td>{$status}</td>
                    </tr>";
                $index++;
            }
            ?>
        </tbody>
    </table>

</body>
</html>
