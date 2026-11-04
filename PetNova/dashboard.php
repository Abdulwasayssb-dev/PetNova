
<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$name = htmlspecialchars(
    $_SESSION["full_name"],
    ENT_QUOTES,
    "UTF-8"
);

$role = htmlspecialchars(
    $_SESSION["user_role"],
    ENT_QUOTES,
    "UTF-8"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetNova | Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<main class="section">

    <h2>Welcome to PetNova, <?= $name ?>!</h2>

    <p>You are successfully logged in.</p>

    <p>
        Account type:
        <strong><?= ucfirst($role) ?></strong>
    </p>

    <div class="card-grid">

        <!-- Pet Marketplace -->
        <article class="card">
            <h3>Pet Marketplace</h3>

            <p>Explore the pet marketplace.</p>

            <a class="button" href="index.html#marketplace">
                Explore Pets
            </a>
        </article>

        <!-- Pet Care -->
        <article class="card">
            <h3>Pet Care</h3>

            <p>Explore pet health and care information.</p>

            <a class="button" href="index.html#health">
                Pet Care
            </a>
        </article>

        <!-- My Appointments: All Logged-in Users -->
        <article class="card">
            <h3>My Appointments</h3>

            <p>
                View your veterinary appointment requests
                and check their status.
            </p>

            <a class="button" href="my_appointments.php">
                View My Appointments
            </a>
        </article>

        <!-- Veterinary Management: Admin Only -->
        <?php if ($_SESSION["user_role"] === "admin"): ?>

            <article class="card">
                <h3>Veterinary Management</h3>

                <p>
                    Manage appointment requests submitted by users.
                    Confirm or reject appointments.
                </p>

                <a class="button" href="admin_appointments.php">
                    Manage Appointments
                </a>
            </article>

        <?php endif; ?>

    </div>

    <p style="margin-top: 25px;">
        <a class="button" href="logout.php">Logout</a>
    </p>

</main>

</body>
</html>
