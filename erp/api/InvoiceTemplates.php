<?php
/**
 * Templates de factures - 5 modèles professionnels
 * 
 * @author Webitech CRM
 * @version 1.0
 * @date 2026-02-20
 */

class InvoiceTemplates {
    
    /**
     * Liste des templates disponibles
     */
    public static function getAvailableTemplates(): array {
        return [
            'modern' => [
                'name' => 'Moderne',
                'description' => 'Design épuré avec dégradés et glassmorphism',
                'preview' => 'assets/img/templates/modern.png',
                'primary_color' => '#667eea',
                'secondary_color' => '#764ba2'
            ],
            'classic' => [
                'name' => 'Classique',
                'description' => 'Style professionnel traditionnel',
                'preview' => 'assets/img/templates/classic.png',
                'primary_color' => '#1e40af',
                'secondary_color' => '#1e3a8a'
            ],
            'elegant' => [
                'name' => 'Élégant',
                'description' => 'Design raffiné avec typographie soignée',
                'preview' => 'assets/img/templates/elegant.png',
                'primary_color' => '#059669',
                'secondary_color' => '#047857'
            ],
            'minimal' => [
                'name' => 'Minimaliste',
                'description' => 'Épuré et simple, focus sur le contenu',
                'preview' => 'assets/img/templates/minimal.png',
                'primary_color' => '#374151',
                'secondary_color' => '#1f2937'
            ],
            'corporate' => [
                'name' => 'Corporate',
                'description' => 'Style entreprise avec structure formelle',
                'preview' => 'assets/img/templates/corporate.png',
                'primary_color' => '#dc2626',
                'secondary_color' => '#991b1b'
            ]
        ];
    }
    
    /**
     * Générer le HTML d'une facture selon le template choisi
     */
    public static function generateHTML(array $invoice, array $company, string $template = 'modern'): string {
        switch ($template) {
            case 'classic':
                return self::generateClassicTemplate($invoice, $company);
            case 'elegant':
                return self::generateElegantTemplate($invoice, $company);
            case 'minimal':
                return self::generateMinimalTemplate($invoice, $company);
            case 'corporate':
                return self::generateCorporateTemplate($invoice, $company);
            case 'modern':
            default:
                return self::generateModernTemplate($invoice, $company);
        }
    }
    
    /**
     * Template 1: Moderne (Défaut)
     */
    private static function generateModernTemplate(array $invoice, array $company): string {
        $vatDetails = is_string($invoice['vat_details']) ? json_decode($invoice['vat_details'], true) : $invoice['vat_details'];
        
        return self::wrapTemplate('
            <div class="invoice-container modern-template">
                <!-- Header avec dégradé -->
                <div class="invoice-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 15px 15px 0 0; margin: -20px -20px 30px -20px;">
                    <div style="display: flex; justify-content: space-between; align-items: start;">
                        <div>
                            <h1 style="margin: 0; font-size: 32px; font-weight: 700;">FACTURE</h1>
                            <p style="margin: 5px 0 0 0; font-size: 18px; opacity: 0.9;">' . htmlspecialchars($invoice['invoice_number']) . '</p>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-size: 24px; font-weight: 700; margin-bottom: 5px;">' . htmlspecialchars($company['name']) . '</div>
                            <div style="font-size: 12px; opacity: 0.9; line-height: 1.6;">
                                ' . nl2br(htmlspecialchars($company['address'] . "\n" . $company['postal_code'] . ' ' . $company['city'])) . '
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Informations -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px;">
                    <div>
                        <div style="background: linear-gradient(135deg, #f3f4f6, #e5e7eb); padding: 20px; border-radius: 12px; border-left: 4px solid #667eea;">
                            <h3 style="margin: 0 0 10px 0; color: #667eea; font-size: 14px; text-transform: uppercase;">Facturé à</h3>
                            <div style="font-size: 16px; font-weight: 700; margin-bottom: 8px;">' . htmlspecialchars($invoice['client_name']) . '</div>
                            <div style="font-size: 12px; color: #6b7280; line-height: 1.6;">
                                ' . htmlspecialchars($invoice['client_address'] ?? '') . '<br>
                                ' . htmlspecialchars(($invoice['client_postal_code'] ?? '') . ' ' . ($invoice['client_city'] ?? '')) . '
                            </div>
                        </div>
                    </div>
                    <div>
                        <div style="background: white; padding: 20px; border-radius: 12px; border: 2px solid #e5e7eb;">
                            <div style="margin-bottom: 12px;">
                                <span style="color: #6b7280; font-size: 12px;">Date émission:</span>
                                <strong style="float: right;">' . date('d/m/Y', strtotime($invoice['issue_date'])) . '</strong>
                            </div>
                            <div style="margin-bottom: 12px;">
                                <span style="color: #6b7280; font-size: 12px;">Date échéance:</span>
                                <strong style="float: right;">' . date('d/m/Y', strtotime($invoice['due_date'])) . '</strong>
                            </div>
                            <div>
                                <span style="color: #6b7280; font-size: 12px;">Statut:</span>
                                <span style="float: right; background: #c6f6d5; color: #22543d; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 700;">' . strtoupper($invoice['payment_status']) . '</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                ' . self::generateItemsTable($invoice['items'], $vatDetails, $invoice) . '
                ' . self::generateMentionsLegales($invoice, $company) . '
            </div>
        ', 'modern');
    }
    
    /**
     * Template 2: Classique
     */
    private static function generateClassicTemplate(array $invoice, array $company): string {
        $vatDetails = is_string($invoice['vat_details']) ? json_decode($invoice['vat_details'], true) : $invoice['vat_details'];
        
        return self::wrapTemplate('
            <div class="invoice-container classic-template">
                <table style="width: 100%; margin-bottom: 30px;">
                    <tr>
                        <td style="width: 50%; vertical-align: top;">
                            <h1 style="color: #1e40af; font-size: 36px; margin: 0;">' . htmlspecialchars($company['name']) . '</h1>
                            <p style="color: #6b7280; font-size: 11px; line-height: 1.8; margin: 10px 0;">
                                ' . htmlspecialchars($company['address']) . '<br>
                                ' . htmlspecialchars($company['postal_code'] . ' ' . $company['city']) . '<br>
                                SIRET: ' . htmlspecialchars($company['siret'] ?? 'N/A') . '
                            </p>
                        </td>
                        <td style="width: 50%; text-align: right; vertical-align: top;">
                            <div style="border: 3px solid #1e40af; padding: 15px; display: inline-block; margin-top: 20px;">
                                <div style="color: #1e40af; font-size: 14px; font-weight: 700; margin-bottom: 5px;">FACTURE</div>
                                <div style="font-size: 20px; font-weight: 700;">' . htmlspecialchars($invoice['invoice_number']) . '</div>
                            </div>
                        </td>
                    </tr>
                </table>
                
                <div style="border-top: 3px solid #1e40af; border-bottom: 3px solid #1e40af; padding: 20px 0; margin-bottom: 30px;">
                    <table style="width: 100%;">
                        <tr>
                            <td style="width: 50%; vertical-align: top;">
                                <div style="font-weight: 700; text-transform: uppercase; font-size: 11px; color: #1e40af; margin-bottom: 10px;">Facturé à:</div>
                                <div style="font-size: 14px; font-weight: 700;">' . htmlspecialchars($invoice['client_name']) . '</div>
                                <div style="font-size: 11px; color: #6b7280; margin-top: 5px;">
                                    ' . htmlspecialchars($invoice['client_address'] ?? '') . '<br>
                                    ' . htmlspecialchars(($invoice['client_postal_code'] ?? '') . ' ' . ($invoice['client_city'] ?? '')) . '
                                </div>
                            </td>
                            <td style="width: 50%; text-align: right; vertical-align: top;">
                                <table style="width: 100%; font-size: 11px;">
                                    <tr>
                                        <td style="padding: 5px 0; color: #6b7280;">Date émission:</td>
                                        <td style="padding: 5px 0; font-weight: 700; text-align: right;">' . date('d/m/Y', strtotime($invoice['issue_date'])) . '</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 5px 0; color: #6b7280;">Date échéance:</td>
                                        <td style="padding: 5px 0; font-weight: 700; text-align: right;">' . date('d/m/Y', strtotime($invoice['due_date'])) . '</td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </div>
                
                ' . self::generateItemsTable($invoice['items'], $vatDetails, $invoice, 'classic') . '
                ' . self::generateMentionsLegales($invoice, $company) . '
            </div>
        ', 'classic');
    }
    
    /**
     * Template 3: Élégant
     */
    private static function generateElegantTemplate(array $invoice, array $company): string {
        $vatDetails = is_string($invoice['vat_details']) ? json_decode($invoice['vat_details'], true) : $invoice['vat_details'];
        
        return self::wrapTemplate('
            <div class="invoice-container elegant-template">
                <div style="text-align: center; margin-bottom: 40px; padding-bottom: 20px; border-bottom: 1px solid #d4af37;">
                    <h1 style="font-family: Georgia, serif; font-size: 48px; color: #059669; margin: 0; letter-spacing: 2px;">FACTURE</h1>
                    <p style="font-size: 16px; color: #6b7280; margin: 10px 0 0 0; letter-spacing: 4px;">' . htmlspecialchars($invoice['invoice_number']) . '</p>
                </div>
                
                <table style="width: 100%; margin-bottom: 40px;">
                    <tr>
                        <td style="width: 50%; vertical-align: top; padding-right: 30px;">
                            <div style="font-family: Georgia, serif; font-size: 11px; color: #059669; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 15px;">De</div>
                            <div style="font-size: 18px; font-weight: 700; margin-bottom: 10px;">' . htmlspecialchars($company['name']) . '</div>
                            <div style="font-size: 11px; color: #6b7280; line-height: 1.8;">
                                ' . htmlspecialchars($company['address']) . '<br>
                                ' . htmlspecialchars($company['postal_code'] . ' ' . $company['city']) . '<br>
                                ' . htmlspecialchars($company['email']) . '
                            </div>
                        </td>
                        <td style="width: 50%; vertical-align: top; padding-left: 30px; border-left: 1px solid #e5e7eb;">
                            <div style="font-family: Georgia, serif; font-size: 11px; color: #059669; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 15px;">À</div>
                            <div style="font-size: 18px; font-weight: 700; margin-bottom: 10px;">' . htmlspecialchars($invoice['client_name']) . '</div>
                            <div style="font-size: 11px; color: #6b7280; line-height: 1.8;">
                                ' . htmlspecialchars($invoice['client_address'] ?? '') . '<br>
                                ' . htmlspecialchars(($invoice['client_postal_code'] ?? '') . ' ' . ($invoice['client_city'] ?? '')) . '
                            </div>
                        </td>
                    </tr>
                </table>
                
                <div style="background: #f9fafb; padding: 15px; border-left: 3px solid #059669; margin-bottom: 30px;">
                    <table style="width: 100%; font-size: 11px;">
                        <tr>
                            <td style="padding: 5px 0;">Date d\'émission:</td>
                            <td style="text-align: right; font-weight: 700;">' . date('d/m/Y', strtotime($invoice['issue_date'])) . '</td>
                        </tr>
                        <tr>
                            <td style="padding: 5px 0;">Date d\'échéance:</td>
                            <td style="text-align: right; font-weight: 700;">' . date('d/m/Y', strtotime($invoice['due_date'])) . '</td>
                        </tr>
                    </table>
                </div>
                
                ' . self::generateItemsTable($invoice['items'], $vatDetails, $invoice, 'elegant') . '
                ' . self::generateMentionsLegales($invoice, $company) . '
            </div>
        ', 'elegant');
    }
    
    /**
     * Template 4: Minimaliste
     */
    private static function generateMinimalTemplate(array $invoice, array $company): string {
        $vatDetails = is_string($invoice['vat_details']) ? json_decode($invoice['vat_details'], true) : $invoice['vat_details'];
        
        return self::wrapTemplate('
            <div class="invoice-container minimal-template">
                <div style="margin-bottom: 60px;">
                    <h1 style="font-size: 12px; font-weight: 400; color: #9ca3af; margin: 0 0 5px 0; letter-spacing: 3px;">FACTURE</h1>
                    <div style="font-size: 32px; font-weight: 700; color: #1f2937;">' . htmlspecialchars($invoice['invoice_number']) . '</div>
                </div>
                
                <table style="width: 100%; margin-bottom: 60px; font-size: 11px;">
                    <tr>
                        <td style="width: 33%; vertical-align: top; padding-right: 20px;">
                            <div style="color: #9ca3af; margin-bottom: 8px;">DE</div>
                            <div style="font-weight: 700; margin-bottom: 5px;">' . htmlspecialchars($company['name']) . '</div>
                            <div style="color: #6b7280; line-height: 1.6;">
                                ' . htmlspecialchars($company['address']) . '<br>
                                ' . htmlspecialchars($company['postal_code'] . ' ' . $company['city']) . '
                            </div>
                        </td>
                        <td style="width: 33%; vertical-align: top; padding: 0 20px; border-left: 1px solid #e5e7eb; border-right: 1px solid #e5e7eb;">
                            <div style="color: #9ca3af; margin-bottom: 8px;">À</div>
                            <div style="font-weight: 700; margin-bottom: 5px;">' . htmlspecialchars($invoice['client_name']) . '</div>
                            <div style="color: #6b7280; line-height: 1.6;">
                                ' . htmlspecialchars($invoice['client_address'] ?? '') . '<br>
                                ' . htmlspecialchars(($invoice['client_postal_code'] ?? '') . ' ' . ($invoice['client_city'] ?? '')) . '
                            </div>
                        </td>
                        <td style="width: 33%; vertical-align: top; padding-left: 20px;">
                            <div style="color: #9ca3af; margin-bottom: 8px;">DATES</div>
                            <div style="margin-bottom: 5px;">
                                <span style="color: #6b7280;">Émission:</span> <strong>' . date('d/m/Y', strtotime($invoice['issue_date'])) . '</strong>
                            </div>
                            <div>
                                <span style="color: #6b7280;">Échéance:</span> <strong>' . date('d/m/Y', strtotime($invoice['due_date'])) . '</strong>
                            </div>
                        </td>
                    </tr>
                </table>
                
                ' . self::generateItemsTable($invoice['items'], $vatDetails, $invoice, 'minimal') . '
                ' . self::generateMentionsLegales($invoice, $company) . '
            </div>
        ', 'minimal');
    }
    
    /**
     * Template 5: Corporate
     */
    private static function generateCorporateTemplate(array $invoice, array $company): string {
        $vatDetails = is_string($invoice['vat_details']) ? json_decode($invoice['vat_details'], true) : $invoice['vat_details'];
        
        return self::wrapTemplate('
            <div class="invoice-container corporate-template">
                <table style="width: 100%; margin-bottom: 30px; background: #dc2626; color: white;">
                    <tr>
                        <td style="padding: 30px;">
                            <table style="width: 100%;">
                                <tr>
                                    <td style="width: 70%;">
                                        <div style="font-size: 28px; font-weight: 700; margin-bottom: 5px;">' . htmlspecialchars($company['name']) . '</div>
                                        <div style="font-size: 11px; opacity: 0.9;">' . htmlspecialchars($company['address']) . ' • ' . htmlspecialchars($company['city']) . '</div>
                                    </td>
                                    <td style="width: 30%; text-align: right;">
                                        <div style="background: white; color: #dc2626; padding: 15px; display: inline-block; border-radius: 8px;">
                                            <div style="font-size: 12px; font-weight: 700; margin-bottom: 5px;">FACTURE</div>
                                            <div style="font-size: 18px; font-weight: 700;">' . htmlspecialchars($invoice['invoice_number']) . '</div>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
                
                <table style="width: 100%; margin-bottom: 30px;">
                    <tr>
                        <td style="width: 50%; vertical-align: top; padding: 20px; background: #fee2e2;">
                            <div style="font-size: 10px; color: #991b1b; font-weight: 700; text-transform: uppercase; margin-bottom: 10px;">Client</div>
                            <div style="font-size: 16px; font-weight: 700; color: #1f2937; margin-bottom: 8px;">' . htmlspecialchars($invoice['client_name']) . '</div>
                            <div style="font-size: 11px; color: #6b7280; line-height: 1.6;">
                                ' . htmlspecialchars($invoice['client_address'] ?? '') . '<br>
                                ' . htmlspecialchars(($invoice['client_postal_code'] ?? '') . ' ' . ($invoice['client_city'] ?? '')) . '
                            </div>
                        </td>
                        <td style="width: 50%; vertical-align: top; padding: 20px; background: #f9fafb;">
                            <table style="width: 100%; font-size: 11px;">
                                <tr>
                                    <td style="padding: 8px 0; color: #6b7280; border-bottom: 1px solid #e5e7eb;">Date d\'émission</td>
                                    <td style="padding: 8px 0; text-align: right; font-weight: 700; border-bottom: 1px solid #e5e7eb;">' . date('d/m/Y', strtotime($invoice['issue_date'])) . '</td>
                                </tr>
                                <tr>
                                    <td style="padding: 8px 0; color: #6b7280; border-bottom: 1px solid #e5e7eb;">Date d\'échéance</td>
                                    <td style="padding: 8px 0; text-align: right; font-weight: 700; border-bottom: 1px solid #e5e7eb;">' . date('d/m/Y', strtotime($invoice['due_date'])) . '</td>
                                </tr>
                                <tr>
                                    <td style="padding: 8px 0; color: #6b7280;">Statut</td>
                                    <td style="padding: 8px 0; text-align: right;"><span style="background: #dc2626; color: white; padding: 4px 12px; border-radius: 4px; font-size: 10px; font-weight: 700;">' . strtoupper($invoice['payment_status']) . '</span></td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
                
                ' . self::generateItemsTable($invoice['items'], $vatDetails, $invoice, 'corporate') . '
                ' . self::generateMentionsLegales($invoice, $company) . '
            </div>
        ', 'corporate');
    }
    
    /**
     * Générer le tableau des lignes de facture
     */
    private static function generateItemsTable(array $items, $vatDetails, array $invoice, string $style = 'modern'): string {
        $colors = [
            'modern' => ['header' => '#667eea', 'border' => '#e5e7eb'],
            'classic' => ['header' => '#1e40af', 'border' => '#1e40af'],
            'elegant' => ['header' => '#059669', 'border' => '#d4af37'],
            'minimal' => ['header' => '#1f2937', 'border' => '#e5e7eb'],
            'corporate' => ['header' => '#dc2626', 'border' => '#dc2626']
        ];
        
        $color = $colors[$style] ?? $colors['modern'];
        
        $html = '<table style="width: 100%; border-collapse: collapse; margin-bottom: 30px;">
            <thead>
                <tr style="background: ' . $color['header'] . '; color: white;">
                    <th style="padding: 12px; text-align: left; font-size: 11px; font-weight: 700; text-transform: uppercase;">Description</th>
                    <th style="padding: 12px; text-align: center; font-size: 11px; font-weight: 700; text-transform: uppercase; width: 80px;">Qté</th>
                    <th style="padding: 12px; text-align: right; font-size: 11px; font-weight: 700; text-transform: uppercase; width: 100px;">PU HT</th>
                    <th style="padding: 12px; text-align: center; font-size: 11px; font-weight: 700; text-transform: uppercase; width: 60px;">TVA</th>
                    <th style="padding: 12px; text-align: right; font-size: 11px; font-weight: 700; text-transform: uppercase; width: 120px;">Total TTC</th>
                </tr>
            </thead>
            <tbody>';
        
        foreach ($items as $item) {
            $html .= '<tr style="border-bottom: 1px solid ' . $color['border'] . ';">
                <td style="padding: 12px; font-size: 12px;">' . htmlspecialchars($item['description']) . '</td>
                <td style="padding: 12px; text-align: center; font-size: 12px;">' . $item['quantity'] . ' ' . htmlspecialchars($item['unit']) . '</td>
                <td style="padding: 12px; text-align: right; font-size: 12px;">' . number_format($item['unit_price_ht'], 2, ',', ' ') . ' €</td>
                <td style="padding: 12px; text-align: center; font-size: 12px;">' . $item['vat_rate'] . '%</td>
                <td style="padding: 12px; text-align: right; font-size: 13px; font-weight: 700;">' . number_format($item['total_ttc'], 2, ',', ' ') . ' €</td>
            </tr>';
        }
        
        $html .= '</tbody></table>';
        
        // Totaux
        $html .= '<div style="margin-left: auto; width: 400px;">
            <table style="width: 100%; font-size: 12px;">
                <tr>
                    <td style="padding: 8px 0;">Total HT:</td>
                    <td style="text-align: right; font-weight: 700;">' . number_format($invoice['total_ht'], 2, ',', ' ') . ' €</td>
                </tr>';
        
        if ($vatDetails && is_array($vatDetails)) {
            foreach ($vatDetails as $vat) {
                $html .= '<tr>
                    <td style="padding: 8px 0; color: #6b7280;">TVA ' . $vat['rate'] . '%:</td>
                    <td style="text-align: right;">' . number_format($vat['amount'], 2, ',', ' ') . ' €</td>
                </tr>';
            }
        }
        
        $html .= '<tr style="border-top: 2px solid ' . $color['header'] . ';">
                    <td style="padding: 15px 0; font-size: 16px; font-weight: 700; color: ' . $color['header'] . ';">Total TTC:</td>
                    <td style="text-align: right; font-size: 20px; font-weight: 700; color: ' . $color['header'] . ';">' . number_format($invoice['total_ttc'], 2, ',', ' ') . ' €</td>
                </tr>';
        
        if ($invoice['paid_amount'] > 0) {
            $html .= '<tr>
                    <td style="padding: 8px 0; color: #059669;">Montant payé:</td>
                    <td style="text-align: right; color: #059669; font-weight: 700;">' . number_format($invoice['paid_amount'], 2, ',', ' ') . ' €</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #dc2626;">Reste à payer:</td>
                    <td style="text-align: right; color: #dc2626; font-weight: 700;">' . number_format($invoice['total_ttc'] - $invoice['paid_amount'], 2, ',', ' ') . ' €</td>
                </tr>';
        }
        
        $html .= '</table></div>';
        
        return $html;
    }
    
    /**
     * Générer les mentions légales
     */
    private static function generateMentionsLegales(array $invoice, array $company): string {
        return '<div style="margin-top: 60px; padding-top: 20px; border-top: 1px solid #e5e7eb; font-size: 9px; color: #6b7280; line-height: 1.8;">
            <strong style="color: #1f2937;">MENTIONS LÉGALES OBLIGATOIRES</strong><br>
            En cas de retard de paiement, seront exigibles conformément aux articles L441-6 et D441-5 du Code de Commerce:<br>
            - Une pénalité de <strong>' . $invoice['late_fee_rate'] . '%</strong> par an<br>
            - Une indemnité forfaitaire de recouvrement de <strong>' . number_format($invoice['recovery_indemnity'], 2, ',', ' ') . ' €</strong><br><br>
            Cette facture est émise conformément à l\'article 242 nonies A du CGI (numérotation séquentielle).<br>
            Conservation obligatoire 10 ans (Article L123-22 du Code de Commerce).<br><br>
            ' . (!empty($company['siret']) ? '<strong>SIRET:</strong> ' . htmlspecialchars($company['siret']) . ' • ' : '') . '
            ' . (!empty($company['vat_number']) ? '<strong>N° TVA:</strong> ' . htmlspecialchars($company['vat_number']) : '') . '
        </div>';
    }
    
    /**
     * Wrapper avec styles communs
     */
    private static function wrapTemplate(string $content, string $template): string {
        return '<style>
            body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; color: #1f2937; }
            .invoice-container { max-width: 800px; margin: 0 auto; padding: 20px; }
            table { border-collapse: collapse; }
        </style>' . $content;
    }
}
