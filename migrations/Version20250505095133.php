<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250505095133 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE elem_panier (id INT AUTO_INCREMENT NOT NULL, panier_id INT DEFAULT NULL, quantite INT NOT NULL, INDEX IDX_9C562BE6F77D927C (panier_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE panier (id INT AUTO_INCREMENT NOT NULL, date_creation DATETIME NOT NULL, date_modification DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE elem_panier ADD CONSTRAINT FK_9C562BE6F77D927C FOREIGN KEY (panier_id) REFERENCES panier (id)');
        $this->addSql('ALTER TABLE utilisateur ADD mon_panier_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE utilisateur ADD CONSTRAINT FK_1D1C63B347A4E9F2 FOREIGN KEY (mon_panier_id) REFERENCES panier (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1D1C63B347A4E9F2 ON utilisateur (mon_panier_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE utilisateur DROP FOREIGN KEY FK_1D1C63B347A4E9F2');
        $this->addSql('ALTER TABLE elem_panier DROP FOREIGN KEY FK_9C562BE6F77D927C');
        $this->addSql('DROP TABLE elem_panier');
        $this->addSql('DROP TABLE panier');
        $this->addSql('DROP INDEX UNIQ_1D1C63B347A4E9F2 ON utilisateur');
        $this->addSql('ALTER TABLE utilisateur DROP mon_panier_id');
    }
}
