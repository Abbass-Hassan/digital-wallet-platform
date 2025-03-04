<?php
require_once __DIR__ . '/../connection/db.php';

class TransactionsModel
{
    private $conn;

    public function __construct()
    {
        // Use the PDO instance from db.php
        global $conn;
        $this->conn = $conn;
    }

    // CREATE
    public function create($sender_id, $recipient_id, $type, $amount)
    {
        $sql = "INSERT INTO transactions (sender_id, recipient_id, transaction_type, amount, created_at)
                VALUES (:sender_id, :recipient_id, :type, :amount, NOW())";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':sender_id', $sender_id);
        $stmt->bindParam(':recipient_id', $recipient_id);
        $stmt->bindParam(':type', $type);
        $stmt->bindParam(':amount', $amount);
        $stmt->execute();
        return $this->conn->lastInsertId();
    }

    // READ - Single by Transaction ID
    public function getTransactionById($id)
    {
        $sql = "SELECT * FROM transactions WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // READ - All Transactions
    public function getAllTransactions()
    {
        $sql = "SELECT * FROM transactions";
        $stmt = $this->conn->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // READ - Transactions by Sender ID
    public function getTransactionsBySenderId($sender_id)
    {
        $sql = "SELECT * FROM transactions WHERE sender_id = :sender_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':sender_id', $sender_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // READ - Transactions by Recipient ID
    public function getTransactionsByRecipientId($recipient_id)
    {
        $sql = "SELECT * FROM transactions WHERE recipient_id = :recipient_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':recipient_id', $recipient_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // UPDATE
    public function update($id, $sender_id, $recipient_id, $type, $amount)
    {
        $sql = "UPDATE transactions
                SET sender_id = :sender_id,
                    recipient_id = :recipient_id,
                    transaction_type = :type,
                    amount = :amount
                WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':sender_id', $sender_id);
        $stmt->bindParam(':recipient_id', $recipient_id);
        $stmt->bindParam(':type', $type);
        $stmt->bindParam(':amount', $amount);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    // DELETE
    public function delete($id)
    {
        $sql = "DELETE FROM transactions WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
}
