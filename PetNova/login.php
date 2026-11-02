
<?php
session_start();
require_once "db.php";

$message = "";

// Redirect already logged-in users
if (isset($_SESSION["user_id"])) {
    header("Location: dashboard.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === "") {

        $message = "Please enter a valid email and password.";

    } else {

        $stmt = $pdo->prepare(
            "SELECT id, full_name, email, password_hash, role
             FROM users
             WHERE email = ?
             LIMIT 1"
        );

        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user["password_hash"])) {

            session_regenerate_id(true);

            // Store user information in session
            $_SESSION["user_id"] = $user["id"];
            $_SESSION["full_name"] = $user["full_name"];
            $_SESSION["user_role"] = $user["role"];

            header("Location: dashboard.php");
            exit;

        } else {

            $message = "Incorrect email or password.";

        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>PetNova | Login</title>

    <link rel="stylesheet" href="style.css">
</head>

<body>

    <main class="section">

        <h2>Login to PetNova</h2>

        <p>Sign in to access your PetNova account.</p>

        <?php if ($message !== ""): ?>

            <p role="alert">
                <?= htmlspecialchars(
                    $message,
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>
            </p>

        <?php endif; ?>

        <form class="feature-form" method="POST" action="login.php">

            <label for="email">Email</label>

            <input
                type="email"
                id="email"
                name="email"
                autocomplete="email"
                required
            >

            <label for="password">Password</label>

            <input
                type="password"
                id="password"
                name="password"
                autocomplete="current-password"
                required
            >

            <button type="submit">Login</button>

        </form>

        <p>
            Don't have an account?
            <a href="register.php">Register here</a>
        </p>

        <p>
            <a href="index.html">Back to PetNova</a>
        </p>

    </main>

</body>
</html>
