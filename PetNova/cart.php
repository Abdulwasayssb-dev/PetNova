
<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["cart"])) {
    $_SESSION["cart"] = [];
}

// Remove product from cart
if (isset($_GET["remove"])) {
    $remove_id = filter_input(
        INPUT_GET,
        "remove",
        FILTER_VALIDATE_INT
    );

    if ($remove_id && isset($_SESSION["cart"][$remove_id])) {
        unset($_SESSION["cart"][$remove_id]);
    }

    header("Location: cart.php");
    exit;
}

$cart = $_SESSION["cart"];
$products = [];
$total = 0;

if (!empty($cart)) {
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
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>PetNova | Shopping Cart</title>

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
            max-width: 900px;
            margin: auto;
        }

        h1 {
            text-align: center;
            color: #176b5b;
        }

        .cart-item {
            background: white;
            padding: 20px;
            margin: 15px 0;
            border-radius: 10px;
            box-shadow: 0 3px 12px #00000012;
        }

        .remove {
            color: #d32f2f;
            text-decoration: none;
        }

        .total {
            font-size: 22px;
            font-weight: bold;
            text-align: right;
            margin-top: 25px;
        }

        .btn {
            display: inline-block;
            padding: 12px 22px;
            background: #176b5b;
            color: white;
            text-decoration: none;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            margin-top: 15px;
        }

        .empty {
            text-align: center;
            background: white;
            padding: 30px;
            border-radius: 10px;
        }

        .actions {
            text-align: right;
        }
    </style>
</head>

<body>

<div class="container">

    <h1>My Shopping Cart</h1>

    <?php if (empty($products)): ?>

        <div class="empty">
            <h3>Your cart is empty!</h3>
            <p>Add some products to continue shopping.</p>

            <a href="products.php" class="btn">
                Browse Products
            </a>
        </div>

    <?php else: ?>

        <?php foreach ($products as $product): ?>

            <?php
            $quantity = (int)($cart[$product["id"]] ?? 0);
            $subtotal = (float)$product["price"] * $quantity;
            $total += $subtotal;
            ?>

            <div class="cart-item">

                <h3>
                    <?= htmlspecialchars($product["product_name"]) ?>
                </h3>

                <p>
                    Price: PKR
                    <?= number_format((float)$product["price"], 2) ?>
                </p>

                <p>Quantity: <?= $quantity ?></p>

                <p>
                    Subtotal: PKR
                    <?= number_format($subtotal, 2) ?>
                </p>

                <a class="remove"
                   href="cart.php?remove=<?= (int)$product["id"] ?>">
                    Remove
                </a>

            </div>

        <?php endforeach; ?>

        <div class="total">
            Total: PKR <?= number_format($total, 2) ?>
        </div>

        <div class="actions">

            <a href="products.php" class="btn">
                Continue Shopping
            </a>

            <a href="checkout.php" class="btn">
                Proceed to Checkout
            </a>

        </div>

    <?php endif; ?>

</div>

</body>
</html>
