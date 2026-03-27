<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260325160837 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE horaire_salle (id INT AUTO_INCREMENT NOT NULL, jour_semaine INT NOT NULL, heure_ouverture TIME DEFAULT NULL, heure_fermeture TIME DEFAULT NULL, est_ouvert TINYINT NOT NULL, salle_id INT NOT NULL, INDEX IDX_2B7FC987DC304035 (salle_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE horaire_salle ADD CONSTRAINT FK_2B7FC987DC304035 FOREIGN KEY (salle_id) REFERENCES salles (id_salles)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE horaire_salle DROP FOREIGN KEY FK_2B7FC987DC304035');
        $this->addSql('DROP TABLE horaire_salle');
    }
}
