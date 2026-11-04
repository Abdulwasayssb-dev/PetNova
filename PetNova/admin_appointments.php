
<?php
session_start();
require_once "db.php";

// ------------------------------------
// 1. Admin Authentication
// ------------------------------------

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

// Verify admin role from database
$checkAdmin = $pdo->prepare(
    "SELECT role FROM users WHERE id = ? LIMIT 1"
);
$checkAdmin->execute([$_SESSION["user_id"]]);
$admin = $checkAdmin->fetch(PDO::FETCH_ASSOC);

if (!$admin || $admin["role"] !== "admin") {
    http_response_code(403);
    exit("Access denied. Admin access only.");
}

// ------------------------------------
// 2. CSRF Protection
// ------------------------------------

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$message = "";

// ------------------------------------
// 3. Confirm or Reject Appointment
// ------------------------------------

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $token = $_POST["csrf_token"] ?? "";
    $appointmentId = $_POST["appointment_id"] ?? "";
    $newStatus = $_POST["status"] ?? "";

    if (!hash_equals($_SESSION["csrf_token"], $token)) {
        $message = "Invalid request. Please try again.";

    } elseif (
        !ctype_digit((string)$appointmentId) ||
        !in_array($newStatus, ["confirmed", "rejected"], true)
    ) {
        $message = "Invalid appointment information.";

    } else {

        $update = $pdo->prepare(
            "UPDATE vet_appointments
             SET status = ?
             WHERE id = ? AND status = 'pending'"
        );

        $update->execute([
            $newStatus,
            (int)$appointmentId
        ]);

        if ($update->rowCount() > 0) {
            header("Location: admin_appointments.php?updated=1");
            exit;
        } else {
            $message = "Appointment not found or already processed.";
        }
    }
}

// ------------------------------------
// 4. Success Message
// ------------------------------------

if (isset($_GET["updated"])) {
    $message = "Appointment status updated successfully.";
}

// ------------------------------------
// 5. Fetch All Appointments
// ------------------------------------

$stmt = $pdo->query(
    "SELECT
        a.id,
        a.user_id,
        a.pet_name,
        a.pet_type,
        a.appointment_date,
        a.appointment_time,
        a.reason,
        a.status,

        u.full_name AS customer_name,

        v.full_name AS veterinarian_name,
        v.speciality,
        v.clinic_name

     FROM vet_appointments a

     INNER JOIN users u
        ON a.user_id = u.id

     INNER JOIN veterinarians v
        ON a.veterinarian_id = v.id

     ORDER BY a.created_at DESC"
);
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>PetNova | Manage Appointments</title>

    <link rel="stylesheet" href="style.css">

    <style>
        .appointment-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 20px;
        }

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
            text-transform: capitalize;
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

        .action-form {
            display: inline-block;
            margin: 12px 8px 0 0;
        }

        .confirm-btn {
            background: #16845f;
            color: white;
            border: none;
            padding: 10px 18px;
            border-radius: 6px;
            cursor: pointer;
        }

        .reject-btn {
            background: #c0392b;
            color: white;
            border: none;
            padding: 10px 18px;
            border-radius: 6px;
            cursor: pointer;
        }

        .message {
            padding: 12px;
            background: #e5f5ed;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .back-link {
            display: inline-block;
            margin-top: 20px;
        }
    </style>
</head>

<body>

<main class="appointment-container">

    <h1>Manage Veterinary Appointments</h1>

    <p>View, confirm, and reject appointment requests.</p>

    <?php if ($message !== ""): ?>
        <p class="message" role="alert">
            <?= e($message) ?>
        </p>
    <?php endif; ?>

    <?php if (count($appointments) > 0): ?>

        <?php foreach ($appointments as $appointment): ?>

            <div class="appointment-card">

                <h2>
                    <?= e($appointment["pet_name"]) ?>
                    (<?= e($appointment["pet_type"]) ?>)
                </h2>

                <p>
                    <strong>Appointment ID:</strong>
                    <?= (int)$appointment["id"] ?>
                </p>
                <p>
    <strong>Customer Name:</strong>
    <?= e($appointment["customer_name"]) ?>
</p>

                <p>
                    <strong>Veterinarian:</strong>
                    <?= e($appointment["veterinarian_name"]) ?>
                </p>

                <p>
                    <strong>Speciality:</strong>
                    <?= e($appointment["speciality"]) ?>
                </p>

                <p>
                    <strong>Clinic:</strong>
                    <?= e($appointment["clinic_name"]) ?>
                </p>

                <p>
                    <strong>Date:</strong>
                    <?= e($appointment["appointment_date"]) ?>
                </p>

                <p>
                    <strong>Time:</strong>
                    <?= e($appointment["appointment_time"]) ?>
                </p>

                <p>
                    <strong>Reason:</strong>
                    <?= nl2br(e($appointment["reason"])) ?>
                </p>

                <p>
                    <strong>Status:</strong>

                    <span class="status <?= e($appointment["status"]) ?>">
                        <?= e($appointment["status"]) ?>
                    </span>
                </p>

                <?php if ($appointment["status"] === "pending"): ?>

                    <form method="POST"
                          action="admin_appointments.php"
                          class="action-form">

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= e($_SESSION["csrf_token"]) ?>"
                        >

                        <input
                            type="hidden"
                            name="appointment_id"
                            value="<?= (int)$appointment["id"] ?>"
                        >

                        <input
                            type="hidden"
                            name="status"
                            value="confirmed"
                        >

                        <button type="submit" class="confirm-btn">
                            Confirm Appointment
                        </button>

                    </form>

                    <form method="POST"
                          action="admin_appointments.php"
                          class="action-form">

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= e($_SESSION["csrf_token"]) ?>"
                        >

                        <input
                            type="hidden"
                            name="appointment_id"
                            value="<?= (int)$appointment["id"] ?>"
                        >

                        <input
                            type="hidden"
                            name="status"
                            value="rejected"
                        >

                        <button type="submit" class="reject-btn">
                            Reject Appointment
                        </button>

                    </form>

                <?php endif; ?>

            </div>

        <?php endforeach; ?>

    <?php else: ?>

        <p>No appointment requests found.</p>

    <?php endif; ?>

    <a href="dashboard.php" class="back-link">
        Back to Dashboard
    </a>

</main>

</body>
</html>
