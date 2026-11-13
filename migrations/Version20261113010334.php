<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261113010334 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE contribution (id INT AUTO_INCREMENT NOT NULL, role VARCHAR(20) NOT NULL, edition_id INT NOT NULL, personne_id INT NOT NULL, INDEX IDX_EA351E1574281A5E (edition_id), INDEX IDX_EA351E15A21BD112 (personne_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE edition (id INT AUTO_INCREMENT NOT NULL, isbn VARCHAR(20) DEFAULT NULL, editeur VARCHAR(150) DEFAULT NULL, annee_publication SMALLINT DEFAULT NULL, langue VARCHAR(5) NOT NULL, nb_pages INT DEFAULT NULL, couverture_url VARCHAR(500) DEFAULT NULL, oeuvre_id INT NOT NULL, UNIQUE INDEX UNIQ_A891181FCC1CF4E6 (isbn), INDEX IDX_A891181F88194DE8 (oeuvre_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE exemplaire (id INT AUTO_INCREMENT NOT NULL, code_barres VARCHAR(50) NOT NULL, etat VARCHAR(20) NOT NULL, statut VARCHAR(20) NOT NULL, edition_id INT NOT NULL, site_courant_id INT NOT NULL, site_origine_id INT NOT NULL, UNIQUE INDEX UNIQ_5EF83C9268EF62E0 (code_barres), INDEX IDX_5EF83C9274281A5E (edition_id), INDEX IDX_5EF83C927D397679 (site_courant_id), INDEX IDX_5EF83C92208E164B (site_origine_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE oeuvre (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, titre_original VARCHAR(255) DEFAULT NULL, type VARCHAR(20) NOT NULL, description LONGTEXT DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE personne (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(100) NOT NULL, prenom VARCHAR(100) DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE site (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(120) NOT NULL, adresse VARCHAR(255) DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE contribution ADD CONSTRAINT FK_EA351E1574281A5E FOREIGN KEY (edition_id) REFERENCES edition (id)');
        $this->addSql('ALTER TABLE contribution ADD CONSTRAINT FK_EA351E15A21BD112 FOREIGN KEY (personne_id) REFERENCES personne (id)');
        $this->addSql('ALTER TABLE edition ADD CONSTRAINT FK_A891181F88194DE8 FOREIGN KEY (oeuvre_id) REFERENCES oeuvre (id)');
        $this->addSql('ALTER TABLE exemplaire ADD CONSTRAINT FK_5EF83C9274281A5E FOREIGN KEY (edition_id) REFERENCES edition (id)');
        $this->addSql('ALTER TABLE exemplaire ADD CONSTRAINT FK_5EF83C927D397679 FOREIGN KEY (site_courant_id) REFERENCES site (id)');
        $this->addSql('ALTER TABLE exemplaire ADD CONSTRAINT FK_5EF83C92208E164B FOREIGN KEY (site_origine_id) REFERENCES site (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE contribution DROP FOREIGN KEY FK_EA351E1574281A5E');
        $this->addSql('ALTER TABLE contribution DROP FOREIGN KEY FK_EA351E15A21BD112');
        $this->addSql('ALTER TABLE edition DROP FOREIGN KEY FK_A891181F88194DE8');
        $this->addSql('ALTER TABLE exemplaire DROP FOREIGN KEY FK_5EF83C9274281A5E');
        $this->addSql('ALTER TABLE exemplaire DROP FOREIGN KEY FK_5EF83C927D397679');
        $this->addSql('ALTER TABLE exemplaire DROP FOREIGN KEY FK_5EF83C92208E164B');
        $this->addSql('DROP TABLE contribution');
        $this->addSql('DROP TABLE edition');
        $this->addSql('DROP TABLE exemplaire');
        $this->addSql('DROP TABLE oeuvre');
        $this->addSql('DROP TABLE personne');
        $this->addSql('DROP TABLE site');
    }
}
