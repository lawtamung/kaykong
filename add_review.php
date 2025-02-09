<?php

session_start();

include 'db.php'; // รวมการเชื่อมต่อฐานข้อมูล



// ตรวจสอบว่าผู้ใช้ล็อกอินและมีสิทธิ์ในการรีวิวหรือไม่

if ($_SESSION['UserType'] !== 'buyer') {

    header('Location: login.php');

    exit;

}



// ตรวจสอบว่ามีการส่งข้อมูลรีวิวมาหรือไม่

if (isset($_POST['rating']) && isset($_POST['comment']) && isset($_POST['product_id'])) {

    $rating = intval($_POST['rating']); // รับคะแนนจากผู้ใช้

    $comment = htmlspecialchars($_POST['comment']); // รับความคิดเห็นจากผู้ใช้

    $product_id = intval($_POST['product_id']); // รับ product_id ที่ส่งมาจากฟอร์ม



    // ตรวจสอบให้แน่ใจว่าคะแนนอยู่ระหว่าง 1 ถึง 5

    if ($rating >= 1 && $rating <= 5) {

        // ตรวจสอบว่าใช้ล็อกอินอยู่หรือไม่

        $buyer_id = $_SESSION['UserID'];



        // เพิ่มรีวิวลงในฐานข้อมูล

        $sql = "INSERT INTO Reviews (ProductID, BuyerID, Rating, Comment, ReviewDate) 

                VALUES (?, ?, ?, ?, NOW())";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param("iiis", $product_id, $buyer_id, $rating, $comment);



        if ($stmt->execute()) {

            echo "<p>ขอบคุณสำหรับการรีวิว! ข้อมูลของคุณได้รับการบันทึกแล้ว.</p>";

        } else {

            echo "<p>เกิดข้อผิดพลาดในการส่งรีวิว โปรดลองอีกครั้ง.</p>";

        }

    } else {

        echo "<p>กรุณาเลือกคะแนนที่ถูกต้อง (1-5 ดาว).</p>";

    }

} else {

    echo "<p>ข้อมูลไม่ครบถ้วน กรุณากรอกข้อมูลให้ครบถ้วน.</p>";

}



// การกลับไปยังหน้ารายละเอียดสินค้า

header('Location: product_detail.php?product_id=' . $product_id);

exit;

?>
