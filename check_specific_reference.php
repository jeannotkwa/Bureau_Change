<?php
// Script pour supprimer une référence spécifique en doublon

$referenceToCheck = 'ENVOI-20251209-154355-1';

$params = [
    'dbname' => 'bureau_change',
    'user' => 'root',
    'password' => '',
    'host' => 'localhost',
];

try {
    $dsn = "mysql:host={$params['host']};dbname={$params['dbname']};charset=utf8mb4";
    $pdo = new PDO($dsn, $params['user'], $params['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== Vérification de la référence: $referenceToCheck ===\n\n";
    
    $sql = "SELECT id_transaction, nature_operation, date_transaction
            FROM transactions 
            WHERE reference = ? 
            ORDER BY id_transaction";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$referenceToCheck]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($results)) {
        echo "✓ Cette référence n'existe pas en base.\n";
        echo "Vous pouvez créer un nouveau transfert sans problème.\n";
    } else {
        echo "Cette référence existe " . count($results) . " fois en base :\n\n";
        foreach ($results as $idx => $row) {
            echo ($idx + 1) . ". ID: " . $row['id_transaction'] . 
                 " | Nature: " . $row['nature_operation'] . 
                 " | Date: " . $row['date_transaction'] . "\n";
        }
        
        if (count($results) > 1) {
            echo "\n⚠ Attention: Plusieurs transactions avec la même référence.\n";
            echo "Il faut supprimer les doublons en gardant le premier.\n";
        }
    }
    
} catch (\Exception $e) {
    echo "Erreur : " . $e->getMessage() . "\n";
}
