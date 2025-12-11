<?php
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
    
    echo "=== Migration de la contrainte UNIQUE ===\n\n";
    
    // Étape 1: Supprimer l'ancien index UNIQUE sur reference seul
    echo "1. Suppression de l'ancien index UNIQUE sur 'reference'...\n";
    try {
        $pdo->exec("ALTER TABLE transactions DROP INDEX reference");
        echo "   ✓ Index supprimé\n";
    } catch (\Exception $e) {
        echo "   ⚠ Erreur: " . $e->getMessage() . "\n";
    }
    
    // Étape 2: Ajouter le nouvel index UNIQUE composite
    echo "\n2. Création du nouvel index UNIQUE composite sur (reference, nature_operation)...\n";
    try {
        $pdo->exec("ALTER TABLE transactions ADD UNIQUE KEY unique_reference_nature (reference, nature_operation)");
        echo "   ✓ Index composite créé\n";
    } catch (\Exception $e) {
        echo "   ⚠ Erreur: " . $e->getMessage() . "\n";
    }
    
    // Étape 3: Vérifier les contraintes
    echo "\n3. Vérification des contraintes mises à jour...\n";
    $sql = "SELECT CONSTRAINT_NAME, COLUMN_NAME 
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
            WHERE TABLE_NAME = 'transactions' 
            AND TABLE_SCHEMA = 'bureau_change'
            AND (CONSTRAINT_NAME LIKE 'unique_%' OR CONSTRAINT_NAME = 'reference')
            ORDER BY CONSTRAINT_NAME";
    
    $stmt = $pdo->query($sql);
    $constraints = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($constraints)) {
        echo "   ✓ Aucune contrainte UNIQUE sur reference seul\n";
    } else {
        foreach ($constraints as $constraint) {
            echo "   - " . $constraint['CONSTRAINT_NAME'] . " sur colonne: " . $constraint['COLUMN_NAME'] . "\n";
        }
    }
    
    echo "\n✅ Migration terminée ! Vous pouvez maintenant avoir :\n";
    echo "   - Une transaction ENVOI-XXX-1 (envoi)\n";
    echo "   - Une transaction ENVOI-XXX-1 (réception) avec la même référence\n";
    
} catch (\Exception $e) {
    echo "Erreur : " . $e->getMessage() . "\n";
}
