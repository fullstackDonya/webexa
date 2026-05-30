<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

class Stock {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function create(string $product_name, float $quantity, float $price): int {
        $stmt = $this->pdo->prepare("INSERT INTO erp_stocks (product_name, quantity, price, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$product_name, $quantity, $price]);
        return (int)$this->pdo->lastInsertId();
    }

    public function read(int $id): ?array {
        $stmt = $this->pdo->prepare("SELECT * FROM erp_stocks WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function update(int $id, string $product_name, float $quantity, float $price): bool {
        $stmt = $this->pdo->prepare("UPDATE erp_stocks SET product_name = ?, quantity = ?, price = ? WHERE id = ?");
        return $stmt->execute([$product_name, $quantity, $price, $id]);
    }

    public function delete(int $id): bool {
        $stmt = $this->pdo->prepare("DELETE FROM erp_stocks WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function list(): array {
        $stmt = $this->pdo->query("SELECT * FROM erp_stocks ORDER BY created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>