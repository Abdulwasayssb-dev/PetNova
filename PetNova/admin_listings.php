
<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

if (($_SESSION["user_role"] ?? "") !== "admin") {
    http_response_code(403);
    exit("Access denied. Admin account required.");
}

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST["csrf_token"] ?? "";

    if (!hash_equals($_SESSION["csrf_token"], $token)) {
        http_response_code(403);
        exit("Invalid security token. Refresh the page and try again.");
    }

    $listingId = filter_input(INPUT_POST, "listing_id", FILTER_VALIDATE_INT);
    $action = $_POST["action"] ?? "";

    if (!$listingId || !in_array($action, ["approved", "rejected"], true)) {
        $message = "Invalid listing action.";
    } else {
        $stmt = $pdo->prepare(
            "UPDATE pet_listings
             SET status = ?
             WHERE id = ? AND status = 'pending'"
        );
        $stmt->execute([$action, $listingId]);

        $message = $stmt->rowCount() > 0
            ? "Listing status updated successfully."
            : "Listing not found or already reviewed.";
    }
}

$stmt = $pdo->query(
    "SELECT p.id, p.pet_type, p.breed, p.age_months, p.city,
            p.photo_path, p.asking_price, p.recommended_max_price,
            p.created_at, u.full_name AS seller_name
     FROM pet_listings p
     JOIN users u ON p.seller_id = u.id
     WHERE p.status = 'pending'
     ORDER BY p.created_at ASC"
);

$listings = $stmt->fetchAll();

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Review | PetNova</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .admin-page {
            max-width: 1100px;
            margin: 35px auto;
            padding: 20px;
        }
        .listing-review {
            display: grid;
            grid-template-columns: 180px 1fr;
            gap: 20px;
            padding: 20px;
            margin: 20px 0;
            border: 1px solid #ddd;
            border-radius: 12px;
        }
        .listing-review img {
            width: 100%;
            height: 160px;
            object-fit: cover;
            border-radius: 8px;
        }
        .review-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .review-actions button {
            padding: 10px 16px;
            border: 0;
            border-radius: 6px;
            cursor: pointer;
        }
        .approve-button {
            background: #16805d;
            color: white;
        }
        .reject-button {
            background: #b42318;
            color: white;
        }
        .notice {
            padding: 12px;
            background: #f1f5f9;
            border-radius: 8px;
        }
        @media (max-width: 600px) {
            .listing-review {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<main class="admin-page">
    <h1>PetNova Admin Panel</h1>
    <p>Review seller-submitted pet listings.</p>

    <p><a href="dashboard.php">Back to Dashboard</a></p>

    <?php if ($message !== ""): ?>
        <p class="notice"><?= e($message) ?></p>
    <?php endif; ?>

    <?php if (!$listings): ?>
        <p class="notice">There are no pending listings to review.</p>
    <?php endif; ?>

    <?php foreach ($listings as $pet): ?>
        <section class="listing-review">
            <div>
                <?php if (!empty($pet["photo_path"])
                    && is_file(__DIR__ . "/" . $pet["photo_path"])): ?>
                    <img src="<?= e($pet["photo_path"]) ?>"
                         alt="<?= e($pet["breed"]) ?>">
                <?php endif; ?>
            </div>

            <div>
                <h2><?= e($pet["breed"]) ?></h2>
                <p>Type: <?= e($pet["pet_type"]) ?></p>
                <p>Age: <?= e($pet["age_months"]) ?> months</p>
                <p>City: <?= e($pet["city"]) ?></p>
                <p>Seller: <?= e($pet["seller_name"]) ?></p>
                <p>Asking price: PKR
                    <?= number_format((float)$pet["asking_price"], 0) ?>
                </p>
                <p>Demo recommended maximum: PKR
                    <?= number_format(
                        (float)$pet["recommended_max_price"], 0
                    ) ?>
                </p>

                <div class="review-actions">
                    <form method="POST">
                        <input type="hidden" name="csrf_token"
                               value="<?= e($_SESSION["csrf_token"]) ?>">
                        <input type="hidden" name="listing_id"
                               value="<?= (int)$pet["id"] ?>">
                        <input type="hidden" name="action" value="approved">
                        <button class="approve-button" type="submit">
                            Approve
                        </button>
                    </form>

                    <form method="POST">
                        <input type="hidden" name="csrf_token"
                               value="<?= e($_SESSION["csrf_token"]) ?>">
                        <input type="hidden" name="listing_id"
                               value="<?= (int)$pet["id"] ?>">
                        <input type="hidden" name="action" value="rejected">
                        <button class="reject-button" type="submit">
                            Reject
                        </button>
                    </form>
                </div>
            </div>
        </section>
    <?php endforeach; ?>
</main>
</body>
</html>
