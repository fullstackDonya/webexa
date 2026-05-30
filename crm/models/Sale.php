<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

class Sale {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function createSale(array $data): int {
        $stmt = $this->pdo->prepare('INSERT INTO erp_sales (product_id, quantity, total_price, sale_date) VALUES (?, ?, ?, NOW())');
        $stmt->execute([$data['product_id'], $data['quantity'], $data['total_price']]);
        return (int)$this->pdo->lastInsertId();
    }

    public function getSales(): array {
        $stmt = $this->pdo->query('SELECT * FROM erp_sales ORDER BY sale_date DESC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getSaleById(int $id): ?array {
        $stmt = $this->pdo->prepare('SELECT * FROM erp_sales WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function updateSale(int $id, array $data): bool {
        $stmt = $this->pdo->prepare('UPDATE erp_sales SET product_id = ?, quantity = ?, total_price = ? WHERE id = ?');
        return $stmt->execute([$data['product_id'], $data['quantity'], $data['total_price'], $id]);
    }

    public function deleteSale(int $id): bool {
        $stmt = $this->pdo->prepare('DELETE FROM erp_sales WHERE id = ?');
        return $stmt->execute([$id]);
    }
}
?>