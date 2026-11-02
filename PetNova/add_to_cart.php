
<?php
session_start();
require_once "db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: products.php");
    exit;
}

$product_id = filter_input(INPUT_POST, "product_id", FILTER_VALIDATE_INT);

if (!$product_id || $product_id < 1) {
    exit("Invalid product.");
}

$stmt = $pdo->prepare(
    "SELECT id, stock FROM products
     WHERE id = ? AND status = 'active'"
);
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product || $product["stock"] < 1) {
    exit("This product is currently unavailable.");
}

if (!isset($_SESSION["cart"])) {
    $_SESSION["cart"] = [];
}

$current_quantity = $_SESSION["cart"][$product_id] ?? 0;

if ($current_quantity + 1 > $product["stock"]) {
    exit("You cannot add more than the available stock.");
}

$_SESSION["cart"][$product_id] = $current_quantity + 1;

header("Location: cart.php");
exit;
?>
