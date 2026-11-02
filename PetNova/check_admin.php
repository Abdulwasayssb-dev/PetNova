
<?php
session_start();
require_once "db.php";

echo "<h2>PetNova Admin Session Check</h2>";

echo "<p>User ID: ";
var_dump($_SESSION["user_id"] ?? "NOT FOUND");
echo "</p>";

echo "<p>User Role: ";
var_dump($_SESSION["user_role"] ?? "NOT FOUND");
echo "</p>";

if (isset($_SESSION["user_id"])) {

    $stmt = $pdo->prepare(
        "SELECT id, full_name, email, role
         FROM users
         WHERE id = ?"
    );

    $stmt->execute([$_SESSION["user_id"]]);
    $user = $stmt->fetch();

    echo "<h3>Database Account Information</h3>";

    echo "<pre>";
    print_r($user);
    echo "</pre>";

} else {
    echo "<p>Please log in first.</p>";
}
?>
