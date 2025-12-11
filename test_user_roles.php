<?php
$conn = new mysqli('localhost', 'root', '', 'bureau_change');
$result = $conn->query('SELECT id, nom, email, roles FROM utilisateurs LIMIT 5');

echo "=== VÉRIFICATION DES RÔLES UTILISATEURS ===\n\n";
while ($row = $result->fetch_assoc()) {
    echo 'ID: ' . $row['id'] . ' | Nom: ' . $row['nom'] . ' | Email: ' . $row['email'] . ' | Roles: ' . $row['roles'] . PHP_EOL;
}
