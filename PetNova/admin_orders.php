
<?php
session_start();
require_once "db.php";

// Only administrators can access this page
// Verify administrator using the database
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$stmt = $pdo->prepare(
    "SELECT role FROM users WHERE id = ? LIMIT 1"
);

$stmt->execute([$_SESSION["user_id"]]);
$currentUser = $stmt->fetch();

if (!$currentUser || $currentUser["role"] !== "admin") {
    http_response_code(403);
    exit("Access denied. Admin only.");
}

$message = "";

// Update order status
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $orderId = filter_input(
        INPUT_POST,
        "order_id",
        FILTER_VALIDATE_INT
    );

    $newStatus = $_POST["new_status"] ?? "";

    if (!$orderId || !in_array($newStatus, ["confirmed", "cancelled"], true)) {
        $message = "Invalid order request.";
    } else {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                "SELECT id, order_status
                 FROM orders
                 WHERE id = ?
                 FOR UPDATE"
            );

            $stmt->execute([$orderId]);
            $order = $stmt->fetch();

            if (!$order || $order["order_status"] !== "pending") {
                throw new Exception(
                    "This order does not exist or has already been processed."
                );
            }

            // Restore stock when an order is cancelled
            if ($newStatus === "cancelled") {

                $stmt = $pdo->prepare(
                    "SELECT product_id, quantity
                     FROM order_items
                     WHERE order_id = ?"
                );

                $stmt->execute([$orderId]);
                $items = $stmt->fetchAll();

                foreach ($items as $item) {
                    $restore = $pdo->prepare(
                        "UPDATE products
                         SET stock = stock + ?
                         WHERE id = ?"
                    );

                    $restore->execute([
                        $item["quantity"],
                        $item["product_id"]
                    ]);
                }
            }

            $stmt = $pdo->prepare(
                "UPDATE orders
                 SET order_status = ?
                 WHERE id = ? AND order_status = 'pending'"
            );

            $stmt->execute([$newStatus, $orderId]);

            if ($stmt->rowCount() !== 1) {
                throw new Exception("Order status could not be updated.");
            }

            $pdo->commit();
            $message = "Order #$orderId updated successfully.";

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log($e->getMessage());
            $message = "Unable to update this order. Please try again.";
        }
    }
}

// Retrieve all orders
$stmt = $pdo->query(
    "SELECT orders.id, orders.user_id,
            orders.total_amount, orders.order_status,
            orders.created_at, users.full_name,
            users.email
     FROM orders
     INNER JOIN users ON orders.user_id = users.id
     ORDER BY orders.created_at DESC"
);

$orders = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>PetNova | Admin Orders</title>

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
            max-width: 1100px;
            margin: auto;
        }

        h1 {
            text-align: center;
            color: #176b5b;
        }

        .order-card {
            background: white;
            padding: 22px;
            margin: 20px 0;
            border-radius: 10px;
            box-shadow: 0 3px 12px #00000012;
        }

        .status {
            font-weight: bold;
            text-transform: capitalize;
        }

        .pending {
            color: #b7791f;
        }

        .confirmed {
            color: #176b5b;
        }

        .cancelled {
            color: #c62828;
        }

        button {
            padding: 10px 16px;
            border: none;
            border-radius: 5px;
            color: white;
            cursor: pointer;
            margin: 5px 5px 0 0;
        }

        .confirm-btn {
            background: #176b5b;
        }

        .cancel-btn {
            background: #c62828;
        }

        .message {
            background: #e8f5e9;
            padding: 12px;
            border-radius: 6px;
            color: #176b5b;
        }

        .empty {
            background: white;
            padding: 25px;
            text-align: center;
            border-radius: 10px;
        }

        .items {
            margin-top: 15px;
            padding: 12px;
            background: #f5f7fa;
            border-radius: 6px;
        }
    </style>
</head>

<body>

<div class="container">

    <h1>Admin Order Management</h1>

    <?php if ($message !== ""): ?>
        <p class="message">
            <?= htmlspecialchars($message) ?>
        </p>
    <?php endif; ?>

    <?php if (count($orders) > 0): ?>

        <?php foreach ($orders as $order): ?>

            <div class="order-card">

                <h3>
                    Order #<?= (int)$order["id"] ?>
                </h3>

                <p>
                    Customer:
                    <?= htmlspecialchars($order["full_name"]) ?>
                </p>

                <p>
                    Email:
                    <?= htmlspecialchars($order["email"]) ?>
                </p>

                <p>
                    Total:
                    <strong>
                        PKR <?= number_format(
                            (float)$order["total_amount"], 2
                        ) ?>
                    </strong>
                </p>

                <p>
                    Date:
                    <?= htmlspecialchars($order["created_at"]) ?>
                </p>

                <p>
                    Status:
                    <span class="status <?= htmlspecialchars($order["order_status"]) ?>">
                        <?= htmlspecialchars($order["order_status"]) ?>
                    </span>
                </p>

                <?php
                $itemStmt = $pdo->prepare(
                    "SELECT products.product_name,
                            order_items.quantity,
                            order_items.unit_price
                     FROM order_items
                     INNER JOIN products
                     ON order_items.product_id = products.id
                     WHERE order_items.order_id = ?"
                );

                $itemStmt->execute([$order["id"]]);
                $items = $itemStmt->fetchAll();
                ?>

                <div class="items">
                    <strong>Ordered Products:</strong>

                    <?php foreach ($items as $item): ?>
                        <p>
                            <?= htmlspecialchars($item["product_name"]) ?>
                            — Quantity: <?= (int)$item["quantity"] ?>
                            — PKR <?= number_format(
                                (float)$item["unit_price"], 2
                            ) ?>
                        </p>
                    <?php endforeach; ?>
                </div>

                <?php if ($order["order_status"] === "pending"): ?>

                    <form method="POST" action="admin_orders.php">

                        <input type="hidden"
                               name="order_id"
                               value="<?= (int)$order["id"] ?>">

                        <button type="submit"
                                name="new_status"
                                value="confirmed"
                                class="confirm-btn">
                            Confirm Order
                        </button>

                        <button type="submit"
                                name="new_status"
                                value="cancelled"
                                class="cancel-btn"
                                onclick="return confirm('Cancel this order?');">
                            Cancel Order
                        </button>

                    </form>

                <?php endif; ?>

            </div>

        <?php endforeach; ?>

    <?php else: ?>

        <div class="empty">
            <h3>No orders available.</h3>
        </div>

    <?php endif; ?>

</div>

</body>
</html>
