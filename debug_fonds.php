<?php
$pdo = new PDO('mysql:host=localhost;dbname=bureau_change', 'root', '');

echo "=== Vérification des mouvements de fonds pour la réception ===\n\n";

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
    echo "Réception trouvée:\n";
    echo "  Transaction ID: " . $trans['id_transaction'] . "\n";
    echo "  Référence: " . $trans['reference'] . "\n";
    echo "  Nature: " . $trans['nature_operation'] . "\n";
    echo "  Agence (ID: " . $trans['agence_id'] . "): " . $trans['nom_agence'] . "\n\n";
    
    // Vérifier les détails de la transaction
    $details = $pdo->prepare("
        SELECT * FROM details_transaction ORDER BY id_detail DESC LIMIT 5
    ");
    $details->execute();
    $rows = $details->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Tous les détails_transaction récents:\n";
    foreach ($rows as $row) {
        echo "  ID Detail: " . $row['id_detail'] . " | Montant: " . $row['montant'] . "\n";
    }
    
    echo "\nMouvements de fonds (details_fonds_depart) pour l'agence " . $trans['nom_agence'] . ":\n";
    
    $fonds = $pdo->query("
        SELECT dfd.*, a.nom_agence
        FROM details_fonds_depart dfd
        JOIN agences a ON dfd.agence_id = a.id_agence
        WHERE dfd.agence_id = " . intval($trans['agence_id']) . "
        ORDER BY dfd.id_detail DESC
        LIMIT 10
    ");
    
    $fonds_rows = $fonds->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($fonds_rows)) {
        echo "  ❌ AUCUN mouvement de fonds trouvé pour cette agence!\n";
    } else {
        foreach ($fonds_rows as $row) {
            echo "  ID: " . $row['id_detail'] . " | Montant: " . $row['montant'] . " | Agence: " . $row['nom_agence'] . "\n";
        }
    }
} else {
    echo "Aucune réception trouvée en base\n";
}
