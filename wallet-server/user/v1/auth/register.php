<?php
header("Content-Type: application/json");
session_start();

// Include the DB connection and the models
require_once __DIR__ . '/../../../connection/db.php';
require_once __DIR__ . '/../../../models/UsersModel.php';
require_once __DIR__ . '/../../../models/UserProfilesModel.php';
require_once __DIR__ . '/../../../models/WalletsModel.php';
require_once __DIR__ . '/../../../models/VerificationsModel.php';

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
        // Initialize the models
        $usersModel = new UsersModel();
        $userProfilesModel = new UserProfilesModel();
        $walletsModel = new WalletsModel();
        $verificationsModel = new VerificationsModel();

        // Check if the email is already registered
        $allUsers = $usersModel->getAllUsers();
        $emailExists = false;
        foreach ($allUsers as $u) {
            if ($u['email'] === $email) {
                $emailExists = true;
                break;
            }
        }

        if ($emailExists) {
            $response["message"] = "Email is already registered";
        } else {
            // Create new user (role=0 means normal user)
            $user_id = $usersModel->create($email, $hashed_password, 0);

            // Extract the portion of the email before the @ to use as the default full_name
            $fullName = explode('@', $email)[0];

            // Insert default profile
            $userProfilesModel->create(
                $user_id,
                $fullName,
                null,   // date_of_birth
                '',     // phone_number
                '',     // street_address
                '',     // city
                ''      // country
            );

            // Create a wallet record for the new user with 0 balance
            $walletsModel->create($user_id, 0.00);

            // Create a verifications record with is_validated = 0 and id_document as NULL
            $verificationsModel->create(
                $user_id,
                null,   // id_document
                0,      // is_validated
                'User not verified yet'
            );

            // Set session and cookies
            $_SESSION["user_id"] = $user_id;
            $_SESSION["user_email"] = $email;
            $_SESSION["user_role"] = 0; // Normal user role

            setcookie("user_id", $user_id, time() + 86400, "/");
            setcookie("user_email", $email, time() + 86400, "/");
            setcookie("user_role", 0, time() + 86400, "/");

            $response = ["status" => "success", "message" => "Registration successful"];
        }
    } catch (PDOException $e) {
        $response["message"] = "Database error: " . $e->getMessage();
    }
}

echo json_encode($response);
