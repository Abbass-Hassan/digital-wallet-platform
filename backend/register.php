<?php

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Invalid email format.");
    }

    if (empty($password) || strlen($password) < 6) {
        die("Password must be at least 6 characters long.");
    }

    if ($password !== $confirm_password) {
        die("Passwords do not match.");
    }

    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        die("Email is already registered.");
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("INSERT INTO users (email, password) VALUES (?, ?)");
    if ($stmt->execute([$email, $hashedPassword])) {
        echo "Registration successful!";
    } else {
        echo "Error during registration. Please try again.";
    }
} else {
    echo "Invalid request method.";
}
?>
