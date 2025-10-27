<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add role and is_bot fields to user_credentials table for admin bot support.
 */
final class Version20251027000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add role and is_bot fields to user_credentials table for admin bot support';
    }

    public function up(Schema $schema): void
    {
        // Add role column (user, admin)
        $this->addSql('ALTER TABLE user_credentials ADD COLUMN role VARCHAR(50) NOT NULL DEFAULT \'user\'');

        // Add is_bot column (for service accounts)
        $this->addSql('ALTER TABLE user_credentials ADD COLUMN is_bot BOOLEAN NOT NULL DEFAULT FALSE');
    }

    public function down(Schema $schema): void
    {
        // Remove added columns
        $this->addSql('ALTER TABLE user_credentials DROP COLUMN role');
        $this->addSql('ALTER TABLE user_credentials DROP COLUMN is_bot');
    }
}
