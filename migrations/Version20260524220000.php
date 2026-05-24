<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260524220000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Corrige les chemins d\'images des produits (JPG → PNG) et rend le champ image nullable';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE product ALTER COLUMN image DROP NOT NULL");

        $this->addSql("UPDATE product SET image = 'assets/images/products/pommes.png' WHERE id = 1 AND image IS DISTINCT FROM 'assets/images/products/pommes.png'");
        $this->addSql("UPDATE product SET image = 'assets/images/products/bananes.png' WHERE id = 2 AND image IS DISTINCT FROM 'assets/images/products/bananes.png'");
        $this->addSql("UPDATE product SET image = 'assets/images/products/oranges.png' WHERE id = 3 AND image IS DISTINCT FROM 'assets/images/products/oranges.png'");
        $this->addSql("UPDATE product SET image = 'assets/images/products/fraises.png' WHERE id = 4 AND image IS DISTINCT FROM 'assets/images/products/fraises.png'");
        $this->addSql("UPDATE product SET image = 'assets/images/products/avocats.png' WHERE id = 5 AND image IS DISTINCT FROM 'assets/images/products/avocats.png'");
        $this->addSql("UPDATE product SET image = 'assets/images/products/mangues.png' WHERE id = 6 AND image IS DISTINCT FROM 'assets/images/products/mangues.png'");
        $this->addSql("UPDATE product SET image = NULL WHERE id = 7");
        $this->addSql("UPDATE product SET image = 'assets/images/products/salades.png' WHERE id = 8 AND image IS DISTINCT FROM 'assets/images/products/salades.png'");
        $this->addSql("UPDATE product SET image = 'assets/images/products/tomates.png' WHERE id = 9 AND image IS DISTINCT FROM 'assets/images/products/tomates.png'");
        $this->addSql("UPDATE product SET image = 'assets/images/products/concombres.png' WHERE id = 10 AND image IS DISTINCT FROM 'assets/images/products/concombres.png'");
        $this->addSql("UPDATE product SET image = 'assets/images/products/courgettes.png' WHERE id = 11 AND image IS DISTINCT FROM 'assets/images/products/courgettes.png'");
        $this->addSql("UPDATE product SET image = 'assets/images/products/basilic.png' WHERE id = 12 AND image IS DISTINCT FROM 'assets/images/products/basilic.png'");
        $this->addSql("UPDATE product SET image = 'assets/images/products/menthe.png' WHERE id = 13 AND image IS DISTINCT FROM 'assets/images/products/menthe.png'");
        $this->addSql("UPDATE product SET image = NULL WHERE id = 14");
        $this->addSql("UPDATE product SET image = 'assets/images/products/ananas.png' WHERE id = 15 AND image IS DISTINCT FROM 'assets/images/products/ananas.png'");
        $this->addSql("UPDATE product SET image = 'assets/images/products/noix-grenoble.png' WHERE id = 16 AND image IS DISTINCT FROM 'assets/images/products/noix-grenoble.png'");
        $this->addSql("UPDATE product SET image = 'assets/images/products/huile-noix-grenoble.png' WHERE id = 17 AND image IS DISTINCT FROM 'assets/images/products/huile-noix-grenoble.png'");
        $this->addSql("UPDATE product SET image = 'assets/images/products/bleu-vercors.png' WHERE id = 18 AND image IS DISTINCT FROM 'assets/images/products/bleu-vercors.png'");
        $this->addSql("UPDATE product SET image = 'assets/images/products/saint-marcellin.png' WHERE id = 19 AND image IS DISTINCT FROM 'assets/images/products/saint-marcellin.png'");
        $this->addSql("UPDATE product SET image = 'assets/images/products/chocolat-bonnat.png' WHERE id = 20 AND image IS DISTINCT FROM 'assets/images/products/chocolat-bonnat.png'");
    }

    public function down(Schema $schema): void
    {
    }
}
