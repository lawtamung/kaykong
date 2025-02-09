<?php
session_start();

if ($_SESSION['UserType'] !== 'admin') {
    header('Location: login.php');
    exit;
}

include 'db.php';  // รวมการเชื่อมต่อฐานข้อมูล
include 'navbaradmin.php';
// รับ user_id จาก URL
$user_id = $_GET['user_id'] ?? null;

if (!$user_id) {
    echo "ไม่พบข้อมูลผู้ใช้";
    exit;
}

// ดึงข้อมูลผู้ใช้
$sql = "SELECT * FROM Users WHERE UserID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    echo "ไม่พบข้อมูลผู้ใช้นี้";
    exit;
}

// ตรวจสอบว่ามีการกดปุ่มลบหรือแบนผู้ใช้หรือไม่
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_user'])) {
        // 1. ลบรีวิวของผู้ใช้ก่อน
        $delete_reviews_sql = "DELETE FROM Reviews WHERE BuyerID = ?";
        $delete_reviews_stmt = $conn->prepare($delete_reviews_sql);
        $delete_reviews_stmt->bind_param('i', $user_id);
        $delete_reviews_stmt->execute();

        // 2. ลบผู้ใช้
        $delete_user_sql = "DELETE FROM Users WHERE UserID = ?";
        $delete_user_stmt = $conn->prepare($delete_user_sql);
        $delete_user_stmt->bind_param('i', $user_id);

        if ($delete_user_stmt->execute()) {
            echo "<script>alert('ลบผู้ใช้เรียบร้อยแล้ว'); window.location.href='admin.php';</script>";
            exit;
        } else {
            echo "<script>alert('เกิดข้อผิดพลาดในการลบผู้ใช้');</script>";
        }
    } elseif (isset($_POST['ban_user'])) {
        // แบน/ปลดแบนผู้ใช้
        $new_status = $user['IsBanned'] ? 0 : 1;
        $update_ban_sql = "UPDATE Users SET IsBanned = ? WHERE UserID = ?";
        $update_ban_stmt = $conn->prepare($update_ban_sql);
        $update_ban_stmt->bind_param('ii', $new_status, $user_id);

        if ($update_ban_stmt->execute()) {
            echo "<script>alert('เปลี่ยนสถานะผู้ใช้เรียบร้อยแล้ว'); window.location.href='view_user.php?user_id={$user_id}';</script>";
            exit;
        } else {
            echo "<script>alert('เกิดข้อผิดพลาดในการเปลี่ยนสถานะผู้ใช้');</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ข้อมูลผู้ใช้</title>
</head>
<body>

    <h1>ข้อมูลผู้ใช้</h1>
    <p><strong>ชื่อผู้ใช้:</strong> <?= htmlspecialchars($user['Username']) ?></p>
    <p><strong>อีเมล:</strong> <?= htmlspecialchars($user['Email']) ?></p>
    <p><strong>ประเภทผู้ใช้:</strong> <?= htmlspecialchars($user['UserType']) ?></p>
    <p><strong>สถานะ:</strong> <?= $user['IsBanned'] ? "<span style='color: red;'>ถูกแบน</span>" : "<span style='color: green;'>ปกติ</span>" ?></p>

    <!-- ปุ่มแบน / ปลดแบน -->
    <form action="" method="POST">
        <button type="submit" name="ban_user" style="background-color: orange; color: white;">
            <?= $user['IsBanned'] ? "ปลดแบนผู้ใช้" : "แบนผู้ใช้" ?>
        </button>
    </form>

    <!-- ปุ่มลบผู้ใช้ -->
    <form action="" method="POST" onsubmit="return confirm('คุณต้องการลบผู้ใช้นี้จริงๆ หรือไม่?');">
        <button type="submit" name="delete_user" style="background-color: red; color: white;">ลบผู้ใช้</button>
    </form>

</body>
</html>
