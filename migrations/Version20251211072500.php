<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251211072500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add affectations_agents table and update utilisateurs table';
    }

    public function up(Schema $schema): void
    {
        // Créer la table affectations_agents si elle n'existe pas
        $this->addSql('CREATE TABLE IF NOT EXISTS affectations_agents (
            id_affectation INT AUTO_INCREMENT NOT NULL,
            utilisateur_id INT NOT NULL,
            agence_id INT NOT NULL,
            date_debut DATE NOT NULL,
            date_fin DATE DEFAULT NULL,
            statut VARCHAR(20) DEFAULT \'actif\' NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME DEFAULT NULL,
            PRIMARY KEY (id_affectation),
            INDEX IDX_UTILISATEUR (utilisateur_id),
            INDEX IDX_AGENCE (agence_id),
            CONSTRAINT FK_AFFECTATION_UTILISATEUR FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs (id),
            CONSTRAINT FK_AFFECTATION_AGENCE FOREIGN KEY (agence_id) REFERENCES agences (id_agence)
        ) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS affectations_agents');
    }
}
