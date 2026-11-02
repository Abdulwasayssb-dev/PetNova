
<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$stmt = $pdo->prepare(
    "SELECT id, total_amount, order_status, created_at
     FROM orders
     WHERE user_id = ?
     ORDER BY created_at DESC"
);

$stmt->execute([$_SESSION["user_id"]]);
$orders = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>PetNova | My Orders</title>

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
            max-width: 950px;
            margin: auto;
        }

        h1 {
            text-align: center;
            color: #176b5b;
        }

        .order-card {
            background: white;
            padding: 22px;
            margin: 18px 0;
            border-radius: 10px;
            box-shadow: 0 3px 12px #00000012;
        }

        .status {
            display: inline-block;
            padding: 7px 14px;
            border-radius: 20px;
            background: #fff3cd;
            color: #856404;
            font-weight: bold;
        }

        .empty {
            background: white;
            padding: 30px;
            text-align: center;
            border-radius: 10px;
        }

        .btn {
            display: inline-block;
            padding: 12px 20px;
            background: #176b5b;
            color: white;
            text-decoration: none;
            border-radius: 6px;
        }
    </style>
</head>

<body>

<div class="container">

    <h1>My Orders</h1>

    <?php if (count($orders) > 0): ?>

        <?php foreach ($orders as $order): ?>

            <div class="order-card">

                <h3>
                    Order #<?= (int)$order["id"] ?>
                </h3>

                <p>
                    Date:
                    <?= htmlspecialchars($order["created_at"]) ?>
                </p>

                <p>
                    Total Amount:
                    <strong>
                        PKR <?= number_format(
                            (float)$order["total_amount"], 2
                        ) ?>
                    </strong>
                </p>

                <p>
                    Status:
                    <span class="status">
                        <?= htmlspecialchars($order["order_status"]) ?>
                    </span>
                </p>

            </div>

        <?php endforeach; ?>

    <?php else: ?>

        <div class="empty">
            <h3>No orders found.</h3>
            <p>Your purchases will appear here.</p>
        </div>

    <?php endif; ?>

    <a href="products.php" class="btn">
        Continue Shopping
    </a>

</div>

</body>
</html>
