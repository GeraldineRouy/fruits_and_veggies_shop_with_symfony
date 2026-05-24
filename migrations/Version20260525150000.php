<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260525150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Corrige les images de Menthe (id=13), Persil (id=14) et Carotte (id=7)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE product SET image = 'assets/images/products/carottes.png' WHERE id = 7 AND image IS DISTINCT FROM 'assets/images/products/carottes.png'");
        $this->addSql("UPDATE product SET image = 'assets/images/products/menthe.png' WHERE id = 13 AND image IS DISTINCT FROM 'assets/images/products/menthe.png'");
        $this->addSql("UPDATE product SET image = 'assets/images/products/persil.png' WHERE id = 14 AND image IS DISTINCT FROM 'assets/images/products/persil.png'");
    }

    public function down(Schema $schema): void
    {
    }
}
