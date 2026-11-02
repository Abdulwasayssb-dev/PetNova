
<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

if (($_SESSION["user_role"] ?? "") !== "seller") {
    http_response_code(403);
    exit("Seller access required. Register or sign in with a seller account.");
}

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$message = "";
$recommendedPrice = null;

// TEMPORARY DEMO VALUES ONLY.
// Replace with market-data-based pricing later.
function getRecommendedMaximum(string $petType): ?float
{
    $demoPrices = [
        "dog" => 30000,
        "cat" => 20000,
        "other" => 10000
    ];

    return isset($demoPrices[$petType])
        ? (float) $demoPrices[$petType]
        : null;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST["csrf_token"] ?? "";

    if (!is_string($token) ||
        !hash_equals($_SESSION["csrf_token"], $token)) {
        $message = "Invalid form submission. Please reload the page.";
    } else {
        $petType = $_POST["pet_type"] ?? "";
        $breed = trim($_POST["breed"] ?? "");
        $ageInput = $_POST["age_months"] ?? "";
        $city = trim($_POST["city"] ?? "");
        $priceInput = $_POST["asking_price"] ?? "";

        $allowedTypes = ["dog", "cat", "other"];

        if (
            !in_array($petType, $allowedTypes, true) ||
            $breed === "" ||
            strlen($breed) > 100 ||
            $city === "" ||
            strlen($city) > 100 ||
            filter_var($ageInput, FILTER_VALIDATE_INT) === false ||
            (int)$ageInput < 0 ||
            (int)$ageInput > 300 ||
            !is_numeric($priceInput) ||
            (float)$priceInput <= 0
        ) {
            $message = "Please enter valid pet details and a positive price.";
        } else {
            // Always calculate the maximum on the server.
            $recommendedPrice = getRecommendedMaximum($petType);
            $askingPrice = (float)$priceInput;

            if ($recommendedPrice === null) {
                $message = "A price recommendation is not available.";
            } elseif ($askingPrice > $recommendedPrice) {
                $message = "Listing rejected. The maximum allowed demo price is PKR " .
                    number_format($recommendedPrice, 0) . ".";
            } elseif (
                !isset($_FILES["pet_photo"]) ||
                $_FILES["pet_photo"]["error"] !== UPLOAD_ERR_OK
            ) {
                $message = "Please upload a valid pet photo.";
            } elseif ($_FILES["pet_photo"]["size"] > 5 * 1024 * 1024) {
                $message = "The photo must be 5 MB or smaller.";
            } else {
                $tmpPath = $_FILES["pet_photo"]["tmp_name"];
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($tmpPath);

                $allowedMimeTypes = [
                    "image/jpeg" => "jpg",
                    "image/png" => "png",
                    "image/webp" => "webp"
                ];

                if (!isset($allowedMimeTypes[$mime])) {
                    $message = "Use a JPG, PNG, or WEBP image.";
                } else {
                    $uploadDir = __DIR__ . "/uploads/";

                    if (!is_dir($uploadDir) ||
                        !is_writable($uploadDir)) {
                        $message = "The uploads folder is missing or not writable.";
                    } else {
                        $fileName = bin2hex(random_bytes(16)) .
                            "." . $allowedMimeTypes[$mime];

                        $destination = $uploadDir . $fileName;
                        $relativePath = "uploads/" . $fileName;

                        if (move_uploaded_file($tmpPath, $destination)) {
                            try {
                                $stmt = $pdo->prepare(
                                    "INSERT INTO pet_listings
                                    (seller_id, pet_type, breed, age_months,
                                     city, photo_path, recommended_max_price,
                                     asking_price, status)
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')"
                                );

                                $stmt->execute([
                                    $_SESSION["user_id"],
                                    $petType,
                                    $breed,
                                    (int)$ageInput,
                                    $city,
                                    $relativePath,
                                    $recommendedPrice,
                                    $askingPrice
                                ]);

                                $message = "Listing submitted successfully. It is pending review.";
                                $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
                            }  catch (PDOException $e) {
                                   if (is_file($destination)) {
                                unlink($destination);
                                }

                         error_log($e->getMessage());

    // Temporary debugging for localhost only
                          $message = "Database Error: " . $e->getMessage();
                        }
                        } else {
                            $message = "The photo could not be uploaded.";
                        }
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetNova | Sell a Pet</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<main class="section">
    <h2>Create a Pet Listing</h2>
    <p>Enter your pet's details to submit a listing for review.</p>

    <?php if ($message !== ""): ?>
        <p role="status">
            <?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?>
        </p>
    <?php endif; ?>

    <?php if ($recommendedPrice !== null): ?>
        <p>
            Demonstration recommended maximum:
            <strong>PKR <?= number_format($recommendedPrice, 0) ?></strong>
        </p>
        <p class="notice">
            This is a placeholder value for testing, not a real market valuation.
        </p>
    <?php endif; ?>

    <form class="feature-form" method="POST"
          action="sell_pet.php" enctype="multipart/form-data">

        <input type="hidden" name="csrf_token"
               value="<?= htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8") ?>">

        <label for="pet_type">Pet Type</label>
        <select id="pet_type" name="pet_type" required>
            <option value="">Select pet type</option>
            <option value="dog">Dog</option>
            <option value="cat">Cat</option>
            <option value="other">Other</option>
        </select>

        <label for="breed">Breed</label>
        <input id="breed" name="breed" maxlength="100" required>

        <label for="age_months">Age (months)</label>
        <input type="number" id="age_months" name="age_months"
               min="0" max="300" step="1" required>

        <label for="city">City</label>
        <input id="city" name="city" maxlength="100" required>

        <label for="pet_photo">Pet Photo (JPG, PNG, or WEBP; max 5 MB)</label>
        <input type="file" id="pet_photo" name="pet_photo"
               accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
               required>

        <label for="asking_price">Asking Price (PKR)</label>
        <input type="number" id="asking_price" name="asking_price"
               min="1" step="0.01" required>

        <button type="submit">Submit Listing</button>
    </form>

    <p style="margin-top: 20px;">
        <a href="dashboard.php">Back to Dashboard</a>
    </p>
</main>
</body>
</html>
