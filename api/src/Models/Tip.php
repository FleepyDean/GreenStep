<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class Tip
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    // Get all tips (Admin)
    public function getAll()
    {
        $stmt = $this->db->query("
            SELECT * FROM Tip
            ORDER BY created_at DESC
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get random tip (User)
    public function getRandom()
    {
        $stmt = $this->db->query("
            SELECT *
            FROM Tip
            ORDER BY RAND()
            LIMIT 1
        ");

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Add tip
    public function create($title, $body, $category, $source_url)
    {
        $stmt = $this->db->prepare("
            INSERT INTO Tip(title,body,category,source_url)
            VALUES(?,?,?,?)
        ");

        return $stmt->execute([
            $title,
            $body,
            $category,
            $source_url
        ]);
    }

    // Delete tip
    public function delete($id)
    {
        $stmt = $this->db->prepare("
            DELETE FROM Tip
            WHERE id=?
        ");

        return $stmt->execute([$id]);
    }
}