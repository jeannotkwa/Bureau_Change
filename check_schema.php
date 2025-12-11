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
    
    echo "=== Contraintes UNIQUE sur la table transactions ===\n\n";
    
    $sql = "SELECT CONSTRAINT_NAME, COLUMN_NAME 
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
            WHERE TABLE_NAME = 'transactions' 
            AND TABLE_SCHEMA = 'bureau_change'
            AND CONSTRAINT_NAME != 'PRIMARY'";
    
    $stmt = $pdo->query($sql);
    $constraints = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($constraints as $constraint) {
        echo "Contrainte: " . $constraint['CONSTRAINT_NAME'] . " sur colonne: " . $constraint['COLUMN_NAME'] . "\n";
    }
    
    echo "\n=== Structure de la table transactions ===\n\n";
    $sql = "SHOW CREATE TABLE transactions";
    $stmt = $pdo->query($sql);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo $result['Create Table'] . "\n";
    
} catch (\Exception $e) {
    echo "Erreur : " . $e->getMessage() . "\n";
}
