<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250606153346 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
        ALTER TABLE profiles
            CHANGE nickname nickname VARCHAR(255) DEFAULT NULL,
            CHANGE first_name first_name VARCHAR(255) DEFAULT NULL,
            CHANGE last_name last_name VARCHAR(255) DEFAULT NULL,
            CHANGE birthdate birthdate DATE DEFAULT NULL,
            CHANGE photo_url photo_url VARCHAR(255) DEFAULT NULL
    SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
        ALTER TABLE profiles
            CHANGE nickname nickname VARCHAR(100) NOT NULL,
            CHANGE first_name first_name VARCHAR(255) NOT NULL,
            CHANGE last_name last_name VARCHAR(255) NOT NULL,
            CHANGE birthdate birthdate DATE NOT NULL,
            CHANGE photo_url photo_url VARCHAR(255) NOT NULL
    SQL);
    }
}
