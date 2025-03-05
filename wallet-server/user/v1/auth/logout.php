<?php
header("Content-Type: application/json");

// Since JWT is stateless, there's no session to destroy.
// The client should remove the token from localStorage or cookies.
// We'll just return a message indicating the user can consider themselves logged out.

$response = [
    "status" => "success",
    "message" => "Logout successful. Please remove your token client-side."
];

echo json_encode($response);
