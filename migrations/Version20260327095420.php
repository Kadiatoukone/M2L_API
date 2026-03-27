<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260327095420 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE horaires (id INT AUTO_INCREMENT NOT NULL, jour VARCHAR(20) NOT NULL, heure_ouverture TIME NOT NULL, heure_fermeture TIME NOT NULL, salle_id INT NOT NULL, INDEX IDX_39B7118FDC304035 (salle_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE horaires ADD CONSTRAINT FK_39B7118FDC304035 FOREIGN KEY (salle_id) REFERENCES salles (id_salles)');
        $this->addSql('ALTER TABLE horaire_salle DROP FOREIGN KEY `FK_2B7FC987DC304035`');
        $this->addSql('DROP TABLE horaire_salle');
        $this->addSql('ALTER TABLE commentaire ADD date DATETIME NOT NULL');
        $this->addSql('ALTER TABLE salles DROP FOREIGN KEY `FK_799D45AA11CF67C9`');
        $this->addSql('DROP INDEX IDX_799D45AA11CF67C9 ON salles');
        $this->addSql('ALTER TABLE salles ADD ville VARCHAR(255) NOT NULL, ADD type VARCHAR(100) NOT NULL, DROP type_salle_id, DROP photo_url, CHANGE capacite capacite VARCHAR(255) NOT NULL, CHANGE gestionnaire_id gestionnaire_id INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE horaire_salle (id INT AUTO_INCREMENT NOT NULL, jour_semaine INT NOT NULL, heure_ouverture TIME DEFAULT NULL, heure_fermeture TIME DEFAULT NULL, est_ouvert TINYINT NOT NULL, salle_id INT NOT NULL, INDEX IDX_2B7FC987DC304035 (salle_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE horaire_salle ADD CONSTRAINT `FK_2B7FC987DC304035` FOREIGN KEY (salle_id) REFERENCES salles (id_salles) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE horaires DROP FOREIGN KEY FK_39B7118FDC304035');
        $this->addSql('DROP TABLE horaires');
        $this->addSql('ALTER TABLE commentaire DROP date');
        $this->addSql('ALTER TABLE salles ADD type_salle_id INT NOT NULL, ADD photo_url VARCHAR(500) DEFAULT NULL, DROP ville, DROP type, CHANGE capacite capacite INT NOT NULL, CHANGE gestionnaire_id gestionnaire_id INT NOT NULL');
        $this->addSql('ALTER TABLE salles ADD CONSTRAINT `FK_799D45AA11CF67C9` FOREIGN KEY (type_salle_id) REFERENCES type_salle (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_799D45AA11CF67C9 ON salles (type_salle_id)');
    }
}
