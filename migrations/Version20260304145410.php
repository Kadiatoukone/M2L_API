<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260304145410 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE adherent (id_adherent INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, prenom VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, mot_de_passe VARCHAR(255) NOT NULL, roles JSON NOT NULL, ligue VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_90D3F060E7927C74 (email), PRIMARY KEY (id_adherent)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE `admin` (id_admin INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, prenom VARCHAR(255) NOT NULL, mot_de_passe VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, PRIMARY KEY (id_admin)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE commentaire (id_commentaire INT AUTO_INCREMENT NOT NULL, contenu LONGTEXT NOT NULL, note VARCHAR(255) NOT NULL, type VARCHAR(100) NOT NULL, date DATETIME NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, PRIMARY KEY (id_commentaire)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE evenement (id_evenement INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, PRIMARY KEY (id_evenement)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE gestionnaires (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, prenom VARCHAR(255) NOT NULL, identifiant VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE reservations (id_reservation INT AUTO_INCREMENT NOT NULL, date_debut DATE NOT NULL, date_fin DATE NOT NULL, heure_debut TIME NOT NULL, heure_fin TIME NOT NULL, motif LONGTEXT DEFAULT NULL, statut VARCHAR(50) NOT NULL, adherent_id INT NOT NULL, INDEX IDX_4DA23925F06C53 (adherent_id), PRIMARY KEY (id_reservation)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE salles (id_salles INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, adresse VARCHAR(255) NOT NULL, capacite VARCHAR(255) NOT NULL, type VARCHAR(100) NOT NULL, description LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, PRIMARY KEY (id_salles)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE sports (id_sport INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, PRIMARY KEY (id_sport)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE reservations ADD CONSTRAINT FK_4DA23925F06C53 FOREIGN KEY (adherent_id) REFERENCES adherent (id_adherent)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE reservations DROP FOREIGN KEY FK_4DA23925F06C53');
        $this->addSql('DROP TABLE adherent');
        $this->addSql('DROP TABLE `admin`');
        $this->addSql('DROP TABLE commentaire');
        $this->addSql('DROP TABLE evenement');
        $this->addSql('DROP TABLE gestionnaires');
        $this->addSql('DROP TABLE reservations');
        $this->addSql('DROP TABLE salles');
        $this->addSql('DROP TABLE sports');
    }
}
