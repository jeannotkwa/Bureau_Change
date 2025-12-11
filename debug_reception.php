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
    
    echo "=== Vérification des transactions et leurs détails ===\n\n";
    
    // Récupérer les transactions récentes
    $sql = "SELECT t.id_transaction, t.reference, t.nature_operation, t.date_transaction, a.nom_agence
            FROM transactions t
            JOIN agences a ON t.agence_id = a.id_agence
            WHERE t.nature_operation IN ('envoi', 'reception')
            ORDER BY t.id_transaction DESC
            LIMIT 5";
    
    $stmt = $pdo->query($sql);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($transactions)) {
        echo "Aucune transaction d'envoi/réception trouvée\n";
    } else {
        foreach ($transactions as $trans) {
            echo "Transaction ID: " . $trans['id_transaction'] . 
                 " | Référence: " . $trans['reference'] . 
                 " | Nature: " . $trans['nature_operation'] . 
                 " | Agence: " . $trans['nom_agence'] . 
                 " | Date: " . $trans['date_transaction'] . "\n";
            
            // Récupérer les détails des transactions
            $detailSql = "SELECT dt.id, dt.montant, dt.devise_input_id
                         FROM details_transaction dt
                         WHERE dt.transaction_id = ?";
            $detailStmt = $pdo->prepare($detailSql);
            $detailStmt->execute([$trans['id_transaction']]);
            $details = $detailStmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($details)) {
                echo "  └─ Détails (details_transaction):\n";
                foreach ($details as $detail) {
                    echo "     • ID: " . $detail['id'] . 
                         " | Montant: " . $detail['montant'] . 
                         " | Devise: " . $detail['devise_input_id'] . "\n";
                }
            }
            
            // Récupérer les mouvements de fonds
            $fondSql = "SELECT fd.id_detail, fd.montant, dv.sigle, fd.agence_id
                       FROM details_fonds_depart fd
                       JOIN devises dv ON fd.devise_id = dv.id_devise
                       WHERE fd.fonds_depart_id IN (
                           SELECT ff.id_fonds_depart FROM fonds_depart ff 
                           WHERE ff.agence_id IN (
                               SELECT DISTINCT agence_id FROM transactions WHERE reference = ?
                           )
                       )
                       ORDER BY fd.id_detail DESC
                       LIMIT 10";
            
            $fondStmt = $pdo->prepare($fondSql);
            $fondStmt->execute([$trans['reference']]);
            $fonds = $fondStmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($fonds)) {
                echo "  └─ Mouvements de fonds (details_fonds_depart):\n";
                foreach ($fonds as $fond) {
                    echo "     • ID: " . $fond['id_detail'] . 
                         " | Montant: " . $fond['montant'] . " " . $fond['sigle'] . 
                         " | Agence: " . $fond['agence_id'] . "\n";
                }
            }
            
            echo "\n";
        }
    }
    
} catch (\Exception $e) {
    echo "Erreur : " . $e->getMessage() . "\n";
}
