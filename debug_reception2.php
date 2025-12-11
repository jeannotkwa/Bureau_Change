<?php
$pdo = new PDO('mysql:host=localhost;dbname=bureau_change', 'root', '');

echo "=== Structure de details_transaction ===\n";
$result = $pdo->query('SHOW COLUMNS FROM details_transaction')->fetchAll(PDO::FETCH_ASSOC);
foreach ($result as $col) {
    echo $col['Field'] . ' - ' . $col['Type'] . '\n';
}

echo "\n=== Dernière réception ===\n";
$trans = $pdo->query("
    SELECT t.id_transaction, t.reference, t.nature_operation, a.nom_agence
    FROM transactions t
    JOIN agences a ON t.agence_id = a.id_agence
    WHERE t.nature_operation = 'reception'
    ORDER BY t.id_transaction DESC
    LIMIT 1
")->fetch(PDO::FETCH_ASSOC);

if ($trans) {
    echo "Transaction ID: " . $trans['id_transaction'] . " | Ref: " . $trans['reference'] . " | Agence: " . $trans['nom_agence'] . "\n\n";
    
    $details = $pdo->prepare("
        SELECT * FROM details_transaction WHERE id_detail = (SELECT id_detail FROM details_transaction LIMIT 1)
    ");
    $details->execute();
    $rows = $details->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Détails transaction (details_transaction):\n";
    if (empty($rows)) {
        echo "  (Aucun détail trouvé)\n";
    } else {
        foreach ($rows as $row) {
            echo "  ID Detail: " . $row['id_detail'] . " | Montant: " . $row['montant'] . "\n";
        }
    }
    
    echo "\nMouvements de fonds pour cette agence:\n";
    $fonds = $pdo->prepare("
        SELECT dfd.*, dv.sigle, a.nom_agence
        FROM details_fonds_depart dfd
        JOIN devises dv ON dfd.devise_id = dv.id_devise
        JOIN agences a ON dfd.agence_id = a.id_agence
        WHERE dfd.fonds_depart_id IN (
            SELECT id_fonds_depart FROM fonds_depart 
            WHERE agence_id = ?
        )
        ORDER BY dfd.id_detail DESC
        LIMIT 10
    ");
    $fonds->execute([$trans['agence_id']]);
    $fonds_rows = $fonds->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($fonds_rows)) {
        echo "  (Aucun mouvement trouvé)\n";
    } else {
        foreach ($fonds_rows as $row) {
            echo "  Montant: " . $row['montant'] . " " . $row['sigle'] . " | Agence: " . $row['nom_agence'] . "\n";
        }
    }
}
