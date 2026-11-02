
<?php
require_once "db.php";

$breed = trim($_GET["breed"] ?? "");
$petType = $_GET["pet_type"] ?? "";
$city = trim($_GET["city"] ?? "");

$allowedTypes = ["dog", "cat", "other"];

if (!in_array($petType, $allowedTypes, true)) {
    $petType = "";
}

$sql = "SELECT pet_type, breed, age_months, city,
               photo_path, asking_price, recommended_max_price
        FROM pet_listings
        WHERE status = 'approved'";

$params = [];

if ($breed !== "") {
    $sql .= " AND breed LIKE ?";
    $params[] = "%" . $breed . "%";
}

if ($petType !== "") {
    $sql .= " AND pet_type = ?";
    $params[] = $petType;
}

if ($city !== "") {
    $sql .= " AND city LIKE ?";
    $params[] = "%" . $city . "%";
}

$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$pets = $stmt->fetchAll();

function e($value) {
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        "UTF-8"
    );
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>PetNova | Pet Marketplace</title>

    <link rel="stylesheet" href="style.css">

    <style>
        .marketplace-page {
            max-width: 1100px;
            margin: 40px auto;
            padding: 20px;
        }

        .search-box {
            background: #f4f8f7;
            padding: 22px;
            border-radius: 12px;
            margin: 25px 0;
        }

        .search-box form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            align-items: end;
        }

        .search-box label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }

        .search-box input,
        .search-box select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 7px;
        }

        .search-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .pet-grid {
            display: grid;
            grid-template-columns:
                repeat(auto-fit, minmax(230px, 1fr));
            gap: 22px;
        }

        .pet-card {
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 16px;
            background: white;
        }

        .pet-card img {
            width: 100%;
            height: 190px;
            object-fit: cover;
            border-radius: 10px;
        }

        .pet-price {
            font-size: 20px;
            font-weight: bold;
            color: #16805d;
        }

        .empty-message {
            padding: 20px;
            background: #f1f5f9;
            border-radius: 10px;
        }

        .marketplace-link {
            display: inline-block;
            margin: 10px 10px 10px 0;
        }
    </style>
</head>

<body>

<main class="marketplace-page">

    <h1>PetNova Marketplace</h1>

    <p>
        Find your desired pet by searching its breed,
        selecting its type, or entering a city.
    </p>

    <a class="marketplace-link" href="index.html">
        Home
    </a>

    <a class="marketplace-link" href="dashboard.php">
        Dashboard
    </a>

    <a class="marketplace-link" href="sell_pet.php">
        Sell a Pet
    </a>

    <!-- Search and Filter Form -->

    <div class="search-box">

        <h2>Find Your Pet</h2>

        <form method="GET" action="pets.php">

            <div>
                <label for="breed">Search by Breed</label>

                <input
                    type="text"
                    id="breed"
                    name="breed"
                    placeholder="e.g. Labrador"
                    value="<?= e($breed) ?>"
                >
            </div>

            <div>
                <label for="pet_type">Pet Type</label>

                <select id="pet_type" name="pet_type">

                    <option value="">All Pets</option>

                    <option value="dog"
                        <?= $petType === "dog" ? "selected" : "" ?>>
                        Dog
                    </option>

                    <option value="cat"
                        <?= $petType === "cat" ? "selected" : "" ?>>
                        Cat
                    </option>

                    <option value="other"
                        <?= $petType === "other" ? "selected" : "" ?>>
                        Other
                    </option>

                </select>
            </div>

            <div>
                <label for="city">Search by City</label>

                <input
                    type="text"
                    id="city"
                    name="city"
                    placeholder="e.g. Mianwali"
                    value="<?= e($city) ?>"
                >
            </div>

            <div class="search-actions">

                <button type="submit">
                    Search Pets
                </button>

                <a href="pets.php" class="button secondary">
                    Clear Filters
                </a>

            </div>

        </form>

    </div>

    <!-- Search Results -->

    <h2>Available Pets</h2>

    <p>
        <?= count($pets) ?> pet(s) found.
    </p>

    <?php if (count($pets) === 0): ?>

        <div class="empty-message">

            <h3>No matching pets found.</h3>

            <p>
                Try another breed, pet type, or city.
            </p>

            <a href="pets.php">View All Pets</a>

        </div>

    <?php else: ?>

        <div class="pet-grid">

            <?php foreach ($pets as $pet): ?>

                <article class="pet-card">

                    <?php if (
                        !empty($pet["photo_path"]) &&
                        is_file(__DIR__ . "/" . $pet["photo_path"])
                    ): ?>

                        <img
                            src="<?= e($pet["photo_path"]) ?>"
                            alt="<?= e($pet["breed"]) ?>"
                        >

                    <?php endif; ?>

                    <h3>
                        <?= e($pet["breed"]) ?>
                    </h3>

                    <p>
                        Type: <?= e(ucfirst($pet["pet_type"])) ?>
                    </p>

                    <p>
                        Age: <?= e($pet["age_months"]) ?> months
                    </p>

                    <p>
                        City: <?= e($pet["city"]) ?>
                    </p>

                    <p class="pet-price">
                        PKR <?= number_format(
                            (float)$pet["asking_price"], 0
                        ) ?>
                    </p>

                    <p>
                        Demo recommended maximum: PKR
                        <?= number_format(
                            (float)$pet["recommended_max_price"], 0
                        ) ?>
                    </p>

                </article>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</main>

</body>
</html>
