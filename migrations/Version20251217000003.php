<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * SupportiveMessage table migration with seed data
 */
final class Version20251217000003 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create supportive_message table and seed with initial messages';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE supportive_message (
            id INT AUTO_INCREMENT NOT NULL,
            message TEXT NOT NULL,
            category VARCHAR(50) NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            INDEX IDX_CATEGORY (category),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Seed with initial messages
        $this->addSql("INSERT INTO supportive_message (message, category, is_active) VALUES
            ('Welcome back! Let\\'s make today count, one small step at a time.', 'welcome', 1),
            ('Good to see you! Remember: progress, not perfection.', 'welcome', 1),
            ('Hey there! Your future self will thank you for showing up today.', 'welcome', 1),
            ('Take it one task at a time. You\\'ve got this!', 'encouragement', 1),
            ('Small steps lead to big changes.', 'encouragement', 1),
            ('Every task completed is a victory.', 'encouragement', 1),
            ('You\\'re doing great. Keep going!', 'encouragement', 1),
            ('Nice work! Every completed task is a victory.', 'completion', 1),
            ('You did it! Small wins lead to big changes.', 'completion', 1),
            ('Task complete! You\\'re building momentum.', 'completion', 1),
            ('Feeling overwhelmed? Focus on just one task. The rest can wait.', 'calming', 1),
            ('Remember to breathe. You don\\'t have to do everything today.', 'calming', 1),
            ('It\\'s okay to move tasks to another day. Be kind to yourself.', 'calming', 1),
            ('Take a moment. Your wellbeing matters more than any task.', 'calming', 1)
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE supportive_message');
    }
}
