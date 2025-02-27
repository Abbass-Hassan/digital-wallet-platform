<?php
session_start();
header("Content-Type: application/json");
require_once __DIR__ . '/../../../connection/db.php';

$response = ["status" => "error", "message" => "Something went wrong"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    try {
        $stmt = $conn->prepare("SELECT id, email, password, role FROM users WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && $user['role'] == 1) {
            if (password_verify($password, $user['password'])) {
                $_SESSION["admin_id"] = $user["id"];
                $_SESSION["admin_email"] = $user["email"];

                $response = ["status" => "success", "message" => "Login successful"];
            } else {
                $response["message"] = "Invalid email or password";
            }
        } else {
            $response["message"] = "Access denied. Only admins can log in.";
        }
    } catch (PDOException $e) {
        $response["message"] = "Database error: " . $e->getMessage();
    }
}

echo json_encode($response);
?>
