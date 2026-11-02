<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261102095313 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Demandes envoyées depuis la page « Devenir partenaire »';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE partnership_request (id INT AUTO_INCREMENT NOT NULL, establishment_name VARCHAR(255) NOT NULL, activity_type VARCHAR(255) NOT NULL, city VARCHAR(120) NOT NULL, contact_name VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, phone VARCHAR(30) DEFAULT NULL, message LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, handled TINYINT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE partnership_request');
    }
}
