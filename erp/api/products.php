<?php
/**
 * API Produits (Stock)
 * Gestion des produits via AJAX
 */

require_once __DIR__ . '/../../crm/config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

$customer_id = $_SESSION['customer_id'] ?? null;

if (!$customer_id) {
    http_response_code(401);
    echo json_encode(['error' => 'Non authentifié']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

try {
    switch ($action) {
        case 'fetch':
        case 'list':
            // Récupérer tous les produits du client
            $stmt = $pdo->prepare("
                SELECT id, product_name as name, price, quantity, category, supplier
                FROM erp_stock 
                WHERE customer_id = ? 
                ORDER BY product_name
            ");
            $stmt->execute([$customer_id]);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode($products);
            break;

        case 'create':
            // Créer un nouveau produit
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Méthode non autorisée');
            }

            $product_name = trim($_POST['product_name'] ?? '');
            $price = isset($_POST['price']) ? (float)$_POST['price'] : 0.0;
            $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;
            $category = trim($_POST['category'] ?? 'Divers');
            $supplier = trim($_POST['supplier'] ?? '');

            if (empty($product_name)) {
                throw new Exception('Le nom du produit est requis');
            }

            $stmt = $pdo->prepare("
                INSERT INTO erp_stock (
                    product_name, price, quantity, category, supplier, 
                    customer_id, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $product_name,
                $price,
                $quantity,
                $category,
                $supplier,
                $customer_id
            ]);

            $newId = (int)$pdo->lastInsertId();

            echo json_encode([
                'success' => true,
                'id' => $newId,
                'message' => 'Produit créé avec succès'
            ]);
            break;

        case 'get':
            // Récupérer un produit par ID
            $id = (int)($_GET['id'] ?? 0);
            
            $stmt = $pdo->prepare("
                SELECT * FROM erp_stock 
                WHERE id = ? AND customer_id = ?
            ");
            $stmt->execute([$id, $customer_id]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$product) {
                http_response_code(404);
                echo json_encode(['error' => 'Produit non trouvé']);
                exit;
            }

            echo json_encode($product);
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Action non reconnue']);
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
