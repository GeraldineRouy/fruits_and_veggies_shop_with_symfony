<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260524120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Insère des catégories et produits d\'exemple (fruits, légumes)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("INSERT INTO category (id, name, description) VALUES
            (1, 'Fruits', 'Fruits frais de saison'),
            (2, 'Légumes', 'Légumes frais de saison'),
            (3, 'Fruits exotiques', 'Fruits tropicaux et exotiques'),
            (4, 'Légumes bio', 'Légumes issus de l''agriculture biologique'),
            (5, 'Herbes aromatiques', 'Herbes et plantes aromatiques')
        ON CONFLICT (id) DO NOTHING");

        $this->addSql("INSERT INTO product (id, name, description, image, price) VALUES
            (1, 'Pomme Golden', 'Pomme Golden sucrée et juteuse', 'images/products/pomme-golden.jpg', 2.50),
            (2, 'Banane', 'Banane mûre à point', 'images/products/banane.jpg', 1.80),
            (3, 'Orange', 'Orange sanguine de Sicile', 'images/products/orange.jpg', 3.00),
            (4, 'Fraise', 'Fraise gariguette parfumée', 'images/products/fraise.jpg', 4.50),
            (5, 'Avocat', 'Avocat prêt à déguster', 'images/products/avocat.jpg', 2.20),
            (6, 'Mangue', 'Mangue bio du Pérou', 'images/products/mangue.jpg', 3.50),
            (7, 'Carotte', 'Carotte bio, lot de 500g', 'images/products/carotte.jpg', 1.50),
            (8, 'Salade', 'Salade verte croquante', 'images/products/salade.jpg', 1.20),
            (9, 'Tomate', 'Tomate cœur de bœuf bio', 'images/products/tomate.jpg', 3.80),
            (10, 'Concombre', 'Concombre long', 'images/products/concombre.jpg', 1.60),
            (11, 'Courgette', 'Courgette verte bio', 'images/products/courgette.jpg', 2.10),
            (12, 'Basilic', 'Basilic frais en pot', 'images/products/basilic.jpg', 2.00),
            (13, 'Menthe', 'Menthe fraîche en pot', 'images/products/menthe.jpg', 2.00),
            (14, 'Persil', 'Persil plat en botte', 'images/products/persil.jpg', 1.00),
            (15, 'Ananas', 'Ananas Victoria', 'images/products/ananas.jpg', 3.90)
        ON CONFLICT (id) DO NOTHING");

        $this->addSql('INSERT INTO product_category (product_id, category_id) VALUES
            (1, 1),
            (2, 1), (2, 3),
            (3, 1),
            (4, 1),
            (5, 3), (5, 2),
            (6, 3),
            (7, 2), (7, 4),
            (8, 2),
            (9, 2), (9, 4),
            (10, 2),
            (11, 2), (11, 4),
            (12, 5),
            (13, 5),
            (14, 5),
            (15, 3)
        ON CONFLICT DO NOTHING');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM product_category WHERE product_id BETWEEN 1 AND 15');
        $this->addSql('DELETE FROM product WHERE id BETWEEN 1 AND 15');
        $this->addSql('DELETE FROM category WHERE id BETWEEN 1 AND 5');
    }
}
