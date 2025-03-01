<?php
header("Content-Type: application/json");
require_once __DIR__ . '/../../../connection/db.php';
session_start();

$response = ["status" => "error", "message" => "Something went wrong"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];

    // Basic validation
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
        // Check if the email is already registered
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = :email");
        $check_stmt->bindParam(':email', $email);
        $check_stmt->execute();

        if ($check_stmt->rowCount() > 0) {
            $response["message"] = "Email is already registered";
        } else {
            // Insert new user (role=0 means normal user)
            $stmt = $conn->prepare("
                INSERT INTO users (email, password, role) 
                VALUES (:email, :password, 0)
            ");
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $hashed_password);

            if ($stmt->execute()) {
                $user_id = $conn->lastInsertId(); // Get the new user ID

                // Extract the portion of the email before the @ to use as the default full_name
                $fullName = explode('@', $email)[0];

                // Insert default values for user_profiles, using $fullName
                $profile_stmt = $conn->prepare("
                    INSERT INTO user_profiles (
                        user_id, 
                        full_name, 
                        date_of_birth, 
                        phone_number, 
                        street_address, 
                        city, 
                        country
                    ) 
                    VALUES (
                        :user_id, 
                        :full_name, 
                        NULL, 
                        '', 
                        '', 
                        '', 
                        ''
                    )
                ");
                $profile_stmt->bindParam(':user_id', $user_id);
                $profile_stmt->bindParam(':full_name', $fullName);
                $profile_stmt->execute();

                // Create a wallet record for the new user with 0 balance
                $wallet_stmt = $conn->prepare("
                    INSERT INTO wallets (user_id, balance) 
                    VALUES (:user_id, 0.00)
                ");
                $wallet_stmt->bindParam(':user_id', $user_id);
                $wallet_stmt->execute();

                // Create a verifications record with is_validated = 0 and id_document as NULL
                $verification_stmt = $conn->prepare("
                    INSERT INTO verifications (user_id, id_document, is_validated, verification_note)
                    VALUES (:user_id, NULL, 0, 'User not verified yet')
                ");
                $verification_stmt->bindParam(':user_id', $user_id);
                $verification_stmt->execute();

                // Set session and cookies
                $_SESSION["user_id"] = $user_id;
                $_SESSION["user_email"] = $email;
                $_SESSION["user_role"] = 0; // Normal user role

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
