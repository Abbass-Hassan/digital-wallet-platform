<?php
header("Content-Type: application/json");
require_once __DIR__ . '/../../connection/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

$userId = $_SESSION['user_id'];

$date = isset($_GET['date']) ? $_GET['date'] : null;
$type = isset($_GET['type']) ? $_GET['type'] : null;

try {
    $whereClauses = [];
    $params = [];

    // Must match rows where user is sender OR recipient
    $whereClauses[] = "(t.sender_id = :userId OR t.recipient_id = :userId)";
    $params[':userId'] = $userId;

    // If type is given, filter by it
    if ($type && in_array($type, ['deposit','withdrawal','transfer'])) {
        $whereClauses[] = "t.transaction_type = :type";
        $params[':type'] = $type;
    }

    // If date is given, filter by that single date
    if ($date) {
        $whereClauses[] = "DATE(t.created_at) = :date";
        $params[':date'] = $date;
    }

    $whereSQL = implode(" AND ", $whereClauses);

    // We'll LEFT JOIN the users table twice:
    //   s.* for the sender
    //   r.* for the recipient
    $sql = "SELECT 
                t.id,
                t.sender_id,
                s.email AS sender_email,
                t.recipient_id,
                r.email AS recipient_email,
                t.amount,
                t.transaction_type,
                t.created_at
            FROM transactions t
            LEFT JOIN users s ON t.sender_id = s.id
            LEFT JOIN users r ON t.recipient_id = r.id
            WHERE $whereSQL
            ORDER BY t.created_at DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["transactions" => $transactions, "userId" => $userId]);
} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
}

$conn = null;
?>
