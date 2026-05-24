<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260525120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Corrige la séquence order_line_id_seq après insertions avec IDs explicites';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("SELECT setval('order_line_id_seq', COALESCE((SELECT MAX(id) FROM order_line), 1))");
    }

    public function down(Schema $schema): void
    {
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
