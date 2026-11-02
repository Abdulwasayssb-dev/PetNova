
<?php
session_start();
require_once "db.php";

// Customer must be logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

// Cart must contain products
if (empty($_SESSION["cart"])) {
    header("Location: products.php");
    exit;
}

// Create CSRF token
if (empty($_SESSION["checkout_token"])) {
    $_SESSION["checkout_token"] = bin2hex(random_bytes(32));
}

$error = "";
$orderPlaced = false;
$orderId = null;
$products = [];
$total = 0;

// Load current product details from database
$cart = $_SESSION["cart"];
$ids = array_keys($cart);
$placeholders = implode(",", array_fill(0, count($ids), "?"));

$stmt = $pdo->prepare(
    "SELECT id, product_name, price, stock
     FROM products
     WHERE id IN ($placeholders)
     AND status = 'active'"
);
$stmt->execute($ids);
$products = $stmt->fetchAll();

if (count($products) !== count($cart)) {
    $error = "One or more cart products are no longer available. Please review your cart.";
}

// Calculate displayed total
foreach ($products as $product) {
    $quantity = (int)($cart[$product["id"]] ?? 0);
    $total += (float)$product["price"] * $quantity;

    if ($quantity < 1 || $quantity > (int)$product["stock"]) {
        $error = "Available stock has changed. Please review your cart.";
    }
}

// Place order
if ($_SERVER["REQUEST_METHOD"] === "POST" && $error === "") {

    if (
        !isset($_POST["checkout_token"]) ||
        !hash_equals(
            $_SESSION["checkout_token"],
            $_POST["checkout_token"]
        )
    ) {
        $error = "Invalid request. Please refresh the page and try again.";
    } else {
        try {
            $pdo->beginTransaction();

            $total = 0;
            $orderProducts = [];

            // Lock and verify stock before placing the order
            foreach ($cart as $productId => $quantity) {

                $stmt = $pdo->prepare(
                    "SELECT id, product_name, price, stock
                     FROM products
                     WHERE id = ? AND status = 'active'
                     FOR UPDATE"
                );

                $stmt->execute([(int)$productId]);
                $product = $stmt->fetch();

                if (
                    !$product ||
                    (int)$quantity < 1 ||
                    (int)$quantity > (int)$product["stock"]
                ) {
                    throw new Exception(
                        "A product is unavailable or has insufficient stock."
                    );
                }

                $product["quantity"] = (int)$quantity;
                $total += (float)$product["price"] * (int)$quantity;

                $orderProducts[] = $product;
            }

            // Create order
            $stmt = $pdo->prepare(
                "INSERT INTO orders (user_id, total_amount, order_status)
                 VALUES (?, ?, 'pending')"
            );

            $stmt->execute([
                $_SESSION["user_id"],
                $total
            ]);

            $orderId = (int)$pdo->lastInsertId();

            // Save order items and update stock
            foreach ($orderProducts as $product) {

                $stmt = $pdo->prepare(
                    "INSERT INTO order_items
                     (order_id, product_id, quantity, unit_price)
                     VALUES (?, ?, ?, ?)"
                );

                $stmt->execute([
                    $orderId,
                    $product["id"],
                    $product["quantity"],
                    $product["price"]
                ]);

                $stmt = $pdo->prepare(
                    "UPDATE products
                     SET stock = stock - ?
                     WHERE id = ? AND stock >= ?"
                );

                $stmt->execute([
                    $product["quantity"],
                    $product["id"],
                    $product["quantity"]
                ]);

                if ($stmt->rowCount() !== 1) {
                    throw new Exception("Stock could not be updated.");
                }
            }

            $pdo->commit();

            // Clear cart after successful order
            $_SESSION["cart"] = [];
            unset($_SESSION["checkout_token"]);

            $orderPlaced = true;

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log($e->getMessage());
            $error = "Your order could not be placed. Please review your cart and try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>PetNova | Checkout</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 30px;
            font-family: Arial, sans-serif;
            background: #f5f7fa;
            color: #263238;
        }

        .container {
            max-width: 750px;
            margin: auto;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 3px 12px #00000012;
        }

        h1 {
            text-align: center;
            color: #176b5b;
        }

        .item {
            padding: 15px 0;
            border-bottom: 1px solid #ddd;
        }

        .total {
            text-align: right;
            font-size: 22px;
            font-weight: bold;
            margin-top: 20px;
        }

        .btn {
            display: inline-block;
            width: 100%;
            padding: 14px;
            margin-top: 20px;
            background: #176b5b;
            color: white;
            border: none;
            border-radius: 6px;
            text-align: center;
            text-decoration: none;
            font-size: 16px;
            cursor: pointer;
        }

        .error {
            color: #b00020;
            background: #ffebee;
            padding: 12px;
            border-radius: 6px;
        }

        .success {
            color: #176b5b;
            text-align: center;
        }
    </style>
</head>

<body>

<div class="container">

    <?php if ($orderPlaced): ?>

        <div class="success">
            <h1>Order Placed Successfully!</h1>

            <p>
                Thank you for shopping with PetNova.
            </p>

            <h3>
                Order Number: #<?= (int)$orderId ?>
            </h3>

            <p>
                Payment Method: Cash on Delivery
            </p>

            <p>
                Total Amount: PKR <?= number_format($total, 2) ?>
            </p>

            <p>
                Order Status: Pending
            </p>

            <a href="products.php" class="btn">
                Continue Shopping
            </a>
        </div>

    <?php else: ?>

        <h1>Checkout</h1>

        <?php if ($error !== ""): ?>
            <p class="error">
                <?= htmlspecialchars($error) ?>
            </p>
        <?php endif; ?>

        <h3>Order Summary</h3>

        <?php foreach ($products as $product): ?>

            <?php
            $quantity = (int)($cart[$product["id"]] ?? 0);
            $subtotal = (float)$product["price"] * $quantity;
            ?>

            <div class="item">

                <strong>
                    <?= htmlspecialchars($product["product_name"]) ?>
                </strong>

                <p>
                    Quantity: <?= $quantity ?>
                </p>

                <p>
                    Subtotal: PKR <?= number_format($subtotal, 2) ?>
                </p>

            </div>

        <?php endforeach; ?>

        <div class="total">
            Total: PKR <?= number_format($total, 2) ?>
        </div>

        <h3>Payment Method</h3>

        <p>Cash on Delivery (COD)</p>

        <?php if ($error === ""): ?>

            <form method="POST" action="checkout.php">

                <input
                    type="hidden"
                    name="checkout_token"
                    value="<?= htmlspecialchars($_SESSION["checkout_token"]) ?>"
                >

                <button type="submit" class="btn">
                    Confirm Order
                </button>

            </form>

        <?php endif; ?>

        <a href="cart.php" class="btn">
            Back to Cart
        </a>

    <?php endif; ?>

</div>

</body>
</html>
