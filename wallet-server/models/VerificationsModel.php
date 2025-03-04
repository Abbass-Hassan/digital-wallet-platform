<?php
require_once __DIR__ . '/../connection/db.php';

class VerificationsModel
{
    private $conn;

    public function __construct()
    {
        // Use the PDO instance from db.php
        global $conn;
        $this->conn = $conn;
    }

    // CREATE
    public function create($user_id, $id_document, $is_validated, $verification_note)
    {
        $sql = "INSERT INTO verifications (
                    user_id, id_document, is_validated, verification_note, created_at
                ) VALUES (
                    :user_id, :id_document, :is_validated, :verification_note, NOW()
                )";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':id_document', $id_document);
        $stmt->bindParam(':is_validated', $is_validated);
        $stmt->bindParam(':verification_note', $verification_note);
        $stmt->execute();
        return $this->conn->lastInsertId();
    }

    // READ - Single by Verification ID
    public function getVerificationById($id)
    {
        $sql = "SELECT * FROM verifications WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // READ - Single by User ID
    public function getVerificationByUserId($user_id)
    {
        $sql = "SELECT * FROM verifications WHERE user_id = :user_id LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // READ - All
    public function getAllVerifications()
    {
        $sql = "SELECT * FROM verifications";
        $stmt = $this->conn->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // READ - Pending Verifications (not validated)
    public function getPendingVerifications()
    {
        $sql = "SELECT * FROM verifications WHERE is_validated = 0";
        $stmt = $this->conn->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // UPDATE
    public function update($id, $user_id, $id_document, $is_validated, $verification_note)
    {
        $sql = "UPDATE verifications
                SET user_id = :user_id,
                    id_document = :id_document,
                    is_validated = :is_validated,
                    verification_note = :verification_note
                WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':id_document', $id_document);
        $stmt->bindParam(':is_validated', $is_validated);
        $stmt->bindParam(':verification_note', $verification_note);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    // DELETE
    public function delete($id)
    {
        $sql = "DELETE FROM verifications WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
}
?>
