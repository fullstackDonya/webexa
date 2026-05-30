<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

class erp_Inventory {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function create(string $name, int $quantity, string $description): int {
        $stmt = $this->pdo->prepare("INSERT INTO erp_inventory (name, quantity, description, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$name, $quantity, $description]);
        return (int)$this->pdo->lastInsertId();
    }

    public function read(int $id): ?array {
        $stmt = $this->pdo->prepare("SELECT * FROM erp_inventory WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function update(int $id, string $name, int $quantity, string $description): bool {
        $stmt = $this->pdo->prepare("UPDATE erp_inventory SET name = ?, quantity = ?, description = ? WHERE id = ?");
        return $stmt->execute([$name, $quantity, $description, $id]);
    }

    public function delete(int $id): bool {
        $stmt = $this->pdo->prepare("DELETE FROM erp_inventory WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function list(): array {
        $stmt = $this->pdo->query("SELECT * FROM erp_inventory ORDER BY created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>