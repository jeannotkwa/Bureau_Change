<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251211074203 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE affectations_agents ADD CONSTRAINT FK_73154BFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs (id)');
        $this->addSql('ALTER TABLE affectations_agents ADD CONSTRAINT FK_73154BD725330D FOREIGN KEY (agence_id) REFERENCES agences (id_agence)');
        $this->addSql('ALTER TABLE details_transaction DROP FOREIGN KEY idx_details_transaction_devise');
        $this->addSql('DROP INDEX idx_details_transaction_devise ON details_transaction');
        $this->addSql('ALTER TABLE details_transaction ADD CONSTRAINT FK_194D2F2E6A25C826 FOREIGN KEY (id_transaction) REFERENCES transactions (id_transaction)');
        $this->addSql('ALTER TABLE details_transaction RENAME INDEX idx_transaction TO IDX_194D2F2E6A25C826');
        $this->addSql('ALTER TABLE details_transaction RENAME INDEX devise_id_output TO IDX_194D2F2EDD11581E');
        $this->addSql('DROP INDEX idx_statut ON devise');
        $this->addSql('ALTER TABLE devise CHANGE statut statut VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE devise RENAME INDEX sigle TO UNIQ_43EDA4DF8776B952');
        $this->addSql('ALTER TABLE fonds_depart DROP FOREIGN KEY `fonds_depart_ibfk_1`');
        $this->addSql('DROP INDEX idx_date_agence ON fonds_depart');
        $this->addSql('ALTER TABLE fonds_depart CHANGE statut statut VARCHAR(20) NOT NULL, CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE fonds_depart ADD CONSTRAINT FK_EC899C94D725330D FOREIGN KEY (agence_id) REFERENCES agences (id_agence)');
        $this->addSql('ALTER TABLE fonds_depart RENAME INDEX agence_id TO IDX_EC899C94D725330D');
        $this->addSql('ALTER TABLE transactions DROP INDEX idx_reference, ADD UNIQUE INDEX UNIQ_EAA81A4CAEA34913 (reference)');
        $this->addSql('DROP INDEX idx_date ON transactions');
        $this->addSql('DROP INDEX idx_nature ON transactions');
        $this->addSql('DROP INDEX idx_transactions_date_agence ON transactions');
        $this->addSql('DROP INDEX unique_reference_nature ON transactions');
        $this->addSql('ALTER TABLE transactions CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE transactions RENAME INDEX identite_id TO IDX_EAA81A4CE5F13C8F');
        $this->addSql('ALTER TABLE transactions RENAME INDEX utilisateur_id TO IDX_EAA81A4CFB88E14F');
        $this->addSql('ALTER TABLE transactions RENAME INDEX idx_agence TO IDX_EAA81A4CD725330D');
        $this->addSql('DROP INDEX libelle_identite ON types_identite');
        $this->addSql('ALTER TABLE utilisateurs DROP FOREIGN KEY `utilisateurs_ibfk_1`');
        $this->addSql('DROP INDEX idx_email ON utilisateurs');
        $this->addSql('DROP INDEX idx_statut ON utilisateurs');
        $this->addSql('ALTER TABLE utilisateurs CHANGE statut statut VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE utilisateurs ADD CONSTRAINT FK_497B315ED725330D FOREIGN KEY (agence_id) REFERENCES agences (id_agence)');
        $this->addSql('ALTER TABLE utilisateurs RENAME INDEX email TO UNIQ_497B315EE7927C74');
        $this->addSql('ALTER TABLE utilisateurs RENAME INDEX agence_id TO IDX_497B315ED725330D');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE affectations_agents DROP FOREIGN KEY FK_73154BFB88E14F');
        $this->addSql('ALTER TABLE affectations_agents DROP FOREIGN KEY FK_73154BD725330D');
        $this->addSql('ALTER TABLE details_transaction DROP FOREIGN KEY FK_194D2F2E6A25C826');
        $this->addSql('CREATE INDEX idx_details_transaction_devise ON details_transaction (devise_id_input, devise_id_output)');
        $this->addSql('ALTER TABLE details_transaction RENAME INDEX idx_194d2f2edd11581e TO devise_id_output');
        $this->addSql('ALTER TABLE details_transaction RENAME INDEX idx_194d2f2e6a25c826 TO idx_transaction');
        $this->addSql('ALTER TABLE devise CHANGE statut statut VARCHAR(20) DEFAULT \'actif\'');
        $this->addSql('CREATE INDEX idx_statut ON devise (statut)');
        $this->addSql('ALTER TABLE devise RENAME INDEX uniq_43eda4df8776b952 TO sigle');
        $this->addSql('ALTER TABLE fonds_depart DROP FOREIGN KEY FK_EC899C94D725330D');
        $this->addSql('ALTER TABLE fonds_depart CHANGE statut statut VARCHAR(20) DEFAULT \'ouvert\', CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('ALTER TABLE fonds_depart ADD CONSTRAINT `fonds_depart_ibfk_1` FOREIGN KEY (agence_id) REFERENCES agences (id_agence) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('CREATE INDEX idx_date_agence ON fonds_depart (date_jour, agence_id)');
        $this->addSql('ALTER TABLE fonds_depart RENAME INDEX idx_ec899c94d725330d TO agence_id');
        $this->addSql('ALTER TABLE transactions DROP INDEX UNIQ_EAA81A4CAEA34913, ADD INDEX idx_reference (reference)');
        $this->addSql('ALTER TABLE transactions CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('CREATE INDEX idx_date ON transactions (date_transaction)');
        $this->addSql('CREATE INDEX idx_nature ON transactions (nature_operation)');
        $this->addSql('CREATE INDEX idx_transactions_date_agence ON transactions (date_transaction, agence_id)');
        $this->addSql('CREATE UNIQUE INDEX unique_reference_nature ON transactions (reference, nature_operation)');
        $this->addSql('ALTER TABLE transactions RENAME INDEX idx_eaa81a4ce5f13c8f TO identite_id');
        $this->addSql('ALTER TABLE transactions RENAME INDEX idx_eaa81a4cd725330d TO idx_agence');
        $this->addSql('ALTER TABLE transactions RENAME INDEX idx_eaa81a4cfb88e14f TO utilisateur_id');
        $this->addSql('CREATE UNIQUE INDEX libelle_identite ON types_identite (libelle_identite)');
        $this->addSql('ALTER TABLE utilisateurs DROP FOREIGN KEY FK_497B315ED725330D');
        $this->addSql('ALTER TABLE utilisateurs CHANGE statut statut VARCHAR(20) DEFAULT \'actif\'');
        $this->addSql('ALTER TABLE utilisateurs ADD CONSTRAINT `utilisateurs_ibfk_1` FOREIGN KEY (agence_id) REFERENCES agences (id_agence) ON UPDATE NO ACTION ON DELETE SET NULL');
        $this->addSql('CREATE INDEX idx_email ON utilisateurs (email)');
        $this->addSql('CREATE INDEX idx_statut ON utilisateurs (statut)');
        $this->addSql('ALTER TABLE utilisateurs RENAME INDEX idx_497b315ed725330d TO agence_id');
        $this->addSql('ALTER TABLE utilisateurs RENAME INDEX uniq_497b315ee7927c74 TO email');
    }
}
