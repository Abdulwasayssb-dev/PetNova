
<?php
session_start();
require_once "db.php";

// ------------------------------------
// 1. User Authentication
// ------------------------------------

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

// ------------------------------------
// 2. CSRF Protection
// ------------------------------------

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$message = "";

// ------------------------------------
// 3. Cancel Appointment
// ------------------------------------

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $token = $_POST["csrf_token"] ?? "";
    $appointmentId = $_POST["appointment_id"] ?? "";

    if (!hash_equals($_SESSION["csrf_token"], $token)) {

        $message = "Invalid request. Please try again.";

    } elseif (!ctype_digit((string)$appointmentId)) {

        $message = "Invalid appointment information.";

    } else {

        // Users can cancel only their own pending appointments.

        $update = $pdo->prepare(
            "UPDATE vet_appointments
             SET status = 'cancelled'
             WHERE id = ?
             AND user_id = ?
             AND status = 'pending'"
        );

        $update->execute([
            (int)$appointmentId,
            $_SESSION["user_id"]
        ]);

        if ($update->rowCount() > 0) {
            header("Location: my_appointments.php?cancelled=1");
            exit;
        } else {
            $message =
                "Appointment cannot be cancelled. It may already be processed.";
        }
    }
}

// ------------------------------------
// 4. Success Messages
// ------------------------------------

if (isset($_GET["cancelled"])) {
    $message = "Your appointment request has been cancelled successfully.";
}

if (isset($_GET["booked"])) {
    $message = "Appointment request submitted successfully. Its status is pending.";
}

// ------------------------------------
// 5. Fetch User Appointments
// ------------------------------------

$stmt = $pdo->prepare(
    "SELECT
        a.id,
        a.pet_name,
        a.pet_type,
        a.appointment_date,
        a.appointment_time,
        a.reason,
        a.status,
        v.full_name AS veterinarian_name,
        v.clinic_name,
        v.city

     FROM vet_appointments a

     JOIN veterinarians v
        ON v.id = a.veterinarian_id

     WHERE a.user_id = ?

     ORDER BY a.created_at DESC"
);

$stmt->execute([$_SESSION["user_id"]]);
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ------------------------------------
// 6. HTML Escaping
// ------------------------------------

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

    <title>PetNova | My Appointments</title>

    <link rel="stylesheet" href="style.css">

    <style>
        .appointment-card {
            background: white;
            border: 1px solid #dce8e2;
            border-radius: 12px;
            padding: 22px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }

        .appointment-card p {
            margin: 9px 0;
        }

        .status {
            font-weight: bold;
        }

        .pending {
            color: #b7791f;
        }

        .confirmed {
            color: #16845f;
        }

        .rejected {
            color: #c0392b;
        }

        .cancelled {
            color: #777;
        }

        .cancel-btn {
            background: #c0392b;
            color: white;
            border: none;
            padding: 10px 18px;
            border-radius: 6px;
            cursor: pointer;
            margin-top: 10px;
        }

        .message {
            padding: 12px;
            background: #e5f5ed;
            border-radius: 6px;
            margin-bottom: 20px;
        }
    </style>

</head>

<body>

<main class="section">

    <h1>My Veterinary Appointments</h1>

    <?php if ($message !== ""): ?>

        <p class="message" role="status">
            <?= e($message) ?>
        </p>

    <?php endif; ?>

    <?php if (!$appointments): ?>

        <p>You have not submitted any appointment requests yet.</p>

    <?php else: ?>

        <?php foreach ($appointments as $a): ?>

            <article class="appointment-card">

                <h2>
                    <?= e($a["pet_name"]) ?>
                    (<?= e($a["pet_type"]) ?>)
                </h2>

                <p>
                    <strong>Veterinarian:</strong>
                    <?= e($a["veterinarian_name"]) ?>
                </p>

                <p>
                    <strong>Clinic:</strong>
                    <?= e($a["clinic_name"]) ?>,
                    <?= e($a["city"]) ?>
                </p>

                <p>
                    <strong>Date:</strong>
                    <?= e($a["appointment_date"]) ?>
                </p>

                <p>
                    <strong>Time:</strong>
                    <?= e(substr($a["appointment_time"], 0, 5)) ?>
                </p>

                <p>
                    <strong>Reason:</strong>
                    <?= e($a["reason"]) ?>
                </p>

                <p>
                    <strong>Status:</strong>

                    <span class="status <?= e($a["status"]) ?>">
                        <?= e(ucfirst($a["status"])) ?>
                    </span>

                </p>

                <?php if ($a["status"] === "pending"): ?>

                    <form method="POST"
                          action="my_appointments.php"
                          onsubmit="return confirm('Are you sure you want to cancel this appointment?');">

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= e($_SESSION["csrf_token"]) ?>"
                        >

                        <input
                            type="hidden"
                            name="appointment_id"
                            value="<?= (int)$a["id"] ?>"
                        >

                        <button type="submit" class="cancel-btn">
                            Cancel Appointment
                        </button>

                    </form>

                <?php endif; ?>

            </article>

        <?php endforeach; ?>

    <?php endif; ?>

    <p>
        <a href="book_appointment.php">
            Book Another Appointment
        </a>
    </p>

    <p>
        <a href="veterinary.php">
            Veterinary Care
        </a>
    </p>

</main>

</body>
</html>
