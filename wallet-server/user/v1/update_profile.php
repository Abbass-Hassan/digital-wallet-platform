<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once("../../connection/db.php");
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "User not logged in."]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$user_id = $_SESSION['user_id'];

try {
    $query = "UPDATE user_profiles SET full_name = :full_name, date_of_birth = :date_of_birth, 
              phone_number = :phone_number, street_address = :street_address, city = :city, 
              country = :country WHERE user_id = :user_id";

    $stmt = $conn->prepare($query);
    
    $stmt->bindParam(":full_name", $data["full_name"]);
    $stmt->bindParam(":date_of_birth", $data["date_of_birth"]);
    $stmt->bindParam(":phone_number", $data["phone_number"]);
    $stmt->bindParam(":street_address", $data["street_address"]);
    $stmt->bindParam(":city", $data["city"]);
    $stmt->bindParam(":country", $data["country"]);
    $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Profile updated successfully!"]);
    } else {
        echo json_encode(["success" => false, "message" => "Error updating profile."]);
    }
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>
