<?php
session_start();
if (!isset($_SESSION['UserID'])) {
    header('Location: login.php');
    exit;
}

switch ($_SESSION['UserType']) {
    case 'admin':
        header('Location: admin.php');
        break;
    case 'seller':
        header('Location: seller.php');
        break;
    case 'buyer':
        header('Location: buyer.php');
        break;
}
?>

---

// admin.php (Admin Panel for Managing Users and Products)
<?php
session_start();
if ($_SESSION['UserType'] !== 'admin') {
    header('Location: login.php');
    exit;
}
include 'db.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Panel</title>
</head>
<body>
    <h1>Welcome, Admin!</h1>
    <h2>Manage Users</h2>
    <a href="api/users.php">View API for Users</a>
    <h2>Manage Products</h2>
    <a href="api/products.php">View API for Products</a>
</body>
</html>