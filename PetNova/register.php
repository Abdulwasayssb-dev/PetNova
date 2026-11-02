
<?php
session_start();
require_once "db.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["full_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $role = $_POST["role"] ?? "buyer";

    if (
        $name === "" ||
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        strlen($password) < 8 ||
        !in_array($role, ["buyer", "seller"], true)
    ) {
        $message = "Enter a valid name and email, use a password of at least 8 characters, and select a valid role.";
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        try {
            $stmt = $pdo->prepare(
                "INSERT INTO users (full_name, email, password_hash, role)
                 VALUES (?, ?, ?, ?)"
            );

            $stmt->execute([$name, $email, $hash, $role]);

            $message = "Registration successful! You can now log in.";
        } catch (PDOException $e) {
            if ($e->getCode() === "23000") {
                $message = "This email is already registered.";
            } else {
                error_log($e->getMessage());
                $message = "Registration failed. Please try again.";
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
    <title>PetNova | Register</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <main class="section">
        <h2>Create Your PetNova Account</h2>

        <?php if ($message !== ""): ?>
            <p role="status"><?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?></p>
        <?php endif; ?>

        <form class="feature-form" method="POST" action="register.php">
            <label for="full_name">Full Name</label>
            <input type="text" id="full_name" name="full_name"
                   maxlength="100" autocomplete="name" required>

            <label for="email">Email</label>
            <input type="email" id="email" name="email"
                   maxlength="150" autocomplete="email" required>

            <label for="password">Password</label>
            <input type="password" id="password" name="password"
                   minlength="8" autocomplete="new-password" required>

            <label for="role">Account Type</label>
            <select id="role" name="role" required>
                <option value="buyer">Buyer</option>
                <option value="seller">Seller</option>
            </select>

            <button type="submit">Register</button>
        </form>

        <p><a href="index.html">Back to PetNova</a></p>
    </main>
</body>
</html>
