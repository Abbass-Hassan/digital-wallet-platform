<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "User not logged in."]);
    exit;
}

include_once("../../connection/db.php");
$user_id = $_SESSION['user_id'];

try {
    // Fetch user profile fields + the tier from the 'users' table.
    // Adjust the column names and table names if yours differ.
    $query = "SELECT 
                up.full_name, 
                up.date_of_birth, 
                up.phone_number, 
                up.street_address, 
                up.city, 
                up.country, 
                u.tier
              FROM user_profiles AS up
              JOIN users AS u ON up.user_id = u.id
              WHERE up.user_id = :user_id";

    $stmt = $conn->prepare($query);
    $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode(["success" => true, "user" => $user]);
    } else {
        echo json_encode(["success" => false, "message" => "User profile not found."]);
    }
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>
