<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200502222135 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE admin_deletion CHANGE timestamp timestamp DATETIME NOT NULL');
        $this->addSql('ALTER TABLE admin_ban CHANGE ban_start ban_start DATETIME NOT NULL, CHANGE ban_end ban_end DATETIME NOT NULL');
        $this->addSql('ALTER TABLE admin_report CHANGE ts ts DATETIME NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE admin_ban CHANGE ban_start ban_start DATETIME NOT NULL, CHANGE ban_end ban_end DATETIME NOT NULL');
        $this->addSql('ALTER TABLE admin_deletion CHANGE timestamp timestamp DATETIME NOT NULL');
        $this->addSql('ALTER TABLE admin_report CHANGE ts ts DATETIME NOT NULL');
    }
}
