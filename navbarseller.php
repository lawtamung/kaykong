

<?php

// navbar.php

?>

<nav class="navbar">

    <div style="color: white; font-size: 1.5rem; font-weight: bold;">

            <a href="seller.php" style="color: white; text-decoration: none;">kaykong</a>

        </div>

    <div class="navbar-links">

        <a href="seller.php" style="color: white; margin: 0 15px; text-decoration: none; font-size: 1rem;">หน้าแรก</a>

            <a href="add_product.php" style="color: white; margin: 0 15px; text-decoration: none; font-size: 1rem;">เพิ่มสินค้า</a>

            <a href="order_history.php" style="color: white; margin: 0 15px; text-decoration: none; font-size: 1rem;">ประวัติคำสั่งซื้อ</a>

            <a href="profile_seller.php" style="color: white; margin: 0 15px; text-decoration: none; font-size: 1rem;">โปรไฟล์ผู้ขาย</a>

        <?php if (isset($_SESSION['UserID'])): ?>

            <a href="logout.php">ออกจากระบบ</a>

        <?php else: ?>

            <a href="login.php">เข้าสู่ระบบ</a>

        <?php endif; ?>

    </div>

</nav>



<style>

/* General styles */

body {

    font-family: Arial, sans-serif;

    margin: 0;

    padding: 0;

    background-color: #f4f4f4;

}



/* Navbar styles */

.navbar {

    display: flex;

    justify-content: space-between;

    align-items: center;

    background-color: #333;

    padding: 10px 20px;

    color: white;

}



.navbar .logo {

    font-size: 24px;

    font-weight: bold;

}



.navbar-links {

    display: flex;

    gap: 20px;

}



.navbar-links a {

    color: white;

    text-decoration: none;

    font-size: 16px;

    padding: 8px 16px;

    border-radius: 4px;

}



.navbar-links a:hover {

    background-color: #575757;

}



.navbar-links a:active {

    background-color: #333;

}



/* Optional: เพิ่มความรู้สึกในการ hover ให้ดูดีขึ้น */

.navbar-links a:hover {

    transition: background-color 0.3s ease;

}

</style>

