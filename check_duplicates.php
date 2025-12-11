<?php
// Script pour vérifier et afficher les références en doublon

$params = [
    'dbname' => 'bureau_change',
    'user' => 'root',
    'password' => '',
    'host' => 'localhost',
    'driver' => 'pdo_mysql',
];

try {
    $dsn = "mysql:host={$params['host']};dbname={$params['dbname']};charset=utf8mb4";
    $pdo = new PDO($dsn, $params['user'], $params['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== Recherche des références en doublon ===\n\n";
    
    $sql = "SELECT reference, COUNT(*) as count 
            FROM transactions 
            GROUP BY reference 
            HAVING count > 1 
            ORDER BY count DESC";
    
    $stmt = $pdo->query($sql);
    $duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($duplicates)) {
        echo "✓ Aucune référence en doublon trouvée.\n";
    } else {
        echo "⚠ Références en doublon trouvées :\n\n";
        foreach ($duplicates as $dup) {
            echo "Référence: " . $dup['reference'] . " - Occurrences: " . $dup['count'] . "\n";
            
            // Afficher les détails de chaque doublon
            $detailSql = "SELECT id_transaction, nature_operation, date_transaction 
                         FROM transactions 
                         WHERE reference = ? 
                         ORDER BY id_transaction";
            $detailStmt = $pdo->prepare($detailSql);
            $detailStmt->execute([$dup['reference']]);
            $details = $detailStmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($details as $detail) {
                echo "  - ID: " . $detail['id_transaction'] . 
                     " | Nature: " . $detail['nature_operation'] . 
                     " | Date: " . $detail['date_transaction'] . "\n";
            }
            echo "\n";
        }
    }
    
} catch (\Exception $e) {
    echo "Erreur : " . $e->getMessage() . "\n";
}
