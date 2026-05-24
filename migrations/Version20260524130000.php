<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260524130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crée le compte administrateur par défaut (admin@example.com)';
    }

    public function up(Schema $schema): void
    {
        $hashedPassword = password_hash('admin', PASSWORD_BCRYPT);

        $this->addSql("INSERT INTO \"user\" (email, first_name, last_name, roles, password, verified_at, is_active)
            VALUES (
                'admin@example.com',
                'Admin',
                'Admin',
                '[\"ROLE_ADMIN\"]',
                '$hashedPassword',
                CURRENT_TIMESTAMP,
                true
            )
            ON CONFLICT (email) DO NOTHING");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM \"user\" WHERE email = 'admin@example.com'");
    }
}
