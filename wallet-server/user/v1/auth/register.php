<?php
header("Content-Type: application/json"); // Set JSON response header
require_once __DIR__ . '/../../../connection/db.php'; // Include database connection
session_start(); // Start session

$response = ["status" => "error", "message" => "Something went wrong"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];

    // 🔍 Validate inputs
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response["message"] = "Invalid email format";
        echo json_encode($response);
        exit;
    }
    if (strlen($password) < 6) {
        $response["message"] = "Password must be at least 6 characters";
        echo json_encode($response);
        exit;
    }
    if ($password !== $confirm_password) {
        $response["message"] = "Passwords do not match";
        echo json_encode($response);
        exit;
    }

    // 🔐 Hash the password securely
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    try {
        // 🚀 Check if the email already exists
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = :email");
        $check_stmt->bindParam(':email', $email);
        $check_stmt->execute();

        if ($check_stmt->rowCount() > 0) {
            $response["message"] = "Email is already registered";
        } else {
            // 🚀 Insert new user into database
            $stmt = $conn->prepare("INSERT INTO users (email, password, role, is_validated) VALUES (:email, :password, 0, 0)");
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $hashed_password);

            if ($stmt->execute()) {
                $user_id = $conn->lastInsertId(); // Get the last inserted user ID
                $_SESSION["user_id"] = $user_id; // Store user ID in session
                
                $response = ["status" => "success", "message" => "Registration successful"];
            } else {
                $response["message"] = "Database error: Unable to register";
            }
        }
    } catch (PDOException $e) {
        $response["message"] = "Database error: " . $e->getMessage();
    }
}

// ✅ Return JSON response
echo json_encode($response);
?>
