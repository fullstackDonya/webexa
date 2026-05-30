#!/usr/bin/env php
<?php
/**
 * Generate encryption key for MAIL_CRYPTO_KEY
 * 
 * Usage: php scripts/generate-key.php
 */

echo "==================================\n";
echo "  Générateur de clé de chiffrement\n";
echo "==================================\n\n";

// Generate 256-bit key
$key = random_bytes(32);
$base64Key = base64_encode($key);

echo "Votre nouvelle clé de chiffrement:\n\n";
echo "MAIL_CRYPTO_KEY={$base64Key}\n\n";

echo "📋 Instructions:\n";
echo "1. Copiez la ligne ci-dessus\n";
echo "2. Ajoutez-la dans votre fichier .env\n";
echo "3. Remplacez l'ancienne valeur de MAIL_CRYPTO_KEY\n\n";

echo "⚠️  IMPORTANT:\n";
echo "- Ne partagez JAMAIS cette clé\n";
echo "- Ne la committez JAMAIS dans Git\n";
echo "- Sauvegardez-la en lieu sûr\n";
echo "- Si vous la changez, les tokens existants devront être re-chiffrés\n\n";

// Test the key
try {
    putenv("MAIL_CRYPTO_KEY={$base64Key}");
    require_once __DIR__ . '/../includes/EmailCrypto.php';
    
    $crypto = new EmailCrypto();
    $testPlain = 'test-message-' . time();
    $encrypted = $crypto->encrypt($testPlain);
    $decrypted = $crypto->decrypt($encrypted);
    
    if ($testPlain === $decrypted) {
        echo "✅ Test de chiffrement: OK\n\n";
    } else {
        echo "❌ Test de chiffrement: ÉCHEC\n\n";
    }
} catch (Exception $e) {
    echo "❌ Erreur lors du test: " . $e->getMessage() . "\n\n";
}
