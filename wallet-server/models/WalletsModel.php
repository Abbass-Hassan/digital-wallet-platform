<?php
require_once __DIR__ . '/../connection/db.php';

class WalletsModel
{
    private $conn;

    public function __construct()
    {
        // Use the PDO instance from db.php
        global $conn;
        $this->conn = $conn;
    }

    // CREATE
    public function create($user_id, $balance)
    {
        $sql = "INSERT INTO wallets (user_id, balance, created_at, updated_at)
                VALUES (:user_id, :balance, NOW(), NOW())";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':balance', $balance);
        $stmt->execute();
        return $this->conn->lastInsertId();
    }

    // READ - Single by Wallet ID
    public function getWalletById($id)
    {
        $sql = "SELECT * FROM wallets WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // READ - Single by User ID
    public function getWalletByUserId($user_id)
    {
        $sql = "SELECT * FROM wallets WHERE user_id = :user_id LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // READ - All
    public function getAllWallets()
    {
        $sql = "SELECT * FROM wallets";
        $stmt = $this->conn->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // UPDATE
    public function update($id, $user_id, $balance)
    {
        $sql = "UPDATE wallets
                SET user_id = :user_id,
                    balance = :balance,
                    updated_at = NOW()
                WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':balance', $balance);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    // DELETE
    public function delete($id)
    {
        $sql = "DELETE FROM wallets WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
}
