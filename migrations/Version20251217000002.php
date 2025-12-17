<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Task table migration
 */
final class Version20251217000002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create task table for task management';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE task (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            scheduled_date DATE NOT NULL,
            position INT NOT NULL DEFAULT 0,
            is_completed TINYINT(1) NOT NULL DEFAULT 0,
            completed_at DATETIME DEFAULT NULL,
            created_at DATETIME NOT NULL,
            INDEX IDX_527EDB25A76ED395 (user_id),
            INDEX IDX_527EDB25A23B42D_DATE (scheduled_date),
            PRIMARY KEY(id),
            CONSTRAINT FK_527EDB25A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE task');
    }
}
