INSERT INTO stock (product_name, quantity, price, created_at) VALUES
('Produit A', 100, 10.00, NOW()),
('Produit B', 200, 15.50, NOW()),
('Produit C', 150, 7.25, NOW());

INSERT INTO inventory (item_name, quantity, location, created_at) VALUES
('Article 1', 50, 'Entrepôt A', NOW()),
('Article 2', 75, 'Entrepôt B', NOW()),
('Article 3', 30, 'Entrepôt C', NOW());

INSERT INTO sales (product_id, quantity_sold, sale_price, sale_date) VALUES
(1, 5, 10.00, NOW()),
(2, 3, 15.50, NOW()),
(3, 10, 7.25, NOW());