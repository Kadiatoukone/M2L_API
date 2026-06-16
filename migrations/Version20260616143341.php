<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260616143341 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE reservations ADD type_resa VARCHAR(20) DEFAULT \'ponctuel\' NOT NULL, ADD refused_at DATETIME DEFAULT NULL, ADD salle_id INT NOT NULL');
        $this->addSql('ALTER TABLE reservations ADD CONSTRAINT FK_4DA239DC304035 FOREIGN KEY (salle_id) REFERENCES salles (id_salles)');
        $this->addSql('CREATE INDEX IDX_4DA239DC304035 ON reservations (salle_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE reservations DROP FOREIGN KEY FK_4DA239DC304035');
        $this->addSql('DROP INDEX IDX_4DA239DC304035 ON reservations');
        $this->addSql('ALTER TABLE reservations DROP type_resa, DROP refused_at, DROP salle_id');
    }
}
