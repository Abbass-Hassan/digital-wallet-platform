<?php
header("Content-Type: application/json");
require_once __DIR__ . '/../../../connection/db.php';
session_start();

$response = ["status" => "error", "message" => "Something went wrong"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];

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

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    try {
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = :email");
        $check_stmt->bindParam(':email', $email);
        $check_stmt->execute();

        if ($check_stmt->rowCount() > 0) {
            $response["message"] = "Email is already registered";
        } else {
            $stmt = $conn->prepare("INSERT INTO users (email, password, role) VALUES (:email, :password, 0)");
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $hashed_password);

            if ($stmt->execute()) {
                $user_id = $conn->lastInsertId(); // Get new user ID

                // ✅ Insert empty values instead of NULL
                $profile_stmt = $conn->prepare("
                    INSERT INTO user_profiles (user_id, full_name, date_of_birth, phone_number, street_address, city, country) 
                    VALUES (:user_id, '', NULL, '', '', '', '')
                ");
                $profile_stmt->bindParam(':user_id', $user_id);
                $profile_stmt->execute();

                $_SESSION["user_id"] = $user_id;
                $_SESSION["user_email"] = $email;
                $_SESSION["user_role"] = 0;

                setcookie("user_id", $user_id, time() + 86400, "/");
                setcookie("user_email", $email, time() + 86400, "/");
                setcookie("user_role", 0, time() + 86400, "/");

                $response = ["status" => "success", "message" => "Registration successful"];
            } else {
                $response["message"] = "Database error: Unable to register";
            }
        }
    } catch (PDOException $e) {
        $response["message"] = "Database error: " . $e->getMessage();
    }
}

echo json_encode($response);
?>
