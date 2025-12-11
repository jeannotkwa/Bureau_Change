<?php
$pdo = new PDO('mysql:host=localhost;dbname=bureau_change', 'root', '');
$result = $pdo->query('SHOW COLUMNS FROM details_fonds_depart')->fetchAll(PDO::FETCH_ASSOC);
foreach ($result as $col) {
    echo $col['Field'] . ' - ' . $col['Type'] . "\n";
}
