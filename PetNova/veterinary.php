
<?php
require_once "db.php";

$stmt = $pdo->query(
    "SELECT id, full_name, speciality, clinic_name, city,
            phone, email, description
     FROM veterinarians
     WHERE status = 'active'
     ORDER BY full_name ASC"
);

$veterinarians = $stmt->fetchAll();

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetNova | Veterinary Care</title>
    <link rel="stylesheet" href="style.css">

    <style>
        .vet-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 20px;
            margin-top: 25px;
        }

        .vet-card {
            border: 1px solid #ddd;
            border-radius: 12px;
            padding: 22px;
            background: #fff;
        }

        .vet-card h2 {
            margin-top: 0;
        }

        .vet-card p {
            line-height: 1.6;
            overflow-wrap: anywhere;
        }

        .vet-contact {
            display: inline-block;
            margin: 6px 8px 0 0;
        }
    </style>
</head>
<body>

<main class="section">
    <h1>Veterinary Care</h1>

    <p>
        Explore veterinarian profiles and find pet-care professionals.
        Contact details should be verified before relying on them.
    </p>

    <?php if (count($veterinarians) > 0): ?>
        <div class="vet-grid">
            <?php foreach ($veterinarians as $vet): ?>
                <article class="vet-card">
                    <h2><?= e($vet["full_name"]) ?></h2>

                    <p>
                        <strong>Speciality:</strong>
                        <?= e($vet["speciality"]) ?>
                    </p>

                    <p>
                        <strong>Clinic:</strong>
                        <?= e($vet["clinic_name"]) ?>
                    </p>

                    <p>
                        <strong>City:</strong>
                        <?= e($vet["city"]) ?>
                    </p>

                    <?php if (!empty($vet["description"])): ?>
                        <p><?= e($vet["description"]) ?></p>
                    <?php endif; ?>

                    <p>
                        <strong>Phone:</strong>
                        <?= e($vet["phone"]) ?>
                    </p>

                    <?php if (!empty($vet["email"])): ?>
                        <p>
                            <strong>Email:</strong>
                            <?= e($vet["email"]) ?>
                        </p>
                    <?php endif; ?>

                    <a
                        class="vet-contact"
                        href="tel:<?= e(preg_replace('/[^0-9+]/', '', $vet["phone"])) ?>"
                    >Call clinic</a>

                    <?php if (!empty($vet["email"])): ?>
                        <a
                            class="vet-contact"
                            href="mailto:<?= e($vet["email"]) ?>"
                        >Email</a>
                    <?php endif; ?>
          
<p>
    <a href="book_appointment.php" class="btn">
        Request an Appointment
    </a>
</p>
                </article>
             
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p>No veterinarian profiles are available yet.</p>
    <?php endif; ?>

    <p><a href="index.html">Back to Home</a></p>
</main>

</body>
</html>
