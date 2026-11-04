
<?php
session_start();
require_once "db.php";

// Only logged-in users can book appointments
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$error = "";

// Load active veterinarians
$stmt = $pdo->query("
    SELECT id, full_name, speciality, clinic_name, city
    FROM veterinarians
    WHERE status = 'active'
    ORDER BY full_name
");

$veterinarians = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle appointment form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $userId = (int) $_SESSION["user_id"];
    $vetId = filter_input(INPUT_POST, "veterinarian_id", FILTER_VALIDATE_INT);
    $petName = trim($_POST["pet_name"] ?? "");
    $petType = trim($_POST["pet_type"] ?? "");
    $date = trim($_POST["appointment_date"] ?? "");
    $time = trim($_POST["appointment_time"] ?? "");
    $reason = trim($_POST["reason"] ?? "");

    // Check required fields
    if (
        !$vetId ||
        $petName === "" ||
        $petType === "" ||
        $date === "" ||
        $time === "" ||
        $reason === ""
    ) {
        $error = "Please fill in all required fields.";
    } else {

        // Validate appointment date
        $dateObject = DateTime::createFromFormat("Y-m-d", $date);

        $validDate = $dateObject &&
            $dateObject->format("Y-m-d") === $date &&
            $date >= date("Y-m-d");

        // Validate 24-hour time format (HH:MM)
        $validTime = (bool) preg_match(
            '/^(?:[01]\d|2[0-3]):[0-5]\d$/',
            $time
        );

        if (!$validDate) {
            $error = "Please select a valid appointment date.";
        } elseif (!$validTime) {
            $error = "Please enter a valid appointment time.";
        } else {

            // Verify selected veterinarian is active
            $checkVet = $pdo->prepare("
                SELECT id
                FROM veterinarians
                WHERE id = ? AND status = 'active'
            ");

            $checkVet->execute([$vetId]);

            if (!$checkVet->fetch()) {
                $error = "The selected veterinarian is not available.";
            } else {

                // Save appointment request
                $insert = $pdo->prepare("
                    INSERT INTO vet_appointments
                    (
                        user_id,
                        veterinarian_id,
                        pet_name,
                        pet_type,
                        appointment_date,
                        appointment_time,
                        reason
                    )
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");

                $insert->execute([
                    $userId,
                    $vetId,
                    $petName,
                    $petType,
                    $date,
                    $time,
                    $reason
                ]);

                header("Location: my_appointments.php?booked=1");
                exit;
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
    <title>Book Veterinary Appointment | PetNova</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

<div class="container">

    <h1>Book a Veterinary Appointment</h1>

    <p>Submit your appointment request to a veterinarian.</p>

    <?php if ($error !== ""): ?>
        <p style="color: red;">
            <?= htmlspecialchars($error) ?>
        </p>
    <?php endif; ?>

    <?php if (count($veterinarians) === 0): ?>

        <p>No active veterinarians are currently available.</p>

    <?php else: ?>

        <form method="POST" action="">

            <label for="veterinarian_id">Select Veterinarian</label>

            <select name="veterinarian_id" id="veterinarian_id" required>
                <option value="">Choose a veterinarian</option>

                <?php foreach ($veterinarians as $vet): ?>

                    <option
                        value="<?= (int) $vet["id"] ?>"
                        <?= (
                            isset($_POST["veterinarian_id"]) &&
                            (int) $_POST["veterinarian_id"] === (int) $vet["id"]
                        ) ? "selected" : "" ?>
                    >
                        <?= htmlspecialchars(
                            $vet["full_name"] . " - " .
                            $vet["speciality"] . " (" .
                            $vet["clinic_name"] . ", " .
                            $vet["city"] . ")"
                        ) ?>
                    </option>

                <?php endforeach; ?>

            </select>

            <br><br>

            <label for="pet_name">Pet Name</label>

            <input
                type="text"
                name="pet_name"
                id="pet_name"
                maxlength="100"
                value="<?= htmlspecialchars($_POST["pet_name"] ?? "") ?>"
                required
            >

            <br><br>

            <label for="pet_type">Pet Type</label>

            <select name="pet_type" id="pet_type" required>

                <option value="">Select pet type</option>

                <?php
                $types = ["Dog", "Cat", "Bird", "Cow", "Other"];

                foreach ($types as $type):
                ?>

                    <option
                        value="<?= htmlspecialchars($type) ?>"
                        <?= (
                            ($_POST["pet_type"] ?? "") === $type
                        ) ? "selected" : "" ?>
                    >
                        <?= htmlspecialchars($type) ?>
                    </option>

                <?php endforeach; ?>

            </select>

            <br><br>

            <label for="appointment_date">Preferred Date</label>

            <input
                type="date"
                name="appointment_date"
                id="appointment_date"
                min="<?= date("Y-m-d") ?>"
                value="<?= htmlspecialchars($_POST["appointment_date"] ?? "") ?>"
                required
            >

            <br><br>

            <label for="appointment_time">Preferred Time</label>

            <input
                type="time"
                name="appointment_time"
                id="appointment_time"
                value="<?= htmlspecialchars($_POST["appointment_time"] ?? "") ?>"
                required
            >

            <br><br>

            <label for="reason">Reason for Appointment</label>

            <textarea
                name="reason"
                id="reason"
                rows="5"
                required
            ><?= htmlspecialchars($_POST["reason"] ?? "") ?></textarea>

            <br><br>

            <button type="submit">
                Submit Appointment Request
            </button>

        </form>

    <?php endif; ?>

    <br>

    <a href="my_appointments.php">My Appointments</a> |
    <a href="veterinary.php">Back to Veterinary Care</a> |
    <a href="dashboard.php">Dashboard</a>

</div>

</body>
</html>
