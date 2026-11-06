<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261106104314 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE comments (id UUID NOT NULL, text TEXT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, user_id UUID NOT NULL, project_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_5F9E962AA76ED395 ON comments (user_id)');
        $this->addSql('CREATE INDEX IDX_5F9E962A166D1F9C ON comments (project_id)');
        $this->addSql('CREATE TABLE components (id UUID NOT NULL, name VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, origin VARCHAR(50) NOT NULL, category VARCHAR(50) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, photo_s3_key VARCHAR(512) DEFAULT \'\' NOT NULL, photo_mime_type VARCHAR(128) DEFAULT \'image/jpeg\' NOT NULL, photo_size BIGINT DEFAULT 0 NOT NULL, project_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_EE48F5FD166D1F9C ON components (project_id)');
        $this->addSql('CREATE TABLE likes (id UUID NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, user_id UUID NOT NULL, project_id UUID DEFAULT NULL, comment_id UUID DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_49CA4E7DA76ED395 ON likes (user_id)');
        $this->addSql('CREATE INDEX IDX_49CA4E7D166D1F9C ON likes (project_id)');
        $this->addSql('CREATE INDEX IDX_49CA4E7DF8697D13 ON likes (comment_id)');
        $this->addSql('CREATE TABLE profiles (id UUID NOT NULL, nickname VARCHAR(100) DEFAULT NULL, first_name VARCHAR(255) DEFAULT NULL, last_name VARCHAR(255) DEFAULT NULL, birthdate DATE DEFAULT NULL, photo_url VARCHAR(255) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL, user_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8B308530A76ED395 ON profiles (user_id)');
        $this->addSql('CREATE TABLE projects (id UUID NOT NULL, title VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL, image_url VARCHAR(255) NOT NULL, user_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_5C93B3A4A76ED395 ON projects (user_id)');
        $this->addSql('CREATE TABLE users (id UUID NOT NULL, email VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, role VARCHAR(5) DEFAULT \'user\' NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9E7927C74 ON users (email)');
        $this->addSql('ALTER TABLE comments ADD CONSTRAINT FK_5F9E962AA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE comments ADD CONSTRAINT FK_5F9E962A166D1F9C FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE components ADD CONSTRAINT FK_EE48F5FD166D1F9C FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE likes ADD CONSTRAINT FK_49CA4E7DA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE likes ADD CONSTRAINT FK_49CA4E7D166D1F9C FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE likes ADD CONSTRAINT FK_49CA4E7DF8697D13 FOREIGN KEY (comment_id) REFERENCES comments (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE profiles ADD CONSTRAINT FK_8B308530A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE projects ADD CONSTRAINT FK_5C93B3A4A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE comments DROP CONSTRAINT FK_5F9E962AA76ED395');
        $this->addSql('ALTER TABLE comments DROP CONSTRAINT FK_5F9E962A166D1F9C');
        $this->addSql('ALTER TABLE components DROP CONSTRAINT FK_EE48F5FD166D1F9C');
        $this->addSql('ALTER TABLE likes DROP CONSTRAINT FK_49CA4E7DA76ED395');
        $this->addSql('ALTER TABLE likes DROP CONSTRAINT FK_49CA4E7D166D1F9C');
        $this->addSql('ALTER TABLE likes DROP CONSTRAINT FK_49CA4E7DF8697D13');
        $this->addSql('ALTER TABLE profiles DROP CONSTRAINT FK_8B308530A76ED395');
        $this->addSql('ALTER TABLE projects DROP CONSTRAINT FK_5C93B3A4A76ED395');
        $this->addSql('DROP TABLE comments');
        $this->addSql('DROP TABLE components');
        $this->addSql('DROP TABLE likes');
        $this->addSql('DROP TABLE profiles');
        $this->addSql('DROP TABLE projects');
        $this->addSql('DROP TABLE users');
    }
}
