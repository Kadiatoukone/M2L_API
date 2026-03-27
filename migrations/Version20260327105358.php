<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260327105358 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE salles ADD type_salle_id INT DEFAULT NULL, DROP type');
        $this->addSql('ALTER TABLE salles ADD CONSTRAINT FK_799D45AA11CF67C9 FOREIGN KEY (type_salle_id) REFERENCES type_salle (id)');
        $this->addSql('CREATE INDEX IDX_799D45AA11CF67C9 ON salles (type_salle_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE salles DROP FOREIGN KEY FK_799D45AA11CF67C9');
        $this->addSql('DROP INDEX IDX_799D45AA11CF67C9 ON salles');
        $this->addSql('ALTER TABLE salles ADD type VARCHAR(100) NOT NULL, DROP type_salle_id');
    }
}
