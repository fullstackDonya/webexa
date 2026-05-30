<?php
/**
 * InvoiceGenerator - Générateur de factures conformes aux lois françaises 2026
 * 
 * Conformité:
 * - Article 242 nonies A du CGI (numérotation séquentielle)
 * - Décret 2022-1299 (facturation électronique)
 * - Mentions obligatoires (pénalités de retard, escompte, etc.)
 * - Conservation 10 ans obligatoire
 * - Format Factur-X (PDF + XML EN 16931)
 * 
 * @author  Webitech CRM
 * @version 1.0
 * @date    2026-02-20
 */

class InvoiceGenerator {
    private PDO $pdo;
    private int $customerId;
    private array $companyInfo;
    
    // Taux de TVA français en vigueur
    const VAT_RATES = [
        'normal' => 20.00,        // Taux normal
        'intermediate' => 10.00,  // Taux intermédiaire
        'reduced' => 5.50,        // Taux réduit
        'super_reduced' => 2.10,  // Taux super-réduit
        'exempt' => 0.00          // Exonéré
    ];
    
    // Mentions légales obligatoires
    const LATE_FEE_RATE = 10.00;      // Taux de pénalités de retard (%)
    const RECOVERY_INDEMNITY = 40.00;  // Indemnité forfaitaire de recouvrement (€)
    
    public function __construct(PDO $pdo, int $customerId) {
        $this->pdo = $pdo;
        $this->customerId = $customerId;
        $this->loadCompanyInfo();
    }
    
    /**
     * Charger les informations de l'entreprise émettrice
     */
    private function loadCompanyInfo(): void {
        $stmt = $this->pdo->prepare("
            SELECT name, email, phone, address, city, postal_code, country, 
                   siren, siret, naf, vat_number, capital
            FROM customers 
            WHERE id = ? 
            LIMIT 1
        ");
        $stmt->execute([$this->customerId]);
        $this->companyInfo = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }
    
    /**
     * Générer un nouveau numéro de facture séquentiel
     * Format: FA2026-0001, FA2026-0002, etc.
     * 
     * @param string $prefix Préfixe (FA, FACT, INV, etc.)
     * @return string Numéro de facture unique
     * @throws Exception Si échec de génération
     */
    public function generateInvoiceNumber(string $prefix = 'FA'): string {
        // Vérifier si une transaction est déjà active
        $transactionStarted = false;
        if (!$this->pdo->inTransaction()) {
            $this->pdo->beginTransaction();
            $transactionStarted = true;
        }
        
        try {
            $year = date('Y');
            
            // Vérifier si une séquence existe pour cette année
            $stmt = $this->pdo->prepare("
                SELECT id, current_number, number_format, padding, separator_char
                FROM erp_invoice_sequences
                WHERE customer_id = ? AND invoice_year = ?
                FOR UPDATE
            ");
            $stmt->execute([$this->customerId, $year]);
            $sequence = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$sequence) {
                // Créer une nouvelle séquence pour cette année
                $stmt = $this->pdo->prepare("
                    INSERT INTO erp_invoice_sequences 
                    (customer_id, invoice_year, prefix, current_number, number_format, padding, separator_char)
                    VALUES (?, ?, ?, 0, '{PREFIX}{YEAR}{SEP}{NUMBER}', 4, '-')
                ");
                $stmt->execute([$this->customerId, $year, $prefix]);
                $sequence = [
                    'id' => $this->pdo->lastInsertId(),
                    'current_number' => 0,
                    'number_format' => '{PREFIX}{YEAR}{SEP}{NUMBER}',
                    'padding' => 4,
                    'separator_char' => '-'
                ];
            }
            
            // Incrémenter le numéro
            $nextNumber = $sequence['current_number'] + 1;
            
            // Mettre à jour la séquence
            $stmt = $this->pdo->prepare("
                UPDATE erp_invoice_sequences
                SET current_number = ?,
                    last_invoice_date = CURDATE()
                WHERE id = ?
            ");
            $stmt->execute([$nextNumber, $sequence['id']]);
            
            // Générer le numéro formaté
            $paddedNumber = str_pad($nextNumber, $sequence['padding'], '0', STR_PAD_LEFT);
            $invoiceNumber = str_replace(
                ['{PREFIX}', '{YEAR}', '{SEP}', '{NUMBER}'],
                [$prefix, $year, $sequence['separator_char'], $paddedNumber],
                $sequence['number_format']
            );
            
            // Commit uniquement si on a démarré la transaction
            if ($transactionStarted) {
                $this->pdo->commit();
            }
            
            return $invoiceNumber;
            
        } catch (Exception $e) {
            // Rollback uniquement si on a démarré la transaction
            if ($transactionStarted) {
                $this->pdo->rollBack();
            }
            throw new Exception("Erreur génération numéro de facture: " . $e->getMessage());
        }
    }
    
    /**
     * Créer une nouvelle facture à partir d'une vente
     * 
     * @param int $saleId ID de la vente
     * @param array $options Options supplémentaires
     * @return int ID de la facture créée
     */
    public function createInvoiceFromSale(int $saleId, array $options = []): int {
        try {
            $this->pdo->beginTransaction();
            
            // Récupérer les détails de la vente
            $stmt = $this->pdo->prepare("
                SELECT s.*, 
                       p.product_name, p.price as unit_price,
                       c.name as client_name, c.email as client_email,
                       c.address, c.city, c.postal_code, c.country,
                       c.siret as client_siret, c.vat_number as client_vat_number
                FROM erp_sales s
                LEFT JOIN erp_stock p ON p.id = s.product_id
                LEFT JOIN companies c ON c.id = s.customer_id
                WHERE s.id = ? AND s.customer_id = ?
            ");
            $stmt->execute([$saleId, $this->customerId]);
            $sale = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$sale) {
                throw new Exception("Vente non trouvée");
            }
            
            // Vérifier si une facture existe déjà pour cette vente
            $stmt = $this->pdo->prepare("SELECT invoice_id FROM erp_sales WHERE id = ?");
            $stmt->execute([$saleId]);
            $existingInvoice = $stmt->fetchColumn();
            
            if ($existingInvoice) {
                throw new Exception("Une facture existe déjà pour cette vente (ID: $existingInvoice)");
            }
            
            // Générer le numéro de facture
            $invoiceNumber = $this->generateInvoiceNumber();
            
            // Déterminer le taux de TVA (par défaut 20%)
            $vatRate = $options['vat_rate'] ?? self::VAT_RATES['normal'];
            
            // Calculer les montants
            $totalHT = $sale['total_price'];
            $totalTVA = round($totalHT * $vatRate / 100, 2);
            $totalTTC = $totalHT + $totalTVA;
            
            // Préparer les détails de TVA
            $vatDetails = json_encode([[
                'rate' => $vatRate,
                'base_ht' => $totalHT,
                'amount' => $totalTVA
            ]]);
            
            // Créer la facture
            $stmt = $this->pdo->prepare("
                INSERT INTO erp_invoices (
                    customer_id, invoice_number, invoice_type,
                    issue_date, due_date, delivery_date,
                    client_type, client_name, client_email,
                    client_address, client_city, client_postal_code, client_country,
                    client_siret, client_vat_number,
                    total_ht, total_tva, total_ttc, currency,
                    vat_details,
                    payment_terms, payment_method, payment_status,
                    late_fee_rate, recovery_indemnity,
                    sale_id, status, created_by
                ) VALUES (
                    ?, ?, 'sale',
                    CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), CURDATE(),
                    'company', ?, ?,
                    ?, ?, ?, ?,
                    ?, ?,
                    ?, ?, ?, 'EUR',
                    ?,
                    'Paiement à 30 jours', 'transfer', 'unpaid',
                    ?, ?,
                    ?, 'draft', NULL
                )
            ");
            
            $stmt->execute([
                $this->customerId,
                $invoiceNumber,
                $sale['client_name'] ?? 'Client',
                $sale['client_email'] ?? '',
                $sale['address'] ?? '',
                $sale['city'] ?? '',
                $sale['postal_code'] ?? '',
                $sale['country'] ?? 'France',
                $sale['client_siret'] ?? '',
                $sale['client_vat_number'] ?? '',
                $totalHT,
                $totalTVA,
                $totalTTC,
                $vatDetails,
                self::LATE_FEE_RATE,
                self::RECOVERY_INDEMNITY,
                $saleId
            ]);
            
            $invoiceId = $this->pdo->lastInsertId();
            
            // Créer la ligne de facture
            $stmt = $this->pdo->prepare("
                INSERT INTO erp_invoice_items (
                    invoice_id, item_order, description, product_id,
                    quantity, unit, unit_price_ht,
                    discount_rate, discount_amount,
                    total_ht, vat_rate, vat_amount, total_ttc
                ) VALUES (
                    ?, 1, ?, ?,
                    ?, 'unité', ?,
                    0, 0,
                    ?, ?, ?, ?
                )
            ");
            
            $stmt->execute([
                $invoiceId,
                $sale['product_name'] ?? 'Produit',
                $sale['product_id'],
                $sale['quantity'],
                $sale['unit_price'],
                $totalHT,
                $vatRate,
                $totalTVA,
                $totalTTC
            ]);
            
            // Mettre à jour la vente avec l'ID de la facture
            $stmt = $this->pdo->prepare("UPDATE erp_sales SET invoice_id = ? WHERE id = ?");
            $stmt->execute([$invoiceId, $saleId]);
            
            // Enregistrer dans l'audit
            $this->logAudit($invoiceId, 'created', null, [
                'invoice_number' => $invoiceNumber,
                'total_ttc' => $totalTTC
            ]);
            
            $this->pdo->commit();
            
            return $invoiceId;
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw new Exception("Erreur création facture: " . $e->getMessage());
        }
    }
    
    /**
     * Créer une facture manuelle avec lignes personnalisées
     * 
     * @param array $invoiceData Données de la facture
     * @param array $items Lignes de facture
     * @return int ID de la facture créée
     */
    public function createCustomInvoice(array $invoiceData, array $items): int {
        try {
            $this->pdo->beginTransaction();
            
            // Générer le numéro de facture
            $invoiceNumber = $this->generateInvoiceNumber();
            
            // Calculer les totaux depuis les lignes
            $totals = $this->calculateTotals($items);
            
            // Créer la facture
            $stmt = $this->pdo->prepare("
                INSERT INTO erp_invoices (
                    customer_id, invoice_number, invoice_type,
                    issue_date, due_date, delivery_date,
                    client_type, client_company_id, client_name, client_email,
                    client_address, client_city, client_postal_code, client_country,
                    client_siret, client_vat_number, client_phone,
                    total_ht, total_tva, total_ttc, currency,
                    vat_details,
                    payment_terms, payment_method, payment_status,
                    late_fee_rate, recovery_indemnity,
                    discount_terms, notes, client_notes,
                    status, created_by
                ) VALUES (
                    ?, ?, ?,
                    ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?,
                    ?, ?, ?, ?,
                    ?,
                    ?, ?, ?,
                    ?, ?,
                    ?, ?, ?,
                    'draft', ?
                )
            ");
            
            $stmt->execute([
                $this->customerId,
                $invoiceNumber,
                $invoiceData['invoice_type'] ?? 'sale',
                $invoiceData['issue_date'] ?? date('Y-m-d'),
                $invoiceData['due_date'] ?? date('Y-m-d', strtotime('+30 days')),
                $invoiceData['delivery_date'] ?? date('Y-m-d'),
                $invoiceData['client_type'] ?? 'company',
                $invoiceData['client_company_id'] ?? null,
                $invoiceData['client_name'],
                $invoiceData['client_email'] ?? '',
                $invoiceData['client_address'] ?? '',
                $invoiceData['client_city'] ?? '',
                $invoiceData['client_postal_code'] ?? '',
                $invoiceData['client_country'] ?? 'France',
                $invoiceData['client_siret'] ?? '',
                $invoiceData['client_vat_number'] ?? '',
                $invoiceData['client_phone'] ?? '',
                $totals['total_ht'],
                $totals['total_tva'],
                $totals['total_ttc'],
                'EUR',
                json_encode($totals['vat_details']),
                $invoiceData['payment_terms'] ?? 'Paiement à 30 jours',
                $invoiceData['payment_method'] ?? 'transfer',
                'unpaid',
                $invoiceData['late_fee_rate'] ?? self::LATE_FEE_RATE,
                $invoiceData['recovery_indemnity'] ?? self::RECOVERY_INDEMNITY,
                $invoiceData['discount_terms'] ?? null,
                $invoiceData['notes'] ?? null,
                $invoiceData['client_notes'] ?? null,
                $invoiceData['created_by'] ?? null
            ]);
            
            $invoiceId = $this->pdo->lastInsertId();
            
            // Créer les lignes de facture
            foreach ($items as $index => $item) {
                $this->addInvoiceItem($invoiceId, $item, $index + 1);
            }
            
            // Enregistrer dans l'audit
            $this->logAudit($invoiceId, 'created', null, [
                'invoice_number' => $invoiceNumber,
                'total_ttc' => $totals['total_ttc']
            ]);
            
            $this->pdo->commit();
            
            return $invoiceId;
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw new Exception("Erreur création facture personnalisée: " . $e->getMessage());
        }
    }
    
    /**
     * Ajouter une ligne à une facture
     */
    private function addInvoiceItem(int $invoiceId, array $item, int $order): void {
        // Calculer les montants de la ligne
        $quantity = $item['quantity'];
        $unitPriceHT = $item['unit_price_ht'];
        $discountRate = $item['discount_rate'] ?? 0;
        $vatRate = $item['vat_rate'];
        
        // Total HT avant remise
        $totalBeforeDiscount = $quantity * $unitPriceHT;
        
        // Remise
        $discountAmount = round($totalBeforeDiscount * $discountRate / 100, 2);
        
        // Total HT après remise
        $totalHT = $totalBeforeDiscount - $discountAmount;
        
        // TVA
        $vatAmount = round($totalHT * $vatRate / 100, 2);
        
        // Total TTC
        $totalTTC = $totalHT + $vatAmount;
        
        $stmt = $this->pdo->prepare("
            INSERT INTO erp_invoice_items (
                invoice_id, item_order, description, product_id,
                quantity, unit, unit_price_ht,
                discount_rate, discount_amount,
                total_ht, vat_rate, vat_amount, total_ttc
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $invoiceId,
            $order,
            $item['description'],
            $item['product_id'] ?? null,
            $quantity,
            $item['unit'] ?? 'unité',
            $unitPriceHT,
            $discountRate,
            $discountAmount,
            $totalHT,
            $vatRate,
            $vatAmount,
            $totalTTC
        ]);
    }
    
    /**
     * Calculer les totaux à partir des lignes
     */
    private function calculateTotals(array $items): array {
        $totalHT = 0;
        $totalTVA = 0;
        $vatByRate = [];
        
        foreach ($items as $item) {
            $quantity = $item['quantity'];
            $unitPriceHT = $item['unit_price_ht'];
            $discountRate = $item['discount_rate'] ?? 0;
            $vatRate = $item['vat_rate'];
            
            // Total HT de la ligne
            $lineHT = $quantity * $unitPriceHT;
            $lineHT -= round($lineHT * $discountRate / 100, 2);
            
            // TVA de la ligne
            $lineTVA = round($lineHT * $vatRate / 100, 2);
            
            $totalHT += $lineHT;
            $totalTVA += $lineTVA;
            
            // Grouper par taux de TVA
            if (!isset($vatByRate[$vatRate])) {
                $vatByRate[$vatRate] = ['base_ht' => 0, 'amount' => 0];
            }
            $vatByRate[$vatRate]['base_ht'] += $lineHT;
            $vatByRate[$vatRate]['amount'] += $lineTVA;
        }
        
        // Formater les détails de TVA
        $vatDetails = [];
        foreach ($vatByRate as $rate => $amounts) {
            $vatDetails[] = [
                'rate' => $rate,
                'base_ht' => round($amounts['base_ht'], 2),
                'amount' => round($amounts['amount'], 2)
            ];
        }
        
        return [
            'total_ht' => round($totalHT, 2),
            'total_tva' => round($totalTVA, 2),
            'total_ttc' => round($totalHT + $totalTVA, 2),
            'vat_details' => $vatDetails
        ];
    }
    
    /**
     * Valider et envoyer une facture
     */
    public function sendInvoice(int $invoiceId): bool {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE erp_invoices
                SET status = 'sent',
                    sent_at = NOW()
                WHERE id = ? AND customer_id = ? AND status = 'draft'
            ");
            
            $result = $stmt->execute([$invoiceId, $this->customerId]);
            
            if ($result && $stmt->rowCount() > 0) {
                $this->logAudit($invoiceId, 'sent', null, ['sent_at' => date('Y-m-d H:i:s')]);
                return true;
            }
            
            return false;
            
        } catch (Exception $e) {
            throw new Exception("Erreur envoi facture: " . $e->getMessage());
        }
    }
    
    /**
     * Enregistrer un paiement
     */
    public function recordPayment(int $invoiceId, array $paymentData): int {
        try {
            $this->pdo->beginTransaction();
            
            $stmt = $this->pdo->prepare("
                INSERT INTO erp_invoice_payments (
                    invoice_id, payment_date, amount, payment_method,
                    reference, bank_account_id, notes, recorded_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $invoiceId,
                $paymentData['payment_date'] ?? date('Y-m-d'),
                $paymentData['amount'],
                $paymentData['payment_method'],
                $paymentData['reference'] ?? null,
                $paymentData['bank_account_id'] ?? null,
                $paymentData['notes'] ?? null,
                $paymentData['recorded_by'] ?? null
            ]);
            
            $paymentId = $this->pdo->lastInsertId();
            
            // Le trigger after_payment_insert met à jour automatiquement la facture
            
            $this->logAudit($invoiceId, 'paid', null, [
                'payment_id' => $paymentId,
                'amount' => $paymentData['amount']
            ]);
            
            $this->pdo->commit();
            
            return $paymentId;
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw new Exception("Erreur enregistrement paiement: " . $e->getMessage());
        }
    }
    
    /**
     * Annuler une facture
     */
    public function cancelInvoice(int $invoiceId, string $reason): bool {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE erp_invoices
                SET status = 'cancelled',
                    payment_status = 'cancelled',
                    cancelled_at = NOW(),
                    cancellation_reason = ?
                WHERE id = ? AND customer_id = ?
            ");
            
            $result = $stmt->execute([$reason, $invoiceId, $this->customerId]);
            
            if ($result && $stmt->rowCount() > 0) {
                $this->logAudit($invoiceId, 'cancelled', null, [
                    'reason' => $reason,
                    'cancelled_at' => date('Y-m-d H:i:s')
                ]);
                return true;
            }
            
            return false;
            
        } catch (Exception $e) {
            throw new Exception("Erreur annulation facture: " . $e->getMessage());
        }
    }
    
    /**
     * Récupérer une facture avec ses lignes
     */
    public function getInvoice(int $invoiceId): ?array {
        $stmt = $this->pdo->prepare("
            SELECT * FROM erp_invoices
            WHERE id = ? AND customer_id = ?
        ");
        $stmt->execute([$invoiceId, $this->customerId]);
        $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$invoice) {
            return null;
        }
        
        // Récupérer les lignes
        $stmt = $this->pdo->prepare("
            SELECT * FROM erp_invoice_items
            WHERE invoice_id = ?
            ORDER BY item_order
        ");
        $stmt->execute([$invoiceId]);
        $invoice['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Récupérer les paiements
        $stmt = $this->pdo->prepare("
            SELECT * FROM erp_invoice_payments
            WHERE invoice_id = ?
            ORDER BY payment_date DESC
        ");
        $stmt->execute([$invoiceId]);
        $invoice['payments'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Décoder le JSON
        if (!empty($invoice['vat_details'])) {
            $invoice['vat_details'] = json_decode($invoice['vat_details'], true);
        }
        
        return $invoice;
    }
    
    /**
     * Lister les factures avec filtres
     */
    public function listInvoices(array $filters = []): array {
        $where = ["i.customer_id = ?"];
        $params = [$this->customerId];
        
        if (!empty($filters['status'])) {
            $where[] = "i.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['payment_status'])) {
            $where[] = "i.payment_status = ?";
            $params[] = $filters['payment_status'];
        }
        
        if (!empty($filters['date_from'])) {
            $where[] = "i.issue_date >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $where[] = "i.issue_date <= ?";
            $params[] = $filters['date_to'];
        }
        
        $sql = "SELECT i.*, 
                       (SELECT COUNT(*) FROM erp_invoice_items WHERE invoice_id = i.id) as items_count,
                       (SELECT COUNT(*) FROM erp_invoice_payments WHERE invoice_id = i.id) as payments_count
                FROM erp_invoices i
                WHERE " . implode(' AND ', $where) . "
                ORDER BY i.issue_date DESC, i.id DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtenir les statistiques de facturation
     */
    public function getStatistics(string $period = 'month'): array {
        $dateCondition = match($period) {
            'day' => "DATE(i.issue_date) = CURDATE()",
            'week' => "YEARWEEK(i.issue_date) = YEARWEEK(CURDATE())",
            'month' => "YEAR(i.issue_date) = YEAR(CURDATE()) AND MONTH(i.issue_date) = MONTH(CURDATE())",
            'year' => "YEAR(i.issue_date) = YEAR(CURDATE())",
            default => "1=1"
        };
        
        $stmt = $this->pdo->prepare("
            SELECT 
                COUNT(*) as total_invoices,
                SUM(total_ttc) as total_amount,
                SUM(CASE WHEN payment_status = 'paid' THEN total_ttc ELSE 0 END) as paid_amount,
                SUM(CASE WHEN payment_status IN ('unpaid', 'partial') THEN total_ttc - paid_amount ELSE 0 END) as outstanding_amount,
                SUM(CASE WHEN payment_status = 'overdue' THEN 1 ELSE 0 END) as overdue_count,
                AVG(total_ttc) as average_invoice_amount
            FROM erp_invoices i
            WHERE i.customer_id = ? 
              AND i.status != 'cancelled'
              AND $dateCondition
        ");
        
        $stmt->execute([$this->customerId]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }
    
    /**
     * Enregistrer dans l'audit trail
     */
    private function logAudit(int $invoiceId, string $action, ?array $oldValues, ?array $newValues): void {
        $stmt = $this->pdo->prepare("
            INSERT INTO erp_invoice_audit (
                invoice_id, action, user_id, old_values, new_values, 
                ip_address, user_agent
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $invoiceId,
            $action,
            $_SESSION['user_id'] ?? null,
            $oldValues ? json_encode($oldValues) : null,
            $newValues ? json_encode($newValues) : null,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    }
    
    /**
     * Mettre à jour les factures en retard
     * À appeler via un cron quotidien
     */
    public function updateOverdueInvoices(): int {
        $stmt = $this->pdo->prepare("
            UPDATE erp_invoices
            SET payment_status = 'overdue'
            WHERE customer_id = ?
              AND payment_status IN ('unpaid', 'partial')
              AND due_date < CURDATE()
              AND status NOT IN ('cancelled', 'archived')
        ");
        
        $stmt->execute([$this->customerId]);
        
        return $stmt->rowCount();
    }
}
