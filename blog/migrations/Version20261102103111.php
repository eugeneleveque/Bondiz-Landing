<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261102103111 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Messages envoyés depuis la page « Nous contacter »';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE contact_message (id INT AUTO_INCREMENT NOT NULL, last_name VARCHAR(120) NOT NULL, first_name VARCHAR(120) NOT NULL, email VARCHAR(255) NOT NULL, subject VARCHAR(255) NOT NULL, message LONGTEXT NOT NULL, created_at DATETIME NOT NULL, handled TINYINT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE contact_message');
    }
}
