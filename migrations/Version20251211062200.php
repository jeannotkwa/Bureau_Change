<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251211062200 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make identite_id nullable for operations diverses';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE transactions MODIFY identite_id INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE transactions MODIFY identite_id INT NOT NULL');
    }
}
