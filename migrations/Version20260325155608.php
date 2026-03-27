<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260325155608 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE evenement (id_evenement INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, PRIMARY KEY (id_evenement)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE sports (id_sport INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, PRIMARY KEY (id_sport)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE type_salle (id INT AUTO_INCREMENT NOT NULL, libelle VARCHAR(100) NOT NULL, categorie VARCHAR(50) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE salles ADD type_salle_id INT NOT NULL, ADD gestionnaire_id INT NOT NULL, DROP type, CHANGE capacite capacite INT NOT NULL');
        $this->addSql('ALTER TABLE salles ADD CONSTRAINT FK_799D45AA11CF67C9 FOREIGN KEY (type_salle_id) REFERENCES type_salle (id)');
        $this->addSql('ALTER TABLE salles ADD CONSTRAINT FK_799D45AA6885AC1B FOREIGN KEY (gestionnaire_id) REFERENCES gestionnaires (id_gestionnaires)');
        $this->addSql('CREATE INDEX IDX_799D45AA11CF67C9 ON salles (type_salle_id)');
        $this->addSql('CREATE INDEX IDX_799D45AA6885AC1B ON salles (gestionnaire_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE evenement');
        $this->addSql('DROP TABLE sports');
        $this->addSql('DROP TABLE type_salle');
        $this->addSql('ALTER TABLE salles DROP FOREIGN KEY FK_799D45AA11CF67C9');
        $this->addSql('ALTER TABLE salles DROP FOREIGN KEY FK_799D45AA6885AC1B');
        $this->addSql('DROP INDEX IDX_799D45AA11CF67C9 ON salles');
        $this->addSql('DROP INDEX IDX_799D45AA6885AC1B ON salles');
        $this->addSql('ALTER TABLE salles ADD type VARCHAR(100) NOT NULL, DROP type_salle_id, DROP gestionnaire_id, CHANGE capacite capacite VARCHAR(255) NOT NULL');
    }
}
