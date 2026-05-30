<?php
/**
 * Système de scoring IA simple basé sur des heuristiques
 * Calcul automatique du score de qualification d'un lead
 */

function calculate_lead_score($lead_data) {
    $score = 0;
    $max_score = 100;
    
    // 1. Complétude du profil (max 40 points)
    $completion_points = 0;
    $fields_to_check = ['first_name', 'last_name', 'email', 'phone', 'position', 'company_id'];
    
    foreach ($fields_to_check as $field) {
        if (!empty($lead_data[$field])) {
            $completion_points += 6; // 6 * 6 = 36 points max
        }
    }
    $score += min($completion_points, 40);
    
    // 2. Stage du lead (max 30 points)
    $stage_scores = [
        'converted' => 30,
        'proposal' => 25,
        'qualified' => 20,
        'contacted' => 10,
        'lead' => 5,
        'unqualified' => 0
    ];
    $stage = $lead_data['stage'] ?? 'lead';
    $score += $stage_scores[$stage] ?? 0;
    
    // 3. Source du lead (max 15 points)
    $source_scores = [
        'direct' => 15,
        'website' => 12,
        'email_campaign' => 10,
        'social_media' => 8,
        'referral' => 10,
        'event' => 9,
        '' => 0
    ];
    $source = $lead_data['source'] ?? '';
    $score += $source_scores[$source] ?? 0;
    
    // 4. Validations supplémentaires (max 15 points)
    $validation_points = 0;
    
    // Email valide: +8 points
    if (!empty($lead_data['email']) && filter_var($lead_data['email'], FILTER_VALIDATE_EMAIL)) {
        $validation_points += 8;
    }
    
    // Téléphone rempli: +4 points
    if (!empty($lead_data['phone'])) {
        $validation_points += 4;
    }
    
    // Assigné à quelqu'un: +3 points (montre l'intérêt)
    if (!empty($lead_data['assigned_to'])) {
        $validation_points += 3;
    }
    
    $score += min($validation_points, 15);
    
    // S'assurer que le score est entre 0 et 100
    $score = max(0, min(100, $score));
    
    error_log("AI SCORING: Lead data: " . json_encode([
        'first_name' => $lead_data['first_name'] ?? null,
        'email' => $lead_data['email'] ?? null,
        'stage' => $lead_data['stage'] ?? null,
        'source' => $lead_data['source'] ?? null
    ]) . " | Calculated score: $score");
    
    return round($score);
}

/**
 * Mettre à jour le score IA d'un lead
 */
function update_lead_ai_score($pdo, $lead_id, $lead_data) {
    try {
        $new_score = calculate_lead_score($lead_data);
        
        $stmt = $pdo->prepare("
            UPDATE leads 
            SET ai_score = ?
            WHERE id = ?
        ");
        $stmt->execute([$new_score, $lead_id]);
        
        error_log("Lead $lead_id AI score updated to: $new_score");
        return $new_score;
    } catch (Exception $e) {
        error_log("Error updating lead AI score: " . $e->getMessage());
        return null;
    }
}

/**
 * Mettre à jour tous les scores des leads (batch update)
 * Utile pour recalculer après une migration
 */
function recalculate_all_lead_scores($pdo, $customer_id = null) {
    try {
        $query = "SELECT id, first_name, last_name, email, phone, position, company_id, stage, source, assigned_to FROM leads";
        $params = [];
        
        if ($customer_id) {
            $query .= " WHERE customer_id = ?";
            $params[] = $customer_id;
        }
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $leads = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $updated_count = 0;
        foreach ($leads as $lead) {
            $score = calculate_lead_score($lead);
            $update_stmt = $pdo->prepare("UPDATE leads SET ai_score = ? WHERE id = ?");
            $update_stmt->execute([$score, $lead['id']]);
            $updated_count++;
        }
        
        error_log("Recalculated AI scores for $updated_count leads");
        return $updated_count;
    } catch (Exception $e) {
        error_log("Error recalculating lead scores: " . $e->getMessage());
        return 0;
    }
}
?>
