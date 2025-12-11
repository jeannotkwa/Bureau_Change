<?php
$pdo = new PDO('mysql:host=localhost;dbname=bureau_change', 'root', '');

echo "=== Test du calcul du solde après réception ===\n\n";

// Trouver la dernière réception
$trans = $pdo->query("
    SELECT t.id_transaction, t.reference, t.nature_operation, t.agence_id, a.nom_agence
    FROM transactions t
    JOIN agences a ON t.agence_id = a.id_agence
    WHERE t.nature_operation = 'reception'
    ORDER BY t.id_transaction DESC
    LIMIT 1
")->fetch(PDO::FETCH_ASSOC);

if ($trans) {
    echo "Dernière réception:\n";
    echo "  ID: " . $trans['id_transaction'] . "\n";
    echo "  Référence: " . $trans['reference'] . "\n";
    echo "  Agence (ID " . $trans['agence_id'] . "): " . $trans['nom_agence'] . "\n\n";
    
    // Vérifier les mouvements de fonds pour cette agence
    echo "Mouvements de fonds (details_fonds_depart) pour l'agence " . $trans['nom_agence'] . ":\n";
    
    $fonds = $pdo->query("
        SELECT dfd.id_detail, dfd.montant, dfd.id_devise, d.sigle
        FROM details_fonds_depart dfd
        JOIN devise d ON dfd.id_devise = d.id_devise
        WHERE dfd.agence_id = " . intval($trans['agence_id']) . "
        ORDER BY dfd.id_detail DESC
        LIMIT 20
    ");
    
    $fonds_rows = $fonds->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($fonds_rows)) {
        echo "  ❌ AUCUN mouvement trouvé!\n";
    } else {
        $soldes = [];
        foreach ($fonds_rows as $row) {
            echo "  ID: " . $row['id_detail'] . 
                 " | Montant: " . $row['montant'] . 
                 " | Devise: " . $row['sigle'] . "\n";
            
            // Calculer le solde par devise
            $devise = $row['sigle'];
            if (!isset($soldes[$devise])) {
                $soldes[$devise] = 0;
            }
            $soldes[$devise] += (float)$row['montant'];
        }
        
        echo "\n=== Soldes calculés par devise ===\n";
        foreach ($soldes as $devise => $solde) {
            echo "  $devise: " . number_format($solde, 2) . "\n";
        }
    }
    
    // Tester la requête SQL utilisée par getSoldesByAgence
    echo "\n=== Test de la requête GROUP BY (comme getSoldesByAgence) ===\n";
    $sql = "
        SELECT 
            d.id_devise as id, 
            d.libelle, 
            d.sigle, 
            d.taux_achat as tauxAchat, 
            d.taux_vente as tauxVente, 
            COALESCE(SUM(dfd.montant), 0) as montant
        FROM details_fonds_depart dfd
        LEFT JOIN devise d ON dfd.id_devise = d.id_devise
        WHERE dfd.agence_id = " . intval($trans['agence_id']) . "
        GROUP BY d.id_devise
        ORDER BY d.sigle ASC
    ";
    
    $result = $pdo->query($sql);
    $rows = $result->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($rows as $row) {
        echo "  " . $row['sigle'] . ": " . number_format($row['montant'], 2) . "\n";
    }
} else {
    echo "Aucune réception trouvée\n";
}
