
<?php
require_once "db.php";

$category = $_GET['category'] ?? '';

if ($category !== '') {
    $stmt = $pdo->prepare(
        "SELECT * FROM products
         WHERE status = 'active' AND category = ?
         ORDER BY id DESC"
    );
    $stmt->execute([$category]);
} else {
    $stmt = $pdo->query(
        "SELECT * FROM products
         WHERE status = 'active'
         ORDER BY id DESC"
    );
}

$products = $stmt->fetchAll();

$categoryStmt = $pdo->query(
    "SELECT DISTINCT category FROM products
     WHERE status = 'active'
     ORDER BY category"
);
$categories = $categoryStmt->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetNova | Pet Products</title>

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
            max-width: 1150px;
            margin: auto;
        }

        h1 {
            text-align: center;
            color: #176b5b;
        }

        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
        }

        .filters {
            text-align: center;
            margin-bottom: 30px;
        }

        .filters a {
            display: inline-block;
            margin: 5px;
            padding: 10px 18px;
            border-radius: 20px;
            background: white;
            color: #176b5b;
            text-decoration: none;
            border: 1px solid #176b5b;
        }

        .filters a:hover {
            background: #176b5b;
            color: white;
        }

        .products {
            display: grid;
            grid-template-columns:
                repeat(auto-fit, minmax(230px, 1fr));
            gap: 22px;
        }

        .product-card {
            background: white;
            padding: 22px;
            border-radius: 12px;
            box-shadow: 0 3px 12px #00000012;
        }

        .product-card h3 {
            color: #176b5b;
        }

        .price {
            font-size: 20px;
            font-weight: bold;
            color: #e67e22;
        }

        .stock {
            color: #555;
        }

        .cart-btn {
            width: 100%;
            padding: 12px;
            background: #176b5b;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 15px;
        }

        .cart-btn:hover {
            background: #105044;
        }

        .empty {
            text-align: center;
            grid-column: 1 / -1;
        }
    </style>
</head>

<body>

<div class="container">

    <h1>PetNova Pet Store</h1>

    <p class="subtitle">
        Explore food, toys, accessories and grooming products.
    </p>

    <div class="filters">

        <a href="products.php">All Products</a>

        <?php foreach ($categories as $cat): ?>
            <a href="products.php?category=<?= urlencode($cat) ?>">
                <?= htmlspecialchars($cat) ?>
            </a>
        <?php endforeach; ?>

    </div>

    <div class="products">

        <?php if (count($products) > 0): ?>

            <?php foreach ($products as $product): ?>

                <div class="product-card">

                    <h3>
                        <?= htmlspecialchars($product['product_name']) ?>
                    </h3>

                    <p>
                        <?= htmlspecialchars($product['category']) ?>
                    </p>

                    <p>
                        <?= htmlspecialchars($product['description'] ?? '') ?>
                    </p>

                    <p class="price">
                        PKR <?= number_format((float)$product['price'], 2) ?>
                    </p>

                    <p class="stock">
                        Available Stock:
                        <?= (int)$product['stock'] ?>
                    </p>

                    <form action="add_to_cart.php" method="POST">

                        <input
                            type="hidden"
                            name="product_id"
                            value="<?= (int)$product['id'] ?>"
                        >

                        <button type="submit" class="cart-btn"
                            <?= (int)$product['stock'] < 1 ? 'disabled' : '' ?>>
                            <?= (int)$product['stock'] < 1
                                ? 'Out of Stock'
                                : 'Add to Cart' ?>
                        </button>

                    </form>

                </div>

            <?php endforeach; ?>

        <?php else: ?>

            <p class="empty">
                No products found in this category.
            </p>

        <?php endif; ?>

    </div>

</div>

</body>
</html>
